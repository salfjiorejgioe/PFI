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

  <section>
    <h3>Potions</h3>
    <table id="potions">
<<<<<<< HEAD
        <!-- include tableau items (potions) -->
=======
      <tr>
        <td><img src="./public/images/minor-healing-potion.png" alt="Minor Healing Potion"></td>
        <td>Potion de soins mineurs</td>
        <td>5 Argent</td>
        <td>Soigne mineure au joueur pendant 5 secondes</td>
        <td>Efficacité : 1</td>
        <td>Stock : 10</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/greater-healing-potion.png"></td>
        <td>Potion de soins supérieurs</td>
        <td>10 Argent</td>
        <td>Regenere les points de vies du personnage pendant 15 secondes</td>
        <td>Efficacité : 3</td>
        <td>Stock : 15</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/mana-potion.png"></td>
        <td>Potion d'armure</td>
        <td>15 Argent</td>
        <td>Augmente la défense du personnage pendant 30 secondes</td>
        <td>Efficacité : 5</td>
        <td>Stock : 13</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/speed-potion.png"></td>
        <td>Potion de vitesse</td>
        <td>10 Or</td>
        <td>Augmente la vitesse pendant 15 secondes</td>
        <td>Efficacité : 10</td>
        <td>Stock : 10</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/strength-potion.png"></td>
        <td>Potion de force</td>
        <td>15 Or</td>
        <td>Augmenter la force d'attaque du personnage pendant 20 secondes</td>
        <td>Efficacité : 5</td>
        <td>Stock : 10</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/stamina-tonic.png"></td>
        <td>Potion d'endurance</td>
        <td>15 Or</td>
        <td>Augmentent les points de vies maximales du joueur pendant 20 secondes</td>
        <td>Efficacité : 8</td>
        <td>Stock : 15</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/invisibility-potion.png" alt="Invisibility Potion"></td>
        <td>Potion d'invisibilité</td>
        <td>25 Or</td>
        <td>Deviens invisible pendant 30 secondes</td>
        <td>Efficacité : 20</td>
        <td>Stock : 5</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
