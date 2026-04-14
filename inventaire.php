<?php
session_start();
require_once 'db.php';

// Fonction pour sécuriser l'affichage
function h($texte)
{
  return htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
}

// Vérifier que le joueur est connecté
if (
  !isset($_SESSION['user']) ||
  !is_array($_SESSION['user']) ||
  !isset($_SESSION['user']['idJoueur'])
) {
  header('Location: login.php');
  exit;
}

$idJoueur = (int) $_SESSION['user']['idJoueur'];

if (isset($_SESSION['user']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

  if ($_POST['action'] === 'Vendre item') {
    try {
      $pdo->beginTransaction();

      //profit 
      if ($_POST['typeItem'] == 'S')
        $profit = $_POST['prix'] - $_POST['rarete'] * 5 + 5;
      else
        $profit = $_POST['prix'] * 0.6;

      //Ajouter l'item au magasin
      $stmt = $pdo->prepare("
            UPDATE Items
            SET quantiteStock = quantiteStock + 1
            WHERE idItem = ?
      ");
      $stmt->execute([$_POST['idItem']]);
      
      //modifier quantite possede ou retirer de l'inventaire
      if ($_POST['quantiteInventaire'] > 1) {
        $stmt = $pdo->prepare("
            UPDATE Inventaires
            SET quantiteInventaire = quantiteInventaire - 1
            WHERE idJoueur = ? AND idItem = ?
        ");
        $stmt->execute([$idJoueur, $_POST['idItem']]);
      } else {
        $stmt = $pdo->prepare("
            DELETE FROM Inventaires
            WHERE idJoueur = ? AND idItem = ?
        ");
        $stmt->execute([$idJoueur, $_POST['idItem']]);
      }
      $stmt = $pdo->prepare("
            UPDATE Joueurs
            Set gold = gold + ?
            WHERE idJoueur = ?
      ");
      $stmt->execute([$profit, $idJoueur]);

      $stmt = $pdo->prepare("SELECT gold
                                FROM Joueurs 
                                WHERE idJoueur = ?");
      $stmt->execute([$idJoueur]);
      $joueur = $stmt->fetch();
      $newGold = (int) $joueur['gold'];

      $pdo->commit();
      $_SESSION['user']['or'] = $newGold;

      header("Location: " . $_SERVER['PHP_SELF']);
      exit();
    } catch (Exception $e) {
      $pdo->rollBack();
    }
  }
}
// Aller chercher tous les items de l'inventaire du joueur
try {
  $sql = "
        SELECT it.idItem,
               it.nom,
               it.quantiteStock,
               it.prix,
               it.photo,
               it.typeItem,
               inv.quantiteInventaire,

               s.estInstantane,
               s.rarete,
               s.typeSort
        FROM Inventaires inv
        INNER JOIN Items it ON inv.idItem = it.idItem
        LEFT JOIN Sorts s ON it.idItem = s.idItem
        WHERE inv.idJoueur = :idJoueur
        ORDER BY it.typeItem, it.nom
    ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':idJoueur' => $idJoueur]);
  $itemsInventaire = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $itemsInventaire = [];
}

// Séparer les items de l'inventaire par type
$armes = [];
$armures = [];
$potions = [];
$sorts = [];

foreach ($itemsInventaire as $item) {
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
  <title>Inventaire du joueur</title>
  <style>
    .btn-panier {
      border: none;
      border-radius: 12px;
      padding: 6px 9px;
      font-size: 0.67rem;
      font-weight: 800;
      cursor: pointer;
      transition: transform 0.15s ease, opacity 0.15s ease, box-shadow 0.15s ease;
    }

    .btn-panier:hover {
      transform: translateY(-1px);
      opacity: 0.95;
    }

    .btn-vider {
      background: linear-gradient(135deg, #b22222, #e63946);
      color: white;
      box-shadow: 0 6px 20px rgba(230, 57, 70, 0.25);
    }
  </style>
</head>

<body>
  <?php include_once 'template/header.php'; ?>


  <main>
    <h1>Mon inventaire</h1>
    <section id="filtres">
      <!-- même id et mêmes values que dans index.php -->
      <input id="barreRecherche" type="text" placeholder="Rechercher...">
      <label><input type="checkbox" value="potions"> Potions</label>
      <label><input type="checkbox" value="armures"> Armures</label>
      <label><input type="checkbox" value="armes"> Armes</label>
      <label><input type="checkbox" value="sorts"> Sorts</label>
    </section>

    <?php if (empty($itemsInventaire)): ?>
      <p>Vous ne possédez aucun item dans votre inventaire.</p>
    <?php else: ?>

      <!-- SECTION ARMES -->
      <section class="section-items">
        <h2>Armes</h2>
        <?php if (empty($armes)): ?>
          <p>Aucune arme dans votre inventaire.</p>
        <?php else: ?>
          <div class="items-grid">
            <?php foreach ($armes as $item): ?>
              <div class="item-card">
                <a style="text-decoration: none" href="details.php?id=<?php echo (int) $item['idItem']; ?>">
                  <?php if (!empty($item['photo'])): ?>
                    <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
                  <?php else: ?>
                    <div class="item-no-image">Aucune image</div>
                  <?php endif; ?>

                  <h3><?php echo h($item['nom']); ?></h3>
                  <p>Quantité possédée : <?php echo (int) $item['quantiteInventaire']; ?></p>
                  <p>Prix unitaire : <?php echo (int) $item['prix']; ?></p>
                </a>
                <form method="post">
                  <input type="hidden" name="idItem" value="<?php echo (int) $item['idItem']; ?>">
                  <input type="hidden" name="typeItem" value="<?php echo h($item['typeItem']); ?>">
                  <input type="hidden" name="prix" value="<?php echo (int) $item['prix']; ?>">
                  <input type="hidden" name="quantiteInventaire" value="<?php echo (int) $item['quantiteInventaire']; ?>">
                  <button type="submit" name="action" value="Vendre item" class="btn-panier btn-vider">Vendre
                    <?php echo (int) $item['prix'] * 0.6; ?> or</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <!-- SECTION ARMURES -->
      <section class="section-items">
        <h2>Armures</h2>
        <?php if (empty($armures)): ?>
          <p>Aucune armure dans votre inventaire.</p>
        <?php else: ?>
          <div class="items-grid">
            <?php foreach ($armures as $item): ?>
              <div class="item-card">
                <a style="text-decoration: none" href="details.php?id=<?php echo (int) $item['idItem']; ?>">
                  <?php if (!empty($item['photo'])): ?>
                    <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
                  <?php else: ?>
                    <div class="item-no-image">Aucune image</div>
                  <?php endif; ?>

                  <h3><?php echo h($item['nom']); ?></h3>
                  <p>Quantité possédée : <?php echo (int) $item['quantiteInventaire']; ?></p>
                  <p>Prix unitaire : <?php echo (int) $item['prix']; ?></p>
                </a>
                <form method="post">
                  <input type="hidden" name="idItem" value="<?php echo (int) $item['idItem']; ?>">
                  <input type="hidden" name="typeItem" value="<?php echo h($item['typeItem']); ?>">
                  <input type="hidden" name="prix" value="<?php echo (int) $item['prix']; ?>">
                  <input type="hidden" name="quantiteInventaire" value="<?php echo (int) $item['quantiteInventaire']; ?>">
                  <button type="submit" name="action" value="Vendre item" class="btn-panier btn-vider">Vendre
                    <?php echo (int) $item['prix'] * 0.6; ?> or</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <!-- SECTION POTIONS -->
      <section class="section-items">
        <h2>Potions</h2>
        <?php if (empty($potions)): ?>
          <p>Aucune potion dans votre inventaire.</p>
        <?php else: ?>
          <div class="items-grid">
            <?php foreach ($potions as $item): ?>
              <div class="item-card">
                <a style="text-decoration: none" href="details.php?id=<?php echo (int) $item['idItem']; ?>">
                  <?php if (!empty($item['photo'])): ?>
                    <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
                  <?php else: ?>
                    <div class="item-no-image">Aucune image</div>
                  <?php endif; ?>

                  <h3><?php echo h($item['nom']); ?></h3>
                  <p>Quantité possédée : <?php echo (int) $item['quantiteInventaire']; ?></p>
                  <p>Prix unitaire : <?php echo (int) $item['prix']; ?></p>
                </a>
                <form method="post">
                  <input type="hidden" name="idItem" value="<?php echo (int) $item['idItem']; ?>">
                  <input type="hidden" name="typeItem" value="<?php echo h($item['typeItem']); ?>">
                  <input type="hidden" name="prix" value="<?php echo (int) $item['prix']; ?>">
                  <input type="hidden" name="quantiteInventaire" value="<?php echo (int) $item['quantiteInventaire']; ?>">
                  <button type="submit" name="action" value="Vendre item" class="btn-panier btn-vider">Vendre
                    <?php echo (int) $item['prix'] * 0.6; ?> or</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <!-- SECTION SORTS -->
      <section class="section-items">
        <h2>Sorts</h2>
        <?php if (empty($sorts)): ?>
          <p>Aucun sort dans votre inventaire.</p>
        <?php else: ?>
          <div class="items-grid">
            <?php foreach ($sorts as $item): ?>
              <div class="item-card">
                <a style="text-decoration: none" href="details.php?id=<?php echo (int) $item['idItem']; ?>">
                  <?php if (!empty($item['photo'])): ?>
                    <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
                  <?php else: ?>
                    <div class="item-no-image">Aucune image</div>
                  <?php endif; ?>

                  <h3><?php echo h($item['nom']); ?></h3>
                  <p>Quantité possédée : <?php echo (int) $item['quantiteInventaire']; ?></p>
                  <p>Prix unitaire : <?php echo (int) $item['prix']; ?></p>
                </a>
                <form method="post">
                  <input type="hidden" name="idItem" value="<?php echo (int) $item['idItem']; ?>">
                  <input type="hidden" name="typeItem" value="<?php echo h($item['typeItem']); ?>">
                  <input type="hidden" name="prix" value="<?php echo (int) $item['prix']; ?>">
                  <input type="hidden" name="rarete" value="<?php echo (int) $item['rarete']; ?>">
                  <input type="hidden" name="quantiteInventaire" value="<?php echo (int) $item['quantiteInventaire']; ?>">
                  <button type="submit" name="action" value="Vendre item" class="btn-panier btn-vider">Vendre
                    <?php echo (int) $item['prix'] - (int) $item['rarete'] * 5 + 5; ?> or</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

    <?php endif; ?>
  </main>

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

      appliquerFiltres();
    });
  </script>
  <?php include_once 'template/footer.php'; ?>

</body>

</html>