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

if (isset($_SESSION['user']['idJoueur'])) {
        $joueur_id = $_SESSION['user']['idJoueur'];
        $joueur_alias = $_SESSION['user']['alias'] ;
        $joueur_or = $_SESSION['user']['or'];
        $joueur_argent = $_SESSION['user']['argent'];
        $joueur_bronze = $_SESSION['user']['bronze'];
        $joueur_est_mage = $_SESSION['user']['estMage'];

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

    $idJoueur = $_SESSION['user']['idJoueur'];

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
    if (!isset($_SESSION['user']["idJoueur"])) {
        return false; // sécurité
    }
    $joueur_id = $_SESSION['user']['idJoueur'];

    
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


    

    try {
        if($item){
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
        echo "Erreur SQL: " . $e->getMessage();
        return false;
    }
}
function retirer_objet_Panier($pdo, $idItem){ // retire -1 objet au panier selon l'id de l'item
    if (!isset($_SESSION['user']["idJoueur"])) {
        return false; // sécurité
    }
    $joueur_id = $_SESSION['user']['idJoueur'];

    
    $sql = "SELECT quantitePanier
            FROM Paniers
            WHERE idJoueur = ? AND idItem = ?";
    
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$joueur_id, $idItem]);
    $item = $stmt->fetch();

    if (!$item) {
        return false; // aucun item correspondant dans le panier
    }

    $quantite = $item["quantitePanier"];


    
    try {
        if($quantite > 1){
            $sql = "UPDATE Paniers
                    SET quantitePanier = quantitePanier - 1
                    WHERE idJoueur = ? AND idItem = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$joueur_id, $idItem]);
        }
        else{
            $sql = "DELETE FROM Paniers
                    WHERE idJoueur = ? AND idItem = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$joueur_id, $idItem]);
        }
        return true;
    } catch (Exception $e) {
        echo "Erreur SQL: " . $e->getMessage();
        return false;
    }
}
function acheter_panier($pdo){ // don't mind this one for now ///////////////////////// buy items
    $liste_items = obtenirArticlesPanier($pdo);
    $prixTotalOr = 0;
    $idJoueur = $_SESSION['user']['idJoueur'];
    foreach ($liste_items as $item){ // ajouter idItem et quantité a inventaire. Si item existe deja, ajouter quantité. Prof fournit un curseur pour maj l'inventaire
        $idItem = $item['idItem'];
        $quantite = $item["quantitePanier"];
        $prix = $liste_items["prix"];

        $prixTotalOr += $prix * $quantite;
    }

    //convertir


    
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