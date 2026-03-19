<?php
session_start();
require_once 'db.php';

// Fonction pour sécuriser l'affichage
function h($texte) {
    return htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
}

// Aller chercher tous les items disponibles
try {
    $sql = "SELECT idItem, nom, quantiteStock, prix, photo, typeItem, estDisponible
            FROM Items
            WHERE estDisponible = 1
            ORDER BY typeItem, nom";
    $stmt = $pdo->query($sql);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $items = [];
}

// Séparer les items par type
$armes = [];
$armures = [];
$potions = [];
$sorts = [];

foreach ($items as $item) {
    if ($item['typeItem'] == 'A') {
        $armes[] = $item;
    } elseif ($item['typeItem'] == 'R') {
        $armures[] = $item;
    } elseif ($item['typeItem'] == 'P') {
        $potions[] = $item;
    } elseif ($item['typeItem'] == 'S') {
        $sorts[] = $item;
    }
}





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
<?php include_once 'template/header.php' ?>
<main>
<section id="filtres">
    <input id="barreRecherche" type="text" placeholder="Rechercher...">
    <label><input type="checkbox" value="potions"> Potions</label>
    <label><input type="checkbox" value="armures"> Armures</label>
    <label><input type="checkbox" value="armes"> Armes</label>
    <label><input type="checkbox" value="sorts"> Sorts</label>
</section>  
  <section>
    <h3>Conversion de l'unité</h3>
    <table id="conversion-monnaie">
      <tr>
        <td>1 Or = La base</td>
        <td>10 Argent = 1 Or</td>
        <td>10 Bronze = 1 Argent</td>
      </tr>
    </table>
  </section>

  <!-- SECTION ARMES -->
  <section class="section-items">
    <h2>Armes</h2>

    <?php if (empty($armes)): ?>
      <p>Aucune arme disponible.</p>
    <?php else: ?>
      <div class="items-grid">
        <?php foreach ($armes as $item): ?>
          <a class="item-card" href="details.php?id=<?php echo (int)$item['idItem']; ?>">
            <?php if (!empty($item['photo'])): ?>
              <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
            <?php else: ?>
              <div class="item-no-image">Aucune image</div>
            <?php endif; ?>

            <h3><?php echo h($item['nom']); ?></h3>
            <p>Prix : <?php echo (int)$item['prix']; ?></p>
            <p>Stock : <?php echo (int)$item['quantiteStock']; ?></p>
            <button class="btn-add" data-item-id="<?php echo (int)$item['idItem']; ?>">Ajouter au panier</button>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- SECTION ARMURES -->
  <section class="section-items">
    <h2>Armures</h2>

    <?php if (empty($armures)): ?>
      <p>Aucune armure disponible.</p>
    <?php else: ?>
      <div class="items-grid">
        <?php foreach ($armures as $item): ?>
          <a class="item-card" href="details.php?id=<?php echo (int)$item['idItem']; ?>">
            <?php if (!empty($item['photo'])): ?>
              <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
            <?php else: ?>
              <div class="item-no-image">Aucune image</div>
            <?php endif; ?>

            <h3><?php echo h($item['nom']); ?></h3>
            <p>Prix : <?php echo (int)$item['prix']; ?></p>
            <p>Stock : <?php echo (int)$item['quantiteStock']; ?></p>
            <button class="btn-add" data-item-id="<?php echo (int)$item['idItem']; ?>">Ajouter au panier</button>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- SECTION POTIONS -->
  <section class="section-items">
    <h2>Potions</h2>

    <?php if (empty($potions)): ?>
      <p>Aucune potion disponible.</p>
    <?php else: ?>
      <div class="items-grid">
        <?php foreach ($potions as $item): ?>
          <a class="item-card" href="details.php?id=<?php echo (int)$item['idItem']; ?>">
            <?php if (!empty($item['photo'])): ?>
              <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
            <?php else: ?>
              <div class="item-no-image">Aucune image</div>
            <?php endif; ?>

            <h3><?php echo h($item['nom']); ?></h3>
            <p>Prix : <?php echo (int)$item['prix']; ?></p>
            <p>Stock : <?php echo (int)$item['quantiteStock']; ?></p>
            <button class="btn-add" data-item-id="<?php echo (int)$item['idItem']; ?>">Ajouter au panier</button>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- SECTION SORTS -->
  <section class="section-items">
    <h2>Sorts</h2>

    <?php if (empty($sorts)): ?>
      <p>Aucun sort disponible.</p>
    <?php else: ?>
      <div class="items-grid">
        <?php foreach ($sorts as $item): ?>
          <a class="item-card" href="details.php?id=<?php echo (int)$item['idItem']; ?>">
            <?php if (!empty($item['photo'])): ?>
              <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
            <?php else: ?>
              <div class="item-no-image">Aucune image</div>
            <?php endif; ?>

            <h3><?php echo h($item['nom']); ?></h3>
            <p>Prix : <?php echo (int)$item['prix']; ?></p>
            <p>Stock : <?php echo (int)$item['quantiteStock']; ?></p>
            <button class="btn-add" data-item-id="<?php echo (int)$item['idItem']; ?>">Ajouter au panier</button>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

</main>

<aside id="cart">
  <div class="cart-head">
    <h4>Panier</h4>
  <form method="post">
    <input type="submit" value="Acheter">
  </form>
    <a class="cart-close" href="#">✕</a>
  </div>

  <div class="cart-items">
    <?php include "panier.php"; ?>
    <p>Le Panier est Vide</p>
  </div>
</aside>

</body>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const barreRecherche = document.getElementById('barreRecherche');
    const checkboxes = document.querySelectorAll('#filtres input[type="checkbox"]');
    const sections = document.querySelectorAll('.section-items');

    function appliquerFiltres() {
        const recherche = barreRecherche.value.toLowerCase().trim();

        // Types cochés
        const typesSelectionnes = [];
        checkboxes.forEach(function (checkbox) {
            if (checkbox.checked) {
                const texteLabel = checkbox.parentElement.textContent.trim().toLowerCase();
                typesSelectionnes.push(texteLabel);
            }
        });

        sections.forEach(function (section) {
            const typeSection = section.querySelector('h2').textContent.trim().toLowerCase();
            const cartes = section.querySelectorAll('.item-card');
            let auMoinsUneVisible = false;

            cartes.forEach(function (carte) {
                const nomItem = carte.querySelector('h3').textContent.toLowerCase();

                const matchRecherche = nomItem.includes(recherche);

                // Si aucune checkbox cochée, on accepte tous les types
                const matchType = typesSelectionnes.length === 0 || typesSelectionnes.includes(typeSection);

                if (matchRecherche && matchType) {
                    carte.style.display = '';
                    auMoinsUneVisible = true;
                } else {
                    carte.style.display = 'none';
                }
            });

            // Cacher la section complète s'il n'y a aucun item visible
            if (auMoinsUneVisible) {
                section.style.display = '';
            } else {
                section.style.display = 'none';
            }
        });
    }

    // Recherche en direct
    barreRecherche.addEventListener('input', appliquerFiltres);

    // Filtres checkbox
    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', appliquerFiltres);
    });

    // Lancer une première fois au chargement
    appliquerFiltres();
});
</script>
</html>