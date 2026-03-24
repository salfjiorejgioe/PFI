<?php 
session_start();


include 'panier.php'?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="public/css/style.css">
        <title>paniertest.</title>
    </head>
    <body>
        <?php include_once 'template/header.php' ?>
        <main>
            <div class="cart-head">
            <h4>Panier</h4>
            <div id="cart-total"></div>
      <form method="post">
        <input type="submit" name="action" value="Acheter">
        <input type="submit" value="Acheter">
      </form>
      <a class="cart-close" href="#">✕</a>
    </div>
    <div class="cart-items">
      <?php include "panier.php"; ?>
      <p>Le Panier est Vide</p>
    </div>

        </main>
        
    </body>
</html>