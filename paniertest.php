<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';
require_once 'panier_de_paniertest.php';

if (isset($_SESSION['user']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'Acheter') {
        $resultat = acheter_panier($pdo);
        $_SESSION['message_panier'] = $resultat['message'];
        $_SESSION['message_panier_success'] = $resultat['success'];

    } else if ($_POST['action'] === 'Vider panier') {
        vider_panier($pdo, $_SESSION['user']['idJoueur']);
        $_SESSION['message_panier'] = "Panier vidé";
        $_SESSION['message_panier_success'] = true;

    } else if ($_POST['action'] === 'Supprimer du panier') {
        $idItem = isset($_POST['idItem']) ? (int) $_POST['idItem'] : 0;
        supprimer_objet_du_panier($pdo, $idItem);
        $_SESSION['message_panier'] = "Article supprimé du panier";
        $_SESSION['message_panier_success'] = true;

    } else if ($_POST['action'] === 'Modifier quantité') {
        $idItem = isset($_POST['idItem']) ? (int) $_POST['idItem'] : 0;
        $quantite = isset($_POST['quantite']) ? (int) $_POST['quantite'] : 0;

        $resultat = modifier_quantite_panier($pdo, $idItem, $quantite);
        $_SESSION['message_panier'] = $resultat['message'];
        $_SESSION['message_panier_success'] = $resultat['success'];
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$articles_panier = obtenirArticlesPanier($pdo);
$totalOr = 0;

foreach ($articles_panier as $article) {
    $info = obtenirArticle($pdo, $article['idItem']);
    if ($info) {
        $totalOr += ((int) $info['prix'] * (int) $article['quantitePanier']);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier</title>

    <link rel="stylesheet" href="public/css/style.css">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Trebuchet MS", "Segoe UI", Arial, sans-serif;
            background-color: #111;
        }

        main {
            padding: 20px 0 50px 0;
        }

        .panier-wrapper {
            width: min(1100px, 88%);
            margin: 0 auto;
        }

        .panier-principal,
        .panier-item-grid {
            background: rgba(20, 20, 20, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.14);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.28);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .panier-principal {
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 24px;
            color: white;
        }

        .panier-principal h2 {
            margin: 0 0 12px 0;
            font-size: 2rem;
            font-weight: 800;
            color: #fff7dc;
            letter-spacing: 0.5px;
        }

        .resume-panier {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .total-panier {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ffe08a;
            text-shadow: 0 0 10px rgba(255, 208, 74, 0.15);
        }

        .actions-panier {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-panier {
            border: none;
            border-radius: 12px;
            padding: 12px 18px;
            font-size: 0.98rem;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.15s ease, opacity 0.15s ease, box-shadow 0.15s ease;
        }

        .btn-panier:hover {
            transform: translateY(-1px);
            opacity: 0.95;
        }

        .btn-acheter {
            background: linear-gradient(135deg, #d4af37, #f6d365);
            color: #2a1b00;
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.35);
        }

        .btn-vider {
            background: linear-gradient(135deg, #b22222, #e63946);
            color: white;
            box-shadow: 0 6px 20px rgba(230, 57, 70, 0.25);
        }

        .message-panier {
            margin: 16px 0 0 0;
            padding: 12px 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.98rem;
        }

        .message-panier.succes {
            background: rgba(60, 160, 90, 0.22);
            border: 1px solid rgba(90, 200, 120, 0.35);
            color: #d8ffe0;
        }

        .message-panier.erreur {
            background: rgba(180, 50, 50, 0.22);
            border: 1px solid rgba(255, 90, 90, 0.35);
            color: #ffd8d8;
        }

        .panier-items {
            display: grid;
            gap: 22px;
        }

        .panier-item-grid {
            border-radius: 20px;
            padding: 22px;
            text-align: center;
            color: white;
        }

        .panier-item-grid a {
            color: white;
            text-decoration: none;
        }

        .panier-item-grid img {
            width: auto;
            max-width: 150px;
            height: 140px;
            object-fit: cover;
            border-radius: 14px;
            margin-bottom: 12px;
        }

        .panier-item-grid h3 {
            margin: 8px 0 10px 0;
            font-size: 2rem;
            font-weight: 800;
            color: #ffffff;
        }

        .panier-item-grid p {
            margin: 6px 0;
            font-size: 1.15rem;
        }

        .panier-item-grid .prix {
            color: #ffe08a;
            font-weight: 700;
        }

        .ligne-total {
            font-weight: 700;
        }

        .form-quantite {
            margin-top: 18px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .form-quantite label {
            font-weight: 700;
            font-size: 1rem;
            color: #f5f5f5;
        }

        .input-quantite {
            width: 90px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.12);
            color: white;
            font-size: 1rem;
            text-align: center;
            outline: none;
        }

        .input-quantite::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .input-quantite.is-loading {
            opacity: 0.65;
        }

        .auto-update-note {
            width: 100%;
            color: #ccc;
            font-size: 0.9rem;
        }

        .btn-supprimer {
            background: linear-gradient(135deg, #b22222, #e63946);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            cursor: pointer;
            font-weight: 800;
        }

        .panier-vide {
            text-align: center;
            color: white;
            font-size: 1.2rem;
            padding: 30px;
            background: rgba(20, 20, 20, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 18px;
            backdrop-filter: blur(8px);
        }
    </style>
</head>

<body>
    <?php include_once 'template/header.php'; ?>

    <main>
        <div class="panier-wrapper">

            <div class="panier-principal">
                <h2>Panier</h2>

                <div class="resume-panier">
                    <div class="total-panier" id="totalPanier">Total : <?php echo (int) $totalOr; ?> or</div>

                    <form method="post" class="actions-panier">
                        <input type="submit" name="action" value="Acheter" class="btn-panier btn-acheter">
                        <input type="submit" name="action" value="Vider panier" class="btn-panier btn-vider">
                    </form>
                </div>

                <?php if (isset($_SESSION['message_panier'])): ?>
                    <div id="messagePanier"
                        class="message-panier <?php echo $_SESSION['message_panier_success'] ? 'succes' : 'erreur'; ?>">
                        <?php echo htmlspecialchars($_SESSION['message_panier']); ?>
                    </div>
                    <?php unset($_SESSION['message_panier'], $_SESSION['message_panier_success']); ?>
                <?php else: ?>
                    <div id="messagePanier" class="message-panier" style="display:none;"></div>
                <?php endif; ?>
            </div>

            <div class="panier-items">
                <?php if (empty($articles_panier)): ?>
                    <div class="panier-vide">
                        Ton panier est vide.
                    </div>
                <?php else: ?>
                    <?php foreach ($articles_panier as $article): ?>
                        <?php
                        $info = obtenirArticle($pdo, $article['idItem']);
                        if (!$info)
                            continue;

                        $idItem = (int) $article['idItem'];
                        $nomItem = $info['nom'];
                        $quantite = (int) $article['quantitePanier'];
                        $prix = (int) $info['prix'];
                        $image = $info['photo'];
                        $prixTotal = $prix * $quantite;
                        ?>
                        <div class="panier-item-grid" id="item-<?php echo $idItem; ?>">
                            <a href="details.php?id=<?php echo $idItem; ?>">
                                <img src="<?php echo htmlspecialchars($image); ?>"
                                    alt="<?php echo htmlspecialchars($nomItem); ?>">
                                <h3><?php echo htmlspecialchars($nomItem); ?></h3>
                                <p class="prix">Prix unitaire : <?php echo $prix; ?> or</p>
                                <p class="ligne-total" id="prix-total-<?php echo $idItem; ?>">
                                    Prix total : <?php echo $prixTotal; ?> or
                                </p>
                            </a>

                            <form method="post" class="form-quantite js-auto-quantite">
                                <input type="hidden" name="idItem" value="<?php echo $idItem; ?>">

                                <label for="quantite_<?php echo $idItem; ?>">Quantité :</label>
                                <input id="quantite_<?php echo $idItem; ?>" class="input-quantite" type="number" name="quantite"
                                    min="0" value="<?php echo $quantite; ?>" data-iditem="<?php echo $idItem; ?>">

                                <button type="submit" name="action" value="Supprimer du panier" class="btn-supprimer">
                                    Supprimer du panier
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <script>
        const totalPanierEl = document.getElementById('totalPanier');
        const messagePanierEl = document.getElementById('messagePanier');

        function afficherMessage(message, succes) {
            messagePanierEl.style.display = 'block';
            messagePanierEl.textContent = message;
            messagePanierEl.className = 'message-panier ' + (succes ? 'succes' : 'erreur');
        }

        document.querySelectorAll('.input-quantite').forEach((input) => {
            let timeout = null;
            let ancienneValeur = input.value;

            input.addEventListener('focus', () => {
                ancienneValeur = input.value;
            });

            input.addEventListener('input', () => {
                clearTimeout(timeout);

                if (input.value.trim() === '') {
                    return;
                }

                timeout = setTimeout(() => {
                    envoyerMaj(input);
                }, 500);
            });

            input.addEventListener('blur', () => {
                clearTimeout(timeout);

                if (input.value.trim() === '') {
                    input.value = ancienneValeur;
                    return;
                }

                envoyerMaj(input);
            });
        });

        function envoyerMaj(input) {
            const valeur = input.value.trim();

            if (valeur === '') {
                return;
            }

            const quantite = parseInt(valeur, 10);

            if (isNaN(quantite) || quantite < 0) {
                afficherMessage('Quantité invalide.', false);
                input.value = input.dataset.lastValid || 1;
                return;
            }

            const form = input.closest('form');
            const idItem = form.querySelector('[name="idItem"]').value;

            const formData = new FormData();
            formData.append('idItem', idItem);
            formData.append('quantite', quantite);

            input.classList.add('is-loading');

            fetch('update_panier_ajax.php', {
                method: 'POST',
                body: formData
            })
                .then((response) => response.json())
                .then((data) => {
                    input.classList.remove('is-loading');

                    if (!data.success) {
                        afficherMessage(data.message, false);
                        input.value = input.dataset.lastValid || input.value;
                        return;
                    }

                    input.value = data.quantiteAppliquee;
                    input.dataset.lastValid = data.quantiteAppliquee;

                    const prixTotalItemEl = document.getElementById('prix-total-' + data.idItem);
                    if (prixTotalItemEl) {
                        prixTotalItemEl.textContent = 'Prix total : ' + data.prixTotalItem + ' or';
                    }

                    if (totalPanierEl) {
                        totalPanierEl.textContent = 'Total : ' + data.totalOr + ' or';
                    }

                    afficherMessage(data.message, true);

                    if (parseInt(data.quantiteAppliquee, 10) === 0) {
                        const blocItem = document.getElementById('item-' + data.idItem);
                        if (blocItem) {
                            blocItem.remove();
                        }

                        if (!document.querySelector('.panier-item-grid')) {
                            const conteneur = document.querySelector('.panier-items');
                            conteneur.innerHTML = '<div class="panier-vide">Ton panier est vide.</div>';
                        }
                    }
                })
                .catch(() => {
                    input.classList.remove('is-loading');
                    afficherMessage('Erreur lors de la mise à jour automatique.', false);
                    input.value = input.dataset.lastValid || input.value;
                });
        }

        document.querySelectorAll('.input-quantite').forEach((input) => {
            input.dataset.lastValid = input.value;
        });
    </script>
</body>

</html>
