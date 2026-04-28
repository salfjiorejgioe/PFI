<?php
require_once 'db.php';

if (!function_exists('h')) {
    function h($texte) {
        return htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
    }
}


if (isset($_SESSION['user'])) {
    $joueur_id = $_SESSION['user']['idJoueur'];
    $joueur_alias = $_SESSION['user']['alias'];

    $joueur_est_mage = $_SESSION['user']['estMage'];
    $pointsVie = $_SESSION['user']['pointsVie'];

}

function sort_heal($pdo, $idItem, $quantiteInventaire){
    $sql = "SELECT s.*, i.nom, t.pvRetire
            FROM Sorts s
            JOIN Items i ON s.idItem = i.idItem
            JOIN TypeSorts t ON s.typeSort = t.typeSort
            WHERE s.idItem = :idItem";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['idItem' => $idItem]);
    $sort = $stmt->fetch();

    if ($sort && $sort['typeSort'] === 'H') {
        $heal = -$sort['pvRetire'];
        echo_Heal($heal, $idItem);
    }
}

function potion_heal($pdo, $idItem, $quantiteInventaire){
    $sql = "SELECT p.*, i.nom 
            FROM Potions p
            JOIN Items i ON p.idItem = i.idItem
            WHERE p.idItem = :idItem";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['idItem' => $idItem]);
    $potion = $stmt->fetch();

    if ($potion) {
        $description = $potion['effet'];

        if (preg_match('/soin|soigne|heal/i', $description)) {
            if (preg_match('/\d+/', $description, $matches)) {
                $heal = (int)$matches[0];
                echo_Heal($heal, $idItem);
            }
        }
    }
}

function echo_Heal($quantite_heal, $idItem){
    echo '
        <form method="post">
            <input type="hidden" name="idItem" value="' . $idItem . '">
            <input type="hidden" name="healing" value="' . $quantite_heal . '">
            <input type="submit" name="action" value="Utiliser item de soin" class="btn-panier btn-vider" style="background: green"/>
        </form>
    ';
}



//sera appelé dans la page d'affichage des items de soins
function modifier_Pv_joueur_connecte($pdo, $idJoueur, $modification_PV){

    // working on it, not done---------------------------------------------------
    $stmt = $pdo->prepare("
        SELECT pointsVie
        FROM Joueurs
        WHERE idJoueur = ?
    ");
    $stmt->execute([$idJoueur]);
    $joueur = $stmt->fetch();
    $currentHealth = (int)$joueur['pointsVie'];




    $pv = $currentHealth + $modification_PV;

    

    
    // Update DB
    if(($currentHealth + $modification_PV) > 50){ // if healing exceeds 50 hp
        $sql = "UPDATE Joueurs 
            SET pointsVie = :heal
            WHERE idJoueur = :idJoueur";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
        'heal' => 50,
        'idJoueur' => $idJoueur
        ]);
     }

    
    else{ // default healing

        $sql = "UPDATE Joueurs 
            SET pointsVie = pointsVie + :heal
            WHERE idJoueur = :idJoueur";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
        'heal' => $modification_PV,
        'idJoueur' => $idJoueur
        ]);
    }
    //---------------------------------------------------------------------------
    

    $stmt = $pdo->prepare("
        SELECT pointsVie
        FROM Joueurs
        WHERE idJoueur = ?
    ");
    $stmt->execute([$idJoueur]);
    $joueur = $stmt->fetch();

    //session update
    $_SESSION['user']['pointsVie'] = (int)$joueur['pointsVie'];
}