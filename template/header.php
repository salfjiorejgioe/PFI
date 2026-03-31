<?php
$user = $_SESSION['user'] ?? null;
?>
<header>
  <div class="header-row">
    <div class="title-block">
      <h1>⚔️ Marché Darquest</h1>
      <h2>Notre bibliothèque des objets magiques et puissants</h2>
    </div>

    <div class="top-actions">
      <a id="cartBtn" class="icon-btn" href="#cart">🛒</a>

      <?php if (!empty($user)): ?>
        <div class="user-info">
          <div class="user-info-top">
            <span class="user-name">Bonjour, <?php echo h($user['alias']); ?></span>
            <span class="user-role">
              <?php echo !empty($user['estMage']) ? 'Est mage' : 'Pas mage'; ?>
            </span>
          </div>

          <div class="user-wallet">
            <div class="wallet-item gold">
              <span class="wallet-emoji">🥇</span>
              <span class="wallet-label">Gold</span>
              <span class="wallet-value"><?php echo (int)$user['or']; ?></span>
            </div>

            <div class="wallet-item silver">
              <span class="wallet-emoji">🥈</span>
              <span class="wallet-label">Argent</span>
              <span class="wallet-value"><?php echo (int)$user['argent']; ?></span>
            </div>

            <div class="wallet-item bronze">
              <span class="wallet-emoji">🥉</span>
              <span class="wallet-label">Bronze</span>
              <span class="wallet-value"><?php echo (int)$user['bronze']; ?></span>
            </div>
          </div>
        </div>

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
      <li><a href="#">Enigma</a></li>
      <li><a href="#">Profil</a></li>
      <li><a href="paniertest.php">Panier</a></li>
      <?php if (!empty($user) && !empty($user['estAdmin']) && (int)$user['estAdmin'] === 1): ?>
        <li><a href="admin.php">Admin</a></li>
        <li><a href="gerer.php">Gérer</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>
