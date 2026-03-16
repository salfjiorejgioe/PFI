<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="public/css/style.css">
  <title>Marché Darquest</title>
</head>

<body>

<header>
  <div class="top-actions">
    <a id="cartBtn" href="#cart">🛒</a>

    <?php if (isset($_SESSION['joueur_id'])): ?>
        <span class="user-info">
            Bonjour, <?php echo htmlspecialchars($_SESSION['joueur_alias']); ?> |
            Solde :
            <?php echo (int)$_SESSION['joueur_or']; ?> Or,
            <?php echo (int)$_SESSION['joueur_argent']; ?> Argent,
            <?php echo (int)$_SESSION['joueur_bronze']; ?> Bronze
        </span>
        <a class="login-btn" href="logout.php">Déconnexion</a>
    <?php else: ?>
        <a class="login-btn" href="login.php">Connexion</a>
        <a class="login-btn" href="signup.php">Création</a>
    <?php endif; ?>
  </div>

  <h1>Marché Darquest</h1>
  <h2>Notre bibliothèque des objets magiques et puissants</h2>

  <nav>
    <ul>
      <li><a href="index.php">Accueil</a></li>
      <li><a href="#">Inventaire</a></li>
      <li><a href="#">Vendre</a></li>
      <li><a href="#">Enigma</a></li>
      <li><a href="#">Profil</a></li>
      <li><a href="admin.php">Admin</a></li>
    </ul>
  </nav>

  <section class="filtres">
    <input type="text" placeholder="Rechercher...">

    <label><input type="checkbox"> Potions</label>
    <label><input type="checkbox"> Armures</label>
    <label><input type="checkbox"> Armes</label>
    <label><input type="checkbox"> Sorts</label>
  </section>
</header>

<main>

  <section>
    <h3>Conversion de l'unité</h3>
    <table id="conversion-monnaie">
      <tr>
        <td>1 Or = 10 Argent</td>
        <td>1 Argent = 10 Bronze</td>
        <td>1 Bronze = La Base</td>
      </tr>
    </table>
  </section>
  
</main>

<aside id="cart">
  <div class="cart-head">
    <h4>Panier</h4>
    <a class="cart-close" href="#">✕</a>
  </div>

  <div class="cart-items">
    <?php include "panier.php"?>

    <p>Le Panier est Vide</p>
  </div>
</aside>

</body>
</html>
