<?php
require_once 'session_config.php';

header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';
require_once 'helpers.php';
require_once 'panier_de_paniertest.php';

function reponse_json($success, $message, $extra = [])
{
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['idJoueur'])) {
    reponse_json(false, 'Utilisateur non connecté.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reponse_json(false, 'Méthode invalide.');
}

$idItem = isset($_POST['idItem']) ? (int) $_POST['idItem'] : 0;
$quantiteRaw = $_POST['quantite'] ?? null;

if ($idItem <= 0) {
    reponse_json(false, 'Article invalide.');
}

if ($quantiteRaw === null || trim((string)$quantiteRaw) === '') {
    reponse_json(false, 'Quantité manquante.');
}

if (!is_numeric($quantiteRaw)) {
    reponse_json(false, 'Quantité invalide.');
}

$quantiteDemandee = (int) $quantiteRaw;

if ($quantiteDemandee < 0) {
    reponse_json(false, 'La quantité ne peut pas être négative.');
}

try {
    $info = obtenirArticle($pdo, $idItem);

    if (!$info) {
        reponse_json(false, 'Article introuvable.');
    }

    if ((int)$info['estDisponible'] !== 1) {
        reponse_json(false, 'Article indisponible.');
    }

    $estMage = (int)($_SESSION['user']['estMage'] ?? 0);

    if ($info['typeItem'] === 'S' && $estMage !== 1) {
        reponse_json(false, 'Seuls les mages peuvent ajouter des sorts au panier.');
    }

    $stockMax = (int)($info['quantiteStock'] ?? 0);
    $quantiteFinale = $quantiteDemandee;

    if ($quantiteDemandee > $stockMax) {
        $quantiteFinale = $stockMax;
        $message = 'Quantité ajustée au stock disponible (' . $stockMax . ').';
    } else {
        $message = '';
    }

    $resultat = modifier_quantite_panier($pdo, $idItem, $quantiteFinale);

    if (!is_array($resultat) || empty($resultat['success'])) {
        reponse_json(false, $resultat['message'] ?? 'Erreur lors de la mise à jour.');
    }

    if ($message === '') {
        $message = $resultat['message'] ?? 'Quantité mise à jour.';
    }

    $articles_panier = obtenirArticlesPanier($pdo);

    $totalOr = 0;
    $prixTotalItem = 0;

    foreach ($articles_panier as $article) {
        $articleInfo = obtenirArticle($pdo, $article['idItem']);

        if (!$articleInfo) {
            continue;
        }

        $ligneTotal = (int)$articleInfo['prix'] * (int)$article['quantitePanier'];
        $totalOr += $ligneTotal;

        if ((int)$article['idItem'] === $idItem) {
            $prixTotalItem = $ligneTotal;
        }
    }

    reponse_json(true, $message, [
        'idItem' => $idItem,
        'quantiteDemandee' => $quantiteDemandee,
        'quantiteAppliquee' => $quantiteFinale,
        'stockMax' => $stockMax,
        'prixTotalItem' => $prixTotalItem,
        'totalOr' => $totalOr
    ]);

} catch (Throwable $e) {
    reponse_json(false, 'Erreur serveur lors de la mise à jour du panier.');
}