<?php
session_start();
require_once 'db.php';


/*if (!isset($_SESSION['user']) || (int)($_SESSION['user']['estAdmin'] ?? 0) !== 1) {
    header('Location: index.php');
    exit;
}*/

$message = '';
$error = '';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_item') {
    $nom = trim($_POST['nom'] ?? '');
    $quantiteStock = (int)($_POST['quantiteStock'] ?? 0);
    $prix = (int)($_POST['prix'] ?? 0);
    $photo = trim($_POST['photo'] ?? '');
    $typeItem = trim($_POST['typeItem'] ?? '');
    $estDisponible = isset($_POST['estDisponible']) ? 1 : 0;

    if ($nom === '') {
        $error = "Le nom est obligatoire.";
    } elseif (!in_array($typeItem, ['A', 'R', 'P', 'S'], true)) {
        $error = "Le type d'item est invalide.";
    } elseif ($quantiteStock < 0 || $prix < 0) {
        $error = "Le stock et le prix doivent être positifs.";
    } else {
        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO Items (nom, quantiteStock, prix, photo, typeItem, estDisponible)
                    VALUES (:nom, :quantiteStock, :prix, :photo, :typeItem, :estDisponible)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom' => $nom,
                ':quantiteStock' => $quantiteStock,
                ':prix' => $prix,
                ':photo' => $photo !== '' ? $photo : null,
                ':typeItem' => $typeItem,
                ':estDisponible' => $estDisponible
            ]);

            $idItem = (int)$pdo->lastInsertId();

            if ($typeItem === 'A') {
                $description = trim($_POST['arme_description'] ?? '');
                $genre = trim($_POST['arme_genre'] ?? '');
                $efficacite = trim($_POST['arme_efficacite'] ?? '');

                $stmt = $pdo->prepare("
                    INSERT INTO Armes (idItem, efficacite, genre, description)
                    VALUES (:idItem, :efficacite, :genre, :description)
                ");
                $stmt->execute([
                    ':idItem' => $idItem,
                    ':efficacite' => $efficacite,
                    ':genre' => $genre,
                    ':description' => $description
                ]);
            }

            if ($typeItem === 'R') {
                $matiere = trim($_POST['armure_matiere'] ?? '');
                $taille = trim($_POST['armure_taille'] ?? '');

                $stmt = $pdo->prepare("
                    INSERT INTO Armures (idItem, matiere, taille)
                    VALUES (:idItem, :matiere, :taille)
                ");
                $stmt->execute([
                    ':idItem' => $idItem,
                    ':matiere' => $matiere,
                    ':taille' => $taille
                ]);
            }

            if ($typeItem === 'P') {
                $effet = trim($_POST['potion_effet'] ?? '');
                $duree = (int)($_POST['potion_duree'] ?? 0);

                $stmt = $pdo->prepare("
                    INSERT INTO Potions (idItem, effet, duree)
                    VALUES (:idItem, :effet, :duree)
                ");
                $stmt->execute([
                    ':idItem' => $idItem,
                    ':effet' => $effet,
                    ':duree' => $duree
                ]);
            }

            if ($typeItem === 'S') {
                $typeSort = trim($_POST['sort_typeSort'] ?? '');
                $estInstantane = ($_POST['sort_estInstantane'] ?? '') === '1' ? 1 : 0;
                $retirePV = (int)($_POST['sort_retirePV'] ?? 0);

                $stmt = $pdo->prepare("
                    INSERT INTO Sorts (idItem, estInstantane, retirePV, typeSort)
                    VALUES (:idItem, :estInstantane, :retirePV, :typeSort)
                ");
                $stmt->execute([
                    ':idItem' => $idItem,
                    ':estInstantane' => $estInstantane,
                    ':retirePV' => $retirePV,
                    ':typeSort' => $typeSort
                ]);
            }

            $pdo->commit();
            $message = "Item ajouté avec succès.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur lors de l'ajout : " . $e->getMessage();
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'disable_item') {
    $idItem = (int)($_POST['idItem'] ?? 0);

    if ($idItem > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE Items SET estDisponible = 0 WHERE idItem = :idItem");
            $stmt->execute([':idItem' => $idItem]);
            $message = "Item retiré de la vente avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur lors de la désactivation : " . $e->getMessage();
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enable_item') {
    $idItem = (int)($_POST['idItem'] ?? 0);

    if ($idItem > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE Items SET estDisponible = 1 WHERE idItem = :idItem");
            $stmt->execute([':idItem' => $idItem]);
            $message = "Item remis en vente avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur lors de la réactivation : " . $e->getMessage();
        }
    }
}


try {
    $stmt = $pdo->query("SELECT idItem, nom, quantiteStock, prix, photo, typeItem, estDisponible
                         FROM Items
                         ORDER BY idItem DESC");
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('typeItem');
    const armeFields = document.getElementById('fields-arme');
    const armureFields = document.getElementById('fields-armure');
    const potionFields = document.getElementById('fields-potion');
    const sortFields = document.getElementById('fields-sort');

    function hideAllFields() {
        armeFields.style.display = 'none';
        armureFields.style.display = 'none';
        potionFields.style.display = 'none';
        sortFields.style.display = 'none';
    }

    function updateFields() {
        hideAllFields();

        switch (typeSelect.value) {
            case 'A':
                armeFields.style.display = 'grid';
                break;
            case 'R':
                armureFields.style.display = 'grid';
                break;
            case 'P':
                potionFields.style.display = 'grid';
                break;
            case 'S':
                sortFields.style.display = 'grid';
                break;
        }
    }

    typeSelect.addEventListener('change', updateFields);
    updateFields();
});
</script>
<body>
    <div class="admin-container">
        <div class="admin-card">
            <h1 class="admin-title">Panneau Admin</h1>
            <p>Gestion des items du Marché Mystique</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="msg-success"><?= h($message) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
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

    <!-- Champs spécifiques ARME -->
    <div id="fields-arme" class="type-fields" style="display:none;">
        <input type="text" name="arme_description" placeholder="Description de l'arme">
        <input type="text" name="arme_genre" placeholder="Genre de l'arme">
        <input type="text" name="arme_efficacite" placeholder="Efficacité">
    </div>

    <!-- Champs spécifiques ARMURE -->
    <div id="fields-armure" class="type-fields" style="display:none;">
        <input type="text" name="armure_matiere" placeholder="Matière">
        <input type="text" name="armure_taille" placeholder="Taille">
    </div>

    <!-- Champs spécifiques POTION -->
    <div id="fields-potion" class="type-fields" style="display:none;">
        <input type="text" name="potion_effet" placeholder="Effet">
        <input type="number" name="potion_duree" placeholder="Durée">
    </div>

    <!-- Champs spécifiques SORT -->
    <div id="fields-sort" class="type-fields" style="display:none;">
        <select name="sort_typeSort">
            <option value="">Choisir un type de sort</option>
            <option value="P">P - Attaque Physique</option>
            <option value="D">D - Defense Physique</option>
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
                                <td><?= (int)$item['idItem'] ?></td>
                                <td>
                                    <?php if (!empty($item['photo'])): ?>
                                        <img class="item-thumb" src="<?= h($item['photo']) ?>" alt="<?= h($item['nom']) ?>">
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?= h($item['nom']) ?></td>
                                <td>
                                    <span class="badge <?= h($item['typeItem']) ?>">
                                        <?= h($item['typeItem']) ?>
                                    </span>
                                </td>
                                <td><?= (int)$item['quantiteStock'] ?></td>
                                <td><?= (int)$item['prix'] ?></td>
                                <td class="<?= (int)$item['estDisponible'] === 1 ? 'status-on' : 'status-off' ?>">
                                    <?= (int)$item['estDisponible'] === 1 ? 'Oui' : 'Non' ?>
                                </td>
                                <td>
                                    <?php if ((int)$item['estDisponible'] === 1): ?>
                                        <form method="post">
                                            <input type="hidden" name="action" value="disable_item">
                                            <input type="hidden" name="idItem" value="<?= (int)$item['idItem'] ?>">
                                            <button type="submit" class="action-btn disable-btn">
                                                Retirer de la vente
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post">
                                            <input type="hidden" name="action" value="enable_item">
                                            <input type="hidden" name="idItem" value="<?= (int)$item['idItem'] ?>">
                                            <button type="submit" class="action-btn enable-btn">
                                                Remettre en vente
                                            </button>
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
</body>
</html>