<?php
$user = $_SESSION['user'] ?? null;
?>

<header>

<style>
.profile-icon-btn {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  border: 3px solid #c9a44c;
  background: rgba(0, 0, 0, 0.6);
  padding: 3px;
  cursor: pointer;
  overflow: hidden;
  flex-shrink: 0;

  box-shadow: 0 0 10px rgba(255, 216, 107, 0.4);
  transition: all 0.2s ease;
}

.profile-icon-btn:hover {
  transform: scale(1.1);
  border-color: #ffd86b;
  box-shadow: 0 0 18px rgba(255, 216, 107, 0.8);
}

.profile-icon-btn img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.icon-popup {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  z-index: 9999;
  justify-content: center;
  align-items: center;
}

.icon-popup-content {
  background: rgba(25, 18, 12, 0.96);
  border: 2px solid #c9a44c;
  border-radius: 14px;
  padding: 20px;
  text-align: center;
  color: white;
}

.icon-choice-form {
  display: grid;
  grid-template-columns: repeat(3, 70px);
  gap: 12px;
  margin: 15px 0;
}

.icon-choice-btn {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  border: 2px solid #c9a44c;
  background: rgba(0, 0, 0, 0.5);
  padding: 3px;
  cursor: pointer;
  overflow: hidden;
}

.icon-choice-btn img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
  display: block;
}

.icon-choice-btn:hover {
  transform: scale(1.08);
  border-color: #ffd86b;
}

.close-icon-popup {
  padding: 8px 16px;
  border-radius: 8px;
  border: 1px solid #c9a44c;
  background: #1b1208;
  color: #ffd86b;
  cursor: pointer;
}
</style>

  <div class="header-row">
    <div class="title-block">
      <img src="public/images/DarquestLogo.png" alt="Darquest Logo" style="max-width: 500px">
    </div>

    <div class="top-actions">

      <?php if (!empty($user)): ?>
        <div class="user-info">
          <div class="user-info-top">

            <button type="button" class="profile-icon-btn" id="openIconPopup">
              <img 
                src="image-site/<?php echo h($user['iconeProfil'] ?? 'icone1.png'); ?>" 
                alt="Icône profil"
              >
            </button>

            <span class="user-name">Bonjour, <?php echo h($user['alias']); ?></span>

            <span class="user-role"
            style="
                    display: flex;
                    justify-content: center;
                    align-items: center;
                  "
            >
              <label for="healthbar" style="position: relative; left: 18px;">
                <?php
                $hp = (int)($user['pointsVie']);
                if($hp >50)
                  echo '<img src="image-site/1Pixel_heart_overflow.png" alt="confident" style="height: 35px;">';
                elseif($hp >=35 && $hp <=50)
                  echo '<img src="image-site/2Pixel_heart.png" alt="omagah" style="height: 35px;">';
                elseif($hp >15 && $hp < 35)
                  echo '<img src="image-site/3Pixel_heart_mid.png" alt="hmmm" style="height: 35px;">';
                elseif($hp <=15)
                  echo '<img src="image-site/4Pixel_heart_damaged.png" alt="o nooooo" style="height: 35px;">';
                ?>
              </label>
              <progress id="healthbar" 
              class="
              <?php
              $hp = (int)($user['pointsVie']);
              if($hp <=15)
                echo 'low_hp';
              if($hp >15 && $hp < 35)
                echo 'mid_hp';
              elseif($hp >=35 && $hp <=50)
                echo 'high_hp';
              elseif($hp >50)
                echo 'overflow_hp';
              ?>
              " 
              value="<?php echo (int)($user['pointsVie']);?>" 
              max="50"></progress>
              <label for="healthbar" style="position: relative; top: 10px; right: 50px; text-shadow: 0px 0px 5px black;"><?php echo (int)($user['pointsVie']);?> / 50</label>
            </span>
            <span class="user-role">
                <?php echo !empty($user['estMage']) ? 'Est mage' : 'Pas mage'; ?>
            </span>
          </div>

          <div class="user-wallet">
            <div class="wallet-item gold">
              <span class="wallet-emoji">
                <img src="image-site/gold_coin.png" alt="Gold Coin" class="coin-icon">
              </span>
       
              <span class="wallet-value"><?php echo (int)$user['or']; ?></span>
            </div>

            <div class="wallet-item silver">
              <span class="wallet-emoji">
                <img src="image-site/argent_coin.png" alt="Silver Coin" class="coin-icon">
              </span>
      
              <span class="wallet-value"><?php echo (int)$user['argent']; ?></span>
            </div>

            <div class="wallet-item bronze">
              <span class="wallet-emoji">
                <img src="image-site/bronze_coin.png" alt="Bronze Coin" class="coin-icon">
              </span>
            
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
      <li><a href="enigme.php">Enigma</a></li>
      <li><a href="#">Profil</a></li>
      <li><a href="paniertest.php">Panier</a></li>
      <?php if (!empty($user) && !empty($user['estAdmin']) && (int)$user['estAdmin'] === 1): ?>
        <li><a href="admin.php">Admin</a></li>
        <li><a href="gerer.php">Gérer</a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <?php if (!empty($user)): ?>
    <div id="iconPopup" class="icon-popup">
      <div class="icon-popup-content">
        <h3>Choisir une icône</h3>

        <form method="post" action="changer_icone.php" class="icon-choice-form">
          <?php for ($i = 1; $i <= 6; $i++): ?>
            <button type="submit" name="icone" value="icone<?php echo $i; ?>.png" class="icon-choice-btn">
              <img src="image-site/icone<?php echo $i; ?>.png" alt="Icône <?php echo $i; ?>">
            </button>
          <?php endfor; ?>
        </form>

        <button type="button" class="close-icon-popup" id="closeIconPopup">
          Fermer
        </button>
      </div>
    </div>
  <?php endif; ?>

</header>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const openBtn = document.getElementById("openIconPopup");
  const closeBtn = document.getElementById("closeIconPopup");
  const popup = document.getElementById("iconPopup");

  if (openBtn && popup) {
    openBtn.addEventListener("click", function () {
      popup.style.display = "flex";
    });
  }

  if (closeBtn && popup) {
    closeBtn.addEventListener("click", function () {
      popup.style.display = "none";
    });
  }

  if (popup) {
    popup.addEventListener("click", function (e) {
      if (e.target === popup) {
        popup.style.display = "none";
      }
    });
  }
});
</script>