>>>>>>> a56d23438adac2f1b068a5b436b17c9a9bb41e4c
    </table>
  </section>

  <section>
    <h3>Armures</h3>
    <table id="armures">
      <tr>
        <td><img src="./public/images/cloth-robe.png" alt="Cloth robe"></td>
        <td>Robe tissu</td>
        <td>10 Bronze</td>
        <td>Fabriqué du tissu de coton doux</td>
        <td>Efficacité : 1</td>
        <td>Stock : 5</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/leather-armor.png" alt="Leather armor"></td>
        <td>Armure de cuir</td>
        <td>20 Argent</td>
        <td>Juste une armure en cuir, mieux que rien</td>
        <td>Efficacité : 5</td>
        <td>Stock : 3</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/armure-de-guerrier.png" alt="Armure de Guerrier"></td>
        <td>Armure de Guerrier</td>
        <td>5 Or</td>
        <td>Fabriqué avec des plaques dur</td>
        <td>Efficacité : 5</td>
        <td>Stock : 2</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/ethereal-robes.png" alt="Ethereal Robes"></td>
        <td>Robes spectrales</td>
        <td>40 Or 9 Argent 5 Bronze</td>
        <td>Fabriqué d'une étoffe spirituelle protégeant des attaques magiques</td>
        <td>Efficacité : 8</td>
        <td>Stock : 2</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/dragon-scale-plate.png" alt="Dragonscale Plating"></td>
        <td>Armure en écailles de dragon</td>
        <td>50 Or</td>
        <td>Fabriqué des écailles de l'ore de dragonstone</td>
        <td>Efficacité : 10</td>
        <td>Stock : 1</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/armure-plaque-jade.png" alt="Armure Plaque Jade"></td>
        <td>Armure Plaque Jade</td>
        <td>50 Or</td>
        <td>Incrustée des pierres de jade</td>
        <td>Efficacité : 12</td>
        <td>Stock : 1</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
    </table>
  </section>

  <section>
    <h3>Armes</h3>
    <table id="armes">
      <tr>
        <td><img src="./public/images/wooden-staff.png" alt="Wooden Staff"></td>
        <td>La moppe bois</td>
        <td>20 Bronze</td>
        <td>Un  bâton en bois pour frapper les ennemis sur la tête</td>
        <td>Efficacité : 2</td>
        <td>Stock : 2</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/iron-sword.png" alt="Iron Sword"></td>
        <td>Épée en fer</td>
        <td>15 Argent</td>
        <td>Une épée en fer pour trancher les cibles. Rien de spécial</td>
        <td>Efficacité : 4</td>
        <td>Stock : 5</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/steel-axe.png" alt="Steel Axe"></td>
        <td>Ache en acier</td>
        <td>20 Argent</td>
        <td>Une hache en acier.. même chose que l'épée mais pour ceux qui préferent les hâches</td>
        <td>Efficacité : 4</td>
        <td>Stock : 3</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/moonlight-dagger.png" alt="Moonlight Dagger"></td>
        <td>Dague de lune</td>
        <td>15 Or</td>
        <td>Une dague incrustée de pierres de lune qui augmentent l'acuité</td>
        <td>Efficacité : 8</td>
        <td>Stock : 4</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/celestial-bow.png" alt="Celestial Bow"></td>
        <td>Arc céleste</td>
        <td>25 Or</td>
        <td>Cet arc n'a pas besoin de flèches, il tire des des éclairs divins</td>
        <td>Efficacité : 6</td>
        <td>Stock : 1</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/thunder-hammer.png" alt="Thunder hammer"></td>
        <td>Marteau de tonnerre</td>
        <td>30 Or</td>
        <td>Un marteau de tonnerre qui évaporise les ennemis tués</td>
        <td>Efficacité : 12</td>
        <td>Stock : 2</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/sword-of-light.png" alt="Sword of Light"></td>
        <td>Lame de lumière</td>
        <td>40 Or</td>
        <td>Non seulement l'acuité permet d'effectuer des tranches devastatrices, mais elle brûle aussi les ennemis touchés</td>
        <td>Efficacité : 10</td>
        <td>Stock : 1</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
    </table>
  </section>

  <section>
    <h3>Sorts</h3>
    <table id="sorts">
      <tr>
        <td><img src="./public/images/apprentice-wand.png" alt="Apprentice Wand"></td>
        <td>Baguette de l'apprenti</td>
        <td>25 Bronze</td>
        <td>Lance un sort de base qui enleve 2 points de vie à l'ennemi</td>
        <td>Efficacité : 1</td>
        <td>Stock : 10</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/tome-of-telepathy.png" alt="Tome of Telepathy"></td>
        <td>Tome of Telepathie</td>
        <td>2 Argent 10 Bronze</td>
        <td>Enlève 0 points de vies, mais permet de lire les pensées des ennemis</td>
        <td>Efficacité : 3</td>
        <td>Stock : 26</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/fireball.png" alt="Fireball"></td>
        <td>Tir de feu</td>
        <td>2 Or</td>
        <td>Enlève 20 points de vies</td>
        <td>Efficacité : 8</td>
        <td>Stock : 5</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/ice-shard.png" alt="Ice Shard"></td>
        <td>Pointe de givre</td>
        <td>2 Or 9 Argent</td>
        <td>Enlève 15 points de vies</td>
        <td>Efficacité : 6</td>
        <td>Stock : 3</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/ghost.png" alt="Ghost"></td>
        <td>Fantôme</td>
        <td>8 Or</td>
        <td>Enlève 0 points de vies. Devient insensible aux dégâts pendant 10 secondes</td>
        <td>Efficacité : 12</td>
        <td>Stock : 1</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/lightning-bolt.png" alt="Lightning Bolt"></td>
        <td>éclair de foudre</td>
        <td>8 Or 5 Argent</td>
        <td>Enlève 25 points de vies</td>
        <td>Efficacité : 10</td>
        <td>Stock : 2</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/soignement.png" alt="Healing Touch"></td>
        <td>Soignement</td>
        <td>10 Or</td>
        <td>Soigne 10 points de vies à un allié</td>
        <td>Efficacité : 5</td>
        <td>Stock : 4</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/death-grip.png" alt="Death grip"></td>
        <td>Poigne de la mort</td>
        <td>15 Or</td>
        <td>Enlève 5 points de vies et paralyse l'ennemi pendant 3 secondes</td>
        <td>Efficacité : 7</td>
        <td>Stock : 3</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/shadow-bolt.png" alt="Shadow Bolt"></td>
        <td>Trait de l'ombre</td>
        <td>150 Or</td>
        <td>Enlève 50 points de vies</td>
        <td>Efficacité : 9999</td>
        <td>Stock : 40</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
      </tr>
      <tr>
        <td><img src="./public/images/Larry.png" alt="Larry"></td>
        <td>Larry</td>
        <td>1000 Or</td>
        <td>Invoquer Larry pour voler les peenars. (Friendly fire)💀🥸🛑</td>
        <td>Efficacité : 10000</td>
        <td>Stock : 1</td>
        <td><button class="btn-add" type="button">Ajouter</button></td>
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
