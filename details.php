<?php
session_start();
require_once 'db.php';

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$idItem = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$item = null;
$evaluations = [];
$error = "";
$messageAction = "";

$idJoueurConnecte = isset($_SESSION['user']['idJoueur']) ? (int)$_SESSION['user']['idJoueur'] : 0;
$estAdmin = isset($_SESSION['user']['estAdmin']) && (int)$_SESSION['user']['estAdmin'] === 1;

if ($idItem <= 0) {
    $error = "Item invalide.";
} else {
    try {

        /*
            ACTIONS COMMENTAIRES
            - Admin : peut supprimer n'importe quel commentaire
            - Joueur : peut modifier ou supprimer seulement son propre commentaire
        */
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

            $action = $_POST['action'];
            $idJoueurCommentaire = isset($_POST['idJoueur']) ? (int)$_POST['idJoueur'] : 0;

            if ($action === 'delete_comment') {

                if ($estAdmin || $idJoueurConnecte === $idJoueurCommentaire) {

                    $sqlDelete = "
                        DELETE FROM Evaluations
                        WHERE idJoueur = ?
                        AND idItem = ?
                    ";

                    $stmtDelete = $pdo->prepare($sqlDelete);
                    $stmtDelete->execute([$idJoueurCommentaire, $idItem]);

                    $messageAction = "Commentaire supprimé.";
                } else {
                    $messageAction = "Vous n'avez pas le droit de supprimer ce commentaire.";
                }
            }

            if ($action === 'update_comment') {

                if ($idJoueurConnecte === $idJoueurCommentaire) {

                    $nbEtoiles = isset($_POST['nbEtoiles']) ? (int)$_POST['nbEtoiles'] : 0;
                    $commentaire = isset($_POST['eCommentaire']) ? trim($_POST['eCommentaire']) : "";

                    if ($nbEtoiles < 1 || $nbEtoiles > 5) {
                        $messageAction = "Le nombre d'étoiles doit être entre 1 et 5.";
                    } else {
                        $sqlUpdate = "
                            UPDATE Evaluations
                            SET nbEtoiles = ?,
                                eCommentaire = ?
                            WHERE idJoueur = ?
                            AND idItem = ?
                        ";

                        $stmtUpdate = $pdo->prepare($sqlUpdate);
                        $stmtUpdate->execute([
                            $nbEtoiles,
                            $commentaire,
                            $idJoueurConnecte,
                            $idItem
                        ]);

                        $messageAction = "Commentaire modifié.";
                    }

                } else {
                    $messageAction = "Vous n'avez pas le droit de modifier ce commentaire.";
                }
            }
        }

        /*
            CHARGEMENT ITEM
        */
        $sql = "
            SELECT 
                i.idItem,
                i.nom,
                i.quantiteStock,
                i.prix,
                i.photo,
                i.typeItem,

                a.efficacite,
                a.genre,
                a.description,

                ar.matiere,
                ar.taille,

                p.effet,
                p.duree,

                s.estInstantane,
                s.rarete,
                s.typeSort,

                ts.description AS descriptionTypeSort,
                ts.pDegat,
                ts.pvRetire

            FROM Items i

            LEFT JOIN Armes a
                ON i.idItem = a.idItem

            LEFT JOIN Armures ar
                ON i.idItem = ar.idItem

            LEFT JOIN Potions p
                ON i.idItem = p.idItem

            LEFT JOIN Sorts s
                ON i.idItem = s.idItem

            LEFT JOIN TypeSorts ts
                ON s.typeSort = ts.typeSort

            WHERE i.idItem = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idItem]);

        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            $error = "Item introuvable.";
        }

        /*
            CHARGEMENT COMMENTAIRES
        */
        if ($item) {
            $sqlEval = "
                SELECT
                    e.idJoueur,
                    e.nbEtoiles,
                    e.eCommentaire,
                    j.alias
                FROM Evaluations e
                INNER JOIN Joueurs j
                    ON e.idJoueur = j.idJoueur
                WHERE e.idItem = ?
                ORDER BY j.alias
            ";

            $stmtEval = $pdo->prepare($sqlEval);
            $stmtEval->execute([$idItem]);

            $evaluations = $stmtEval->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        $error = "Erreur lors du chargement.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Détails Item</title>
<link rel="stylesheet" href="public/css/style.css">
</head>

<body>

<?php include_once "template/header.php"; ?>

<main>

<?php if ($error != ""): ?>

<p><?php echo h($error); ?></p>

<?php else: ?>

<?php if ($messageAction != ""): ?>
<p><?php echo h($messageAction); ?></p>
<?php endif; ?>

<div class="item-card details-card">

<?php if (!empty($item['photo'])): ?>
<img src="<?php echo h($item['photo']); ?>"
alt="<?php echo h($item['nom']); ?>">
<?php endif; ?>

<h2><?php echo h($item['nom']); ?></h2>

<p>Prix : <?php echo (int)$item['prix']; ?> or</p>

<p>Stock : <?php echo (int)$item['quantiteStock']; ?></p>

<?php if ($item['typeItem'] == 'A'): ?>

<p>Type : Arme</p>
<p>Efficacité : <?php echo h($item['efficacite']); ?></p>
<p>Genre : <?php echo h($item['genre']); ?></p>
<p>Description : <?php echo h($item['description']); ?></p>

<?php endif; ?>

<?php if ($item['typeItem'] == 'R'): ?>

<p>Type : Armure</p>
<p>Matière : <?php echo h($item['matiere']); ?></p>
<p>Taille : <?php echo h($item['taille']); ?></p>

<?php endif; ?>

<?php if ($item['typeItem'] == 'P'): ?>

<p>Type : Potion</p>
<p>Effet : <?php echo h($item['effet']); ?></p>
<p>Durée : <?php echo (int)$item['duree']; ?></p>

<?php endif; ?>

<?php if ($item['typeItem'] == 'S'): ?>

<p>Type : Sort</p>

<p>Type de sort :
<?php echo h($item['typeSort']); ?>
</p>

<p>Description :
<?php echo h($item['descriptionTypeSort']); ?>
</p>

<p>Rareté :
<?php echo h($item['rarete']); ?>
</p>

<p>Instantané :
<?php echo ((int)$item['estInstantane'] == 1) ? 'Oui' : 'Non'; ?>
</p>

<p>Dégâts infligés :
<?php echo (int)$item['pDegat']; ?>
</p>

<p>PV retirés au lanceur :
<?php echo (int)$item['pvRetire']; ?>
</p>

<?php endif; ?>

<?php
$peutAjouter = true;
$message = "Ajouter au panier";

if ((int)$item['quantiteStock'] <= 0) {
    $peutAjouter = false;
    $message = "Rupture de stock";
}

if (
    $item['typeItem'] == 'S'
    &&
    (
        !isset($_SESSION['user']['estMage'])
        ||
        (int)$_SESSION['user']['estMage'] !== 1
    )
) {
    $peutAjouter = false;
    $message = "Réservé aux mages";
}
?>

<?php if ($peutAjouter): ?>

<form action="add_to_cart.php" method="post">
<input type="hidden"
name="idItem"
value="<?php echo (int)$item['idItem']; ?>">

<button class="btn-add">
Ajouter au panier
</button>
</form>

<?php else: ?>

<button class="btn-add disabled" disabled>
<?php echo h($message); ?>
</button>

<?php endif; ?>

</div>

<section class="evaluations-section">

<h3>Commentaires des joueurs</h3>

<?php if (empty($evaluations)): ?>

<p>Aucun commentaire pour cet item.</p>

<?php else: ?>

<?php foreach ($evaluations as $eval): ?>

<div class="evaluation-card">

    <div class="evaluation-header">
        <h4><?php echo h($eval['alias']); ?></h4>

        <?php
        $commentaireDuJoueurConnecte = $idJoueurConnecte === (int)$eval['idJoueur'];
        $peutModifier = $commentaireDuJoueurConnecte;
        $peutSupprimer = $estAdmin || $commentaireDuJoueurConnecte;
        ?>

        <?php if ($peutModifier || $peutSupprimer): ?>
            <details class="comment-menu">
                <summary>⋮</summary>

                <div class="comment-menu-content">

                    <?php if ($peutModifier): ?>
                        <button type="button" onclick="document.getElementById('edit-<?php echo (int)$eval['idJoueur']; ?>').style.display='block'">
                            Modifier
                        </button>
                    <?php endif; ?>

                    <?php if ($peutSupprimer): ?>
                        <form method="post" action="details.php?id=<?php echo (int)$idItem; ?>">
                            <input type="hidden" name="action" value="delete_comment">
                            <input type="hidden" name="idJoueur" value="<?php echo (int)$eval['idJoueur']; ?>">

                            <button type="submit">
                                Supprimer
                            </button>
                        </form>
                    <?php endif; ?>

                </div>
            </details>
        <?php endif; ?>
    </div>

    <p>
        <?php
        $etoiles = (int)$eval['nbEtoiles'];

        for ($i = 1; $i <= $etoiles; $i++) {
            echo "⭐";
        }
        ?>
    </p>

    <p><?php echo h($eval['eCommentaire']); ?></p>

    <?php if ($peutModifier): ?>
        <form 
            id="edit-<?php echo (int)$eval['idJoueur']; ?>"
            class="edit-comment-form"
            method="post"
            action="details.php?id=<?php echo (int)$idItem; ?>"
            style="display:none;"
        >
            <input type="hidden" name="action" value="update_comment">
            <input type="hidden" name="idJoueur" value="<?php echo (int)$eval['idJoueur']; ?>">

            <label>Étoiles :</label>

            <select name="nbEtoiles">
                <option value="1" <?php if ((int)$eval['nbEtoiles'] == 1) echo "selected"; ?>>1</option>
                <option value="2" <?php if ((int)$eval['nbEtoiles'] == 2) echo "selected"; ?>>2</option>
                <option value="3" <?php if ((int)$eval['nbEtoiles'] == 3) echo "selected"; ?>>3</option>
                <option value="4" <?php if ((int)$eval['nbEtoiles'] == 4) echo "selected"; ?>>4</option>
                <option value="5" <?php if ((int)$eval['nbEtoiles'] == 5) echo "selected"; ?>>5</option>
            </select>

            <br><br>

            <textarea name="eCommentaire" maxlength="45"><?php echo h($eval['eCommentaire']); ?></textarea>

            <br><br>

            <button type="submit">
                Enregistrer
            </button>
        </form>
    <?php endif; ?>

</div>

<?php endforeach; ?>

<?php endif; ?>

</section>

<?php endif; ?>

</main>

<?php include_once "template/footer.php"; ?>

</body>
</html>