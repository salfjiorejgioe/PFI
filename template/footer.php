<footer class="simple-footer">

  <style>
    .simple-footer {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;

      background: rgba(5, 8, 15, 0.7);
      backdrop-filter: blur(8px);

      display: flex;
      align-items: center;
      justify-content: center;

      padding: 10px 20px;
      border-top: 1px solid rgba(167,139,250,0.15);

      font-size: 13px;
      color: #9ca3af;
      z-index: 100;
    }

    .footer-container {
      width: 100%;
      max-width: 1200px;

      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    /* gauche */
    .footer-left {
      color: #9ca3af;
      font-style: italic;
    }

    /* centre */
    .footer-center {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      color: #a78bfa;
      letter-spacing: 0.5px;
    }

    /* droite */
    .footer-right {
      display: flex;
      gap: 8px;
    }

    .footer-right a {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      padding: 5px 10px;
      border-radius: 6px;
      color: #e5e7eb;
      text-decoration: none;
      font-size: 12px;
      transition: 0.2s;
    }

    .footer-right a:hover {
      background: rgba(167,139,250,0.2);
      border-color: rgba(167,139,250,0.4);
      color: #c4b5fd;
    }
  </style>

  <div class="footer-container">

    <!-- gauche -->
    <div class="footer-left">
      ✨ Bon magasinage
    </div>

    <!-- centre -->
    <div class="footer-center">
      ⚔️ Marché Darquest ©
    </div>

    <!-- droite -->
    <div class="footer-right">
      <a href="contact.php">Contact</a>

      <?php if (isset($_SESSION['user'])): ?>
        <a href="logout.php">Déconnexion</a>
      <?php endif; ?>
    </div>

  </div>

</footer>