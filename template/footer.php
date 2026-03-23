<footer class="darquest-footer">
  <div class="footer-container">

    <!-- Logo / titre -->
    <div class="footer-section">
      <h2>⚔️ Darquest</h2>
      <p>Le marché mystique des aventuriers.</p>
    </div>

    <!-- Navigation -->
    <div class="footer-section">
      <h3>Navigation</h3>
      <ul>
        <li><a href="index.php">Marché</a></li>
        <li><a href="#">Inventaire</a></li>
        <li><a href="#">Profil</a></li>
        <li><a href="#">Quêtes</a></li>
      </ul>
    </div>

    <!-- Compte -->
    <div class="footer-section">
      <h3>Compte</h3>
      <ul>
        <?php if (isset($_SESSION['user'])): ?>
          <li><a href="logout.php">Déconnexion</a></li>
        <?php else: ?>
          <li><a href="login.php">Connexion</a></li>
          <li><a href="signup.php">Créer un compte</a></li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Infos -->
    <div class="footer-section">
      <h3>Infos</h3>
      <p>Projet Darquest © 2026</p>
      <p>420-KBD - Marché Mystique</p>
    </div>

  </div>

  <div class="footer-bottom">
    <p>✨ Bonne Magasinage ✨</p>
  </div>
</footer>