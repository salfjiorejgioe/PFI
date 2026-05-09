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

        if ($ligne['quantitePanier'] < 0) {
            $ligne['quantitePanier'] = 0;
        }

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
    reponse_json(false, 'Vous devez être connecté.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reponse_json(false, 'Méthode invalide.');
}

$idJoueur = (int)$_SESSION['user']['idJoueur'];
$action = $_POST['action'] ?? '';
$idItem = isset($_POST['idItem']) ? (int)$_POST['idItem'] : 0;

$actionsValides = ['clear', 'remove', 'increase', 'decrease'];

if (!in_array($action, $actionsValides, true)) {
    reponse_json(false, 'Action invalide.');
}

if ($action !== 'clear' && $idItem <= 0) {
    reponse_json(false, 'Article invalide.');
}

try {
    if ($action === 'clear') {
        $stmt = $pdo->prepare("
            DELETE FROM Paniers
            WHERE idJoueur = ?
        ");
        $stmt->execute([$idJoueur]);

        $panier = charger_panier_json($pdo, $idJoueur);
        reponse_json(true, 'Panier vidé.', $panier['items'], $panier['total']);
    }

    if ($action === 'remove') {
        $stmt = $pdo->prepare("
            DELETE FROM Paniers
            WHERE idJoueur = ? AND idItem = ?
        ");
        $stmt->execute([$idJoueur, $idItem]);

        $panier = charger_panier_json($pdo, $idJoueur);
        reponse_json(true, 'Article retiré du panier.', $panier['items'], $panier['total']);
    }

    if ($action === 'increase') {
        $stmt = $pdo->prepare("
            SELECT 
                p.quantitePanier,
                i.quantiteStock,
                i.estDisponible,
                i.typeItem
            FROM Paniers p
            INNER JOIN Items i ON p.idItem = i.idItem
            WHERE p.idJoueur = ? AND p.idItem = ?
            LIMIT 1
        ");
        $stmt->execute([$idJoueur, $idItem]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            reponse_json(false, 'Article absent du panier.');
        }

        if ((int)$ligne['estDisponible'] !== 1) {
            reponse_json(false, 'Article indisponible.');
        }

        $estMage = (int)($_SESSION['user']['estMage'] ?? 0);

        if ($ligne['typeItem'] === 'S' && $estMage !== 1) {
            reponse_json(false, 'Seuls les mages peuvent acheter des sorts.');
        }

        $quantiteActuelle = (int)$ligne['quantitePanier'];
        $stockMax = (int)$ligne['quantiteStock'];
        $nouvelleQuantite = $quantiteActuelle + 1;

        if ($nouvelleQuantite > $stockMax) {
            reponse_json(false, 'Stock maximal atteint.');
        }

        $stmt = $pdo->prepare("
            UPDATE Paniers
            SET quantitePanier = ?
            WHERE idJoueur = ? AND idItem = ?
        ");
        $stmt->execute([$nouvelleQuantite, $idJoueur, $idItem]);

        $panier = charger_panier_json($pdo, $idJoueur);
        reponse_json(true, 'Quantité augmentée.', $panier['items'], $panier['total']);
    }

    if ($action === 'decrease') {
        $stmt = $pdo->prepare("
            SELECT quantitePanier
            FROM Paniers
            WHERE idJoueur = ? AND idItem = ?
            LIMIT 1
        ");
        $stmt->execute([$idJoueur, $idItem]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            reponse_json(false, 'Article absent du panier.');
        }

        $nouvelleQuantite = (int)$ligne['quantitePanier'] - 1;

        if ($nouvelleQuantite <= 0) {
            $stmt = $pdo->prepare("
                DELETE FROM Paniers
                WHERE idJoueur = ? AND idItem = ?
            ");
            $stmt->execute([$idJoueur, $idItem]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE Paniers
                SET quantitePanier = ?
                WHERE idJoueur = ? AND idItem = ?
            ");
            $stmt->execute([$nouvelleQuantite, $idJoueur, $idItem]);
        }

        $panier = charger_panier_json($pdo, $idJoueur);
        reponse_json(true, 'Quantité diminuée.', $panier['items'], $panier['total']);
    }

} catch (Throwable $e) {
    reponse_json(false, 'Erreur serveur lors de la modification du panier.');
}