<?php
require_once 'db.php';
require_once 'helpers.php';

if (isset($_SESSION['user'])) {
    $joueur_id = $_SESSION['user']['idJoueur'];
    $joueur_alias = $_SESSION['user']['alias'];

    $joueur_or = $_SESSION['user']['or'];
    $joueur_argent = $_SESSION['user']['argent'];
    $joueur_bronze = $_SESSION['user']['bronze'];

    $joueur_est_mage = $_SESSION['user']['estMage'];

}

function obtenirArticle($pdo, $idItem)
{ // affiche un item recherché

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
    if (!isset($_SESSION['user']['idJoueur'])) {
        return [];
    }
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
function ajouter_objet_panier_nombre($pdo, $idItem, $nombre)/////////////////////////////////////////////////////////////
{ // ajoute +1 objet au panier selon l'id de l'item
    if (!isset($_SESSION['user']['idJoueur'])) {
        return false; // sécurité
    }
    $joueur_id = $_SESSION['user']['idJoueur'];

    $info_item = obtenirArticle($pdo, $idItem);

    if (!$info_item || $info_item["estDisponible"] != 1 || $info_item['quantiteStock'] <= 0) { //item non-disponible. Impossible d'ajouter
        //echo "<script>alert('Ajout d'item impossible');</script>";
        return false;
    }
    $sql = "SELECT quantitePanier
            FROM Paniers 
            WHERE idJoueur = ? AND idItem = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$joueur_id, $idItem]);
    $item = $stmt->fetch();

    try {
        if ($item) {
            $sql = "UPDATE Paniers
                    SET quantitePanier = quantitePanier + $nombre
                    WHERE idJoueur = ? AND idItem = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$joueur_id, $idItem, $nombre]);

        } else {
            $sql = "INSERT INTO Paniers 
                    (idJoueur, idItem, quantitePanier)
                    VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$joueur_id, $idItem, $nombre]);
        }

        return true;

    } catch (Exception $e) {

    }
}
function ajouter_objet_panier($pdo, $idItem) // ajouter nombre optionnel?
{ // ajoute +1 objet au panier selon l'id de l'item
    if (!isset($_SESSION['user']['idJoueur'])) {
        return false; // sécurité
    }
    $joueur_id = $_SESSION['user']['idJoueur'];

    $info_item = obtenirArticle($pdo, $idItem);

    if (!$info_item || $info_item["estDisponible"] != 1 || $info_item['quantiteStock'] <= 0) { //item non-disponible. Impossible d'ajouter
        //echo "<script>alert('Ajout d'item impossible');</script>";
        return false;
    }
    $sql = "SELECT quantitePanier
            FROM Paniers 
            WHERE idJoueur = ? AND idItem = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$joueur_id, $idItem]);
    $item = $stmt->fetch();

    try {
        if ($item) {
            $sql = "UPDATE Paniers
                    SET quantitePanier = quantitePanier + 1
                    WHERE idJoueur = ? AND idItem = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$joueur_id, $idItem]);

        } else {
            $sql = "INSERT INTO Paniers 
                    (idJoueur, idItem, quantitePanier)
                    VALUES (?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$joueur_id, $idItem]);
        }

        return true;

    } catch (Exception $e) {

    }
}

function retirer_objet_panier($pdo, $idItem)
{
    if (!isset($_SESSION['user']['idJoueur'])) {
        return false;
    }

    $idJoueur = $_SESSION['user']['idJoueur'];

    $sql = "SELECT quantitePanier
            FROM Paniers
            WHERE idJoueur = ? AND idItem = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idJoueur, $idItem]);
    $item = $stmt->fetch();

    if (!$item) {
        //echo "<script>alert('Item absent du panier');</script>";
        return false; //pas dans panier
    }

    $quantite = (int) $item['quantitePanier'];

    try {
        if ($quantite > 1) {
            //delete from qtty item
            $sql = "UPDATE Paniers
                    SET quantitePanier = quantitePanier -1
                    WHERE idJoueur = ? AND idItem = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idJoueur, $idItem]);

        } else {
            // remve itm from paniers
            $sql = "DELETE FROM Paniers
                    WHERE idJoueur = ? AND idItem = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idJoueur, $idItem]);
        }

        return true;

    } catch (Exception $e) {
        //echo "<script>alert('Erreur');</script>";
        return false;
    }
}

