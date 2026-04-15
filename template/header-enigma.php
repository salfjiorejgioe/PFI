<?php
$user = $_SESSION['user'] ?? null;
?>
<header>
  <div class="header-row">
    <div class="title-block">
      <h1>✨Enigma✨</h1>
      <h2>Les quêtes pour les aventuriers sussy-bakas</h2>
    </div>

    <div class="top-actions">

      <?php if (!empty($user)): ?>

        <a class="login-btn" href="logout.php">Déconnexion</a>
      <?php else: ?>
        <a class="login-btn" href="login.php">Connexion</a>
        <a class="login-btn" href="signup.php">Création</a>
      <?php endif; ?>
    </div>
  </div>

  <nav>
    <ul>
      <li><a href="index.php">Accueil</a></li>
      <li><a href="inventaire.php">Inventaire</a></li>
      <li><a href="enigme.php">Enigma</a></li>
      <li><a href="#">Profil</a></li>
      <li><a href="paniertest.php">Panier</a></li>
      <?php if (!empty($user) && !empty($user['estAdmin']) && (int)$user['estAdmin'] === 1): ?>
        <li><a href="admin.php">Admin</a></li>
        <li><a href="gerer.php">Gérer</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>
