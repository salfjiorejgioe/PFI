<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['idJoueur'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour ajouter au panier.'
    ]);
    exit;
}

$idJoueur = (int)$_SESSION['user']['idJoueur'];
$idItem = isset($_POST['idItem']) ? (int)$_POST['idItem'] : 0;

if ($idItem <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Item invalide.'
    ]);
    exit;
}

try {
    // Vérifier que l'item existe et est disponible
    $stmt = $pdo->prepare("
        SELECT idItem, nom, prix, photo, quantiteStock
        FROM Items
        WHERE idItem = ? AND estDisponible = 1
        LIMIT 1
    ");
    $stmt->execute([$idItem]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode([
            'success' => false,
            'message' => 'Item introuvable ou non disponible.'
        ]);
        exit;
    }

    // Vérifier si déjà dans le panier
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

        // optionnel: bloquer si dépasse le stock
        if ($nouvelleQuantite > (int)$item['quantiteStock']) {
            echo json_encode([
                'success' => false,
                'message' => 'Quantité maximale atteinte pour cet item.'
            ]);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE Paniers
            SET quantitePanier = ?
            WHERE idJoueur = ? AND idItem = ?
        ");
        $stmt->execute([$nouvelleQuantite, $idJoueur, $idItem]);
    } else {
        if ((int)$item['quantiteStock'] < 1) {
            echo json_encode([
                'success' => false,
                'message' => 'Cet item est en rupture de stock.'
            ]);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO Paniers (idJoueur, idItem, quantitePanier)
            VALUES (?, ?, 1)
        ");
        $stmt->execute([$idJoueur, $idItem]);
    }

    // Recharger le panier complet
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
        'message' => 'Item ajouté au panier.',
        'items' => $itemsPanier,
        'total' => $total
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur SQL : ' . $e->getMessage()
    ]);
}