function acheter_panier($pdo)
{
    if (!isset($_SESSION['user']['idJoueur'])) {
        //echo "<script>alert('Joueur non-connecté');</script>";
        return false;
    }

    $idJoueur = $_SESSION['user']['idJoueur'];

    try {
        $pdo->beginTransaction();

        //get currency
        $stmt = $pdo->prepare("SELECT gold, argent, bronze 
                                FROM Joueurs 
                                WHERE idJoueur = ?");
        $stmt->execute([$idJoueur]);
        $joueur = $stmt->fetch();

        if (!$joueur) {
            //echo "<script>alert('Joueur introuvable');</script>";
            throw new Exception("Joueur introuvable");
        }

        $gold = (int) $joueur['gold'];
        $argent = (int) $joueur['argent'];
        $bronze = (int) $joueur['bronze'];

        // get panier
        $stmt = $pdo->prepare("
            SELECT 
            p.idItem, 
            p.quantitePanier, 
            i.prix, 
            i.typeItem, 
            i.quantiteStock, 
            i.estDisponible
            FROM Paniers p
            JOIN Items i ON p.idItem = i.idItem
            WHERE p.idJoueur = ?
        ");
        $stmt->execute([$idJoueur]);
        $panier = $stmt->fetchAll();

        if (!$panier) {
            throw new Exception("Panier vide");
        }

        // get total
        $total = 0;

        foreach ($panier as $item) {
            if ($item['typeItem'] == 'S' && $_SESSION['user']['estMage'] != 1) {
                throw new Exception("Seuls les mages peuvent acheter des sorts");

            }
            $prix = (int) $item['prix'];
            $quantite = (int) $item['quantitePanier'];
            $stock = (int) $item['quantiteStock'];
            $dispo = (int) $item['estDisponible'];

            if ($dispo != 1) {
                throw new Exception("Item indisponible");
            }

            if ($stock < $quantite) {
                throw new Exception("Stock insuffisant");
            }

            $total += $prix * $quantite;
        }

        $goldRestant = $gold;
        $argentRestant = $argent;
        $bronzeRestant = $bronze;

        $manque = $total;

        // achat or
        if ($goldRestant >= $manque) {
            $goldRestant -= $manque;
            $manque = 0;
        } else {
            $manque -= $goldRestant;
            $goldRestant = 0;
        }

        // achat argent
        if ($manque > 0 && $argentRestant > 0) {

            // combien d'or on peut générer avec argent
            $orFromArgent = intdiv($argentRestant, 10);

            if ($orFromArgent >= $manque) {

                $argentUtilise = $manque * 10;
                $argentRestant -= $argentUtilise;
                $manque = 0;
            } else {
                // tout utiliser
                $manque -= $orFromArgent;
                $argentRestant -= ($orFromArgent * 10);
            }
        }

        // achat bronze
        if ($manque > 0 && $bronzeRestant > 0) {

            $orFromBronze = intdiv($bronzeRestant, 100);

            if ($orFromBronze >= $manque) {
                $bronzeUtilise = $manque * 100;
                $bronzeRestant -= $bronzeUtilise;
                $manque = 0;
            } else {
                $manque -= $orFromBronze;
                $bronzeRestant -= ($orFromBronze * 100);
            }
        }

        // pas assez au total
        if ($manque > 0) {
            //echo "<script>alert('Pas assez d'argent au total');</script>";
            throw new Exception("Pas assez d'argent total");
        }

        $nouveauGold = $goldRestant;
        $nouveauArgent = $argentRestant;
        $nouveauBronze = $bronzeRestant;


        foreach ($panier as $item) {
            $idItem = $item['idItem'];
            $qte = $item['quantitePanier'];

            // ajouter item => inventaire joueur
            $stmt = $pdo->prepare("
                INSERT INTO Inventaires (idJoueur, idItem, quantiteInventaire)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                quantiteInventaire = quantiteInventaire + VALUES(quantiteInventaire)
            ");
            $stmt->execute([$idJoueur, $idItem, $qte]);

            // reduire stock
            $stmt = $pdo->prepare("
                UPDATE Items
                SET quantiteStock = quantiteStock - ?
                WHERE idItem = ?
            ");
            $stmt->execute([$qte, $idItem]);
        }

        // Remove gold
        $stmt = $pdo->prepare("
            UPDATE Joueurs
            SET gold = ?, argent = ?, bronze = ?
            WHERE idJoueur = ?
        ");
        $stmt->execute([$nouveauGold, $nouveauArgent, $nouveauBronze, $idJoueur]);

        //vider panier
        vider_panier($pdo, $idJoueur);

$pdo->commit();

// update session
$_SESSION['user']['or'] = $nouveauGold;
$_SESSION['user']['argent'] = $nouveauArgent;
$_SESSION['user']['bronze'] = $nouveauBronze;

return [
    "success" => true,
    "message" => "Achat réussi"
];

} catch (Exception $e) {
    $pdo->rollBack();

    return [
        "success" => false,
        "message" => $e->getMessage()
    ];
}
}
function supprimer_objet_du_panier($pdo, $idItem)
{
    if (!isset($_SESSION['user']['idJoueur'])) {
        //echo "<script>alert('Joueur non-connecté');</script>";
        return false;
    }

    $idJoueur = $_SESSION['user']['idJoueur'];

    try {
        $stmt = $pdo->prepare("
            DELETE FROM Paniers
            WHERE idJoueur = ? AND idItem = ?
        ");
        $stmt->execute([$idJoueur, $idItem]);

        return true;

    } catch (Exception $e) {
        return false;
    }
}
function modifier_quantite_panier($pdo, $idItem, $quantite)
{
    if (!isset($_SESSION['user']['idJoueur'])) {
        return [
            "success" => false,
            "message" => "Joueur non connecté"
        ];
    }

    $idJoueur = $_SESSION['user']['idJoueur'];
    $quantite = (int)$quantite;

    if ($quantite < 0) {
        return [
            "success" => false,
            "message" => "Quantité invalide"
        ];
    }

    $info_item = obtenirArticle($pdo, $idItem);

    if (!$info_item) {
        return [
            "success" => false,
            "message" => "Article introuvable"
        ];
    }

    if ($quantite == 0) {
        $stmt = $pdo->prepare("
            DELETE FROM Paniers
            WHERE idJoueur = ? AND idItem = ?
        ");
        $stmt->execute([$idJoueur, $idItem]);

        return [
            "success" => true,
            "message" => "Article retiré du panier"
        ];
    }

    if ($info_item['estDisponible'] != 1) {
        return [
            "success" => false,
            "message" => "Article indisponible"
        ];
    }

    if ($quantite > (int)$info_item['quantiteStock']) {
        return [
            "success" => false,
            "message" => "Stock insuffisant"
        ];
    }

    $stmt = $pdo->prepare("
        UPDATE Paniers
        SET quantitePanier = ?
        WHERE idJoueur = ? AND idItem = ?
    ");
    $stmt->execute([$quantite, $idJoueur, $idItem]);

    return [
        "success" => true,
        "message" => "Quantité mise à jour"
    ];
}
function vider_panier($pdo, $idJoueur)
{
    $stmt = $pdo->prepare("
            DELETE FROM Paniers
            WHERE idJoueur = ?
        ");
    $stmt->execute([$idJoueur]);
}

function afficher_panier($pdo)
{
    $articles_panier = obtenirArticlesPanier($pdo);

    $prixTotalOr = 0;

    foreach ($articles_panier as $articles) {

        $info_article = obtenirArticle($pdo, $articles['idItem']);
        $prixTotalOr += $info_article["prix"] * $articles["quantitePanier"];
    }

    echo "<h3>Total: $prixTotalOr or</h3>";
    // question: en permanence vérifier si l'item est toujours disponible?
    foreach ($articles_panier as $articles) {
        $info_article = obtenirArticle($pdo, $articles['idItem']);


        $idItem = $articles['idItem'];
        $nomItem = $info_article["nom"];
        $quantite = $articles["quantitePanier"];
        $prix = $info_article["prix"];
        $image = $info_article["photo"];
        $prixtotal = $prix * $quantite;

        // faire en sorte d'avoir des boutons qui appellent les fonctions ajouter/retirer en passant l'id de l'item quand appuyés
        // ajouter input number qui:
        // - submit le form et refresh page quand stopped typing (javascript) (php input number submit form auto when stopped typing)
        // - update shown number depending 
        //          - (default number) placeholder = 0
        //          
        echo '
    <div class="panier-item-grid">
        <a class="" href="details.php?id=' . $idItem . '">
            <img src="' . $image . '">
            <h3>' . $nomItem . '</h3>
            <p>Prix unitaire: ' . $prix . ' or</p>
            <p>Quantité: ' . $quantite . '</p>
            <p>Prix total: ' . $prixtotal . ' or</p>
        </a>
        <form method="post">
            <input type="hidden" name="idItem" value="' . $idItem . '">
            <input type="submit" name="action" value="Ajouter"/> 
            
            <input type="submit" name="action" value="Retirer"/>
            <input type="submit" name="action" value="Supprimer du panier"/>
        </form>
        
    </div>
    ';
    }
}
?>