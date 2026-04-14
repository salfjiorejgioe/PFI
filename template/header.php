<?php
$user = $_SESSION['user'] ?? null;
?>
<header>
  <div class="header-row">
    <div class="title-block">
      <img src="public/images/DarquestLogo.png" alt="Darquest Logo" style="max-width: 500px">
      <!-- <h2>Notre bibliothèque des objets magiques et puissants</h2> -->
    </div>

    <div class="top-actions">

      <?php if (!empty($user)): ?>
        <div class="user-info">
          <div class="user-info-top">
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
      <li><a href="#">Vendre</a></li>
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
