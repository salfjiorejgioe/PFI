<?php
session_start();
require_once 'db.php';

// Fonction pour sécuriser l'affichage
function h($texte)
{
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
  <title>⚔️ Marché Darquest</title>
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
            <a class="item-card" href="details.php?id=<?php echo (int) $item['idItem']; ?>">
              <?php if (!empty($item['photo'])): ?>
                <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
              <?php else: ?>
                <div class="item-no-image">Aucune image</div>
              <?php endif; ?>

              <h3><?php echo h($item['nom']); ?></h3>
              <p>Prix : <?php echo (int) $item['prix']; ?></p>
              <p>Stock : <?php echo (int) $item['quantiteStock']; ?></p>
              <form method="post" action="panier.php">
                <input type="hidden" name="idItem" value="<?php echo (int)$item['idItem']; ?>">
                <input type="submit" name="action" value="Ajouter" class="btn-add">
              </form>
          
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
            <a class="item-card" href="details.php?id=<?php echo (int) $item['idItem']; ?>">
              <?php if (!empty($item['photo'])): ?>
                <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
              <?php else: ?>
                <div class="item-no-image">Aucune image</div>
              <?php endif; ?>

              <h3><?php echo h($item['nom']); ?></h3>
              <p>Prix : <?php echo (int) $item['prix']; ?></p>
              <p>Stock : <?php echo (int) $item['quantiteStock']; ?></p>
              <form method="post" action="panier.php">
                <input type="hidden" name="idItem" value="<?php echo (int)$item['idItem']; ?>">
                <input type="submit" name="action" value="Ajouter" class="btn-add">
              </form>
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
            <a class="item-card" href="details.php?id=<?php echo (int) $item['idItem']; ?>">
              <?php if (!empty($item['photo'])): ?>
                <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
              <?php else: ?>
                <div class="item-no-image">Aucune image</div>
              <?php endif; ?>

              <h3><?php echo h($item['nom']); ?></h3>
              <p>Prix : <?php echo (int) $item['prix']; ?></p>
              <p>Stock : <?php echo (int) $item['quantiteStock']; ?></p>
              <form method="post" action="panier.php">
                <input type="hidden" name="idItem" value="<?php echo (int)$item['idItem']; ?>">
                <input type="submit" name="action" value="Ajouter" class="btn-add">
              </form>
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
            <a class="item-card" href="details.php?id=<?php echo (int) $item['idItem']; ?>">
              <?php if (!empty($item['photo'])): ?>
                <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
              <?php else: ?>
                <div class="item-no-image">Aucune image</div>
              <?php endif; ?>

              <h3><?php echo h($item['nom']); ?></h3>
              <p>Prix : <?php echo (int) $item['prix']; ?></p>
              <p>Stock : <?php echo (int) $item['quantiteStock']; ?></p>


             

              <form method="post" action="panier.php">
                <input type="hidden" name="idItem" value="<?php echo (int)$item['idItem']; ?>">
                <input type="submit" name="action" value="Ajouter" class="btn-add">
              </form>

            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

  </main>
   <?php include_once 'template/footer.php'; ?>

  <aside id="cart">
    <div class="cart-head">
      <h4>Panier</h4>
      <div id="cart-total"></div>
      <form method="post">
        <input type="submit" name="action" value="Acheter">
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
    let panier = {};
    const barreRecherche = document.getElementById('barreRecherche');
    const checkboxes = document.querySelectorAll('#filtres input[type="checkbox"]');
    const sections = document.querySelectorAll('.section-items');
    const cartContainer = document.getElementById('.cart-items');
    const cartTotal = document.getElementById('cart-total');

    function refreshCart(){
      cartContainer.innerHTML = '';
      let total = 0;
      let estVide = true;
      for(let i in panier){
        const item = panier[id];
        estVide = false;
        const div = document.createElement('div');
        div.classList.add('cart-item');
        div.innerHTML = `<strong>${item.nomItem}</strong><br>
                          ${item.prix} x ${item.quantite} = ${item.prix * item.quantite}
                        `;
        cartContainer.appendChild(div);
        total += item.prix * item.quantite;
      }
      if(estVide){
        cartContainer.innerHTML = "<p>Le panier est vide.</p>"
      }
      cartTotal.innerHTML = total;
    }
    document.querySelectorAll('.btn-add').forEach(function(btn){
      btn.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();


        const cardPanier = this.closest('.item-card');
        const id = this.dataset.idItem;
        const nom = cardPanier.querySelector('h3').textContent;
        const prix = parseInt(cardPanier.querySelector('p').textContent);

        if(panier[id]){
          panier[id].quantite++;

        }
        else{
          panier[id] = {
                id:id,
                nom:nom,
                prix:prix,
                quantite:1};
        }
        refreshCart();
        this.textContent = "item a été ajouté";


      })
    })

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

    document.querySelectorAll('.btn-add').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault(); // Empêche le comportement par défaut du bouton details.php
        e.stopPropagation(); // Empêche le clic de déclencher le lien parent

        const idItem = this.dataset.id; // Récupère l'id de l'item à ajouter

        console.log("Ajout item :", idItem);  // Affiche dans la console pour vérifier que l'id est correct

        

        this.textContent = "Ajouté !";
        this.style.background = "#adadad";

        setTimeout(() => {
          this.textContent = "Ajouter au panier";
          this.style.background = "";
        }, 1000);
      });
    });

    appliquerFiltres();
  });
</script>

</html>