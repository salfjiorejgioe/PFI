<?php
session_start();
require_once 'db.php';

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
try {
    $stmt = $pdo->query("SELECT idItem, nom, quantiteStock, prix, photo, typeItem
                         FROM Items WHERE idItem = :idItem");
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $items = [];
    $error = "Erreur lors du chargement des items : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/css/style.css">
    <title>Details</title>
</head>

<body>
    <?php include_once "template/header.php" ?>

    <div class="items-grid">
        <div class="item-card">
            <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
            <h3><?php echo h($item['nom']); ?></h3>
            <p>Prix : <?php echo (int) $item['prix']; ?></p>
            <p>Stock : <?php echo (int) $item['quantiteStock']; ?></p>
        </div>
    </div>

</body>

</html>