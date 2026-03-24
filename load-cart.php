<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['idJoueur'])) {
    echo json_encode([
        'success' => false,
        'items' => [],
        'total' => 0,
        'message' => 'Utilisateur non connecté.'
    ]);
    exit;
}

$idJoueur = (int)$_SESSION['user']['idJoueur'];

try {
    $stmt = $pdo->prepare("
        SELECT p.idItem, p.quantitePanier, i.nom, i.prix, i.photo
        FROM Paniers p
        INNER JOIN Items i ON p.idItem = i.idItem
        WHERE p.idJoueur = ?
        ORDER BY i.nom
    ");
    $stmt->execute([$idJoueur]);
    $itemsPanier = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = 0;
    foreach ($itemsPanier as &$ligne) {
        $ligne['prix'] = (int)$ligne['prix'];
        $ligne['quantitePanier'] = (int)$ligne['quantitePanier'];
        $ligne['sousTotal'] = $ligne['prix'] * $ligne['quantitePanier'];
        $total += $ligne['sousTotal'];
    }

    echo json_encode([
        'success' => true,
        'items' => $itemsPanier,
        'total' => $total
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'items' => [],
        'total' => 0,
        'message' => 'Erreur SQL : ' . $e->getMessage()
    ]);
}