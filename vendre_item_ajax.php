<?php
session_start();
header('Content-Type: application/json');

require_once 'db.php';

if (
    !isset($_SESSION['user']) ||
    !is_array($_SESSION['user']) ||
    !isset($_SESSION['user']['idJoueur'])
) {
    echo json_encode([
        'success' => false,
        'message' => 'Utilisateur non connecté.'
    ]);
    exit;
}

$idJoueur = (int) $_SESSION['user']['idJoueur'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode invalide.'
    ]);
    exit;
}

$idItem = isset($_POST['idItem']) ? (int) $_POST['idItem'] : 0;
$quantiteDemandee = isset($_POST['quantiteVente']) ? (int) $_POST['quantiteVente'] : 0;

if ($idItem <= 0 || $quantiteDemandee <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Quantité invalide.'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT it.idItem,
               it.nom,
               it.prix,
               it.typeItem,
               inv.quantiteInventaire,
               s.rarete
        FROM Inventaires inv
        INNER JOIN Items it ON inv.idItem = it.idItem
        LEFT JOIN Sorts s ON it.idItem = s.idItem
        WHERE inv.idJoueur = ? AND inv.idItem = ?
        LIMIT 1
    ");
    $stmt->execute([$idJoueur, $idItem]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Item introuvable dans l’inventaire.'
        ]);
        exit;
    }

    $quantitePossedee = (int) $item['quantiteInventaire'];
    $quantiteVendue = min($quantiteDemandee, $quantitePossedee);

    if ($quantiteVendue <= 0) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Aucune quantité à vendre.'
        ]);
        exit;
    }

    if ($item['typeItem'] === 'S') {
        $profitUnitaire = (int) $item['prix'] - ((int) $item['rarete'] * 5) + 5;
    } else {
        $profitUnitaire = (int) round((int) $item['prix'] * 0.6);
    }

    if ($profitUnitaire < 0) {
        $profitUnitaire = 0;
    }

    $profitTotal = $profitUnitaire * $quantiteVendue;

    $stmt = $pdo->prepare("
        UPDATE Items
        SET quantiteStock = quantiteStock + ?
        WHERE idItem = ?
    ");
    $stmt->execute([$quantiteVendue, $idItem]);

    if ($quantitePossedee > $quantiteVendue) {
        $stmt = $pdo->prepare("
            UPDATE Inventaires
            SET quantiteInventaire = quantiteInventaire - ?
            WHERE idJoueur = ? AND idItem = ?
        ");
        $stmt->execute([$quantiteVendue, $idJoueur, $idItem]);
        $quantiteRestante = $quantitePossedee - $quantiteVendue;
    } else {
        $stmt = $pdo->prepare("
            DELETE FROM Inventaires
            WHERE idJoueur = ? AND idItem = ?
        ");
        $stmt->execute([$idJoueur, $idItem]);
        $quantiteRestante = 0;
    }

    $stmt = $pdo->prepare("
        UPDATE Joueurs
        SET gold = gold + ?
        WHERE idJoueur = ?
    ");
    $stmt->execute([$profitTotal, $idJoueur]);

    $stmt = $pdo->prepare("
        SELECT gold
        FROM Joueurs
        WHERE idJoueur = ?
        LIMIT 1
    ");
    $stmt->execute([$idJoueur]);
    $joueur = $stmt->fetch(PDO::FETCH_ASSOC);
    $gold = (int) $joueur['gold'];

    $_SESSION['user']['or'] = $gold;

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Vente effectuée avec succès.',
        'idItem' => $idItem,
        'nomItem' => $item['nom'],
        'quantiteDemandee' => $quantiteDemandee,
        'quantiteVendue' => $quantiteVendue,
        'quantiteRestante' => $quantiteRestante,
        'profitUnitaire' => $profitUnitaire,
        'profitTotal' => $profitTotal,
        'gold' => $gold
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur lors de la vente.'
    ]);
    exit;
}