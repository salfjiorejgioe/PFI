<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

$items = [];
$armes = [];
$armures = [];
$potions = [];
$sorts = [];

try {
    $sql = "SELECT idItem, nom, quantiteStock, prix, photo, typeItem, estDisponible
            FROM Items
            WHERE estDisponible = 1
            ORDER BY typeItem, prix ASC";

    $stmt = $pdo->query($sql);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $items = [];
}

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

function afficherSection($titre, $listeItems)
{
    ?>
    <section class="section-items">
        <h2><?php echo h($titre); ?></h2>

        <?php if (empty($listeItems)): ?>
            <p>Aucun item disponible.</p>
        <?php else: ?>
            <div class="items-grid">
                <?php foreach ($listeItems as $item): ?>
                    <a class="item-card" href="details.php?id=<?php echo (int)$item['idItem']; ?>">
                        <?php if (!empty($item['photo'])): ?>
                            <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
                        <?php else: ?>
                            <div class="item-no-image">Aucune image</div>
                        <?php endif; ?>

                        <h3><?php echo h($item['nom']); ?></h3>
                        <p>Prix : <?php echo (int)$item['prix']; ?></p>
                        <p>Stock : <?php echo (int)$item['quantiteStock']; ?></p>

                        <button class="btn-add" data-item-id="<?php echo (int)$item['idItem']; ?>">
                            Ajouter au panier
                        </button>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marché Darquest</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>

<?php include_once 'template/header.php'; ?>

<main>
    <section id="filtres">
        <input id="barreRecherche" type="text" placeholder="Rechercher...">

        <label><input type="checkbox" value="armes"> Armes</label>
        <label><input type="checkbox" value="armures"> Armures</label>
        <label><input type="checkbox" value="potions"> Potions</label>
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

    <?php afficherSection('Armes', $armes); ?>
    <?php afficherSection('Armures', $armures); ?>
    <?php afficherSection('Potions', $potions); ?>
    <?php afficherSection('Sorts', $sorts); ?>
</main>

<?php include_once 'template/footer.php'; ?>

<aside id="cart">
    <div class="cart-head">
        <h4>Panier</h4>
        <div class="cart-head-actions">
            <button type="button" id="btn-clear-cart">Vider</button>

            <form action="panier.php" method="get">
               <button type="button" id="btn-buy-cart">Acheter</button>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const barreRecherche = document.getElementById('barreRecherche');
    const cases = document.querySelectorAll('#filtres input[type="checkbox"]');
    const sections = document.querySelectorAll('.section-items');

    const cartItems = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    const btnClearCart = document.getElementById('btn-clear-cart');

    function appliquerFiltres() {
        const texteRecherche = barreRecherche.value.toLowerCase().trim();
        let typesChoisis = [];

        cases.forEach(function (c) {
            if (c.checked) {
                typesChoisis.push(c.value.toLowerCase());
            }
        });

        sections.forEach(function (section) {
            const titre = section.querySelector('h2').textContent.toLowerCase();
            const cartes = section.querySelectorAll('.item-card');
            let visibleDansSection = false;

            cartes.forEach(function (carte) {
                const nom = carte.querySelector('h3').textContent.toLowerCase();

                const matchRecherche = nom.includes(texteRecherche);
                const matchType = typesChoisis.length === 0 || typesChoisis.includes(titre);

                if (matchRecherche && matchType) {
                    carte.style.display = '';
                    visibleDansSection = true;
                } else {
                    carte.style.display = 'none';
                }
            });

            if (visibleDansSection) {
                section.style.display = '';
            } else {
                section.style.display = 'none';
            }
        });
    }

    function afficherPanier(items, total) {
        cartItems.innerHTML = '';

        if (!items || items.length === 0) {
            cartItems.innerHTML = '<p>Le Panier est Vide</p>';
            cartTotal.textContent = 'Total : 0';
            return;
        }

        items.forEach(function (item) {
            let image = '<div class="cart-item-image no-image">Aucune image</div>';

            if (item.photo) {
                image = '<img src="' + item.photo + '" alt="' + item.nom + '" class="cart-item-image">';
            }

            const div = document.createElement('div');
            div.className = 'cart-item';

            div.innerHTML = `
                <div class="cart-item-row">
                    ${image}
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
        activerBoutonsPanier();
    }

    function chargerPanier() {
        fetch('load_cart.php')
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.success) {
                    afficherPanier(data.items, data.total);
                } else {
                    cartItems.innerHTML = '<p>Connectez-vous pour utiliser le panier.</p>';
                    cartTotal.textContent = 'Total : 0';
                }
            })
            .catch(function () {
                cartItems.innerHTML = '<p>Erreur lors du chargement du panier.</p>';
                cartTotal.textContent = 'Total : 0';
            });
    }

    function modifierPanier(action, idItem) {
        let corps = 'action=' + encodeURIComponent(action);

        if (idItem) {
            corps += '&idItem=' + encodeURIComponent(idItem);
        }

        fetch('update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: corps
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (!data.success) {
                alert(data.message);
                return;
            }

            afficherPanier(data.items, data.total);
        })
        .catch(function () {
            alert('Erreur lors de la modification du panier.');
        });
    }

    function activerBoutonsPanier() {
        document.querySelectorAll('.btn-cart-plus').forEach(function (btn) {
            btn.addEventListener('click', function () {
                modifierPanier('increase', this.dataset.id);
            });
        });

        document.querySelectorAll('.btn-cart-minus').forEach(function (btn) {
            btn.addEventListener('click', function () {
                modifierPanier('decrease', this.dataset.id);
            });
        });

        document.querySelectorAll('.btn-cart-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                modifierPanier('remove', this.dataset.id);
            });
        });
    }

    function activerBoutonsAjouter() {
        document.querySelectorAll('.btn-add').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const idItem = this.dataset.itemId;
                const bouton = this;

                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'idItem=' + encodeURIComponent(idItem)
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) {
                        alert(data.message);
                        return;
                    }

                    afficherPanier(data.items, data.total);

                    bouton.textContent = 'Ajouté !';

                    setTimeout(function () {
                        bouton.textContent = 'Ajouter au panier';
                    }, 1000);
                })
                .catch(function () {
                    alert('Erreur lors de l’ajout au panier.');
                });
            });
        });
    }

    barreRecherche.addEventListener('input', appliquerFiltres);

    cases.forEach(function (c) {
        c.addEventListener('change', appliquerFiltres);
    });

    if (btnClearCart) {
        btnClearCart.addEventListener('click', function () {
            modifierPanier('clear', null);
        });
    }

document.getElementById('btn-buy-cart').addEventListener('click', function (e) {
    e.preventDefault();

    fetch('panier.php', {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            chargerPanier();
        }
    })
    .catch(() => {
        alert("Erreur lors de l'achat.");
    });
});
    appliquerFiltres();
    chargerPanier();
    activerBoutonsAjouter();
});
</script>

</body>
</html>