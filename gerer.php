<?php
require_once 'session_config.php';
require_once 'db.php';
require_once 'helpers.php';

if (
    !isset($_SESSION['user']) ||
    !isset($_SESSION['user']['estAdmin']) ||
    (int) $_SESSION['user']['estAdmin'] !== 1
) {
    header('Location: index.php');
    exit;
}

$message = "";
$error = "";
$itemChoisi = null;
$items = [];
$joueurs = [];

function h($texte)
{
    return htmlspecialchars($texte ?? '', ENT_QUOTES, 'UTF-8');
}

/* =========================
   INTERVENTION JOUEUR
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'intervention_joueur') {
    $idJoueurIntervention = (int)($_POST['idJoueur'] ?? 0);

    if ($idJoueurIntervention <= 0) {
        $error = "Veuillez choisir un joueur.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT idJoueur, alias, nbInterventionsAdmin
                FROM Joueurs
                WHERE idJoueur = ?
                LIMIT 1
            ");
            $stmt->execute([$idJoueurIntervention]);
            $joueurChoisi = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$joueurChoisi) {
                throw new Exception("Joueur introuvable.");
            }

            $nbInterventions = (int)($joueurChoisi['nbInterventionsAdmin'] ?? 0);

            if ($nbInterventions >= 3) {
                throw new Exception("Ce joueur a déjà reçu 3 interventions.");
            }

            if ($nbInterventions === 0) {
                $stmt = $pdo->prepare("
                    UPDATE Joueurs
                    SET gold = gold + 100,
                        nbInterventionsAdmin = nbInterventionsAdmin + 1
                    WHERE idJoueur = ?
                ");
                $bonus = "100 gold";
            } elseif ($nbInterventions === 1) {
                $stmt = $pdo->prepare("
                    UPDATE Joueurs
                    SET argent = argent + 100,
                        nbInterventionsAdmin = nbInterventionsAdmin + 1
                    WHERE idJoueur = ?
                ");
                $bonus = "100 argent";
            } else {
                $stmt = $pdo->prepare("
                    UPDATE Joueurs
                    SET bronze = bronze + 100,
                        nbInterventionsAdmin = nbInterventionsAdmin + 1
                    WHERE idJoueur = ?
                ");
                $bonus = "100 bronze";
            }

            $stmt->execute([$idJoueurIntervention]);
            $pdo->commit();

            $message = "Intervention réussie pour " . $joueurChoisi['alias'] . " : +" . $bonus . ".";
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $e->getMessage();
        }
    }
}

/* =========================
   CHARGER JOUEURS
========================= */
try {
    $stmtJoueurs = $pdo->query("
        SELECT idJoueur, alias, gold, argent, bronze, nbInterventionsAdmin
        FROM Joueurs
        ORDER BY alias
    ");
    $joueurs = $stmtJoueurs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $joueurs = [];
    $error = "Erreur lors du chargement des joueurs.";
}

/* =========================
   CHARGER ITEMS
========================= */
try {
    $stmt = $pdo->query("SELECT idItem, nom FROM Items ORDER BY nom");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors du chargement des items.";
}

$idItem = isset($_GET['idItem']) ? (int) $_GET['idItem'] : 0;

/* =========================
   CHARGER ITEM CHOISI
========================= */
if ($idItem > 0) {
    try {
        $sql = "
            SELECT 
                i.idItem,
                i.nom,
                i.quantiteStock,
                i.prix,
                i.photo,
                i.typeItem,
                i.estDisponible,

                a.efficacite,
                a.genre,
                a.description,

                ar.matiere,
                ar.taille,

                p.effet,
                p.duree,

                s.estInstantane,
                s.rarete,
                s.typeSort
            FROM Items i
            LEFT JOIN Armes a ON i.idItem = a.idItem
            LEFT JOIN Armures ar ON i.idItem = ar.idItem
            LEFT JOIN Potions p ON i.idItem = p.idItem
            LEFT JOIN Sorts s ON i.idItem = s.idItem
            WHERE i.idItem = ?
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idItem]);
        $itemChoisi = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$itemChoisi) {
            $error = "Item introuvable.";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors du chargement de l'item.";
    }
}

