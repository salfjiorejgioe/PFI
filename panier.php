<?php 
session_start();
require_once 'db.php';



$joueur_id = $_SESSION['joueur_id'];
$joueur_alias = $_SESSION['joueur_alias'] ;
$joueur_or = $_SESSION['joueur_or'];
$joueur_argent = $_SESSION['joueur_argent'];
$joueur_bronze = $_SESSION['joueur_bronze'];
$joueur_est_mage = $_SESSION['joueur_est_mage'];

function payer(){


}
function obtenirArticlesPanier()
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
$articles_panier = obtenirArticlesPanier()








// question: en permanence vérifier si l'item est toujours disponible?
foreach($articles_panier as $articles){
    $info_article = obtenirArticle();

    $nomItem = $article["nom"];
    $quantite = $quantite["quantitePanier"];
    $prix = $article["prix"];
    $image = $article["photo"];
                            $nomfichier = $article["chemin_image"];
                            $vendeur = $article["usager"];
                            $date = get_date($article["date_pub"]);
                            $categorie = $article["nomcategorie"];
                            $negtxt = "";



    echo '
            <div>
    
    
    
    
    '
    }



?>
