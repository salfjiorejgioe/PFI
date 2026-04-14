<?php
session_start();
header('Content-Type: application/json');

require_once 'db.php';
require_once 'helpers.php';
require_once 'panier_de_paniertest.php';

if (!isset($_SESSION['user'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Utilisateur non connecté.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode invalide.'
    ]);
    exit;
}

if (!isset($_POST['idItem']) || !isset($_POST['quantite'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Données manquantes.'
    ]);
    exit;
}

if ($_POST['quantite'] === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Quantité vide ignorée.'
    ]);
    exit;
}

$idItem = (int) $_POST['idItem'];
$quantiteDemandee = (int) $_POST['quantite'];

if ($idItem <= 0 || $quantiteDemandee < 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Valeur invalide.'
    ]);
    exit;
}

$info = obtenirArticle($pdo, $idItem);

if (!$info) {
    echo json_encode([
        'success' => false,
        'message' => 'Article introuvable.'
    ]);
    exit;
}

$stockMax = isset($info['quantiteStock']) ? (int) $info['quantiteStock'] : 0;
$quantiteFinale = $quantiteDemandee;
$message = '';
$success = true;

if ($quantiteDemandee > $stockMax) {
    $quantiteFinale = $stockMax;
    $message = 'Quantité ajustée au stock disponible (' . $stockMax . ').';
}

$resultat = modifier_quantite_panier($pdo, $idItem, $quantiteFinale);

if (!$resultat['success']) {
    echo json_encode([
        'success' => false,
        'message' => $resultat['message']
    ]);
    exit;
}

if ($message === '') {
    $message = $resultat['message'];
}

$articles_panier = obtenirArticlesPanier($pdo);
$totalOr = 0;
$prixTotalItem = 0;

foreach ($articles_panier as $article) {
    $articleInfo = obtenirArticle($pdo, $article['idItem']);
    if ($articleInfo) {
        $ligneTotal = (int) $articleInfo['prix'] * (int) $article['quantitePanier'];
        $totalOr += $ligneTotal;

        if ((int) $article['idItem'] === $idItem) {
            $prixTotalItem = $ligneTotal;
        }
    }
}

echo json_encode([
    'success' => $success,
    'message' => $message,
    'idItem' => $idItem,
    'quantiteDemandee' => $quantiteDemandee,
    'quantiteAppliquee' => $quantiteFinale,
    'stockMax' => $stockMax,
    'prixTotalItem' => $prixTotalItem,
    'totalOr' => $totalOr
]);
exit;