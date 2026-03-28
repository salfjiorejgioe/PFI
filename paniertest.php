<?php 
session_start();

?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="public/css/style.css">
        <title>paniertest.</title>
        <style>
            #panier-principal{
                margin-left: 10%;
                margin-right: 10%;
                display:grid;
                align-items: ;
                border: 50px red solid;
            }
            #panier-items-grid{
                display: grid;
                width:20%;
                height:400px;
                border: 50px red solid;
                flex-wrap: wrap;
                grid-template-columns: 30% auto;
            }
            
        </style>
    </head>
    <body>
        <?php //include_once 'template/header.php' ?>
        
        
        <main>
            <div class="panier-principal">
                <h4>Panier</h4>
                <div id="cart-total"></div>
                <form method="post">
                    <input type="submit" name="action" value="Acheter">
                    <input type="submit" name="action" value="Vider">
                </form>
                <a class="cart-close" href="index.php">sortir</a>
            </div>
            <div class="panier-items">
                <?php include "panier.php"; ?>
            </div>
        </main>
    </body>
</html>