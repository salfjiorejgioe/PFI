<?php
$user = $_SESSION['user'] ?? null;
?>

<style>
  .enigme-header {
    position: fixed;
    top: 18px;
    left: 18px;
    z-index: 1200;
  }

  .enigme-menu-wrap {
    position: relative;
    display: inline-block;
  }

  .enigme-menu-btn {
    width: 58px;
    height: 58px;
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .enigme-menu-btn img {
    width: 42px;
    height: 42px;
    object-fit: contain;
    filter:
      brightness(0)
      saturate(100%)
      invert(66%)
      sepia(39%)
      saturate(1516%)
      hue-rotate(225deg)
      brightness(104%)
      contrast(101%)
      drop-shadow(0 0 6px rgba(195, 140, 255, 0.55))
      drop-shadow(0 0 14px rgba(195, 140, 255, 0.35));
    transition: transform 0.18s ease, filter 0.18s ease;
  }

  .enigme-menu-btn:hover img {
    transform: scale(1.08);
    filter:
      brightness(0)
      saturate(100%)
      invert(66%)
      sepia(39%)
      saturate(1516%)
      hue-rotate(225deg)
      brightness(115%)
      contrast(105%)
      drop-shadow(0 0 10px rgba(215, 160, 255, 0.75))
      drop-shadow(0 0 22px rgba(195, 140, 255, 0.50));
  }

  .enigme-menu-btn.active img {
    transform: rotate(90deg) scale(1.06);
  }

  .enigme-nav {
    position: absolute;
    top: 68px;
    left: 0;
    min-width: 220px;
    display: flex;
    flex-direction: column;
    gap: 10px;

    opacity: 0;
    transform: translateY(-10px) scale(0.98);
    pointer-events: none;
    transition: opacity 0.22s ease, transform 0.22s ease;
  }

  .enigme-nav.open {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
  }

  .enigme-nav a {
    color: #e9ccff;
    text-decoration: none;
    font-weight: 800;
    font-size: 1rem;
    letter-spacing: 0.2px;
    text-shadow: 0 0 10px rgba(187, 130, 255, 0.18);
    padding: 4px 0;
    transform: translateX(-8px);
    opacity: 0;
  }

  .enigme-nav.open a {
    animation: enigmeLinkIn 0.28s ease forwards;
  }

  .enigme-nav.open a:nth-child(1) { animation-delay: 0.03s; }
  .enigme-nav.open a:nth-child(2) { animation-delay: 0.07s; }
  .enigme-nav.open a:nth-child(3) { animation-delay: 0.11s; }
  .enigme-nav.open a:nth-child(4) { animation-delay: 0.15s; }
  .enigme-nav.open a:nth-child(5) { animation-delay: 0.19s; }
  .enigme-nav.open a:nth-child(6) { animation-delay: 0.23s; }
  .enigme-nav.open a:nth-child(7) { animation-delay: 0.27s; }

  .enigme-nav a:hover {
    color: #ff7ed0;
    text-shadow:
      0 0 8px rgba(255, 126, 208, 0.45),
      0 0 16px rgba(195, 140, 255, 0.28);
    transform: translateX(4px);
    transition: 0.18s ease;
  }

  @keyframes enigmeLinkIn {
    from {
      opacity: 0;
      transform: translateX(-10px);
    }
    to {
      opacity: 1;
      transform: translateX(0);
    }
  }

  @media (max-width: 768px) {
    .enigme-header {
      top: 12px;
      left: 12px;
    }

    .enigme-menu-btn {
      width: 52px;
      height: 52px;
    }

    .enigme-menu-btn img {
      width: 36px;
      height: 36px;
    }

    .enigme-nav {
      top: 60px;
      min-width: 180px;
    }

    .enigme-nav a {
      font-size: 0.95rem;
    }
  }
</style>

<header class="enigme-header">
  <div class="enigme-menu-wrap">
    <button class="enigme-menu-btn" id="enigmeMenuBtn" type="button" aria-label="Ouvrir le menu">
      <img src="image-site/liste.png" alt="Menu">
    </button>

    <nav class="enigme-nav" id="enigmeNav">
      <a href="index.php">• Accueil</a>
      <a href="inventaire.php">• Inventaire</a>
      <a href="enigme.php">• Enigma</a>
      <a href="paniertest.php">• Panier</a>
      <a href="#">• Profil</a>

      <?php if (!empty($user) && !empty($user['estAdmin']) && (int)$user['estAdmin'] === 1): ?>
        <a href="admin.php">• Admin</a>
        <a href="gerer.php">• Gérer</a>
      <?php endif; ?>

      <?php if (!empty($user)): ?>
        <a href="logout.php">• Déconnexion</a>
      <?php else: ?>
        <a href="login.php">• Connexion</a>
        <a href="signup.php">• Création</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<script>
  const enigmeMenuBtn = document.getElementById('enigmeMenuBtn');
  const enigmeNav = document.getElementById('enigmeNav');

  enigmeMenuBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    enigmeNav.classList.toggle('open');
    enigmeMenuBtn.classList.toggle('active');
  });

  document.addEventListener('click', function (e) {
    if (!enigmeNav.contains(e.target) && !enigmeMenuBtn.contains(e.target)) {
      enigmeNav.classList.remove('open');
      enigmeMenuBtn.classList.remove('active');
    }
  });
</script>