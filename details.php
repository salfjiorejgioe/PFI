<?php
session_start();
require_once 'db.php';

function h($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$idItem = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$item = null;
$error = "";

if ($idItem <= 0) {
    $error = "Item invalide.";
} else {
    try {
        $sql = "
    SELECT 
        i.idItem,
        i.nom,
        i.quantiteStock,
        i.prix,
        i.photo,
        i.typeItem,

        a.efficacite,
        a.genre,
        a.description,

        ar.matiere,
        ar.taille,

        p.effet,
        p.duree,

        s.estInstantane,
        s.rarete,
        s.typeSort,

        ts.description AS descriptionTypeSort,
        ts.pDegat,
        ts.pvRetire

    FROM Items i
    LEFT JOIN Armes a ON i.idItem = a.idItem
    LEFT JOIN Armures ar ON i.idItem = ar.idItem
    LEFT JOIN Potions p ON i.idItem = p.idItem
    LEFT JOIN Sorts s ON i.idItem = s.idItem
    LEFT JOIN TypeSorts ts ON s.typeSort = ts.typeSort
    WHERE i.idItem = ?
";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idItem]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            $error = "Item introuvable.";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors du chargement.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>

<body>

    <?php include_once "template/header.php"; ?>

    <main>
        <?php if ($error != ""): ?>
            <p><?php echo h($error); ?></p>
        <?php else: ?>

            <div class="item-card details-card">
                <?php if (!empty($item['photo'])): ?>
                    <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
                <?php else: ?>
                    <div class="item-no-image">Aucune image</div>
                <?php endif; ?>

                <h2><?php echo h($item['nom']); ?></h2>
                <p>Prix : <?php echo (int) $item['prix']; ?></p>
                <p>Stock : <?php echo (int) $item['quantiteStock']; ?></p>

                <?php if ($item['typeItem'] == 'R'): ?>
                    <p>Type : Armure</p>
                    <p>Matière : <?php echo h($item['matiere']); ?></p>
                    <p>Taille : <?php echo h($item['taille']); ?></p>
                <?php endif; ?>

                <?php if ($item['typeItem'] == 'A'): ?>
                    <p>Type : Arme</p>
                    <p>Efficacité : <?php echo h($item['efficacite']); ?></p>
                    <p>Genre : <?php echo h($item['genre']); ?></p>
                    <p>Description : <?php echo h($item['description']); ?></p>
                <?php endif; ?>

                <?php if ($item['typeItem'] == 'P'): ?>
                    <p>Type : Potion</p>
                    <p>Effet : <?php echo h($item['effet']); ?></p>
                    <p>Durée : <?php echo (int) $item['duree']; ?></p>
                <?php endif; ?>

                <?php if ($item['typeItem'] == 'S'): ?>
                    <p>Type : Sort</p>
                    <p>Type sort : <?php echo h($item['typeSort']); ?></p>
                    <p>Description : <?php echo h($item['descriptionTypeSort']); ?></p>
                    <p>Rareté : <?php echo h($item['rarete']); ?></p>
                    <p>Instantané : <?php echo ((int) $item['estInstantane'] === 1) ? 'Oui' : 'Non'; ?></p>
                    <p>Dégâts infligés : <?php echo (int) $item['pDegat']; ?></p>
                    <p>PV retirés au lanceur : <?php echo (int) $item['pvRetire']; ?></p>
                <?php endif; ?>

                <button class="btn-add" data-item-id="<?php echo (int) $item['idItem']; ?>">
                    Ajouter au panier
                </button>
            </div>
        <?php endif; ?>
    </main>

    <aside id="cart">
        <div class="cart-head">
            <h4>Panier</h4>

            <div class="cart-head-actions">
                <button type="button" id="btn-clear-cart">Vider</button>

                <form action="panier.php" method="get">
                    <button id="btn-buy-cart">Acheter</button>
                </form>
            </div>
        </div>

        <div class="cart-items" id="cart-items">
            <p>Le Panier est Vide</p>
        </div>

        <div class="cart-total" id="cart-total">
            Total : 0
        </div>
    </aside>

    <?php include_once "template/footer.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cartItems = document.getElementById('cart-items');
            const cartTotal = document.getElementById('cart-total');
            const btnClearCart = document.getElementById('btn-clear-cart');
            const btnAdd = document.querySelector('.btn-add');

            function afficherPanier(items, total) {
                cartItems.innerHTML = '';

                if (!items || items.length === 0) {
                    cartItems.innerHTML = '<p>Le Panier est Vide</p>';
                    cartTotal.textContent = 'Total : 0';
                    return;
                }

                for (let i = 0; i < items.length; i++) {
                    const item = items[i];

                    const div = document.createElement('div');
                    div.className = 'cart-item';

                    let imageHtml = '<div class="cart-item-image no-image">Aucune image</div>';
                    if (item.photo) {
                        imageHtml = '<img src="' + item.photo + '" alt="' + item.nom + '" class="cart-item-image">';
                    }

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
            `;

                    cartItems.appendChild(div);
                }

                cartTotal.textContent = 'Total : ' + total;
                activerBoutonsPanier();
            }

            function chargerPanier() {
                fetch('load_cart.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            afficherPanier(data.items, data.total);
                        } else {
                            cartItems.innerHTML = '<p>Connectez-vous pour utiliser le panier.</p>';
                            cartTotal.textContent = 'Total : 0';
                        }
                    })
                    .catch(() => {
                        cartItems.innerHTML = '<p>Erreur panier.</p>';
                        cartTotal.textContent = 'Total : 0';
                    });
            }

            function actionPanier(action, idItem) {
                let body = 'action=' + encodeURIComponent(action);

                if (idItem) {
                    body += '&idItem=' + encodeURIComponent(idItem);
                }

                fetch('update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: body
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            alert(data.message);
                            return;
                        }

                        afficherPanier(data.items, data.total);
                    })
                    .catch(() => {
                        alert('Erreur lors de la modification du panier.');
                    });
            }

            function activerBoutonsPanier() {
                const btnPlus = document.querySelectorAll('.btn-cart-plus');
                const btnMinus = document.querySelectorAll('.btn-cart-minus');
                const btnRemove = document.querySelectorAll('.btn-cart-remove');

                for (let i = 0; i < btnPlus.length; i++) {
                    btnPlus[i].addEventListener('click', function () {
                        actionPanier('increase', this.dataset.id);
                    });
                }

                for (let i = 0; i < btnMinus.length; i++) {
                    btnMinus[i].addEventListener('click', function () {
                        actionPanier('decrease', this.dataset.id);
                    });
                }

                for (let i = 0; i < btnRemove.length; i++) {
                    btnRemove[i].addEventListener('click', function () {
                        actionPanier('remove', this.dataset.id);
                    });
                }
            }

            if (btnAdd) {
                btnAdd.addEventListener('click', function () {
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

                            afficherPanier(data.items, data.total);

                            this.textContent = 'Ajouté !';

                            setTimeout(() => {
                                this.textContent = 'Ajouter au panier';
                            }, 800);
                        })
                        .catch(() => {
                            alert('Erreur ajout panier.');
                        });
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

            if (btnClearCart) {
                btnClearCart.addEventListener('click', function () {
                    actionPanier('clear', null);
                });
            }

            chargerPanier();
        });
    </script>

</body>

</html>