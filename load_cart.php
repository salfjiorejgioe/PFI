<?php
require_once 'session_config.php';

header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

function reponse_json($success, $items = [], $total = 0, $message = '')
{
    echo json_encode([
        'success' => $success,
        'items' => $items,
        'total' => $total,
        'message' => $message
    ]);
    exit;
}

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['idJoueur'])) {
    reponse_json(false, [], 0, 'Utilisateur non connecté.');
}

$idJoueur = (int)$_SESSION['user']['idJoueur'];

try {
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
        $ligne['prix'] = (int)$ligne['prix'];
        $ligne['quantitePanier'] = (int)$ligne['quantitePanier'];
        $ligne['quantiteStock'] = (int)$ligne['quantiteStock'];
        $ligne['estDisponible'] = (int)$ligne['estDisponible'];

        if ($ligne['quantitePanier'] < 0) {
            $ligne['quantitePanier'] = 0;
        }

        $ligne['sousTotal'] = $ligne['prix'] * $ligne['quantitePanier'];
        $total += $ligne['sousTotal'];
    }

    unset($ligne);

    reponse_json(true, $itemsPanier, $total, 'Panier chargé.');

} catch (Throwable $e) {
    reponse_json(false, [], 0, 'Erreur serveur lors du chargement du panier.');
}