<?php
//index.php
$pageTitle = "Mystical Marketplace";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #faf5ff 0%, #eff6ff 50%, #fdf2f8 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 2rem;
        }

        h1 {
            font-size: 2.5rem;
            background: linear-gradient(to right, #9333ea, #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #6b7280;
        }

        .currency-guide {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 2px solid #bfdbfe;
        }

        .currency-guide h3 {
            color: #374151;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .currency-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .currency-item {
            padding: 0.75rem;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .currency-item.gold { background: #fef3c7; }
        .currency-item.silver { background: #f3f4f6; }
        .currency-item.bronze { background: #fed7aa; }

        .currency-icon { font-size: 2rem; }

        .search-section {
            margin-bottom: 2rem;
        }

        .search-box {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 2.5rem;
            border: 2px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #2563eb;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .clear-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 1.25rem;
            display: none;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
            background: #e5e7eb;
            color: #374151;
        }

        .filter-btn:hover {
            background: #d1d5db;
        }

        .filter-btn.active {
            color: white;
        }

        .filter-btn.active.all { background: #2563eb; }
        .filter-btn.active.gold { background: #eab308; }
        .filter-btn.active.silver { background: #6b7280; }
        .filter-btn.active.bronze { background: #ea580c; }

        .items-count {
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 5rem;
        }

        .item-card {
            background: white;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .item-card:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.15);
        }

        .item-card.gold { border: 2px solid #eab308; }
        .item-card.silver { border: 2px solid #9ca3af; }
        .item-card.bronze { border: 2px solid #ea580c; }

        .item-image {
            height: 8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .item-image.gold { background: linear-gradient(135deg, #fbbf24, #d97706); }
        .item-image.silver { background: linear-gradient(135deg, #d1d5db, #6b7280); }
        .item-image.bronze { background: linear-gradient(135deg, #fb923c, #c2410c); }

        .item-content {
            padding: 1rem;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        .item-name {
            font-size: 1.125rem;
            font-weight: 500;
        }

        .item-tier {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: white;
        }

        .item-tier.gold { background: linear-gradient(135deg, #fbbf24, #d97706); }
        .item-tier.silver { background: linear-gradient(135deg, #d1d5db, #6b7280); }
        .item-tier.bronze { background: linear-gradient(135deg, #fb923c, #c2410c); }

        .item-description {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .item-ability {
            color: #9333ea;
            font-size: 0.75rem;
            font-style: italic;
            margin-bottom: 0.5rem;
        }

        .item-category {
            color: #9ca3af;
            font-size: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .item-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .item-price {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .item-price.gold { color: #d97706; }
        .item-price.silver { color: #6b7280; }
        .item-price.bronze { color: #ea580c; }

        .add-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .add-btn:hover {
            background: #1d4ed8;
        }

        .cart-toggle {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            background: #2563eb;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 100;
            transition: background 0.2s;
        }

        .cart-toggle:hover {
            background: #1d4ed8;
        }

        .cart-badge {
            position: absolute;
            top: -0.5rem;
            right: -0.5rem;
            background: #ef4444;
            color: white;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        .cart-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 150;
        }

        .cart-overlay.active {
            display: block;
        }

        .cart-sidebar {
            position: fixed;
            top: 0;
            right: -100%;
            height: 100%;
            width: 100%;
            max-width: 28rem;
            background: white;
            z-index: 200;
            transition: right 0.3s;
            display: flex;
            flex-direction: column;
            box-shadow: -4px 0 6px rgba(0, 0, 0, 0.1);
        }

        .cart-sidebar.active {
            right: 0;
        }

        .cart-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-header h2 {
            font-size: 1.5rem;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
        }

        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .cart-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #9ca3af;
        }

        .cart-empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .cart-item {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
        }

        .cart-item-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .cart-item-info h4 {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .cart-item-tags {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .cart-item-tag {
            font-size: 0.75rem;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
        }

        .cart-item-tag.gold { background: #fef3c7; color: #92400e; }
        .cart-item-tag.silver { background: #e5e7eb; color: #374151; }
        .cart-item-tag.bronze { background: #fed7aa; color: #9a3412; }

        .cart-item-category {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .delete-btn {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 1rem;
        }

        .cart-item-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.75rem;
        }

        .quantity-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .qty-btn {
            width: 1.75rem;
            height: 1.75rem;
            background: #e5e7eb;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .qty-btn:hover {
            background: #d1d5db;
        }

        .quantity {
            width: 2rem;
            text-align: center;
        }

        .cart-item-price {
            color: #2563eb;
            font-weight: 600;
        }

        .cart-footer {
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .cart-total-label {
            font-size: 1.125rem;
        }

        .cart-total-price {
            font-size: 1.5rem;
            color: #2563eb;
            font-weight: 600;
        }

        .checkout-btn {
            width: 100%;
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.2s;
        }

        .checkout-btn:hover {
            background: #1d4ed8;
        }

        .no-results {
            text-align: center;
            padding: 4rem 1rem;
            color: #6b7280;
        }

        .no-results h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .no-results p {
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Mystical Marketplace</h1>
            <p class="subtitle">Discover rare and magical items from across the realm</p>
        </header>

        <div class="currency-guide">
            <h3>💰 Currency Conversion</h3>
            <div class="currency-grid">
                <div class="currency-item gold">
                    <span class="currency-icon">🪙</span>
                    <div>
                        <div style="color: #92400e; font-weight: 600;">1 Gold</div>
                        <div style="font-size: 0.75rem; color: #6b7280;">= 10 Silver = 100 Bronze</div>
                    </div>
                </div>
                <div class="currency-item silver">
                    <span class="currency-icon">⚪</span>
                    <div>
                        <div style="color: #374151; font-weight: 600;">1 Silver</div>
                        <div style="font-size: 0.75rem; color: #6b7280;">= 10 Bronze</div>
                    </div>
                </div>
                <div class="currency-item bronze">
                    <span class="currency-icon">🟤</span>
                    <div>
                        <div style="color: #9a3412; font-weight: 600;">1 Bronze</div>
                        <div style="font-size: 0.75rem; color: #6b7280;">Base currency</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="search-section">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInput" placeholder="Search for magical items...">
                <button class="clear-btn" id="clearBtn">×</button>
            </div>
            <div class="filter-buttons">
                <button class="filter-btn active all" data-tier="all">All Items</button>
                <button class="filter-btn gold" data-tier="gold">Gold</button>
                <button class="filter-btn silver" data-tier="silver">Silver</button>
                <button class="filter-btn bronze" data-tier="bronze">Bronze</button>
            </div>
        </div>

        <div class="items-count" id="itemsCount">Showing 24 items</div>
        <div class="items-grid" id="itemsGrid"></div>
        <div class="no-results" id="noResults" style="display: none;">
            <h3>No items found matching your search</h3>
            <p>Try adjusting your filters or search query</p>
        </div>
    </div>

    <button class="cart-toggle" id="cartToggle">
        🛒
        <span class="cart-badge" id="cartBadge" style="display: none;">0</span>
    </button>

    <div class="cart-overlay" id="cartOverlay"></div>
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h2>Shopping Cart</h2>
            <button class="close-btn" id="closeCart">×</button>
        </div>
        <div class="cart-items" id="cartItems">
            <div class="cart-empty">
                <div class="cart-empty-icon">🛒</div>
                <p>Your cart is empty</p>
            </div>
        </div>
        <div class="cart-footer" id="cartFooter" style="display: none;">
            <div class="cart-total">
                <span class="cart-total-label">Total:</span>
                <span class="cart-total-price" id="cartTotal">0b</span>
            </div>
            <button class="checkout-btn">Checkout</button>
        </div>
    </div>

    <script>
        // Items data
        const items = [
            // Gold Tier Weapons
            { id: 1, name: "Sword of Light", description: "A legendary blade that shines with holy radiance. Cuts through any darkness.", priceInBronze: 550, tier: "gold", category: "Weapon", icon: "⚔️" },
            { id: 2, name: "Staff of Elements", description: "Control fire, water, earth, and air with this powerful arcane staff.", priceInBronze: 650, tier: "gold", category: "Weapon", icon: "⚔️" },
            { id: 3, name: "Celestial Bow", description: "Arrows shot from this bow never miss their intended target.", priceInBronze: 600, tier: "gold", category: "Weapon", icon: "⚔️" },

            // Gold Tier Armor
            { id: 4, name: "Dragon Scale Plate", description: "Forged from the scales of an ancient dragon. Provides exceptional protection.", priceInBronze: 700, tier: "gold", category: "Armor", icon: "🛡️" },
            { id: 5, name: "Ethereal Robes", description: "Woven from pure magic. Makes the wearer resistant to all elemental damage.", priceInBronze: 620, tier: "gold", category: "Armor", icon: "🛡️" },

            // Gold Tier Abilities
            { id: 6, name: "Codex of Timebending", description: "An ancient tome containing the secrets of manipulating time itself.", priceInBronze: 800, tier: "gold", category: "Ability", icon: "📜", ability: "Slow Time - Slow down time for 10 seconds" },
            { id: 7, name: "Phoenix Wand", description: "A wand crafted from a phoenix feather. Grants resurrection abilities.", priceInBronze: 750, tier: "gold", category: "Ability", icon: "📜", ability: "Rebirth - Return from death once per day" },

            // Silver Tier Weapons
            { id: 8, name: "Moonlight Dagger", description: "A blade that grows stronger under moonlight. Perfect for night raids.", priceInBronze: 280, tier: "silver", category: "Weapon", icon: "⚔️" },
            { id: 9, name: "Thunder Hammer", description: "Each strike releases a powerful shockwave of thunder.", priceInBronze: 320, tier: "silver", category: "Weapon", icon: "⚔️" },

            // Silver Tier Armor
            { id: 10, name: "Mithril Chainmail", description: "Lightweight yet incredibly strong. Offers great mobility in combat.", priceInBronze: 350, tier: "silver", category: "Armor", icon: "🛡️" },
            { id: 11, name: "Shadow Cloak", description: "Grants the ability to blend into shadows and move unseen.", priceInBronze: 380, tier: "silver", category: "Armor", icon: "🛡️" },

            // Silver Tier Potions
            { id: 12, name: "Elixir of Strength", description: "Doubles your physical strength for one hour.", priceInBronze: 150, tier: "silver", category: "Potion", icon: "🧪" },
            { id: 13, name: "Greater Healing Potion", description: "Instantly restores most injuries and ailments.", priceInBronze: 120, tier: "silver", category: "Potion", icon: "🧪" },

            // Silver Tier Abilities
            { id: 14, name: "Tome of Telepathy", description: "Learn to read minds and communicate telepathically.", priceInBronze: 290, tier: "silver", category: "Ability", icon: "📜", ability: "Mind Reading - Read thoughts of nearby creatures" },
            { id: 15, name: "Illusionist's Codex", description: "Master the art of creating powerful illusions.", priceInBronze: 260, tier: "silver", category: "Ability", icon: "📜", ability: "Create Illusion - Conjure realistic illusions" },

            // Bronze Tier Weapons
            { id: 16, name: "Iron Sword", description: "A reliable blade for any beginning adventurer.", priceInBronze: 45, tier: "bronze", category: "Weapon", icon: "⚔️" },
            { id: 17, name: "Wooden Staff", description: "A simple staff for practicing basic magic.", priceInBronze: 30, tier: "bronze", category: "Weapon", icon: "⚔️" },

            // Bronze Tier Armor
            { id: 18, name: "Leather Armor", description: "Basic protection for light combat situations.", priceInBronze: 50, tier: "bronze", category: "Armor", icon: "🛡️" },
            { id: 19, name: "Cloth Robe", description: "Simple robes for novice magic users.", priceInBronze: 35, tier: "bronze", category: "Armor", icon: "🛡️" },

            // Bronze Tier Potions
            { id: 20, name: "Minor Healing Potion", description: "Heals minor wounds and scrapes. Every adventurer needs these.", priceInBronze: 8, tier: "bronze", category: "Potion", icon: "🧪" },
            { id: 21, name: "Mana Potion", description: "Restores a small amount of magical energy.", priceInBronze: 10, tier: "bronze", category: "Potion", icon: "🧪" },
            { id: 22, name: "Stamina Tonic", description: "Reduces fatigue and restores energy for a short duration.", priceInBronze: 12, tier: "bronze", category: "Potion", icon: "🧪" },

            // Bronze Tier Abilities
            { id: 23, name: "Apprentice Wand", description: "A beginner's wand for learning basic spells.", priceInBronze: 25, tier: "bronze", category: "Ability", icon: "📜", ability: "Spark - Create a small flame or light" },
            { id: 24, name: "Scroll of Minor Shield", description: "Learn to create a small protective barrier.", priceInBronze: 18, tier: "bronze", category: "Ability", icon: "📜", ability: "Minor Shield - Creates a weak protective barrier" }
        ];

        let cart = [];
        let currentFilter = 'all';
        let currentSearch = '';

        // Currency conversion
        function formatPrice(bronze) {
            const gold = Math.floor(bronze / 100);
            const remaining = bronze % 100;
            const silver = Math.floor(remaining / 10);
            const bronzeLeft = remaining % 10;

            const parts = [];
            if (gold > 0) parts.push(`${gold}g`);
            if (silver > 0) parts.push(`${silver}s`);
            if (bronzeLeft > 0 || parts.length === 0) parts.push(`${bronzeLeft}b`);

            return parts.join(' ');
        }

        // Filter and render items
        function renderItems() {
            const filtered = items.filter(item => {
                const matchesTier = currentFilter === 'all' || item.tier === currentFilter;
                const matchesSearch = currentSearch === '' ||
                    item.name.toLowerCase().includes(currentSearch) ||
                    item.description.toLowerCase().includes(currentSearch) ||
                    item.category.toLowerCase().includes(currentSearch);
                return matchesTier && matchesSearch;
            });

            const grid = document.getElementById('itemsGrid');
            const noResults = document.getElementById('noResults');
            const itemsCount = document.getElementById('itemsCount');

            if (filtered.length === 0) {
                grid.style.display = 'none';
                noResults.style.display = 'block';
                itemsCount.style.display = 'none';
            } else {
                grid.style.display = 'grid';
                noResults.style.display = 'none';
                itemsCount.style.display = 'block';
                itemsCount.textContent = `Showing ${filtered.length} item${filtered.length !== 1 ? 's' : ''}`;

                grid.innerHTML = filtered.map(item => `
                    <div class="item-card ${item.tier}">
                        <div class="item-image ${item.tier}">${item.icon}</div>
                        <div class="item-content">
                            <div class="item-header">
                                <h3 class="item-name">${item.name}</h3>
                                <span class="item-tier ${item.tier}">${item.tier}</span>
                            </div>
                            <p class="item-description">${item.description}</p>
                            ${item.ability ? `<p class="item-ability">Ability: ${item.ability}</p>` : ''}
                            <div class="item-category">${item.category}</div>
                            <div class="item-footer">
                                <span class="item-price ${item.tier}">${formatPrice(item.priceInBronze)}</span>
                                <button class="add-btn" onclick="addToCart(${item.id})">
                                    🛒 Add
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        }

        // Add to cart
        function addToCart(itemId) {
            const item = items.find(i => i.id === itemId);
            const cartItem = cart.find(c => c.id === itemId);

            if (cartItem) {
                cartItem.quantity++;
            } else {
                cart.push({ ...item, quantity: 1 });
            }

            updateCart();
        }

        // Remove from cart
        function removeFromCart(itemId) {
            cart = cart.filter(item => item.id !== itemId);
            updateCart();
        }

        // Update quantity
        function updateQuantity(itemId, delta) {
            const cartItem = cart.find(c => c.id === itemId);
            if (cartItem) {
                cartItem.quantity += delta;
                if (cartItem.quantity <= 0) {
                    removeFromCart(itemId);
                } else {
                    updateCart();
                }
            }
        }

        // Update cart display
        function updateCart() {
            const cartItems = document.getElementById('cartItems');
            const cartBadge = document.getElementById('cartBadge');
            const cartFooter = document.getElementById('cartFooter');
            const cartTotal = document.getElementById('cartTotal');

            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            const totalPrice = cart.reduce((sum, item) => sum + (item.priceInBronze * item.quantity), 0);

            if (totalItems > 0) {
                cartBadge.textContent = totalItems;
                cartBadge.style.display = 'flex';
            } else {
                cartBadge.style.display = 'none';
            }

            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div class="cart-empty">
                        <div class="cart-empty-icon">🛒</div>
                        <p>Your cart is empty</p>
                    </div>
                `;
                cartFooter.style.display = 'none';
            } else {
                cartItems.innerHTML = cart.map(item => `
                    <div class="cart-item">
                        <div class="cart-item-header">
                            <div class="cart-item-info">
                                <h4>${item.name}</h4>
                                <div class="cart-item-tags">
                                    <span class="cart-item-tag ${item.tier}">${item.tier}</span>
                                    <span class="cart-item-category">${item.category}</span>
                                </div>
                            </div>
                            <button class="delete-btn" onclick="removeFromCart(${item.id})">🗑️</button>
                        </div>
                        <div class="cart-item-footer">
                            <div class="quantity-controls">
                                <button class="qty-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                                <span class="quantity">${item.quantity}</span>
                                <button class="qty-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                            </div>
                            <span class="cart-item-price">${formatPrice(item.priceInBronze * item.quantity)}</span>
                        </div>
                    </div>
                `).join('');
                cartFooter.style.display = 'block';
                cartTotal.textContent = formatPrice(totalPrice);
            }
        }

        // Event listeners
        document.getElementById('searchInput').addEventListener('input', (e) => {
            currentSearch = e.target.value.toLowerCase();
            document.getElementById('clearBtn').style.display = currentSearch ? 'block' : 'none';
            renderItems();
        });

        document.getElementById('clearBtn').addEventListener('click', () => {
            document.getElementById('searchInput').value = '';
            currentSearch = '';
            document.getElementById('clearBtn').style.display = 'none';
            renderItems();
        });

        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentFilter = btn.dataset.tier;
                renderItems();
            });
        });

        document.getElementById('cartToggle').addEventListener('click', () => {
            document.getElementById('cartSidebar').classList.add('active');
            document.getElementById('cartOverlay').classList.add('active');
        });

        document.getElementById('closeCart').addEventListener('click', () => {
            document.getElementById('cartSidebar').classList.remove('active');
            document.getElementById('cartOverlay').classList.remove('active');
        });

        document.getElementById('cartOverlay').addEventListener('click', () => {
            document.getElementById('cartSidebar').classList.remove('active');
            document.getElementById('cartOverlay').classList.remove('active');
        });

        // Initial render
        renderItems();
    </script>
</body>
</html>