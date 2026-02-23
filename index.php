<?php
// marketplace.php (sans JavaScript, 100% PHP)

declare(strict_types=1);
session_start();

$pageTitle = "Marché Mystique";

/**
 * Données de base (catalogue).
 * IMPORTANT: on ne stocke pas le stock ici pour de vrai si on veut qu'il varie.
 * On initialise le stock une seule fois dans $_SESSION.
 */
$items = [
  // Gold Tier Weapons
  1  => [ 'id'=>1,  'name'=>"Épée de Lumière",          'description'=>"Une lame légendaire qui brille d’une radiance sacrée. Elle tranche toute obscurité.", 'priceInBronze'=>550, 'tier'=>"gold",   'category'=>"Arme",    'imagePath'=>"public/images/sword-of-light.png" ],
  2  => [ 'id'=>2,  'name'=>"Bâton des Éléments",       'description'=>"Contrôlez le feu, l’eau, la terre et l’air grâce à ce puissant bâton arcanique.",           'priceInBronze'=>650, 'tier'=>"gold",   'category'=>"Arme",    'imagePath'=>"public/images/staff-of-elements.png" ],
  3  => [ 'id'=>3,  'name'=>"Arc Céleste",              'description'=>"Les flèches tirées par cet arc ne manquent jamais leur cible.",                             'priceInBronze'=>600, 'tier'=>"gold",   'category'=>"Arme",    'imagePath'=>"public/images/celestial-bow.png" ],

  // Gold Tier Armor
  4  => [ 'id'=>4,  'name'=>"Armure en Écailles de Dragon", 'description'=>"Forgée à partir des écailles d’un dragon ancien. Protection exceptionnelle.",         'priceInBronze'=>700, 'tier'=>"gold",   'category'=>"Armure",  'imagePath'=>"public/images/dragon-scale-plate.png" ],
  5  => [ 'id'=>5,  'name'=>"Robe Éthérée",             'description'=>"Tissée de magie pure. Rend le porteur résistant aux dégâts élémentaires.",               'priceInBronze'=>620, 'tier'=>"gold",   'category'=>"Armure",  'imagePath'=>"public/images/ethereal-robes.png" ],

  // Gold Tier Abilities
  6  => [ 'id'=>6,  'name'=>"Codex de Manipulation du Temps", 'description'=>"Un tome ancien contenant les secrets pour plier le temps.",                        'priceInBronze'=>800, 'tier'=>"gold",   'category'=>"Pouvoir", 'imagePath'=>"public/images/codex-of-time-bending.png", 'ability'=>"Ralentir le temps — Ralentit le temps pendant 10 secondes" ],
  7  => [ 'id'=>7,  'name'=>"Baguette du Phénix",       'description'=>"Faite d’une plume de phénix. Permet de revenir d’entre les morts.",                       'priceInBronze'=>750, 'tier'=>"gold",   'category'=>"Pouvoir", 'imagePath'=>"public/images/phoenix-wand.png",          'ability'=>"Renaissance — Revient à la vie une fois par jour" ],

  // Silver Tier Weapons
  8  => [ 'id'=>8,  'name'=>"Dague de Clair de Lune",   'description'=>"Une lame qui devient plus forte sous la lune. Parfaite pour les raids nocturnes.",      'priceInBronze'=>280, 'tier'=>"silver", 'category'=>"Arme",    'imagePath'=>"public/images/moonlight-dagger.png" ],
  9  => [ 'id'=>9,  'name'=>"Marteau du Tonnerre",      'description'=>"Chaque coup libère une puissante onde de choc tonitruante.",                              'priceInBronze'=>320, 'tier'=>"silver", 'category'=>"Arme",    'imagePath'=>"public/images/thunder-hammer.png" ],

  // Silver Tier Armor
  10 => [ 'id'=>10, 'name'=>"Cotte de Mailles en Mithril", 'description'=>"Légère mais incroyablement solide. Très bonne mobilité.",                               'priceInBronze'=>350, 'tier'=>"silver", 'category'=>"Armure",  'imagePath'=>"public/images/mithril-chain-mail.png" ],
  11 => [ 'id'=>11, 'name'=>"Cape des Ombres",          'description'=>"Permet de se fondre dans les ombres et de se déplacer sans être vu.",                     'priceInBronze'=>380, 'tier'=>"silver", 'category'=>"Armure",  'imagePath'=>"public/images/shadow-cloak.png" ],

  // Silver Tier Potions
  12 => [ 'id'=>12, 'name'=>"Élixir de Force",          'description'=>"Double votre force physique pendant une heure.",                                         'priceInBronze'=>150, 'tier'=>"silver", 'category'=>"Potion",  'imagePath'=>"public/images/elixir-of-strength.png" ],
  13 => [ 'id'=>13, 'name'=>"Potion de Soin Supérieure",'description'=>"Restaure instantanément la plupart des blessures et afflictions.",                         'priceInBronze'=>120, 'tier'=>"silver", 'category'=>"Potion",  'imagePath'=>"public/images/greater-healing-potion.png" ],

  // Silver Tier Abilities
  14 => [ 'id'=>14, 'name'=>"Tome de Télépathie",       'description'=>"Apprenez à lire les esprits et communiquer par télépathie.",                               'priceInBronze'=>290, 'tier'=>"silver", 'category'=>"Pouvoir", 'imagePath'=>"public/images/tome-of-telepathy.png",      'ability'=>"Lecture mentale — Lit les pensées à proximité" ],
  15 => [ 'id'=>15, 'name'=>"Codex de l’Illusionniste", 'description'=>"Maîtrisez l’art de créer des illusions puissantes.",                                      'priceInBronze'=>260, 'tier'=>"silver", 'category'=>"Pouvoir", 'imagePath'=>"public/images/illusionist-codex.png",      'ability'=>"Créer une illusion — Conjure des illusions réalistes" ],

  // Bronze Tier Weapons
  16 => [ 'id'=>16, 'name'=>"Épée en Fer",              'description'=>"Une lame fiable pour tout aventurier débutant.",                                         'priceInBronze'=>45,  'tier'=>"bronze", 'category'=>"Arme",    'imagePath'=>"public/images/iron-sword.png" ],
  17 => [ 'id'=>17, 'name'=>"Bâton en Bois",            'description'=>"Un bâton simple pour s’entraîner à la magie de base.",                                    'priceInBronze'=>30,  'tier'=>"bronze", 'category'=>"Arme",    'imagePath'=>"public/images/wooden-staff.png" ],

  // Bronze Tier Armor
  18 => [ 'id'=>18, 'name'=>"Armure de Cuir",           'description'=>"Protection de base pour les combats légers.",                                             'priceInBronze'=>50,  'tier'=>"bronze", 'category'=>"Armure",  'imagePath'=>"public/images/leather-armor.png" ],
  19 => [ 'id'=>19, 'name'=>"Robe en Tissu",            'description'=>"Des robes simples pour les apprentis mages.",                                              'priceInBronze'=>35,  'tier'=>"bronze", 'category'=>"Armure",  'imagePath'=>"public/images/cloth-roobe.png" ],

  // Bronze Tier Potions
  20 => [ 'id'=>20, 'name'=>"Potion de Soin Mineure",   'description'=>"Soigne les petites blessures. Indispensable pour tout aventurier.",                      'priceInBronze'=>8,   'tier'=>"bronze", 'category'=>"Potion",  'imagePath'=>"public/images/minor-healing-potion.png" ],
  21 => [ 'id'=>21, 'name'=>"Potion de Mana",           'description'=>"Restaure une petite quantité d’énergie magique.",                                          'priceInBronze'=>10,  'tier'=>"bronze", 'category'=>"Potion",  'imagePath'=>"public/images/mana-potion.png" ],
  22 => [ 'id'=>22, 'name'=>"Tonique d’Endurance",      'description'=>"Réduit la fatigue et redonne de l’énergie pendant un court moment.",                      'priceInBronze'=>12,  'tier'=>"bronze", 'category'=>"Potion",  'imagePath'=>"public/images/stamina-tonic.png" ],

  // Bronze Tier Abilities
  23 => [ 'id'=>23, 'name'=>"Baguette d’Apprenti",      'description'=>"Une baguette pour apprendre des sorts simples.",                                          'priceInBronze'=>25,  'tier'=>"bronze", 'category'=>"Pouvoir", 'imagePath'=>"public/images/apprentice-wand.png",        'ability'=>"Étincelle — Crée une petite flamme ou une lumière" ],
  24 => [ 'id'=>24, 'name'=>"Parchemin de Bouclier Mineur",'description'=>"Apprenez à créer une petite barrière protectrice.",                                      'priceInBronze'=>18,  'tier'=>"bronze", 'category'=>"Pouvoir", 'imagePath'=>"public/images/scroll-of-minor-shield.png", 'ability'=>"Bouclier mineur — Barrière protectrice faible" ],
];

