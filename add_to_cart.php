<?php
require_once 'session_config.php';

header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

function reponse_json($success, $message = '', $items = [], $total = 0)
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'items' => $items,
        'total' => $total
    ]);
    exit;
}

function charger_panier_json($pdo, $idJoueur)
{
    $stmt = $pdo->prepare("
        SELECT 
            p.idItem,
            p.quantitePanier,
            i.nom,
            i.prix,
            i.photo,
            i.quantiteStock,
            i.estDisponible
        FROM Paniers p
        INNER JOIN Items i ON p.idItem = i.idItem
        WHERE p.idJoueur = ?
        ORDER BY i.nom
    ");
    $stmt->execute([$idJoueur]);

    $itemsPanier = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = 0;

    foreach ($itemsPanier as &$ligne) {
        $ligne['idItem'] = (int)$ligne['idItem'];
        $ligne['quantitePanier'] = (int)$ligne['quantitePanier'];
        $ligne['prix'] = (int)$ligne['prix'];
        $ligne['quantiteStock'] = (int)$ligne['quantiteStock'];
        $ligne['estDisponible'] = (int)$ligne['estDisponible'];
        $ligne['sousTotal'] = $ligne['prix'] * $ligne['quantitePanier'];

        $total += $ligne['sousTotal'];
    }

    unset($ligne);

    return [
        'items' => $itemsPanier,
        'total' => $total
    ];
}

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['idJoueur'])) {
    reponse_json(false, 'Vous devez être connecté pour ajouter au panier.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reponse_json(false, 'Méthode invalide.');
}

$idJoueur = (int)$_SESSION['user']['idJoueur'];
$idItem = isset($_POST['idItem']) ? (int)$_POST['idItem'] : 0;

if ($idItem <= 0) {
    reponse_json(false, 'Item invalide.');
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            idItem,
            nom,
            prix,
            photo,
            quantiteStock,
            typeItem,
            estDisponible
        FROM Items
        WHERE idItem = ?
        LIMIT 1
    ");
    $stmt->execute([$idItem]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item || (int)$item['estDisponible'] !== 1) {
        reponse_json(false, 'Item introuvable ou non disponible.');
    }

    if ((int)$item['quantiteStock'] <= 0) {
        reponse_json(false, 'Cet item est en rupture de stock.');
    }

    $estMage = (int)($_SESSION['user']['estMage'] ?? 0);

    if ($item['typeItem'] === 'S' && $estMage !== 1) {
        reponse_json(false, 'Seuls les mages peuvent ajouter des sorts au panier.');
    }

    $stmt = $pdo->prepare("
        SELECT quantitePanier
        FROM Paniers
        WHERE idJoueur = ? AND idItem = ?
        LIMIT 1
    ");
    $stmt->execute([$idJoueur, $idItem]);
    $lignePanier = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lignePanier) {
        $nouvelleQuantite = (int)$lignePanier['quantitePanier'] + 1;

        if ($nouvelleQuantite > (int)$item['quantiteStock']) {
            reponse_json(false, 'Quantité maximale atteinte pour cet item.');
        }

        $stmt = $pdo->prepare("
            UPDATE Paniers
            SET quantitePanier = ?
            WHERE idJoueur = ? AND idItem = ?
        ");
        $stmt->execute([$nouvelleQuantite, $idJoueur, $idItem]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO Paniers (idJoueur, idItem, quantitePanier)
            VALUES (?, ?, 1)
        ");
        $stmt->execute([$idJoueur, $idItem]);
    }

    $panier = charger_panier_json($pdo, $idJoueur);

    reponse_json(
        true,
        'Item ajouté au panier.',
        $panier['items'],
        $panier['total']
    );

} catch (Throwable $e) {
    reponse_json(false, 'Erreur serveur lors de l’ajout au panier.');
}