<header>
  <div class="header-row">
    <div class="title-block">
      <h1>Marché Darquest</h1>
      <h2>Notre bibliothèque des objets magiques et puissants</h2>
    </div>

    <div class="top-actions">
      <a id="cartBtn" class="icon-btn" href="#cart">🛒</a>

      <?php if (isset($_SESSION['joueur_id'])): ?>
        <div class="user-info">
          <div class="user-info-top">
            <span class="user-name">Bonjour, <?php echo h($_SESSION['joueur_alias']); ?></span>
            <span class="user-role">
              <?php echo !empty($_SESSION['joueur_estMage']) ? 'Est mage' : 'Pas mage'; ?>
            </span>
          </div>

          <div class="user-wallet">
            <div class="wallet-item gold">
              <span class="wallet-emoji">🪙</span>
              <span class="wallet-label">Gold</span>
              <span class="wallet-value"><?php echo (int)$_SESSION['joueur_or']; ?></span>
            </div>

            <div class="wallet-item silver">
              <span class="wallet-emoji">🥈</span>
              <span class="wallet-label">Argent</span>
              <span class="wallet-value"><?php echo (int)$_SESSION['joueur_argent']; ?></span>
            </div>

            <div class="wallet-item bronze">
              <span class="wallet-emoji">🥉</span>
              <span class="wallet-label">Bronze</span>
              <span class="wallet-value"><?php echo (int)$_SESSION['joueur_bronze']; ?></span>
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
      <li><a href="#">Inventaire</a></li>
      <li><a href="#">Vendre</a></li>
      <li><a href="#">Enigma</a></li>
      <li><a href="#">Profil</a></li>
      <?php if (!empty($_SESSION['joueur_estAdmin']) && (int)$_SESSION['joueur_estAdmin'] === 1): ?>
    <a href="admin.php">Admin</a>
<?php endif; ?>
    </ul>
  </nav>
</header>
