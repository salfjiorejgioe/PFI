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

            $message = "Item ajouté avec succès.";
        } catch (PDOException $e) {
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
        .action-btn {
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .admin-form button {
            background: #2563eb;
            color: white;
        }

        .disable-btn {
            background: #dc2626;
            color: white;
        }

        .enable-btn {
            background: #16a34a;
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
            vertical-align: middle;
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            font-size: 0.8rem;
            color: white;
            display: inline-block;
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

        .status-on {
            color: #166534;
            font-weight: 600;
        }

        .status-off {
            color: #991b1b;
            font-weight: 600;
        }
    </style>
</head>
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