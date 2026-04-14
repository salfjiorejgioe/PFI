<?php
require_once 'bd.php';

if (!function_exists('h')) {
    function h($texte) {
        return htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
    }
}


function filtre_potion_heal($idItem){
    // verifier si la desctiption contient %soigne%, %soin%, %Soigne%, %Soin%, etc
    // dans la description
    //
    //




    // verifier le nombre décrit dans la description ("Soignera 20 pv):
    // filtrer les nombres et déposer dans un array
    // mettre les nombres toString et incrémenter dans un string
    // toInt le string pour obtenir un nombre.

    echo '
         <form method="post">
            <input type="hidden" name="idItem" value="' . $idItem . '">
            <input type="hidden" name="Healing" value="' . $healing . '">
            <input type="submit" name="action" value="Utiliser"/>
        </form>
    
    
    ';

}






function ajouterPvSorts($idItem){
    // verifier si l'

}