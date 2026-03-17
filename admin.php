<?php
session_start();
require_once 'db.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user']) || $_SESSION['user']['estAdmin'] != 1) {
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


// ajouter un item

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_item') {

    $nom = trim($_POST['nom']);
    $quantiteStock = (int) $_POST['quantiteStock'];
    $prix = (int) $_POST['prix'];
    $photo = trim($_POST['photo']);
    $typeItem = $_POST['typeItem'];
    $estDisponible = isset($_POST['estDisponible']) ? 1 : 0;

    // Validations simples
    if ($nom == "") {
        $error = "Le nom est obligatoire.";
    } elseif ($quantiteStock < 0 || $prix < 0) {
        $error = "Le stock et le prix doivent être positifs.";
    } elseif ($typeItem != 'A' && $typeItem != 'R' && $typeItem != 'P' && $typeItem != 'S') {
        $error = "Type d'item invalide.";
    } else {
        try {
            $pdo->beginTransaction();

            // Ajouter dans Items
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

            // Si c'est une arme
            if ($typeItem == 'A') {
                $description = trim($_POST['arme_description']);
                $genre = trim($_POST['arme_genre']);
                $efficacite = trim($_POST['arme_efficacite']);

                $sqlArme = "INSERT INTO Armes (idItem, efficacite, genre, description)
                            VALUES (?, ?, ?, ?)";
                $stmtArme = $pdo->prepare($sqlArme);
                $stmtArme->execute([$idItem, $efficacite, $genre, $description]);
            }

            // Si c'est une armure
            if ($typeItem == 'R') {
                $matiere = trim($_POST['armure_matiere']);
                $taille = trim($_POST['armure_taille']);

                $sqlArmure = "INSERT INTO Armures (idItem, matiere, taille)
                              VALUES (?, ?, ?)";
                $stmtArmure = $pdo->prepare($sqlArmure);
                $stmtArmure->execute([$idItem, $matiere, $taille]);
            }

            // Si c'est une potion
            if ($typeItem == 'P') {
                $effet = trim($_POST['potion_effet']);
                $duree = (int) $_POST['potion_duree'];

                $sqlPotion = "INSERT INTO Potions (idItem, effet, duree)
                              VALUES (?, ?, ?)";
                $stmtPotion = $pdo->prepare($sqlPotion);
                $stmtPotion->execute([$idItem, $effet, $duree]);
            }

            // Si c'est un sort
            if ($typeItem == 'S') {
                $typeSort = $_POST['sort_typeSort'];
                $estInstantane = $_POST['sort_estInstantane'];
                $retirePV = (int) $_POST['sort_retirePV'];

                $sqlSort = "INSERT INTO Sorts (idItem, estInstantane, retirePV, typeSort)
                            VALUES (?, ?, ?, ?)";
                $stmtSort = $pdo->prepare($sqlSort);
                $stmtSort->execute([$idItem, $estInstantane, $retirePV, $typeSort]);
            }

            $pdo->commit();
            $message = "Item ajouté avec succès.";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur lors de l'ajout : " . $e->getMessage();
        }
    }
}

// rendre indisponible un item

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'disable_item') {
    $idItem = (int) $_POST['idItem'];

    if ($idItem > 0) {
        try {
            $sql = "UPDATE Items SET estDisponible = 0 WHERE idItem = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idItem]);
            $message = "Item retiré de la vente avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur lors de la désactivation : " . $e->getMessage();
        }
    }
}


// Rendre disponible pour la vente un item qui était retiré

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enable_item') {
    $idItem = (int) $_POST['idItem'];

    if ($idItem > 0) {
        try {
            $sql = "UPDATE Items SET estDisponible = 1 WHERE idItem = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idItem]);
            $message = "Item remis en vente avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur lors de la réactivation : " . $e->getMessage();
        }
    }
}


// charger les items pour les afficher dans la table

try {
    $sql = "SELECT idItem, nom, quantiteStock, prix, photo, typeItem, estDisponible
            FROM Items
            ORDER BY idItem DESC";
    $stmt = $pdo->query($sql);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $items = [];
    $error = "Erreur lors du chargement des items : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion des items</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>

<body>
    <div class="admin-container">

        <div class="admin-card">
            <h1 class="admin-title">Panneau Admin</h1>
            <p>Gestion des items du Marché Mystique</p>
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
                    </select>

                    <select name="sort_estInstantane">
                        <option value="">Instantané ?</option>
                        <option value="1">Oui</option>
                        <option value="0">Non</option>
                    </select>

                    <input type="number" name="sort_retirePV" placeholder="PV retirés" min="0">
                </div>

                <button type="submit">Ajouter l'item</button>
            </form>
        </div>

        <div class="admin-card">
            <h2>Liste des items</h2>

            <?php if (empty($items)): ?>
                <p>Aucun item trouvé.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Stock</th>
                            <th>Prix</th>
                            <th>Disponible</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= $item['idItem'] ?></td>
                                <td>
                                    <?php if (!empty($item['photo'])): ?>
                                        <img class="item-thumb" src="<?= h($item['photo']) ?>" alt="<?= h($item['nom']) ?>">
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?= h($item['nom']) ?></td>
                                <td><?= h($item['typeItem']) ?></td>
                                <td><?= $item['quantiteStock'] ?></td>
                                <td><?= $item['prix'] ?></td>
                                <td>
                                    <?= $item['estDisponible'] == 1 ? 'Oui' : 'Non' ?>
                                </td>
                                <td>
                                    <?php if ($item['estDisponible'] == 1): ?>
                                        <form method="post">
                                            <input type="hidden" name="action" value="disable_item">
                                            <input type="hidden" name="idItem" value="<?= $item['idItem'] ?>">
                                            <button type="submit">Retirer de la vente</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post">
                                            <input type="hidden" name="action" value="enable_item">
                                            <input type="hidden" name="idItem" value="<?= $item['idItem'] ?>">
                                            <button type="submit">Remettre en vente</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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

        typeItem.addEventListener("change", afficherBonsChamps);
        afficherBonsChamps();
    </script>
</body>

</html>