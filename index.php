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
  <title> Marché Darquest</title>
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
              <button class="btn-add" data-item-id="<?php echo (int) $item['idItem']; ?>">Ajouter au panier</button>
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
              <button class="btn-add" data-item-id="<?php echo (int) $item['idItem']; ?>">Ajouter au panier</button>
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
              <button class="btn-add" data-item-id="<?php echo (int) $item['idItem']; ?>">Ajouter au panier</button>
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
              <button class="btn-add" data-item-id="<?php echo (int) $item['idItem']; ?>">Ajouter au panier</button>
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
    <a href="paniertest.php">paniertest.php</a> 

    <div class="cart-head-actions">
      <button type="button" id="btn-clear-cart">Vider</button>

      <form action="panier.php" method="get">
        <input type="submit" value="Acheter">
      </form>
    </div>

    <a class="cart-close" href="#">✕</a>
  </div>

  <div class="cart-items" id="cart-items">
    <p>Le Panier est Vide</p>
  </div>

  <div class="cart-total" id="cart-total">
    Total : 0
  </div>
</aside>

</body>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const barreRecherche = document.getElementById('barreRecherche');
  const checkboxes = document.querySelectorAll('#filtres input[type="checkbox"]');
  const sections = document.querySelectorAll('.section-items');

  const cartItems = document.getElementById('cart-items');
  const cartTotal = document.getElementById('cart-total');
  const btnClearCart = document.getElementById('btn-clear-cart');

  function appliquerFiltres() {
    const recherche = barreRecherche.value.toLowerCase().trim();
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
        const matchType = typesSelectionnes.length === 0 || typesSelectionnes.includes(typeSection);

        if (matchRecherche && matchType) {
          carte.style.display = '';
          auMoinsUneVisible = true;
        } else {
          carte.style.display = 'none';
        }
      });

      section.style.display = auMoinsUneVisible ? '' : 'none';
    });
  }

  function afficherPanierDepuisJson(items, total) {
    cartItems.innerHTML = '';

    if (!items || items.length === 0) {
      cartItems.innerHTML = '<p>Le Panier est Vide</p>';
      cartTotal.textContent = 'Total : 0';
      return;
    }

    items.forEach(function (item) {
      const div = document.createElement('div');
      div.className = 'cart-item';

      const imageHtml = item.photo
        ? `<img src="${item.photo}" alt="${item.nom}" class="cart-item-image">`
        : `<div class="cart-item-image no-image">Aucune image</div>`;

      div.innerHTML = `
        <div class="cart-item-row">
          ${imageHtml}
          <div class="cart-item-info">
            <strong>${item.nom}</strong><br>
            Prix : ${item.prix}<br>
            Quantité : ${item.quantitePanier}<br>
            Sous-total : ${item.sousTotal}
          </div>
        </div>

        <div class="cart-item-actions">
          <button type="button" class="btn-cart-minus" data-id="${item.idItem}">-</button>
          <button type="button" class="btn-cart-plus" data-id="${item.idItem}">+</button>
          <button type="button" class="btn-cart-remove" data-id="${item.idItem}">Retirer</button>
        </div>

        <hr>
      `;

      cartItems.appendChild(div);
    });

    cartTotal.textContent = 'Total : ' + total;

    bindCartActionButtons();
  }

  function envoyerActionPanier(action, idItem = null) {
    const body = new URLSearchParams();
    body.append('action', action);

    if (idItem !== null) {
      body.append('idItem', idItem);
    }

    fetch('update_cart.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: body.toString()
    })
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        alert(data.message);
        return;
      }

      afficherPanierDepuisJson(data.items, data.total);
    })
    .catch(() => {
      alert('Erreur lors de la mise à jour du panier.');
    });
  }

  function bindCartActionButtons() {
    document.querySelectorAll('.btn-cart-plus').forEach(function (btn) {
      btn.addEventListener('click', function () {
        envoyerActionPanier('increase', this.dataset.id);
      });
    });

    document.querySelectorAll('.btn-cart-minus').forEach(function (btn) {
      btn.addEventListener('click', function () {
        envoyerActionPanier('decrease', this.dataset.id);
      });
    });

    document.querySelectorAll('.btn-cart-remove').forEach(function (btn) {
      btn.addEventListener('click', function () {
        envoyerActionPanier('remove', this.dataset.id);
      });
    });
  }

  function chargerPanier() {
    fetch('load_cart.php')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          afficherPanierDepuisJson(data.items, data.total);
        } else {
          cartItems.innerHTML = '<p>Connectez-vous pour utiliser le panier.</p>';
          cartTotal.textContent = 'Total : 0';
        }
      })
      .catch(() => {
        cartItems.innerHTML = '<p>Erreur lors du chargement du panier.</p>';
        cartTotal.textContent = 'Total : 0';
      });
  }

  barreRecherche.addEventListener('input', appliquerFiltres);

  checkboxes.forEach(function (checkbox) {
    checkbox.addEventListener('change', appliquerFiltres);
  });

  document.querySelectorAll('.btn-add').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();

      const idItem = this.dataset.itemId;

      fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'idItem=' + encodeURIComponent(idItem)
      })
      .then(response => response.json())
      .then(data => {
        if (!data.success) {
          alert(data.message);
          return;
        }

        afficherPanierDepuisJson(data.items, data.total);

        this.textContent = 'Ajouté !';
        this.style.background = '#adadad';

        setTimeout(() => {
          this.textContent = 'Ajouter au panier';
          this.style.background = '';
        }, 1000);
      })
      .catch(() => {
        alert('Erreur lors de l’ajout au panier.');
      });
    });
  });

  if (btnClearCart) {
    btnClearCart.addEventListener('click', function () {
      envoyerActionPanier('clear');
    });
  }

  appliquerFiltres();
  chargerPanier();
});
</script>

</html>