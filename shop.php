<?php
require_once 'db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pagination settings
$items_per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'popular';
$min_price = $_GET['min_price'] ?? 0;
$max_price = $_GET['max_price'] ?? 1000;

// Build query
$where_conditions = ["quantity > 0"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(item_name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($category)) {
    $where_conditions[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

$where_conditions[] = "price BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;
$types .= "dd";

$where_clause = implode(" AND ", $where_conditions);

// Sorting
$order_by = match($sort) {
    'price_low' => 'price ASC',
    'price_high' => 'price DESC',
    'name' => 'item_name ASC',
    'newest' => 'created_at DESC',
    default => 'item_id DESC'
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - PharmaCare Medical E-Commerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .product-card {
            animation: fadeIn 0.5s ease-out;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .filter-sidebar {
            transition: transform 0.3s ease-in-out;
        }
        
        .filter-sidebar.hidden-mobile {
            transform: translateX(-100%);
        }
        
        @media (min-width: 768px) {
            .filter-sidebar.hidden-mobile {
                transform: translateX(0);
            }
        }
        
        .price-range-slider::-webkit-slider-thumb {
            appearance: none;
            width: 20px;
            height: 20px;
            background: #3b82f6;
            cursor: pointer;
            border-radius: 50%;
        }
        
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .badge-animate {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-purple-50 min-h-screen">
    
    <!-- Navigation Bar -->
    <nav class="sticky top-0 z-50 bg-white/90 backdrop-blur-md shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-20">
                <a href="index.php" class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-heartbeat text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">PharmaCare</h1>
                        <p class="text-xs text-gray-600 hidden md:block">Medical E-Commerce</p>
                    </div>
                </a>
                
                <div class="flex items-center space-x-4">
                    <button onclick="toggleCart()" class="relative p-3 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-xl hover:shadow-xl transition-all duration-300">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span id="cartCount" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 rounded-full text-xs font-bold flex items-center justify-center badge-animate">0</span>
                    </button>
                    
                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <a href="customer_dashboard.php" class="hidden md:flex items-center space-x-2 px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                            <i class="fas fa-user-circle text-xl"></i>
                            <span class="font-medium">Dashboard</span>
                        </a>
                    <?php else: ?>
                        <a href="customer_login.php" class="hidden md:flex items-center space-x-2 px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                            <i class="fas fa-sign-in-alt"></i>
                            <span class="font-medium">Login</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="bg-white border-b">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center space-x-2 text-sm">
                <a href="index.php" class="text-blue-600 hover:text-blue-700">Home</a>
                <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                <span class="text-gray-600">Shop</span>
                <?php if ($category): ?>
                    <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                    <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($category); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Filter Sidebar -->
            <aside id="filterSidebar" class="w-full lg:w-64 filter-sidebar">
                <div class="bg-white rounded-xl shadow-lg p-6 sticky top-24">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Filters</h2>
                        <button onclick="resetFilters()" class="text-sm text-blue-600 hover:text-blue-700">Reset All</button>
                    </div>

                    <!-- Search Filter -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="searchInput"
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Search products..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                onchange="applyFilters()"
                            >
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Categories</h3>
                        <div class="space-y-2">
                            <label class="flex items-center space-x-3 cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition">
                                <input type="radio" name="category" value="" <?php echo empty($category) ? 'checked' : ''; ?> onchange="applyFilters()" class="text-blue-600">
                                <span class="text-sm">All Products</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition">
                                <input type="radio" name="category" value="Pain Relief" <?php echo $category === 'Pain Relief' ? 'checked' : ''; ?> onchange="applyFilters()" class="text-blue-600">
                                <span class="text-sm">Pain Relief</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition">
                                <input type="radio" name="category" value="Antibiotic" <?php echo $category === 'Antibiotic' ? 'checked' : ''; ?> onchange="applyFilters()" class="text-blue-600">
                                <span class="text-sm">Antibiotics</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition">
                                <input type="radio" name="category" value="Vitamins" <?php echo $category === 'Vitamins' ? 'checked' : ''; ?> onchange="applyFilters()" class="text-blue-600">
                                <span class="text-sm">Vitamins</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition">
                                <input type="radio" name="category" value="Cold & Flu" <?php echo $category === 'Cold & Flu' ? 'checked' : ''; ?> onchange="applyFilters()" class="text-blue-600">
                                <span class="text-sm">Cold & Flu</span>
                            </label>
                        </div>
                    </div>

                    <!-- Price Range Filter -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Price Range</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Min: $<span id="minPriceDisplay"><?php echo $min_price; ?></span></span>
                                <span class="text-sm text-gray-600">Max: $<span id="maxPriceDisplay"><?php echo $max_price; ?></span></span>
                            </div>
                            <input 
                                type="range" 
                                id="minPrice" 
                                min="0" 
                                max="1000" 
                                step="10"
                                value="<?php echo $min_price; ?>"
                                class="w-full price-range-slider"
                                oninput="updatePriceRange()"
                            >
                            <input 
                                type="range" 
                                id="maxPrice" 
                                min="0" 
                                max="1000" 
                                step="10"
                                value="<?php echo $max_price; ?>"
                                class="w-full price-range-slider"
                                oninput="updatePriceRange()"
                            >
                            <button onclick="applyFilters()" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                                Apply Price Filter
                            </button>
                        </div>
                    </div>

                    <!-- Stock Status -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Availability</h3>
                        <label class="flex items-center space-x-3 cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition">
                            <input type="checkbox" class="text-blue-600" checked>
                            <span class="text-sm">In Stock Only</span>
                        </label>
                    </div>

                    <!-- Special Offers -->
                    <div class="bg-gradient-to-br from-orange-50 to-red-50 border border-orange-200 rounded-lg p-4">
                        <div class="flex items-center space-x-2 mb-2">
                            <i class="fas fa-tags text-orange-600"></i>
                            <h3 class="text-sm font-bold text-gray-900">Special Offers</h3>
                        </div>
                        <label class="flex items-center space-x-3 cursor-pointer hover:bg-white/50 p-2 rounded-lg transition">
                            <input type="checkbox" class="text-orange-600">
                            <span class="text-sm">On Sale</span>
                        </label>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1">
                <!-- Toolbar -->
                <div class="bg-white rounded-xl shadow-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex items-center space-x-4 w-full md:w-auto">
                            <button onclick="toggleFilters()" class="lg:hidden px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-filter mr-2"></i>Filters
                            </button>
                            <div class="text-sm text-gray-600">
                                Showing <span class="font-semibold">1-12</span> of <span class="font-semibold">347</span> products
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3 w-full md:w-auto">
                            <label class="text-sm text-gray-600 hidden md:block">Sort by:</label>
                            <select id="sortSelect" onchange="applyFilters()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                                <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name: A-Z</option>
                            </select>
                            
                            <div class="flex bg-gray-100 rounded-lg p-1">
                                <button onclick="setViewMode('grid')" id="gridViewBtn" class="px-3 py-2 rounded-lg bg-white shadow transition">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button onclick="setViewMode('list')" id="listViewBtn" class="px-3 py-2 rounded-lg transition">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div id="productsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <?php
                    // Fetch products from database
                    $conn = getDatabaseConnection();
                    
                    $query = "SELECT * FROM items WHERE $where_clause ORDER BY $order_by LIMIT ? OFFSET ?";
                    $stmt = $conn->prepare($query);
                    
                    if (!empty($params)) {
                        $params[] = $items_per_page;
                        $params[] = $offset;
                        $types .= "ii";
                        $stmt->bind_param($types, ...$params);
                    } else {
                        $stmt->bind_param("ii", $items_per_page, $offset);
                    }
                    
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($product = $result->fetch_assoc()):
                        $discount = rand(0, 30); // Simulated discount
                        $has_discount = $discount > 0;
                        $original_price = $product['price'];
                        $discounted_price = $has_discount ? $original_price * (1 - $discount / 100) : $original_price;
                        $stock_status = $product['quantity'] > 50 ? 'in-stock' : ($product['quantity'] > 0 ? 'low-stock' : 'out-of-stock');
                    ?>
                    
                    <!-- Product Card -->
                    <div class="product-card bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="relative">
                            <a href="product_details.php?id=<?php echo $product['item_id']; ?>">
                                <img src="https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=400" alt="<?php echo htmlspecialchars($product['item_name']); ?>" class="w-full h-48 object-cover">
                            </a>
                            
                            <?php if ($has_discount): ?>
                                <span class="absolute top-4 left-4 px-3 py-1 bg-red-500 text-white text-xs font-bold rounded-full">
                                    <?php echo $discount; ?>% OFF
                                </span>
                            <?php endif; ?>
                            
                            <div class="absolute top-4 right-4 space-y-2">
                                <button onclick="addToWishlist(<?php echo $product['item_id']; ?>)" class="w-10 h-10 bg-white rounded-full shadow-lg hover:bg-red-50 transition flex items-center justify-center">
                                    <i class="far fa-heart text-gray-600 hover:text-red-500"></i>
                                </button>
                                <a href="product_details.php?id=<?php echo $product['item_id']; ?>" class="w-10 h-10 bg-white rounded-full shadow-lg hover:bg-blue-50 transition flex items-center justify-center">
                                    <i class="fas fa-eye text-gray-600"></i>
                                </a>
                            </div>

                            <?php if ($stock_status === 'low-stock'): ?>
                                <div class="absolute bottom-4 left-4">
                                    <span class="px-3 py-1 bg-orange-500 text-white text-xs font-semibold rounded-full">
                                        Only <?php echo $product['quantity']; ?> left!
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-5">
                            <div class="mb-2">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                                    Pain Relief
                                </span>
                            </div>
                            
                            <a href="product_details.php?id=<?php echo $product['item_id']; ?>">
                                <h3 class="font-bold text-lg mb-2 text-gray-800 hover:text-blue-600 transition">
                                    <?php echo htmlspecialchars($product['item_name']); ?>
                                </h3>
                            </a>
                            
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                <?php echo htmlspecialchars($product['description'] ?? 'High-quality pharmaceutical product'); ?>
                            </p>
                            
                            <div class="flex items-center mb-3">
                                <div class="flex text-yellow-400 text-sm">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                                <span class="text-xs text-gray-500 ml-2">(<?php echo rand(50, 500); ?> reviews)</span>
                            </div>
                            
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <span class="text-2xl font-bold text-blue-600">$<?php echo number_format($discounted_price, 2); ?></span>
                                    <?php if ($has_discount): ?>
                                        <span class="text-sm text-gray-500 line-through ml-2">$<?php echo number_format($original_price, 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs <?php echo $stock_status === 'in-stock' ? 'text-green-600' : 'text-orange-600'; ?> font-semibold">
                                    <?php echo $stock_status === 'in-stock' ? 'In Stock' : 'Low Stock'; ?>
                                </span>
                            </div>
                            
                            <button onclick="addToCart(<?php echo $product['item_id']; ?>, '<?php echo addslashes($product['item_name']); ?>', <?php echo $discounted_price; ?>)" class="w-full py-3 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-xl hover:shadow-lg transition-all duration-300 transform hover:scale-105 font-semibold">
                                <i class="fas fa-cart-plus mr-2"></i>Add to Cart
                            </button>
                        </div>
                    </div>
                    
                    <?php endwhile; ?>
                    
                    <?php
                    $stmt->close();
                    closeDatabaseConnection($conn);
                    ?>
                </div>

                <!-- Pagination -->
                <div class="mt-12 flex items-center justify-center">
                    <nav class="flex items-center space-x-2">
                        <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg">1</button>
                        <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">2</button>
                        <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">3</button>
                        <span class="px-4 py-2 text-gray-500">...</span>
                        <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">12</button>
                        <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </nav>
                </div>
            </main>
        </div>
    </div>

    <!-- Shopping Cart Sidebar -->
    <div id="cartSidebar" class="fixed inset-y-0 right-0 w-full md:w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-50">
        <div class="h-full flex flex-col">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 flex justify-between items-center">
                <h2 class="text-xl font-bold">Shopping Cart</h2>
                <button onclick="toggleCart()" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <div id="cartItems" class="flex-1 overflow-y-auto p-6">
                <div class="text-center text-gray-500 py-12">
                    <i class="fas fa-shopping-cart text-6xl mb-4"></i>
                    <p>Your cart is empty</p>
                </div>
            </div>
            
            <div class="border-t p-6 space-y-4">
                <div class="flex justify-between text-lg font-bold">
                    <span>Total:</span>
                    <span id="cartTotal" class="text-blue-600">$0.00</span>
                </div>
                <button onclick="window.location.href='checkout.php'" class="w-full py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:shadow-lg transition font-semibold">
                    <i class="fas fa-lock mr-2"></i>Proceed to Checkout
                </button>
                <button onclick="toggleCart()" class="w-full py-3 border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition font-semibold">
                    Continue Shopping
                </button>
            </div>
        </div>
    </div>

    <script>
        let cart = JSON.parse(localStorage.getItem('pharmacare_cart') || '[]');
        let viewMode = 'grid';

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateCartDisplay();
            updateCartCount();
        });

        // Filter Functions
        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const category = document.querySelector('input[name="category"]:checked').value;
            const sort = document.getElementById('sortSelect').value;
            const minPrice = document.getElementById('minPrice').value;
            const maxPrice = document.getElementById('maxPrice').value;
            
            const url = new URL(window.location.href);
            url.searchParams.set('search', search);
            url.searchParams.set('category', category);
            url.searchParams.set('sort', sort);
            url.searchParams.set('min_price', minPrice);
            url.searchParams.set('max_price', maxPrice);
            
            window.location.href = url.toString();
        }

        function resetFilters() {
            window.location.href = 'shop.php';
        }

        function updatePriceRange() {
            const minPrice = document.getElementById('minPrice').value;
            const maxPrice = document.getElementById('maxPrice').value;
            document.getElementById('minPriceDisplay').textContent = minPrice;
            document.getElementById('maxPriceDisplay').textContent = maxPrice;
        }

        function toggleFilters() {
            const sidebar = document.getElementById('filterSidebar');
            sidebar.classList.toggle('hidden-mobile');
        }

        // View Mode Functions
        function setViewMode(mode) {
            viewMode = mode;
            const grid = document.getElementById('productsGrid');
            const gridBtn = document.getElementById('gridViewBtn');
            const listBtn = document.getElementById('listViewBtn');
            
            if (mode === 'grid') {
                grid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6';
                gridBtn.className = 'px-3 py-2 rounded-lg bg-white shadow transition';
                listBtn.className = 'px-3 py-2 rounded-lg transition';
            } else {
                grid.className = 'space-y-4';
                gridBtn.className = 'px-3 py-2 rounded-lg transition';
                listBtn.className = 'px-3 py-2 rounded-lg bg-white shadow transition';
            }
        }

        // Cart Functions
        function addToCart(id, name, price) {
            const existingItem = cart.find(item => item.id === id);
            
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({ id, name, price, quantity: 1 });
            }
            
            localStorage.setItem('pharmacare_cart', JSON.stringify(cart));
            updateCartDisplay();
            updateCartCount();
            showNotification('Added to cart!', 'success');
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            localStorage.setItem('pharmacare_cart', JSON.stringify(cart));
            updateCartDisplay();
            updateCartCount();
        }

        function updateQuantity(id, delta) {
            const item = cart.find(item => item.id === id);
            if (item) {
                item.quantity += delta;
                if (item.quantity <= 0) {
                    removeFromCart(id);
                } else {
                    localStorage.setItem('pharmacare_cart', JSON.stringify(cart));
                    updateCartDisplay();
                    updateCartCount();
                }
            }
        }

        function updateCartDisplay() {
            const cartContainer = document.getElementById('cartItems');
            
            if (cart.length === 0) {
                cartContainer.innerHTML = `
                    <div class="text-center text-gray-500 py-12">
                        <i class="fas fa-shopping-cart text-6xl mb-4"></i>
                        <p>Your cart is empty</p>
                    </div>
                `;
                document.getElementById('cartTotal').textContent = '$0.00';
                return;
            }

            cartContainer.innerHTML = cart.map(item => `
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg mb-3">
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900">${item.name}</h4>
                        <p class="text-sm text-gray-600">${item.price.toFixed(2)} each</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="updateQuantity(${item.id}, -1)" class="w-8 h-8 bg-red-100 text-red-600 rounded-lg hover:bg-red-200">
                            <i class="fas fa-minus text-xs"></i>
                        </button>
                        <span class="font-bold w-8 text-center">${item.quantity}</span>
                        <button onclick="updateQuantity(${item.id}, 1)" class="w-8 h-8 bg-green-100 text-green-600 rounded-lg hover:bg-green-200">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                        <button onclick="removeFromCart(${item.id})" class="w-8 h-8 bg-gray-200 text-gray-600 rounded-lg hover:bg-gray-300 ml-2">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            `).join('');

            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            document.getElementById('cartTotal').textContent = `${total.toFixed(2)}`;
        }

        function updateCartCount() {
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cartCount').textContent = count;
        }

        function toggleCart() {
            const sidebar = document.getElementById('cartSidebar');
            sidebar.classList.toggle('translate-x-full');
        }

        function addToWishlist(id) {
            showNotification('Added to wishlist!', 'success');
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-24 right-6 px-6 py-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white font-medium animate-fade-in`;
            notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'times'}-circle mr-2"></i>${message}`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    </script>
</body>
</html>
