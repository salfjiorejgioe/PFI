<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] == 'Ajouter') {
        ajouter_objet_panier($pdo, $_POST['idItem']);
    } 
    else if ($_POST['action'] == 'Retirer') {
        retirer_objet_panier($pdo, $_POST['idItem']);
    }
    else if ($_POST['action'] == 'Acheter') {
        acheter_panier($pdo);
    }

    header("Location: " . $_SERVER['PHP_SELF']); // refresh
    exit();
}

if (isset($_SESSION['joueur_id'])) {
        $joueur_id = $_SESSION['joueur_id'];
        $joueur_alias = $_SESSION['joueur_alias'] ;
        $joueur_or = $_SESSION['joueur_or'];
        $joueur_argent = $_SESSION['joueur_argent'];
        $joueur_bronze = $_SESSION['joueur_bronze'];
        $joueur_est_mage = $_SESSION['joueur_estMage'];

}

function obtenirArticle($pdo, $idItem){ // affiche un item recherché

    $sql = "SELECT 
                idItem,
                nom,
                quantiteStock,
                prix,
                photo,
                typeItem,
                estDisponible
            FROM Items
            WHERE idItem = ?";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idItem]);
        $articles = $stmt->fetch();
        return $articles;

    } catch (Exception $e) {
        return null;
    }
}
function obtenirArticlesPanier($pdo) // obtenir tous les articles du panier du joueur connecté
{
    $sql = "SELECT 
                idJoueur,
                idItem,
                quantitePanier
                FROM Paniers
            WHERE idJoueur = ?";

    $idJoueur = $_SESSION['joueur_id'];

    try {
       $stmt = $pdo->prepare($sql);
        $stmt->execute([$idJoueur]);
        $articles = $stmt->fetchAll(); // toutes les lignes
        return $articles;

    } catch (Exception $e) {
        return [];
    }
}
function ajouter_objet_Panier($pdo, $idItem){ // ajoute +1 objet au panier selon l'id de l'item
    if (!isset($_SESSION["joueur_id"])) {
        return false; // sécurité
    }
    $joueur_id = $_SESSION['joueur_id'];
      
    $info_item = obtenirArticle($pdo, $idItem);

    if (!$info_item|| $info_item["estDisponible"] != 1 || $info_item['quantiteStock'] <= 0) { //item non-disponible. Impossible d'ajouter
        return false; // faire un popup "item indisponible"/////////////////////////////////////
    }
   $sql = "SELECT quantitePanier
            FROM Panier 
            WHERE idJoueur = ? AND idItem = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$joueur_id, $idItem]);
    $item = $stmt->fetch();


    

    try {       if($item){
            $sql = "UPDATE Panier
                    SET quantitePanier = quantitePanier + 1
                    WHERE idJoueur = ? AND idItem = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$joueur_id, $idItem]);
        
        }
        else{
            $sql = "INSERT INTO Panier 
                    (idJoueur, idItem, quantitePanier)
                    VALUES (?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$joueur_id, $idItem]);
        }

        return true;

    } catch (Exception $e) {
            //convertir
    }


    
}

$articles_panier = obtenirArticlesPanier($pdo);

$prixTotalOr = 0;

foreach ($articles_panier as $articles){

    $info_article = obtenirArticle($pdo, $articles['idItem']);
    $prixTotalOr += $info_article["prix"] * $articles["quantitePanier"];
}

echo "<h4>Total: $prixTotalOr or</h4>";
// question: en permanence vérifier si l'item est toujours disponible?
foreach ($articles_panier as $articles){
    $info_article = obtenirArticle($pdo, $articles['idItem']);


    $idItem = $articles['idItem'];
    $nomItem = $info_article["nom"];
    $quantite = $articles["quantitePanier"];
    $prix = $info_article["prix"];
    $image = $info_article["photo"];
        
    
    // faire en sorte d'avoir des boutons qui appellent les fonctions ajouter/retirer en passant l'id de l'item quand appuyés
    echo '
    <div class="panier-item-grid">
        <a class="" href="details.php?id='. $idItem .'">
            <img src="' . $image . '">
            <h3>' . $nomItem . '</h3>
            <p>' . $prix . '</p>
            <p>' . $prix . ' or</p>
            <p>' . $quantite . '</p>
        </a>
        <form method="post">
            <input type="hidden" name="idItem" value="' . $idItem . '">
            <input type="submit" name="action" value="Ajouter"/> 
            <input type="submit" name="action" value="Retirer"/>
        </form>
        
    </div>
    ';
    }
?>