/**
 * Stock initial (tu peux ajuster les chiffres).
 * On le garde en session pour simuler une boutique sans BD.
 */
if (!isset($_SESSION['stock'])) {
  $_SESSION['stock'] = [];
  foreach ($items as $id => $_item) {
    // Exemple: plus de stock pour bronze, moins pour gold
    $_SESSION['stock'][$id] = match ($_item['tier']) {
      'gold'   => 3,
      'silver' => 6,
      default  => 12,
    };
  }
}

/**
 * Panier en session
 * structure: $_SESSION['cart'][itemId] = quantity
 */
if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function formatPrice(int $bronze): string {
  $gold = intdiv($bronze, 100);
  $remaining = $bronze % 100;
  $silver = intdiv($remaining, 10);
  $bronzeLeft = $remaining % 10;

  $parts = [];
  if ($gold > 0) $parts[] = "{$gold}g";
  if ($silver > 0) $parts[] = "{$silver}s";
  if ($bronzeLeft > 0 || count($parts) === 0) $parts[] = "{$bronzeLeft}b";
  return implode(' ', $parts);
}
function itemTypeLabel(array $item): string {
  // Ton data utilise category: "Arme", "Armure", "Potion", "Pouvoir"
  return match ($item['category'] ?? '') {
    'Pouvoir' => 'Sort',
    default => (string)($item['category'] ?? 'Type'),
  };
}