/* =========================
   MODIFIER ITEM
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_item'])) {
    $idItemPost = (int) ($_POST['idItem'] ?? 0);

    $nom = trim($_POST['nom'] ?? '');
    $prix = (int) ($_POST['prix'] ?? 0);
    $quantiteStock = (int) ($_POST['quantiteStock'] ?? 0);
    $photo = trim($_POST['photo'] ?? '');
    $estDisponible = isset($_POST['estDisponible']) ? 1 : 0;
    $typeItem = $_POST['typeItem'] ?? '';

    if ($idItemPost <= 0) {
        $error = "Item invalide.";
    } elseif ($nom === '') {
        $error = "Le nom est obligatoire.";
    } elseif ($prix < 0 || $quantiteStock < 0) {
        $error = "Le prix et le stock doivent être positifs.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE Items
                SET nom = ?, quantiteStock = ?, prix = ?, photo = ?, estDisponible = ?
                WHERE idItem = ?
            ");
            $stmt->execute([
                $nom,
                $quantiteStock,
                $prix,
                $photo === '' ? null : $photo,
                $estDisponible,
                $idItemPost
            ]);

            if ($typeItem === 'A') {
                $description = trim($_POST['description'] ?? '');
                $efficacite = trim($_POST['efficacite'] ?? '');
                $genre = trim($_POST['genre'] ?? '');

                $stmt = $pdo->prepare("
                    UPDATE Armes
                    SET description = ?, efficacite = ?, genre = ?
                    WHERE idItem = ?
                ");
                $stmt->execute([$description, $efficacite, $genre, $idItemPost]);
            }

            if ($typeItem === 'P') {
                $effet = trim($_POST['effet'] ?? '');
                $duree = (int) ($_POST['duree'] ?? 0);

                $stmt = $pdo->prepare("
                    UPDATE Potions
                    SET effet = ?, duree = ?
                    WHERE idItem = ?
                ");
                $stmt->execute([$effet, $duree, $idItemPost]);
            }

            if ($typeItem === 'R') {
                $matiere = trim($_POST['matiere'] ?? '');
                $taille = trim($_POST['taille'] ?? '');

                $stmt = $pdo->prepare("
                    UPDATE Armures
                    SET matiere = ?, taille = ?
                    WHERE idItem = ?
                ");
                $stmt->execute([$matiere, $taille, $idItemPost]);
            }

            if ($typeItem === 'S') {
                $rarete = (int) ($_POST['rarete'] ?? 0);
                $typeSort = trim($_POST['typeSort'] ?? '');
                $estInstantane = isset($_POST['estInstantane']) ? 1 : 0;

                $stmt = $pdo->prepare("
                    UPDATE Sorts
                    SET rarete = ?, typeSort = ?, estInstantane = ?
                    WHERE idItem = ?
                ");
                $stmt->execute([$rarete, $typeSort, $estInstantane, $idItemPost]);
            }

            if ($quantiteStock === 0) {
                $stmt = $pdo->prepare("
                    UPDATE Items
                    SET estDisponible = 0
                    WHERE idItem = ?
                ");
                $stmt->execute([$idItemPost]);
            }

            $pdo->commit();

            header("Location: gerer.php?idItem=" . $idItemPost . "&ok=1");
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Erreur lors de la modification.";
        }
    }
}

if (isset($_GET['ok'])) {
    $message = "Item modifié avec succès.";
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Gérer un item</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>

<body>

<?php include_once "template/header.php"; ?>

<main class="admin-container">
    <div class="admin-card">
        <h1>Gérer un item</h1>

        <?php if ($message !== ""): ?>
            <p class="msg-success"><?php echo h($message); ?></p>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <p class="msg-error"><?php echo h($error); ?></p>
        <?php endif; ?>

        <form method="get" class="admin-form">
            <label for="idItem">Choisir un item</label>
            <select name="idItem" id="idItem" onchange="this.form.submit()">
                <option value="">-- Sélectionner un item --</option>
                <?php foreach ($items as $item): ?>
                    <option value="<?php echo (int) $item['idItem']; ?>" <?php echo ($idItem === (int) $item['idItem']) ? 'selected' : ''; ?>>
                        <?php echo h($item['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="admin-card">
        <h2>Intervention joueur</h2>
        <p>1ère intervention : +100 gold. 2ème : +100 argent. 3ème : +100 bronze.</p>

        <form method="post" class="admin-form">
            <input type="hidden" name="action" value="intervention_joueur">

            <label for="idJoueur">Choisir un joueur</label>
            <select name="idJoueur" id="idJoueur" required>
                <option value="">-- Sélectionner un joueur --</option>

                <?php foreach ($joueurs as $joueur): ?>
                    <?php
                    $nb = (int)($joueur['nbInterventionsAdmin'] ?? 0);
                    $disabled = $nb >= 3 ? 'disabled' : '';
                    ?>
                    <option value="<?php echo (int) $joueur['idJoueur']; ?>" <?php echo $disabled; ?>>
                        <?php echo h($joueur['alias']); ?>
                        — <?php echo $nb; ?>/3
                        — <?php echo (int) $joueur['gold']; ?> or,
                        <?php echo (int) $joueur['argent']; ?> argent,
                        <?php echo (int) $joueur['bronze']; ?> bronze
                        <?php echo $nb >= 3 ? ' — MAX' : ''; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Faire une intervention</button>
        </form>
    </div>

    <?php if ($itemChoisi): ?>
        <div class="admin-card">
            <h2>Modifier : <?php echo h($itemChoisi['nom']); ?></h2>

            <form method="post" class="admin-form">
                <input type="hidden" name="modifier_item" value="1">
                <input type="hidden" name="idItem" value="<?php echo (int) $itemChoisi['idItem']; ?>">
                <input type="hidden" name="typeItem" value="<?php echo h($itemChoisi['typeItem']); ?>">

                <label>Nom</label>
                <input type="text" name="nom" value="<?php echo h($itemChoisi['nom']); ?>" required>

                <label>Prix</label>
                <input type="number" name="prix" value="<?php echo (int) $itemChoisi['prix']; ?>" min="0" required>

                <label>Stock</label>
                <input type="number" name="quantiteStock" value="<?php echo (int) $itemChoisi['quantiteStock']; ?>" min="0" required>

                <label>Image</label>
                <input type="text" name="photo" value="<?php echo h($itemChoisi['photo']); ?>">

                <label class="admin-check">
                    <input type="checkbox" name="estDisponible" <?php echo ((int) $itemChoisi['estDisponible'] === 1) ? 'checked' : ''; ?>>
                    Disponible
                </label>

                <?php if ($itemChoisi['typeItem'] === 'A'): ?>
                    <h3>Infos Arme</h3>

                    <label>Description</label>
                    <input type="text" name="description" value="<?php echo h($itemChoisi['description']); ?>">

                    <label>Efficacité</label>
                    <input type="text" name="efficacite" value="<?php echo h($itemChoisi['efficacite']); ?>">

                    <label>Genre</label>
                    <input type="text" name="genre" value="<?php echo h($itemChoisi['genre']); ?>">
                <?php endif; ?>

                <?php if ($itemChoisi['typeItem'] === 'P'): ?>
                    <h3>Infos Potion</h3>

                    <label>Effet</label>
                    <input type="text" name="effet" value="<?php echo h($itemChoisi['effet']); ?>">

                    <label>Durée</label>
                    <input type="number" name="duree" value="<?php echo (int) $itemChoisi['duree']; ?>" min="0">
                <?php endif; ?>

                <?php if ($itemChoisi['typeItem'] === 'R'): ?>
                    <h3>Infos Armure</h3>

                    <label>Matière</label>
                    <input type="text" name="matiere" value="<?php echo h($itemChoisi['matiere']); ?>">

                    <label>Taille</label>
                    <input type="text" name="taille" value="<?php echo h($itemChoisi['taille']); ?>">
                <?php endif; ?>

                <?php if ($itemChoisi['typeItem'] === 'S'): ?>
                    <h3>Infos Sort</h3>

                    <label>Rareté</label>
                    <input type="number" name="rarete" value="<?php echo (int) $itemChoisi['rarete']; ?>" min="0">

                    <label>Type sort</label>
                    <input type="text" name="typeSort" value="<?php echo h($itemChoisi['typeSort']); ?>">

                    <label class="admin-check">
                        <input type="checkbox" name="estInstantane" <?php echo ((int) $itemChoisi['estInstantane'] === 1) ? 'checked' : ''; ?>>
                        Instantané
                    </label>
                <?php endif; ?>

                <button type="submit">Enregistrer les modifications</button>
            </form>
        </div>
    <?php endif; ?>
</main>

<?php include_once "template/footer.php"; ?>

</body>
</html>