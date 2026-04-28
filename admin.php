<?php
session_start();
require_once 'db.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['estAdmin']) || (int) $_SESSION['user']['estAdmin'] !== 1) {
    header('Location: index.php');
    exit;
}

$message = "";
$error = "";

// Fonction pour sécuriser l'affichage
function h($texte)
{
    return htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
}

/* =========================
   AJOUTER LES CATÉGORIES DE BASE
========================= */
try {
    $categoriesBase = [
        ['C', 'Le chaos qui englobe le monde'],
        ['A', 'Les aventures dans le royaume Darquest'],
        ['J', 'Jew 🥸'],
        ['D', 'Les Dragons'],
        ['E', 'Les Elements'],
        ['V', 'Le vide'],
        ['W', 'Les armes']
    ];

    $stmtInsertCategorie = $pdo->prepare("
        INSERT IGNORE INTO Categories (idCategorie, nomCategorie)
        VALUES (?, ?)
    ");

    foreach ($categoriesBase as $cat) {
        $stmtInsertCategorie->execute([$cat[0], $cat[1]]);
    }
} catch (PDOException $e) {
    $error = "Erreur lors de l'initialisation des catégories : " . $e->getMessage();
}


/* =========================
   AJOUTER UN ITEM
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_item') {

    $nom = trim($_POST['nom']);
    $quantiteStock = (int) $_POST['quantiteStock'];
    $prix = (int) $_POST['prix'];
    $photo = trim($_POST['photo']);
    $typeItem = $_POST['typeItem'];
    $estDisponible = isset($_POST['estDisponible']) ? 1 : 0;

    if ($nom == "") {
        $error = "Le nom est obligatoire.";
    } elseif ($quantiteStock < 0 || $prix < 0) {
        $error = "Le stock et le prix doivent être positifs.";
    } elseif ($typeItem != 'A' && $typeItem != 'R' && $typeItem != 'P' && $typeItem != 'S') {
        $error = "Type d'item invalide.";
    } else {
        try {
            $pdo->beginTransaction();

            $sqlItem = "INSERT INTO Items (nom, quantiteStock, prix, photo, typeItem, estDisponible)
                        VALUES (?, ?, ?, ?, ?, ?)";
            $stmtItem = $pdo->prepare($sqlItem);
            $stmtItem->execute([
                $nom,
                $quantiteStock,
                $prix,
                $photo == "" ? null : $photo,
                $typeItem,
                $estDisponible
            ]);

            $idItem = $pdo->lastInsertId();

            if ($typeItem == 'A') {
                $description = trim($_POST['arme_description']);
                $genre = trim($_POST['arme_genre']);
                $efficacite = trim($_POST['arme_efficacite']);

                $sqlArme = "INSERT INTO Armes (idItem, efficacite, genre, description)
                            VALUES (?, ?, ?, ?)";
                $stmtArme = $pdo->prepare($sqlArme);
                $stmtArme->execute([$idItem, $efficacite, $genre, $description]);
            }

            if ($typeItem == 'R') {
                $matiere = trim($_POST['armure_matiere']);
                $taille = trim($_POST['armure_taille']);

                $sqlArmure = "INSERT INTO Armures (idItem, matiere, taille)
                              VALUES (?, ?, ?)";
                $stmtArmure = $pdo->prepare($sqlArmure);
                $stmtArmure->execute([$idItem, $matiere, $taille]);
            }

            if ($typeItem == 'P') {
                $effet = trim($_POST['potion_effet']);
                $duree = (int) $_POST['potion_duree'];

                $sqlPotion = "INSERT INTO Potions (idItem, effet, duree)
                              VALUES (?, ?, ?)";
                $stmtPotion = $pdo->prepare($sqlPotion);
                $stmtPotion->execute([$idItem, $effet, $duree]);
            }

            if ($typeItem == 'S') {
                $typeSort = $_POST['sort_typeSort'];
                $estInstantane = $_POST['sort_estInstantane'];
                $rarete = (int) $_POST['sort_retirePV'];

                $sqlSort = "INSERT INTO Sorts (idItem, estInstantane, rarete, typeSort)
                            VALUES (?, ?, ?, ?)";
                $stmtSort = $pdo->prepare($sqlSort);
                $stmtSort->execute([$idItem, $estInstantane, $rarete, $typeSort]);
            }

            $pdo->commit();
            $message = "Item ajouté avec succès.";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur lors de l'ajout : " . $e->getMessage();
        }
    }
}


/* =========================
   AJOUTER UNE ÉNIGME / QUÊTE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_enigme') {

    $enonce = trim($_POST['enonce']);
    $idCategorie = trim($_POST['idCategorie']);
    $difficulte = trim($_POST['difficulte']);
    $estPigee = isset($_POST['estPigee']) ? 1 : 0;

    $reponse1 = trim($_POST['reponse1']);
    $reponse2 = trim($_POST['reponse2']);
    $reponse3 = trim($_POST['reponse3']);
    $reponse4 = trim($_POST['reponse4']);

    $bonneReponse = isset($_POST['bonneReponse']) ? (int) $_POST['bonneReponse'] : 0;

    $reponses = [];

    if ($reponse1 != "") {
        $reponses[1] = $reponse1;
    }

    if ($reponse2 != "") {
        $reponses[2] = $reponse2;
    }

    if ($reponse3 != "") {
        $reponses[3] = $reponse3;
    }

    if ($reponse4 != "") {
        $reponses[4] = $reponse4;
    }

    if ($enonce == "" || $idCategorie == "" || $difficulte == "") {
        $error = "L'énoncé, la catégorie et la difficulté sont obligatoires.";
    } elseif (count($reponses) < 2) {
        $error = "Il faut au moins 2 réponses.";
    } elseif ($bonneReponse == 0) {
        $error = "Vous devez choisir la bonne réponse.";
    } elseif (!isset($reponses[$bonneReponse])) {
        $error = "La bonne réponse choisie doit avoir un champ rempli.";
    } else {
        try {
            $pdo->beginTransaction();

            $sqlEnigme = "INSERT INTO Enigmes (enonce, idCategorie, difficulte, estPigee)
                          VALUES (?, ?, ?, ?)";

            $stmtEnigme = $pdo->prepare($sqlEnigme);
            $stmtEnigme->execute([$enonce, $idCategorie, $difficulte, $estPigee]);

            $idEnigme = $pdo->lastInsertId();

            $sqlReponse = "INSERT INTO Reponses (estBonneReponse, reponse, idEnigme)
                           VALUES (?, ?, ?)";

            $stmtReponse = $pdo->prepare($sqlReponse);

            foreach ($reponses as $numero => $texteReponse) {
                $estBonne = ($bonneReponse == $numero) ? 1 : 0;
                $stmtReponse->execute([$estBonne, $texteReponse, $idEnigme]);
            }

            $pdo->commit();
            $message = "Énigme ajoutée avec succès.";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur lors de l'ajout de l'énigme : " . $e->getMessage();
        }
    }
}


/* =========================
   CHARGER LES CATÉGORIES
========================= */
try {
    $sqlCategories = "SELECT idCategorie, nomCategorie FROM Categories ORDER BY nomCategorie";
    $stmtCategories = $pdo->query($sqlCategories);
    $categories = $stmtCategories->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    $error = "Erreur lors du chargement des catégories : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion des items et énigmes</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>

<body>
    <?php include_once 'template/header.php'; ?>

    <div class="admin-container">

        <div class="admin-card">
            <h1 class="admin-title">Panneau Admin</h1>
            <p>Gestion des items du Marché Mystique et ajout des quêtes</p>
        </div>

        <?php if ($message != ""): ?>
            <div class="msg-success"><?= h($message) ?></div>
        <?php endif; ?>

        <?php if ($error != ""): ?>
            <div class="msg-error"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="admin-card">
            <h2>Ajouter un item</h2>

            <form method="post" class="admin-form">
                <input type="hidden" name="action" value="add_item">

                <input type="text" name="nom" placeholder="Nom de l'item" required>
                <input type="number" name="quantiteStock" placeholder="Quantité en stock" min="0" required>
                <input type="number" name="prix" placeholder="Prix" min="0" required>
                <input type="text" name="photo" placeholder="Chemin image ex: public/images/mon-item.png">

                <select name="typeItem" id="typeItem" required>
                    <option value="">Choisir un type</option>
                    <option value="A">Arme</option>
                    <option value="R">Armure</option>
                    <option value="P">Potion</option>
                    <option value="S">Sort</option>
                </select>

                <label class="admin-check">
                    <input type="checkbox" name="estDisponible" checked>
                    Disponible
                </label>

                <div id="fields-arme" class="type-fields" style="display:none;">
                    <input type="text" name="arme_description" placeholder="Description de l'arme">
                    <input type="text" name="arme_genre" placeholder="Genre de l'arme">
                    <input type="text" name="arme_efficacite" placeholder="Efficacité">
                </div>

                <div id="fields-armure" class="type-fields" style="display:none;">
                    <input type="text" name="armure_matiere" placeholder="Matière">
                    <input type="text" name="armure_taille" placeholder="Taille">
                </div>

                <div id="fields-potion" class="type-fields" style="display:none;">
                    <input type="text" name="potion_effet" placeholder="Effet">
                    <input type="number" name="potion_duree" placeholder="Durée">
                </div>

                <div id="fields-sort" class="type-fields" style="display:none;">
                    <select name="sort_typeSort">
                        <option value="">Choisir un type de sort</option>
                        <option value="P">P - Attaque Physique</option>
                        <option value="D">D - Défense Physique</option>
                        <option value="Z">Z - Défense Magique</option>
                        <option value="O">O - Ombre</option>
                        <option value="F">F - Feu</option>
                        <option value="N">N - Nature</option>
                        <option value="G">G - Givre</option>
                        <option value="I">I - Invocation</option>
                        <option value="H">H - Heal</option>
                        <option value="C">C - Chaos</option>
                        <option value="T">T - Teleport</option>
                        <option value="W">W - Water</option>
                    </select>

                    <select name="sort_estInstantane">
                        <option value="">Instantané ?</option>
                        <option value="1">Oui</option>
                        <option value="0">Non</option>
                    </select>

                    <input type="number" name="sort_retirePV" placeholder="Rareté" min="1" max="10">
                </div>

                <button type="submit">Ajouter l'item</button>
            </form>
        </div>

        <div class="admin-card">
            <h2>Ajouter une quête / énigme</h2>

            <form method="post" class="admin-form">
                <input type="hidden" name="action" value="add_enigme">

                <textarea name="enonce" placeholder="Énoncé de l'énigme" required></textarea>

                <select name="idCategorie" required>
                    <option value="">Choisir une catégorie</option>
                    <?php foreach ($categories as $categorie): ?>
                        <option value="<?= h($categorie['idCategorie']) ?>">
                            <?= h($categorie['idCategorie'] . ' - ' . $categorie['nomCategorie']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="difficulte" required>
                    <option value="">Choisir une difficulté</option>
                    <option value="F">Facile</option>
                    <option value="M">Moyen</option>
                    <option value="D">Difficile</option>
                </select>



                <input type="text" name="reponse1" id="reponse1" placeholder="Réponse 1" required>
                <input type="text" name="reponse2" id="reponse2" placeholder="Réponse 2" required>
                <input type="text" name="reponse3" id="reponse3" placeholder="Réponse 3">
                <input type="text" name="reponse4" id="reponse4" placeholder="Réponse 4">

                <select name="bonneReponse" id="bonneReponse" required>
                    <option value="">Choisir la bonne réponse</option>
                    <option value="1">Réponse 1</option>
                    <option value="2">Réponse 2</option>
                    <option value="3">Réponse 3</option>
                    <option value="4">Réponse 4</option>
                </select>

                <button type="submit">Ajouter l'énigme</button>
            </form>
        </div>
    </div>

    <script>
        const typeItem = document.getElementById("typeItem");
        const arme = document.getElementById("fields-arme");
        const armure = document.getElementById("fields-armure");
        const potion = document.getElementById("fields-potion");
        const sort = document.getElementById("fields-sort");

        function cacherTousLesChamps() {
            arme.style.display = "none";
            armure.style.display = "none";
            potion.style.display = "none";
            sort.style.display = "none";
        }

        function afficherBonsChamps() {
            cacherTousLesChamps();

            if (typeItem.value === "A") {
                arme.style.display = "block";
            } else if (typeItem.value === "R") {
                armure.style.display = "block";
            } else if (typeItem.value === "P") {
                potion.style.display = "block";
            } else if (typeItem.value === "S") {
                sort.style.display = "block";
            }
        }

        const reponse1 = document.getElementById("reponse1");
        const reponse2 = document.getElementById("reponse2");
        const reponse3 = document.getElementById("reponse3");
        const reponse4 = document.getElementById("reponse4");
        const bonneReponse = document.getElementById("bonneReponse");

        function verifierReponsesRemplies() {
            const champs = [reponse1, reponse2, reponse3, reponse4];

            for (let i = 0; i < champs.length; i++) {
                const option = bonneReponse.querySelector('option[value="' + (i + 1) + '"]');

                if (champs[i].value.trim() === "") {
                    option.disabled = true;

                    if (bonneReponse.value == (i + 1)) {
                        bonneReponse.value = "";
                    }
                } else {
                    option.disabled = false;
                }
            }
        }

        reponse1.addEventListener("input", verifierReponsesRemplies);
        reponse2.addEventListener("input", verifierReponsesRemplies);
        reponse3.addEventListener("input", verifierReponsesRemplies);
        reponse4.addEventListener("input", verifierReponsesRemplies);

        verifierReponsesRemplies();

        typeItem.addEventListener("change", afficherBonsChamps);
        afficherBonsChamps();
    </script>
</body>

</html>