function itemTypeClass(array $item): string {
  // Classes CSS stables (sans accents)
  return match ($item['category'] ?? '') {
    'Arme'   => 'type-weapon',
    'Armure' => 'type-armor',
    'Potion' => 'type-potion',
    'Pouvoir'=> 'type-spell',
    default  => 'type-other',
  };
}

function cartTotals(array $items): array {
  $totalItems = 0;
  $totalPrice = 0;

  foreach ($_SESSION['cart'] as $id => $qty) {
    $qty = (int)$qty;
    if ($qty <= 0) continue;
    if (!isset($items[$id])) continue;

    $totalItems += $qty;
    $totalPrice += $items[$id]['priceInBronze'] * $qty;
  }

  return [$totalItems, $totalPrice];
}

/**
 * Actions POST (add / remove / qty +/-)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $itemId = isset($_POST['itemId']) ? (int)$_POST['itemId'] : 0;

  // Pour revenir sur la même page avec les mêmes filtres
  $redirect = $_POST['redirect'] ?? 'marketplace.php';
  if (!str_starts_with($redirect, 'marketplace.php')) {
    $redirect = 'marketplace.php';
  }

  if ($itemId > 0 && isset($items[$itemId])) {
    if ($action === 'add') {
      // ajouter 1, si stock dispo
      if (($_SESSION['stock'][$itemId] ?? 0) > 0) {
        $_SESSION['cart'][$itemId] = (int)($_SESSION['cart'][$itemId] ?? 0) + 1;
        $_SESSION['stock'][$itemId] -= 1;
      } else {
        $_SESSION['flash'] = "Rupture de stock !";
      }
    }

    if ($action === 'remove') {
      // retirer complètement et remettre le stock
      $qty = (int)($_SESSION['cart'][$itemId] ?? 0);
      if ($qty > 0) {
        $_SESSION['stock'][$itemId] = (int)($_SESSION['stock'][$itemId] ?? 0) + $qty;
      }
      unset($_SESSION['cart'][$itemId]);
    }

    if ($action === 'qty') {
      $delta = isset($_POST['delta']) ? (int)$_POST['delta'] : 0;
      $currentQty = (int)($_SESSION['cart'][$itemId] ?? 0);

      if ($delta > 0) {
        if (($_SESSION['stock'][$itemId] ?? 0) > 0) {
          $_SESSION['cart'][$itemId] = $currentQty + 1;
          $_SESSION['stock'][$itemId] -= 1;
        } else {
          $_SESSION['flash'] = "Rupture de stock !";
        }
      } elseif ($delta < 0) {
        if ($currentQty > 0) {
          $_SESSION['cart'][$itemId] = $currentQty - 1;
          $_SESSION['stock'][$itemId] += 1;
          if ($_SESSION['cart'][$itemId] <= 0) unset($_SESSION['cart'][$itemId]);
        }
      }
    }
  }

  header("Location: {$redirect}");
  exit;
}

/**
 * GET: recherche + filtre
 */
