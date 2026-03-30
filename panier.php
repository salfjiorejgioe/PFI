<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['idJoueur'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Non connecté'
    ]);
    exit;
}

$idJoueur = (int) $_SESSION['user']['idJoueur'];

try {
    $pdo->beginTransaction();

    // 1. Charger le joueur
    $stmt = $pdo->prepare("
        SELECT idJoueur, gold, argent, bronze
        FROM Joueurs
        WHERE idJoueur = ?
        LIMIT 1
    ");
    $stmt->execute([$idJoueur]);
    $joueur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$joueur) {
        throw new Exception("Joueur introuvable");
    }

    // 2. Charger le panier
    $stmt = $pdo->prepare("
        SELECT p.idItem, p.quantitePanier, i.nom, i.prix, i.quantiteStock, i.estDisponible
        FROM Paniers p
        INNER JOIN Items i ON p.idItem = i.idItem
        WHERE p.idJoueur = ?
    ");
    $stmt->execute([$idJoueur]);
    $panier = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$panier || count($panier) === 0) {
        throw new Exception("Panier vide");
    }

    // 3. Vérifier stock + calculer total
    $total = 0;

    foreach ($panier as $item) {
        $stock = (int) $item['quantiteStock'];
        $qte = (int) $item['quantitePanier'];
        $prix = (int) $item['prix'];
        $dispo = (int) $item['estDisponible'];

        if ($dispo !== 1) {
            throw new Exception("Un item n'est plus disponible : " . $item['nom']);
        }

        if ($stock < $qte) {
            throw new Exception("Stock insuffisant pour : " . $item['nom']);
        }

        $total += ($prix * $qte);
    }

    // 4. Vérifier l'or du joueur
    $capitalGold = (int) $joueur['gold'];

    if ($capitalGold < $total) {
        throw new Exception("Pas assez d'or");
    }

    
foreach ($panier as $item) {
    $idItem = (int) $item['idItem'];
    $qte = (int) $item['quantitePanier'];
    $stockActuel = (int) $item['quantiteStock'];

    // Vérifier si l'item existe déjà dans Inventaires pour ce joueur
    $stmt = $pdo->prepare("
        SELECT quantiteInventaire
        FROM Inventaires
        WHERE idJoueur = ? AND idItem = ?
    ");
    $stmt->execute([$idJoueur, $idItem]);
    $itemExiste = $stmt->fetch();

    // Ajouter ou mettre à jour dans Inventaires
    if ($itemExiste) {
        $stmt = $pdo->prepare("
            UPDATE Inventaires
            SET quantiteInventaire = quantiteInventaire + ?
            WHERE idJoueur = ? AND idItem = ?
        ");
        $stmt->execute([$qte, $idJoueur, $idItem]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO Inventaires (idJoueur, idItem, quantiteInventaire)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$idJoueur, $idItem, $qte]);
    }

    // Réduire le stock
    $nouveauStock = $stockActuel - $qte;

    if ($nouveauStock <= 0) {
        $stmt = $pdo->prepare("
            UPDATE Items
            SET quantiteStock = 0, estDisponible = 0
            WHERE idItem = ?
        ");
        $stmt->execute([$idItem]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE Items
            SET quantiteStock = ?
            WHERE idItem = ?
        ");
        $stmt->execute([$nouveauStock, $idItem]);
    }
}


    $nouveauGold = $capitalGold - $total;

    $stmt = $pdo->prepare("
        UPDATE Joueurs
        SET gold = ?
        WHERE idJoueur = ?
    ");
    $stmt->execute([$nouveauGold, $idJoueur]);

    $stmt = $pdo->prepare("
        DELETE FROM Paniers
        WHERE idJoueur = ?
    ");
    $stmt->execute([$idJoueur]);

    $pdo->commit();

    $_SESSION['user']['or'] = $nouveauGold;

    echo json_encode([
        'success' => true,
        'message' => 'Achat réussi',
        'nouveauGold' => $nouveauGold
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}