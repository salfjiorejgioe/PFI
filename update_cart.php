<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['idJoueur'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté.'
    ]);
    exit;
}

$idJoueur = (int)$_SESSION['user']['idJoueur'];
$action = $_POST['action'] ?? '';
$idItem = isset($_POST['idItem']) ? (int)$_POST['idItem'] : 0;

try {
    if ($action === 'clear') {
        $stmt = $pdo->prepare("DELETE FROM Paniers WHERE idJoueur = ?");
        $stmt->execute([$idJoueur]);
    }

    elseif ($action === 'remove' && $idItem > 0) {
        $stmt = $pdo->prepare("DELETE FROM Paniers WHERE idJoueur = ? AND idItem = ?");
        $stmt->execute([$idJoueur, $idItem]);
    }

    elseif ($action === 'increase' && $idItem > 0) {
        // stock actuel
        $stmt = $pdo->prepare("
            SELECT p.quantitePanier, i.quantiteStock
            FROM Paniers p
            INNER JOIN Items i ON p.idItem = i.idItem
            WHERE p.idJoueur = ? AND p.idItem = ?
            LIMIT 1
        ");
        $stmt->execute([$idJoueur, $idItem]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ligne) {
            $nouvelleQuantite = (int)$ligne['quantitePanier'] + 1;

            if ($nouvelleQuantite <= (int)$ligne['quantiteStock']) {
                $stmt = $pdo->prepare("
                    UPDATE Paniers
                    SET quantitePanier = ?
                    WHERE idJoueur = ? AND idItem = ?
                ");
                $stmt->execute([$nouvelleQuantite, $idJoueur, $idItem]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Stock maximal atteint.'
                ]);
                exit;
            }
        }
    }

    elseif ($action === 'decrease' && $idItem > 0) {
        $stmt = $pdo->prepare("
            SELECT quantitePanier
            FROM Paniers
            WHERE idJoueur = ? AND idItem = ?
            LIMIT 1
        ");
        $stmt->execute([$idJoueur, $idItem]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ligne) {
            $nouvelleQuantite = (int)$ligne['quantitePanier'] - 1;

            if ($nouvelleQuantite <= 0) {
                $stmt = $pdo->prepare("DELETE FROM Paniers WHERE idJoueur = ? AND idItem = ?");
                $stmt->execute([$idJoueur, $idItem]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE Paniers
                    SET quantitePanier = ?
                    WHERE idJoueur = ? AND idItem = ?
                ");
                $stmt->execute([$nouvelleQuantite, $idJoueur, $idItem]);
            }
        }
    }

    // recharger le panier
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
        'message' => 'Erreur SQL : ' . $e->getMessage()
    ]);
}