$currentFilter = $_GET['type'] ?? 'all';
$currentFilter = in_array($currentFilter, ['all','arme','armure','potion','sort'], true) ? $currentFilter : 'all';

$currentSearch = trim((string)($_GET['q'] ?? ''));
$searchLower = mb_strtolower($currentSearch);

/**
 * Filtrage items
 */
$filteredItems = array_filter($items, function($item) use ($currentFilter, $searchLower) {

  // Map category -> filter key
  $typeKey = match ($item['category'] ?? '') {
    'Arme'   => 'arme',
    'Armure' => 'armure',
    'Potion' => 'potion',
    'Pouvoir'=> 'sort',
    default  => 'other',
  };

  $matchesType = ($currentFilter === 'all' || $typeKey === $currentFilter);

  if ($searchLower === '') return $matchesType;

  $hay = mb_strtolower($item['name'].' '.$item['description'].' '.$item['category']);
  $matchesSearch = (mb_strpos($hay, $searchLower) !== false);

  return $matchesType && $matchesSearch;
});

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

[$totalItems, $totalPrice] = cartTotals($items);

// construit une URL "courante" pour redirect après POST (pour garder q/tier)
$query = http_build_query([
  'q' => $currentSearch !== '' ? $currentSearch : null,
  'type' => $currentFilter !== 'all' ? $currentFilter : null,
]);
$redirectUrl = 'marketplace.php' . ($query ? ('?'.$query) : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="public/css/style.css">
  <title><?= h($pageTitle) ?></title>
</head>

<body>
  <div class="container">
    <header class="header">
      <div class="header-left">
        <h1>Marché Mystique</h1>
        <p class="subtitle">Notre bibliothèque des objets magiques et puissants</p>
      </div>

      <div class="header-right">
        <!-- Pas de JS: on laisse le bouton mais il ne fait rien / ou lien vers une page login -->
        <button class="login-btn" type="button" disabled title="Pas disponible pour le moment">Connexion</button>
      </div>
    </header>

    <?php if ($flash): ?>
      <div style="margin: 12px 0; padding: 10px 12px; border-radius: 10px; background: #fff7ed; color:#9a3412;">
        <?= h($flash) ?>
      </div>
    <?php endif; ?>

    <div class="currency-guide">
      <h3>Conversion des devises</h3>
      <div class="currency-grid">
        <div class="currency-item gold">
          <span class="currency-icon"></span>
          <div>
            <div style="color: #92400e; font-weight: 600;">1 Or</div>
            <div style="font-size: 0.75rem; color: #6b7280;">= 10 Argent = 100 Bronze</div>
          </div>
        </div>
        <div class="currency-item silver">
          <span class="currency-icon"></span>
          <div>
            <div style="color: #374151; font-weight: 600;">1 Argent</div>
            <div style="font-size: 0.75rem; color: #6b7280;">= 10 Bronze</div>
          </div>
        </div>
        <div class="currency-item bronze">
          <span class="currency-icon"></span>
          <div>
            <div style="color: #9a3412; font-weight: 600;">1 Bronze</div>
            <div style="font-size: 0.75rem; color: #6b7280;">Devise de base</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recherche + filtres (GET) -->
    <div class="search-section">
      <form class="search-box" method="get" action="marketplace.php">
        <span class="search-icon">🔍</span>
        <input type="text" id="searchInput" name="q" value="<?= h($currentSearch) ?>" placeholder="Rechercher des objets magiques...">
       <?php if ($currentFilter !== 'all'): ?>
  <input type="hidden" name="type" value="<?= h($currentFilter) ?>">
<?php endif; ?>
        <button class="clear-btn" type="submit" name="q" value="" aria-label="Effacer">×</button>
      </form>

  <div class="filter-buttons">
  <a class="filter-btn <?= $currentFilter==='all'?'active':'' ?> all"
     href="marketplace.php<?= $currentSearch!=='' ? ('?'.http_build_query(['q'=>$currentSearch])) : '' ?>">
     Tous
  </a>

  <a class="filter-btn <?= $currentFilter==='arme'?'active':'' ?> arme"
     href="marketplace.php?<?= h(http_build_query(['type'=>'arme','q'=>$currentSearch!==''?$currentSearch:null])) ?>">
     Armes
  </a>

  <a class="filter-btn <?= $currentFilter==='armure'?'active':'' ?> armure"
     href="marketplace.php?<?= h(http_build_query(['type'=>'armure','q'=>$currentSearch!==''?$currentSearch:null])) ?>">
     Armures
  </a>

  <a class="filter-btn <?= $currentFilter==='potion'?'active':'' ?> potion"
     href="marketplace.php?<?= h(http_build_query(['type'=>'potion','q'=>$currentSearch!==''?$currentSearch:null])) ?>">
     Potions
  </a>

  <a class="filter-btn <?= $currentFilter==='sort'?'active':'' ?> sort"
     href="marketplace.php?<?= h(http_build_query(['type'=>'sort','q'=>$currentSearch!==''?$currentSearch:null])) ?>">
     Sorts
  </a>
</div>
    </div>

    <div class="items-count" id="itemsCount">
      Affichage : <?= count($filteredItems) ?> objet<?= count($filteredItems) !== 1 ? 's' : '' ?>
    </div>

    <?php if (count($filteredItems) === 0): ?>
      <div class="no-results" id="noResults">
        <h3>Aucun objet ne correspond à votre recherche</h3>
        <p>Essayez de modifier les filtres ou le texte recherché</p>
      </div>
    <?php else: ?>
      <div class="items-grid" id="itemsGrid">
        <?php foreach ($filteredItems as $item): 
          $stock = (int)($_SESSION['stock'][$item['id']] ?? 0);
          $out = ($stock <= 0);
        ?>
          <div class="item-card <?= h(itemTypeClass($item)) ?>">
            <div class="item-image <?= h($item['tier']) ?>">
              <img src="<?= h($item['imagePath']) ?>" alt="<?= h($item['name']) ?>">
            </div>

            <div class="item-content">
              <div class="item-header">
                <h3 class="item-name"><?= h($item['name']) ?></h3>
                <span class="item-tier <?= h(itemTypeClass($item)) ?>"><?= h(itemTypeLabel($item)) ?></span>
              </div>

              <p class="item-description"><?= h($item['description']) ?></p>

              <?php if (!empty($item['ability'])): ?>
                <p class="item-ability">Pouvoir : <?= h($item['ability']) ?></p>
              <?php endif; ?>

              <div class="item-category"><?= h($item['category']) ?></div>

              <div class="item-stock <?= $out ? 'out' : '' ?>">
                Stock : <?= $out ? 'Rupture' : $stock ?>
              </div>

              <div class="item-footer">
                <span class="item-price <?= h($item['tier']) ?>"><?= h(formatPrice((int)$item['priceInBronze'])) ?></span>

                <form method="post" style="display:inline;">
                  <input type="hidden" name="action" value="add">
                  <input type="hidden" name="itemId" value="<?= (int)$item['id'] ?>">
                  <input type="hidden" name="redirect" value="<?= h($redirectUrl) ?>">
                  <button class="add-btn" type="submit" <?= $out ? 'disabled' : '' ?>>
                    🛒 Ajouter
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Pas de JS => bouton panier devient un ancre vers #cartSidebar -->
  <a class="cart-toggle" id="cartToggle" href="#cartSidebar">
    🛒
    <?php if ($totalItems > 0): ?>
      <span class="cart-badge" id="cartBadge"><?= (int)$totalItems ?></span>
    <?php else: ?>
      <span class="cart-badge" id="cartBadge" style="display:none;">0</span>
    <?php endif; ?>
  </a>

  <!-- Overlay ne peut pas être interactif sans JS, on le garde pour le layout -->
  <div class="cart-overlay" id="cartOverlay"></div>

  <!-- Sidebar panier (toujours visible selon ton CSS; sans JS, tu peux l'afficher avec :target si tu veux) -->
  <div class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
      <h2>Panier</h2>
      <!-- Sans JS: retour haut de page -->
      <a class="close-btn" id="closeCart" href="#">×</a>
    </div>

    <div class="cart-items" id="cartItems">
      <?php if (empty($_SESSION['cart'])): ?>
        <div class="cart-empty">
          <div class="cart-empty-icon">🛒</div>
          <p>Votre panier est vide</p>
        </div>
      <?php else: ?>
        <?php foreach ($_SESSION['cart'] as $id => $qty):
          $id = (int)$id;
          $qty = (int)$qty;
          if ($qty <= 0 || !isset($items[$id])) continue;
          $it = $items[$id];
        ?>
          <div class="cart-item">
            <div class="cart-item-header">
              <div class="cart-item-info">
                <h4><?= h($it['name']) ?></h4>
                <div class="cart-item-tags">
                  <span class="cart-item-tag <?= h(itemTypeClass($it)) ?>"><?= h(itemTypeLabel($it)) ?></span>
                  <span class="cart-item-category"><?= h($it['category']) ?></span>
                </div>
              </div>

              <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="itemId" value="<?= $id ?>">
                <input type="hidden" name="redirect" value="<?= h($redirectUrl) ?>#cartSidebar">
                <button class="delete-btn" type="submit" title="Retirer">🗑️</button>
              </form>
            </div>

            <div class="cart-item-footer">
              <div class="quantity-controls">
                <form method="post" style="display:inline;">
                  <input type="hidden" name="action" value="qty">
                  <input type="hidden" name="itemId" value="<?= $id ?>">
                  <input type="hidden" name="delta" value="-1">
                  <input type="hidden" name="redirect" value="<?= h($redirectUrl) ?>#cartSidebar">
                  <button class="qty-btn" type="submit">-</button>
                </form>

                <span class="quantity"><?= $qty ?></span>

                <form method="post" style="display:inline;">
                  <input type="hidden" name="action" value="qty">
                  <input type="hidden" name="itemId" value="<?= $id ?>">
                  <input type="hidden" name="delta" value="1">
                  <input type="hidden" name="redirect" value="<?= h($redirectUrl) ?>#cartSidebar">
                  <button class="qty-btn" type="submit">+</button>
                </form>
              </div>

              <span class="cart-item-price"><?= h(formatPrice((int)$it['priceInBronze'] * $qty)) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if ($totalItems > 0): ?>
      <div class="cart-footer" id="cartFooter">
        <div class="cart-total">
          <span class="cart-total-label">Total :</span>
          <span class="cart-total-price" id="cartTotal"><?= h(formatPrice((int)$totalPrice)) ?></span>
        </div>
        <button class="checkout-btn" type="button" disabled title="Pas disponible sans backend paiement">Commander</button>
      </div>
    <?php else: ?>
      <div class="cart-footer" id="cartFooter" style="display:none;"></div>
    <?php endif; ?>
  </div>

</body>
</html>