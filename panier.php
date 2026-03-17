<?php 
session_start();
require_once 'db.php';

$joueur_id = $_SESSION['joueur_id'];
$joueur_alias = $_SESSION['joueur_alias'] ;
$joueur_or = $_SESSION['joueur_or'];
$joueur_argent = $_SESSION['joueur_argent'];
$joueur_bronze = $_SESSION['joueur_bronze'];
$joueur_est_mage = $_SESSION['joueur_est_mage'];

function obtenirArticlesPanier($pdo)
{
    $sql = "SELECT 
                idJoueur,
                idItem,
                quantitePanier
            FROM Paniers";
    try {
        $stmt = $pdo->query($sql);
        $articles = $stmt->fetchAll(); // toutes les lignes
        return $articles;

    } catch (Exception $e) {
        return [];
    }
}

function obtenir_article($pdo, $idItem) {


    $sql = "SELECT 
                idItem,
                nom,
                quantiteStock,
                prix,
                photo,
                typeItem,
                estDisponible
            FROM Items
            ORDER BY typeItem DESC
            WHERE idItem = ?"; // pas fini


    try {
        $stmt = $pdo->query($sql);
        $articles = $stmt->fetchAll(); // aussi inclure $idItem
        return $articles;

    } catch (Exception $e) {
        return [];
    }
}
$articles_panier = obtenirArticlesPanier();

// question: en permanence vérifier si l'item est toujours disponible?
foreach ($articles_panier as $articles){
    $info_article = obtenir_article($articles['idItem']);

    $nomItem = $info_article["nom"];
    $quantite = $info_article["quantitePanier"];
    $prix = $info_article["prix"];
    $image = $info_article["photo"];



}




?>
        