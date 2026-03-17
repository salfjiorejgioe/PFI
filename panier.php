<?php
require_once 'db.php';


if (isset($_SESSION['joueur_id'])) {
        $joueur_id = $_SESSION['joueur_id'];
        $joueur_alias = $_SESSION['joueur_alias'] ;
        $joueur_or = $_SESSION['joueur_or'];
        $joueur_argent = $_SESSION['joueur_argent'];
        $joueur_bronze = $_SESSION['joueur_bronze'];
        $joueur_est_mage = $_SESSION['joueur_est_mage'];

}


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
        $articles = $stmt->fetchAll(); 
        return $articles;

    } catch (Exception $e) {
        return [];
    }
}
$articles_panier = obtenirArticlesPanier($pdo);

// question: en permanence vérifier si l'item est toujours disponible?
foreach ($articles_panier as $articles){
    $info_article = obtenir_article($pdo, $articles['idItem']);

    $nomItem = $info_article["nom"];
    $quantite = $info_article["quantitePanier"];
    $prix = $info_article["prix"];
    $image = $info_article["photo"];


    echo '
    <div class="panier-item-grid">
            <img src="' . $image . '">
            <h3>' . $nomItem . '</h3>
            <p>' . $prix . '</p>
            <p>' . $quantite . '</p>
    </div>
    ';

    // avec lien vers details?

    // echo '
    // <div class="panier-item-grid">
    //     <a class="item-card" href="details.php?id='.$articles['idItem'] .'">
    //         <img src="' . $image . '">
    //         <h3>' . $nomItem . '</h3>
    //         <p>' . $prix . '</p>
    //         <p>' . $quantite . '</p>
    //     </a>
    // </div>
    // ';
}
?>