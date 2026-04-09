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
        supprimer_objet_du_panier($pdo, $_POST['idItem']);
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
        $totalOr += ((int)$info['prix'] * (int)$article['quantitePanier']);
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
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.12);
            color: white;
            font-size: 1rem;
            text-align: center;
            outline: none;
        }

        .input-quantite::placeholder {
            color: rgba(255,255,255,0.7);
        }

        .btn-maj {
            background: rgba(255,255,255,0.14);
            color: white;
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 10px;
            padding: 10px 14px;
            cursor: pointer;
            font-weight: 700;
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

        .cart-btn {
            padding: 14px 24px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 24px;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            color: #fff;
            margin: 0 8px;
            flex: 1;
            text-align: center;
            display: inline-block;
        }

        .cart-btn.acheter {
            background: linear-gradient(135deg, #f6d26a, #f59e0b);
            box-shadow: 0 6px 20px rgba(246, 210, 106, 0.4);
        }

        .cart-btn.acheter:hover {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 8px 24px rgba(246, 210, 106, 0.5);
            transform: translateY(-2px);
        }

        .cart-btn.vider {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        .cart-btn.vider:hover {
            background: linear-gradient(135deg, #b91c1c, #991b1b);
            box-shadow: 0 8px 24px rgba(239, 68, 68, 0.5);
            transform: translateY(-2px);
        }

        .panier-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 12px;
            margin: 15px 0;
            border-radius: 8px;
            font-weight: bold;
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
                    <div class="total-panier">Total : <?php echo (int)$totalOr; ?> or</div>

                    <form method="post" class="actions-panier">
                        <input type="submit" name="action" value="Acheter" class="btn-panier btn-acheter">
                        <input type="submit" name="action" value="Vider panier" class="btn-panier btn-vider">
                    </form>
                </div>

                <?php if (isset($_SESSION['message_panier'])): ?>
                    <div class="message-panier <?php echo $_SESSION['message_panier_success'] ? 'succes' : 'erreur'; ?>">
                        <?php echo htmlspecialchars($_SESSION['message_panier']); ?>
                    </div>
                    <?php unset($_SESSION['message_panier'], $_SESSION['message_panier_success']); ?>
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
                        if (!$info) continue;

                        $idItem = (int)$article['idItem'];
                        $nomItem = $info['nom'];
                        $quantite = (int)$article['quantitePanier'];
                        $prix = (int)$info['prix'];
                        $image = $info['photo'];
                        $prixTotal = $prix * $quantite;
                        ?>
                        <div class="panier-item-grid">
                            <a href="details.php?id=<?php echo $idItem; ?>">
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($nomItem); ?>">
                                <h3><?php echo htmlspecialchars($nomItem); ?></h3>
                                <p class="prix">Prix unitaire : <?php echo $prix; ?> or</p>
                                <p>Prix total : <?php echo $prixTotal; ?> or</p>
                            </a>

                            <form method="post" class="form-quantite">
                                <input type="hidden" name="idItem" value="<?php echo $idItem; ?>">

                                <label for="quantite_<?php echo $idItem; ?>">Quantité :</label>
                                <input
                                    id="quantite_<?php echo $idItem; ?>"
                                    class="input-quantite"
                                    type="number"
                                    name="quantite"
                                    min="0"
                                    value="<?php echo $quantite; ?>"
                                >

                                <button type="submit" name="action" value="Modifier quantité" class="btn-maj">
                                    Mettre à jour
                                </button>
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

    <?php include_once 'template/footer.php'; ?>
</body>
</html>