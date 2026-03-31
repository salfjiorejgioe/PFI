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
        $result = acheter_panier($pdo);
        if ($result !== true) {
            $_SESSION['panier_message'] = $result;
        }
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
            padding: 20px;
            margin: 0 10%;
            border-radius: 12px;
            text-align: center;
        }

        /* Total */
        #cart-total {}

        /* Grille des items */
        .panier-items {
            display: grid;
            gap: 20px;
            margin: 20px 10%;
        }

        /* Bulle (item) */
        .panier-item-grid {
            padding: 16px;
            border-radius: 16px;
            background: rgba(0, 0, 0, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.14);
            transition: transform 0.2s ease, background 0.2s ease, border 0.2s ease;
        }

        .panier-item-grid * {
            color: white;
            text-decoration: none;
        }


        .panier-item-grid:hover {
            transform: translateY(-4px);
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.22);
        }

        /* Image */
        .panier-item-grid img {
            width: 100%;
            height: 180px;
            object-fit: contain;
            display: block;
            margin-bottom: 12px;
            border-radius: 14px;
            padding: 8px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.10);
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

        .cart-btn {
            padding: 14px 24px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 24px;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            color: #fff;
            margin: 0 8px;
            flex: 1;
            text-align: center;
            display: inline-block;
        }

        .cart-btn.acheter {
            background: linear-gradient(135deg, #f6d26a, #f59e0b);
            box-shadow: 0 6px 20px rgba(246, 210, 106, 0.4);
        }

        .cart-btn.acheter:hover {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 8px 24px rgba(246, 210, 106, 0.5);
            transform: translateY(-2px);
        }

        .cart-btn.vider {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        .cart-btn.vider:hover {
            background: linear-gradient(135deg, #b91c1c, #991b1b);
            box-shadow: 0 8px 24px rgba(239, 68, 68, 0.5);
            transform: translateY(-2px);
        }

        .panier-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 12px;
            margin: 15px 0;
            border-radius: 8px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php include_once 'template/header.php'; ?>


    <main>
        <div class="panier-principal">
            <h1>Mon Panier</h1>
            <div id="cart-total"></div>
            <?php
            if (isset($_SESSION['panier_message'])) {
                echo '<div class="panier-message">' . htmlspecialchars($_SESSION['panier_message']) . '</div>';
                unset($_SESSION['panier_message']);
            }
            ?>
            <form method="post">
                <div class="boutonPanier">
                    <input type="submit" name="action" value="Acheter" class="cart-btn acheter">
                    <input type="submit" name="action" value="Vider panier" class="cart-btn vider">
                </div>
            </form>
        </div>
        <div class="panier-items">
            <?php afficher_panier($pdo); ?>
        </div>
    </main>

    <?php include_once 'template/footer.php'; ?>
</body>

</html>