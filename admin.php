<?php
session_start();

/*
  Hypothèses:
  - tu stockes l'utilisateur connecté dans $_SESSION['user']
  - et $_SESSION['user']['estAdmin'] vaut 1 pour un admin
  - ta BD s'appelle dbdarquest2
  - ta table Items contient :
      idItem, nom, quantiteStock, prix, photo, typeItem, estDisponible
*/

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['estAdmin']) || (int)$_SESSION['user']['estAdmin'] !== 1) {
    header("Location: index.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "dbdarquest2");
if ($conn->connect_error) {
    die("Erreur connexion BD : " . $conn->connect_error);
}

$message = "";
$error = "";

/* =========================
   AJOUT ITEM
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_item') {
    $nom = trim($_POST['nom'] ?? '');
    $quantiteStock = (int)($_POST['quantiteStock'] ?? 0);
    $prix = (int)($_POST['prix'] ?? 0);
    $photo = trim($_POST['photo'] ?? '');
    $typeItem = trim($_POST['typeItem'] ?? '');
    $estDisponible = isset($_POST['estDisponible']) ? 1 : 0;

    if ($nom === '' || !in_array($typeItem, ['A', 'R', 'P', 'S'], true)) {
        $error = "Veuillez remplir correctement les champs.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO Items (nom, quantiteStock, prix, photo, typeItem, estDisponible)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("siissi", $nom, $quantiteStock, $prix, $photo, $typeItem, $estDisponible);

        if ($stmt->execute()) {
            $message = "Item ajouté avec succès.";
        } else {
            $error = "Erreur lors de l'ajout : " . $stmt->error;
        }

        $stmt->close();
    }
}

/* =========================
   SUPPRESSION ITEM
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_item') {
    $idItem = (int)($_POST['idItem'] ?? 0);

    if ($idItem > 0) {
        // Option 1 : suppression réelle
        // Si ton item est référencé ailleurs, ça peut échouer à cause des FK
        $stmt = $conn->prepare("DELETE FROM Items WHERE idItem = ?");
        $stmt->bind_param("i", $idItem);

        if ($stmt->execute()) {
            $message = "Item supprimé avec succès.";
        } else {
            $error = "Impossible de supprimer cet item. Il est peut-être utilisé ailleurs. " . $stmt->error;
        }

        $stmt->close();
    }
}

/* =========================
   LISTE ITEMS
========================= */
$result = $conn->query("SELECT idItem, nom, quantiteStock, prix, photo, typeItem, estDisponible FROM Items ORDER BY idItem DESC");
$items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion des items</title>
    <link rel="stylesheet" href="public/css/styles.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }

        .admin-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }

        .admin-title {
            margin-bottom: 1rem;
        }

        .msg-success {
            background: #dcfce7;
            color: #166534;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .msg-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .admin-form {
            display: grid;
            gap: 1rem;
        }

        .admin-form input,
        .admin-form select {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            width: 100%;
        }

        .admin-form button,
        .delete-btn {
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .admin-form button {
            background: #2563eb;
            color: white;
        }

        .delete-btn {
            background: #dc2626;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            font-size: 0.8rem;
            color: white;
        }

        .badge.A { background: #2563eb; }
        .badge.R { background: #7c3aed; }
        .badge.P { background: #ea580c; }
        .badge.S { background: #16a34a; }

        img.item-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            background: #f3f4f6;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-card">
            <h1 class="admin-title">Panneau Admin</h1>
            <p>Gestion des items du Marché Mystique</p>
        </div>

        <?php if ($message): ?>
            <div class="msg-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="msg-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="admin-card">
            <h2>Ajouter un item</h2>

            <form method="post" class="admin-form">
                <input type="hidden" name="action" value="add_item">

                <input type="text" name="nom" placeholder="Nom de l'item" required>

                <input type="number" name="quantiteStock" placeholder="Quantité en stock" min="0" required>

                <input type="number" name="prix" placeholder="Prix (en bronze ou unité choisie)" min="0" required>

                <input type="text" name="photo" placeholder="Chemin de l'image ex: public/images/nom.png">

                <select name="typeItem" required>
                    <option value="">Choisir un type</option>
                    <option value="A">Arme</option>
                    <option value="R">Armure</option>
                    <option value="P">Potion</option>
                    <option value="S">Sort</option>
                </select>

                <label>
                    <input type="checkbox" name="estDisponible" checked>
                    Disponible
                </label>

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
                                        <img class="item-thumb" src="<?= htmlspecialchars($item['photo']) ?>" alt="<?= htmlspecialchars($item['nom']) ?>">
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($item['nom']) ?></td>
                                <td>
                                    <span class="badge <?= htmlspecialchars($item['typeItem']) ?>">
                                        <?= htmlspecialchars($item['typeItem']) ?>
                                    </span>
                                </td>
                                <td><?= (int)$item['quantiteStock'] ?></td>
                                <td><?= (int)$item['prix'] ?></td>
                                <td><?= (int)$item['estDisponible'] === 1 ? 'Oui' : 'Non' ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Supprimer cet item ?');">
                                        <input type="hidden" name="action" value="delete_item">
                                        <input type="hidden" name="idItem" value="<?= (int)$item['idItem'] ?>">
                                        <button type="submit" class="delete-btn">Supprimer</button>
                                    </form>
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
<?php
$conn->close();
?>