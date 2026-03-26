<footer>

  <style>
 
    .footer-follow {
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
      z-index: 200;
    }

    .footer-follow-container {
      width: 100%;
      max-width: 1200px;

      display: flex;
      align-items: center;
      justify-content: space-between;
      position: relative;
    }

    .footer-follow-left {
      color: #9ca3af;
      font-style: italic;
    }

    .footer-follow-center {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      color: #a78bfa;
      letter-spacing: 0.5px;
    }

    .footer-follow-right {
      display: flex;
      gap: 8px;
    }

    .footer-follow-right a {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      padding: 5px 10px;
      border-radius: 6px;
      color: #e5e7eb;
      text-decoration: none;
      font-size: 12px;
      transition: 0.2s;
    }

    .footer-follow-right a:hover {
      background: rgba(167,139,250,0.2);
      border-color: rgba(167,139,250,0.4);
      color: #c4b5fd;
    }

    .site-footer-main {
      background: rgba(5, 8, 15, 0.9);
      color: #9ca3af;
      font-size: 13px;
      margin-top: 40px;
      padding: 16px 20px 60px;
    }

    .footer-main-container {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      gap: 24px;
      flex-wrap: wrap;
    }

    .footer-main-left {
      color: #9ca3af;
    }

    .footer-logo {
      font-weight: 600;
      color: #a78bfa;
    }

    .footer-tagline {
      margin: 0;
      font-size: 12px;
      font-style: italic;
    }

    .footer-team-title {
      margin: 0 0 4px;
      font-size: 13px;
      color: #e5e7eb;
    }

    .footer-team-names {
      margin: 0;
      font-size: 12px;
    }
  </style>



  <div class="footer-follow">
    <div class="footer-follow-container">


      <div class="footer-follow-left">
        ✨ Bon magasinage
      </div>

      <div class="footer-follow-center">
        ⚔️ Marché Darquest ©
      </div>

      <div class="footer-follow-right">
        <a href="contact.php">Contact</a>
        <?php if (isset($_SESSION['user'])): ?>
          <a href="logout.php">Déconnexion</a>
        <?php endif; ?>
      </div>

    </div>
  </div>

</footer>
