<script>
document.addEventListener('DOMContentLoaded', function () {
  const barreRecherche = document.getElementById('barreRecherche');
  const checkboxes = document.querySelectorAll('#filtres input[type="checkbox"]');
  const sections = document.querySelectorAll('.section-items');

  const cartItems = document.getElementById('cart-items');
  const cartTotal = document.getElementById('cart-total');

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

      div.innerHTML = `
        <strong>${item.nom}</strong><br>
        Prix : ${item.prix}<br>
        Quantité : ${item.quantitePanier}<br>
        Sous-total : ${item.sousTotal}
        <hr>
      `;

      cartItems.appendChild(div);
    });

    cartTotal.textContent = 'Total : ' + total;
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

  appliquerFiltres();
  chargerPanier();
});
</script>