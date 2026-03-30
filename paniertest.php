<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';
require_once 'panier_de_paniertest.php';


if (isset($_SESSION['user']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'Ajouter') {
        ajouter_objet_panier($pdo, $_POST['idItem']);
    } else if ($_POST['action'] === 'Retirer') {
        retirer_objet_panier($pdo, $_POST['idItem']);
    } else if ($_POST['action'] === 'Acheter') {
        acheter_panier($pdo);
    } else if ($_POST['action'] === 'Vider panier') {
        vider_panier($pdo, $_SESSION['user']['idJoueur']);
    } else if ($_POST['action'] === 'Supprimer du panier') {
        supprimer_objet_du_panier($pdo, $_POST['idItem']);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/css/style.css">
    <title>paniertest.</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        main {
            padding: 20px;
        }

        /* Bloc principal */
        .panier-principal {
            background: black;
            padding: 20px;
            margin: 0 10%;
            border-radius: 12px;
        }

        .panier-principal>* {
            opacity: 1;
        }

        /* Total */
        #cart-total {
            font-weight: bold;
            margin-bottom: 10px;
        }

        /* Grille des items */
        .panier-items {
            display: grid;
            gap: 20px;
            margin: 20px 10%;
        }

        /* Bulle (item) */
        .panier-item-grid {
            background: black;
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .panier-item-grid * {
            color:white;
            text-decoration: none;
        }


        .panier-item-grid:hover {
            transform: scale(1.03);
        }

        /* Image */
        .panier-item-grid img {
            width: auto;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
        }

        /* Texte */
        .panier-item-grid h3 {
            margin: 10px 0 5px;
        }

        .panier-item-grid p {
            margin: 5px 0;
        }

        /* Boutons */
        .panier-item-grid form {
            margin-top: 10px;
        }

        .panier-item-grid input[type="submit"] {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background-color: white;
            color: black;
        }
        .panier-item-grid input[type="submit"]:hover {
            opacity: 0.8;
        }
        .panier-item-grid input[value="Supprimer du panier"] {
            background-color: #dc3545;
        }
    </style>
</head>

<body>
    <?php include_once 'template/header.php'; ?>


    <main>
        <div class="panier-principal">
            <h4>Panier</h4>
            <div id="cart-total"></div>
            <form method="post">
                <input type="submit" name="action" value="Acheter">
                <input type="submit" name="action" value="Vider panier">
            </form>
        </div>
        <div class="panier-items">
            <?php afficher_panier($pdo); ?>
        </div>
    </main>
</body>

</html>