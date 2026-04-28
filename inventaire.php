<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

function h($texte)
{
  return htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
}

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
  if ($_POST['action'] === 'Utiliser item de soin') {
    try {
      $pdo->beginTransaction();

      $heal = (int) $_POST['healing'];
      $idItem = (int) $_POST['idItem'];

      modifier_Pv_joueur_connecte($pdo, $idJoueur, $heal);

      $stmt = $pdo->prepare("
        SELECT quantiteInventaire
        FROM Inventaires
        WHERE idJoueur = ? AND idItem = ?
      ");
      $stmt->execute([$idJoueur, $idItem]);
      $inventaire = $stmt->fetch();

      if (!$inventaire) {
        $pdo->rollBack();
        exit;
      }

      $quantite = (int) $inventaire['quantiteInventaire'];

      if ($quantite >= 1) {
        $stmt = $pdo->prepare("
          UPDATE Inventaires
          SET quantiteInventaire = quantiteInventaire - 1
          WHERE idJoueur = ? AND idItem = ?
        ");
        $stmt->execute([$idJoueur, $idItem]);
      } 
      // supprimer le sort/potion de l'inventaire après utilisation
      //else {
      //   $stmt = $pdo->prepare("
      //     DELETE FROM Inventaires
      //     WHERE idJoueur = ? AND idItem = ?
      //   ");
      //   $stmt->execute([$idJoueur, $idItem]);
      // }

      $pdo->commit();
      header("Location: " . $_SERVER['PHP_SELF']);
      exit();

    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
    }
  }
}

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

function prixVenteItem($item)
{
  if ($item['typeItem'] === 'S') {
    return (int) $item['prix'] - (int) $item['rarete'] * 5 + 5;
  }
  return (int) round((int) $item['prix'] * 0.6);
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
    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }

    .message-inventaire {
      width: min(1100px, 88%);
      margin: 10px auto 18px auto;
      padding: 12px 14px;
      border-radius: 14px;
      font-weight: 700;
      font-size: 0.95rem;
      display: none;
      backdrop-filter: blur(8px);
    }

    .message-inventaire.succes {
      display: block;
      background: rgba(60, 160, 90, 0.20);
      border: 1px solid rgba(90, 200, 120, 0.35);
      color: #d8ffe0;
      box-shadow: 0 8px 24px rgba(20, 80, 35, 0.18);
    }

    .message-inventaire.erreur {
      display: block;
      background: rgba(180, 50, 50, 0.20);
      border: 1px solid rgba(255, 90, 90, 0.35);
      color: #ffd8d8;
      box-shadow: 0 8px 24px rgba(90, 20, 20, 0.18);
    }

    .item-card {
      display: flex;
      flex-direction: column;
      gap: 10px;
      position: relative;
      overflow: hidden;
    }

    .item-card > a {
      display: block;
      text-decoration: none;
    }

    .btn-panier {
      border: none;
      border-radius: 12px;
      padding: 10px 12px;
      font-size: 0.82rem;
      font-weight: 800;
      cursor: pointer;
      transition: transform 0.15s ease, opacity 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
    }

    .btn-panier:hover {
      transform: translateY(-1px);
      opacity: 0.98;
      filter: brightness(1.03);
    }

    .btn-panier:active {
      transform: translateY(0);
    }

    .btn-vider,
    .btn-sell {
      background: linear-gradient(135deg, #a61d1d, #db3b45 55%, #f06b57);
      color: white;
      box-shadow:
        0 8px 24px rgba(230, 57, 70, 0.22),
        inset 0 1px 0 rgba(255,255,255,0.18);
    }

    .sell-panel {
      margin-top: auto;
      padding: 12px;
      border-radius: 14px;
      background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.03));
      border: 1px solid rgba(255,255,255,0.10);
      box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.06),
        0 8px 24px rgba(0,0,0,0.18);
    }

    .sell-topline {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin-bottom: 10px;
    }

    .sell-label {
      font-size: 0.82rem;
      font-weight: 800;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      color: #ffe7a8;
    }

    .sell-owned {
      font-size: 0.78rem;
      color: rgba(255,255,255,0.72);
    }

    .vente-ligne {
      display: grid;
      grid-template-columns: minmax(98px, 110px) 1fr;
      gap: 10px;
      align-items: stretch;
    }

    .qte-box {
      display: flex;
      align-items: center;
      gap: 8px;
      min-height: 42px;
      padding: 0 10px;
      border-radius: 12px;
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.14);
    }

    .qte-prefix {
      font-size: 0.82rem;
      font-weight: 700;
      color: rgba(255,255,255,0.84);
      white-space: nowrap;
    }

    .input-vente {
      width: 100%;
      min-width: 0;
      border: none;
      outline: none;
      background: transparent;
      color: white;
      font-size: 0.95rem;
      font-weight: 700;
      text-align: center;
      appearance: textfield;
      -moz-appearance: textfield;
    }

    .input-vente::-webkit-outer-spin-button,
    .input-vente::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }

    .vente-total {
      margin: 10px 0 0 0;
      padding-top: 10px;
      border-top: 1px solid rgba(255,255,255,0.10);
      color: #ffe08a;
      font-weight: 800;
      font-size: 0.9rem;
      text-align: center;
      text-shadow: 0 0 12px rgba(255, 208, 74, 0.14);
    }

    .modal-confirmation {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
      background:
        radial-gradient(circle at top, rgba(255,120,80,0.10), transparent 35%),
        rgba(5, 5, 8, 0.72);
      backdrop-filter: blur(8px);
      z-index: 9999;
    }

    .modal-confirmation.open {
      display: flex;
    }

    .modal-box {
      width: min(520px, 100%);
      position: relative;
      overflow: hidden;
      border-radius: 22px;
      padding: 0;
      background: linear-gradient(180deg, rgba(37,20,20,0.98), rgba(19,19,23,0.98));
      border: 1px solid rgba(255,255,255,0.10);
      box-shadow:
        0 24px 80px rgba(0,0,0,0.50),
        0 0 0 1px rgba(255,255,255,0.04) inset;
      color: white;
      animation: modalPop 180ms ease-out;
    }

    @keyframes modalPop {
      from {
        transform: translateY(10px) scale(0.98);
        opacity: 0;
      }
      to {
        transform: translateY(0) scale(1);
        opacity: 1;
      }
    }

    .modal-box::before {
      content: "";
      display: block;
      height: 4px;
      background: linear-gradient(90deg, #ffcf70, #ff6b57, #d73449);
    }

    .modal-header {
      padding: 18px 20px 10px 20px;
    }

    .modal-kicker {
      display: inline-block;
      margin-bottom: 6px;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #ffd78e;
    }

    .modal-title {
      margin: 0;
      font-size: 1.35rem;
      font-weight: 900;
      color: #fff4d2;
    }

    .modal-body {
      padding: 4px 20px 18px 20px;
    }

    .modal-item-preview {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 12px;
      align-items: center;
      padding: 14px;
      border-radius: 16px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
    }

    .modal-item-name {
      font-size: 1rem;
      font-weight: 800;
    }

    .modal-item-meta {
      margin-top: 6px;
      font-size: 0.86rem;
      color: rgba(255,255,255,0.72);
    }

    .modal-gain-badge {
      padding: 10px 12px;
      border-radius: 999px;
      background: linear-gradient(135deg, rgba(255,208,116,0.16), rgba(255,149,79,0.16));
      border: 1px solid rgba(255,208,116,0.28);
      color: #ffe6a3;
      font-weight: 900;
      white-space: nowrap;
    }

    .modal-warning {
      margin-top: 14px;
      padding: 12px 14px;
      border-radius: 14px;
      background: rgba(215, 52, 73, 0.10);
      border: 1px solid rgba(215, 52, 73, 0.22);
      color: rgba(255,255,255,0.86);
      font-size: 0.9rem;
      line-height: 1.45;
    }

    .modal-actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      padding: 0 20px 20px 20px;
    }

    .btn-annuler {
      background: rgba(255,255,255,0.08);
      color: white;
      border: 1px solid rgba(255,255,255,0.12);
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .btn-confirmer {
      background: linear-gradient(135deg, #a61d1d, #db3b45 55%, #f06b57);
      color: white;
      box-shadow:
        0 8px 24px rgba(230, 57, 70, 0.24),
        inset 0 1px 0 rgba(255,255,255,0.16);
    }

    @media (max-width: 640px) {
      .vente-ligne {
        grid-template-columns: 1fr;
      }

      .btn-sell {
        width: 100%;
      }
    }

    @media (max-width: 520px) {
      .modal-actions {
        grid-template-columns: 1fr;
      }

      .modal-item-preview {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <?php include_once 'template/header.php'; ?>

  <main>
    <h1>Mon inventaire</h1>

    <div id="messageInventaire" class="message-inventaire"></div>

    <section id="filtres">
      <input id="barreRecherche" type="text" placeholder="Rechercher...">
      <label><input type="checkbox" value="potions"> Potions</label>
      <label><input type="checkbox" value="armures"> Armures</label>
      <label><input type="checkbox" value="armes"> Armes</label>
      <label><input type="checkbox" value="sorts"> Sorts</label>
    </section>

    <?php if (empty($itemsInventaire)): ?>
      <p>Vous ne possédez aucun item dans votre inventaire.</p>
    <?php else: ?>

      <?php
      $sectionsData = [
        'Armes' => $armes,
        'Armures' => $armures,
        'Potions' => $potions,
        'Sorts' => $sorts
      ];
      ?>

      <?php foreach ($sectionsData as $titreSection => $listeItems): ?>
        <section class="section-items">
          <h2><?php echo h($titreSection); ?></h2>

          <?php if (empty($listeItems)): ?>
            <p>Aucun item dans cette section.</p>
          <?php else: ?>
            <div class="items-grid">
              <?php foreach ($listeItems as $item): ?>
                <?php if( (int) $item['quantiteInventaire'] > 0): ?> <!-- Condition affichage quantiteitem > 0-->
                <?php $prixVente = prixVenteItem($item); ?>
                <div class="item-card" id="item-<?php echo (int) $item['idItem']; ?>">
                  <a href="details.php?id=<?php echo (int) $item['idItem']; ?>">
                    <?php if (!empty($item['photo'])): ?>
                      <img src="<?php echo h($item['photo']); ?>" alt="<?php echo h($item['nom']); ?>">
                    <?php else: ?>
                      <div class="item-no-image">Aucune image</div>
                    <?php endif; ?>

                    <h3><?php echo h($item['nom']); ?></h3>
                    <p id="quantite-item-<?php echo (int) $item['idItem']; ?>">
                      Quantité possédée : <?php echo (int) $item['quantiteInventaire']; ?>
                    </p>
                    <p>Prix unitaire : <?php echo (int) $item['prix']; ?></p>
                  </a>

                  <?php if ($item['typeItem'] == 'P'): ?>
                    <?php potion_heal($pdo, $item['idItem'], $item['quantiteInventaire']); ?>
                  <?php endif; ?>

                  <?php if ($item['typeItem'] == 'S'): ?>
                    <?php sort_heal($pdo, $item['idItem'], $item['quantiteInventaire']); ?>
                  <?php endif; ?>

                  <form method="post" class="form-vente-auto" data-iditem="<?php echo (int) $item['idItem']; ?>">
                    <input type="hidden" name="idItem" value="<?php echo (int) $item['idItem']; ?>">

                    <div class="sell-panel">
                      <div class="sell-topline">
                        <span class="sell-label">Vente</span>
                        <span class="sell-owned">Max : <?php echo (int) $item['quantiteInventaire']; ?></span>
                      </div>

                      <div class="vente-ligne">
                        <label for="vente_<?php echo (int) $item['idItem']; ?>" class="sr-only">Quantité à vendre</label>

                        <div class="qte-box">
                          <span class="qte-prefix">Qté</span>
                          <input
                            id="vente_<?php echo (int) $item['idItem']; ?>"
                            class="input-vente"
                            type="number"
                            name="quantiteVente"
                            min="1"
                            max="<?php echo (int) $item['quantiteInventaire']; ?>"
                            value="1"
                            data-iditem="<?php echo (int) $item['idItem']; ?>"
                            data-prixvente="<?php echo (int) $prixVente; ?>"
                          >
                        </div>

                        <button type="submit" class="btn-panier btn-sell">
                          Vendre
                        </button>
                      </div>

                      <p class="vente-total" id="vente-total-<?php echo (int) $item['idItem']; ?>">
                        Gain estimé : <?php echo (int) $prixVente; ?> or
                      </p>
                    </div>
                  </form>
                </div>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      <?php endforeach; ?>

    <?php endif; ?>
  </main>

  <div id="modalConfirmation" class="modal-confirmation" aria-hidden="true">
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <div class="modal-header">
        <span class="modal-kicker">Confirmation</span>
        <h3 id="modalTitle" class="modal-title">Confirmer la vente</h3>
      </div>

      <div class="modal-body">
        <div class="modal-item-preview">
          <div>
            <div class="modal-item-name" id="modalNomItem"></div>
            <div class="modal-item-meta" id="modalQuantite"></div>
          </div>
          <div class="modal-gain-badge" id="modalGain"></div>
        </div>

        <div class="modal-warning">
          Cette action vendra l’objet immédiatement et ajoutera l’or correspondant à votre inventaire.
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-panier btn-annuler" id="btnAnnulerVente">Annuler</button>
        <button type="button" class="btn-panier btn-confirmer" id="btnConfirmerVente">Confirmer la vente</button>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const barreRecherche = document.getElementById('barreRecherche');
      const checkboxes = document.querySelectorAll('#filtres input[type="checkbox"]');
      const sections = document.querySelectorAll('.section-items');
      const messageInventaire = document.getElementById('messageInventaire');

      const modal = document.getElementById('modalConfirmation');
      const modalNomItem = document.getElementById('modalNomItem');
      const modalQuantite = document.getElementById('modalQuantite');
      const modalGain = document.getElementById('modalGain');
      const btnAnnulerVente = document.getElementById('btnAnnulerVente');
      const btnConfirmerVente = document.getElementById('btnConfirmerVente');

      let venteEnAttente = null;

      function afficherMessage(message, succes) {
        messageInventaire.textContent = message;
        messageInventaire.className = 'message-inventaire ' + (succes ? 'succes' : 'erreur');
      }

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

      function ouvrirModal(data) {
        venteEnAttente = data;
        modalNomItem.textContent = data.nomItem;
        modalQuantite.textContent = 'Quantité vendue : ' + data.quantite;
        modalGain.textContent = '+' + data.gainTotal + ' or';
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
      }

      function fermerModal() {
        venteEnAttente = null;
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
      }

      function executerVente() {
        if (!venteEnAttente) return;

        const input = venteEnAttente.input;
        const formData = new FormData();
        formData.append('idItem', venteEnAttente.idItem);
        formData.append('quantiteVente', venteEnAttente.quantite);

        input.disabled = true;

        fetch('vendre_item_ajax.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          input.disabled = false;
          fermerModal();

          if (!data.success) {
            afficherMessage(data.message, false);
            return;
          }

          const quantiteEl = document.getElementById('quantite-item-' + data.idItem);
          if (quantiteEl) {
            quantiteEl.textContent = 'Quantité possédée : ' + data.quantiteRestante;
          }

          const card = document.getElementById('item-' + data.idItem);

          if (data.quantiteRestante > 0) {
            input.max = data.quantiteRestante;
            input.value = 1;

            const totalEl = document.getElementById('vente-total-' + data.idItem);
            if (totalEl) {
              totalEl.textContent = 'Gain estimé : ' + data.profitUnitaire + ' or';
            }

            const maxLabel = card ? card.querySelector('.sell-owned') : null;
            if (maxLabel) {
              maxLabel.textContent = 'Max : ' + data.quantiteRestante;
            }
          } else if (card) {
            card.remove();
          }

          const goldElement = document.querySelector('.wallet-item.gold .wallet-value');
          if (goldElement) {
            goldElement.textContent = data.gold;
          }

          afficherMessage(
            'Vente confirmée : ' + data.quantiteVendue + ' item(s) pour ' + data.profitTotal + ' or.',
            true
          );
        })
        .catch(() => {
          input.disabled = false;
          fermerModal();
          afficherMessage('Erreur lors de la vente.', false);
        });
      }

      barreRecherche.addEventListener('input', appliquerFiltres);
      checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', appliquerFiltres);
      });
      appliquerFiltres();

      document.querySelectorAll('.input-vente').forEach((input) => {
        input.addEventListener('input', () => {
          const idItem = input.dataset.iditem;
          const prixVente = parseInt(input.dataset.prixvente || '0', 10);
          const totalEl = document.getElementById('vente-total-' + idItem);

          let quantite = parseInt(input.value || '1', 10);
          const max = parseInt(input.max || '1', 10);

          if (isNaN(quantite) || quantite < 1) {
            quantite = 1;
          }

          if (quantite > max) {
            quantite = max;
            input.value = max;
          }

          if (totalEl) {
            totalEl.textContent = 'Gain estimé : ' + (quantite * prixVente) + ' or';
          }
        });
      });

      document.querySelectorAll('.form-vente-auto').forEach((form) => {
        form.addEventListener('submit', (e) => {
          e.preventDefault();

          const input = form.querySelector('.input-vente');
          const idItem = form.dataset.iditem;
          const card = document.getElementById('item-' + idItem);
          const nomItem = card ? card.querySelector('h3').textContent.trim() : 'Item';

          let quantite = parseInt(input.value || '1', 10);
          const max = parseInt(input.max || '1', 10);
          const prixVente = parseInt(input.dataset.prixvente || '0', 10);

          if (isNaN(quantite) || quantite < 1) {
            quantite = 1;
          }

          if (quantite > max) {
            quantite = max;
            input.value = max;
          }

          const gainTotal = quantite * prixVente;

          ouvrirModal({
            idItem: idItem,
            nomItem: nomItem,
            quantite: quantite,
            gainTotal: gainTotal,
            input: input
          });
        });
      });

      btnAnnulerVente.addEventListener('click', fermerModal);
      btnConfirmerVente.addEventListener('click', executerVente);

      modal.addEventListener('click', function (e) {
        if (e.target === modal) {
          fermerModal();
        }
      });
    });
  </script>

  <?php include_once 'template/footer.php'; ?>
</body>
</html>
