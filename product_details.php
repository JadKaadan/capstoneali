<?php
require_once 'db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header("Location: shop.php");
    exit();
}

// Fetch product details
$conn = getDatabaseConnection();
$stmt = $conn->prepare("SELECT * FROM items WHERE item_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: shop.php");
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Simulated data
$discount = rand(10, 30);
$has_discount = $discount > 0;
$original_price = $product['price'];
$discounted_price = $has_discount ? $original_price * (1 - $discount / 100) : $original_price;
$stock_status = $product['quantity'] > 50 ? 'in-stock' : ($product['quantity'] > 0 ? 'low-stock' : 'out-of-stock');

// Fetch related products
$related_stmt = $conn->prepare("SELECT * FROM items WHERE item_id != ? AND quantity > 0 ORDER BY RAND() LIMIT 4");
$related_stmt->bind_param("i", $product_id);
$related_stmt->execute();
$related_products = $related_stmt->get_result();
$related_stmt->close();

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['item_name']); ?> - PharmaCare</title>
    <meta name="description" content="<?php echo htmlspecialchars($product['description'] ?? 'Premium pharmaceutical product'); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        .image-gallery-thumb {
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .image-gallery-thumb:hover {
            transform: scale(1.05);
            border-color: #3b82f6;
        }
        
        .image-gallery-thumb.active {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .quantity-input::-webkit-inner-spin-button,
        .quantity-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease-out;
        }
        
        .tab-button {
            position: relative;
            transition: all 0.3s;
        }
        
        .tab-button.active {
            color: #3b82f6;
        }
        
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            border-radius: 2px 2px 0 0;
        }
        
        .zoom-container {
            overflow: hidden;
            cursor: zoom-in;
        }
        
        .zoom-image {
            transition: transform 0.3s ease;
        }
        
        .zoom-container:hover .zoom-image {
            transform: scale(1.5);
        }
        
        .review-star {
            color: #d1d5db;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .review-star.filled {
            color: #fbbf24;
        }
        
        .review-star:hover,
        .review-star:hover ~ .review-star {
            color: #fbbf24;
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
                        <span id="cartCount" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 rounded-full text-xs font-bold flex items-center justify-center">0</span>
                    </button>
                    
                    <a href="shop.php" class="hidden md:flex items-center space-x-2 px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        <i class="fas fa-arrow-left"></i>
                        <span class="font-medium">Back to Shop</span>
                    </a>
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
                <a href="shop.php" class="text-blue-600 hover:text-blue-700">Shop</a>
                <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                <span class="text-gray-600">Pain Relief</span>
                <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($product['item_name']); ?></span>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-12">
            
            <!-- Product Images -->
            <div class="fade-in">
                <!-- Main Image -->
                <div class="bg-white rounded-2xl shadow-xl p-6 mb-4 zoom-container">
                    <img id="mainImage" src="https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=600" alt="<?php echo htmlspecialchars($product['item_name']); ?>" class="w-full h-96 object-contain rounded-lg zoom-image">
                </div>
                
                <!-- Thumbnail Gallery -->
                <div class="grid grid-cols-4 gap-4">
                    <div class="image-gallery-thumb active bg-white rounded-lg p-3 border-2 border-gray-200" onclick="changeImage(this, 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=600')">
                        <img src="https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=200" alt="View 1" class="w-full h-20 object-contain rounded">
                    </div>
                    <div class="image-gallery-thumb bg-white rounded-lg p-3 border-2 border-gray-200" onclick="changeImage(this, 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=600')">
                        <img src="https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=200" alt="View 2" class="w-full h-20 object-contain rounded">
                    </div>
                    <div class="image-gallery-thumb bg-white rounded-lg p-3 border-2 border-gray-200" onclick="changeImage(this, 'https://images.unsplash.com/photo-1585435557343-3b092031a831?w=600')">
                        <img src="https://images.unsplash.com/photo-1585435557343-3b092031a831?w=200" alt="View 3" class="w-full h-20 object-contain rounded">
                    </div>
                    <div class="image-gallery-thumb bg-white rounded-lg p-3 border-2 border-gray-200" onclick="changeImage(this, 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=600')">
                        <img src="https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=200" alt="View 4" class="w-full h-20 object-contain rounded">
                    </div>
                </div>

                <!-- Trust Badges -->
                <div class="mt-6 grid grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 text-center">
                        <i class="fas fa-shield-alt text-green-600 text-2xl mb-2"></i>
                        <p class="text-xs font-semibold text-gray-800">100% Authentic</p>
                    </div>
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 text-center">
                        <i class="fas fa-truck text-blue-600 text-2xl mb-2"></i>
                        <p class="text-xs font-semibold text-gray-800">Fast Delivery</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 text-center">
                        <i class="fas fa-undo text-purple-600 text-2xl mb-2"></i>
                        <p class="text-xs font-semibold text-gray-800">Easy Returns</p>
                    </div>
                </div>
            </div>

            <!-- Product Info -->
            <div class="fade-in">
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <!-- Product Title and Rating -->
                    <div class="mb-6">
                        <div class="flex items-center space-x-2 mb-3">
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-semibold rounded-full">Pain Relief</span>
                            <?php if ($has_discount): ?>
                                <span class="px-3 py-1 bg-red-500 text-white text-sm font-bold rounded-full">
                                    <?php echo $discount; ?>% OFF
                                </span>
                            <?php endif; ?>
                            <?php if ($stock_status === 'in-stock'): ?>
                                <span class="px-3 py-1 bg-green-100 text-green-700 text-sm font-semibold rounded-full">
                                    <i class="fas fa-check-circle mr-1"></i>In Stock
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-orange-100 text-orange-700 text-sm font-semibold rounded-full">
                                    <i class="fas fa-exclamation-circle mr-1"></i>Low Stock
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                            <?php echo htmlspecialchars($product['item_name']); ?>
                        </h1>
                        
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="flex text-yellow-400 text-lg">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                            <span class="text-sm text-gray-600">(4.5/5 based on 247 reviews)</span>
                        </div>

                        <p class="text-gray-600 leading-relaxed">
                            <?php echo htmlspecialchars($product['description'] ?? 'Fast-acting pain relief tablets for effective management of headaches, muscle aches, and fever. Trusted by healthcare professionals worldwide.'); ?>
                        </p>
                    </div>

                    <!-- Price -->
                    <div class="border-t border-b border-gray-200 py-6 mb-6">
                        <div class="flex items-baseline space-x-4">
                            <span class="text-4xl font-bold text-blue-600">$<?php echo number_format($discounted_price, 2); ?></span>
                            <?php if ($has_discount): ?>
                                <span class="text-2xl text-gray-500 line-through">$<?php echo number_format($original_price, 2); ?></span>
                                <span class="px-3 py-1 bg-green-100 text-green-700 text-sm font-bold rounded-full">
                                    Save $<?php echo number_format($original_price - $discounted_price, 2); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>Inclusive of all taxes. Free shipping on orders above $50
                        </p>
                    </div>

                    <!-- Quantity Selector -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Quantity:</label>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center border-2 border-gray-300 rounded-lg">
                                <button onclick="decreaseQty()" class="px-4 py-3 hover:bg-gray-100 transition">
                                    <i class="fas fa-minus text-gray-600"></i>
                                </button>
                                <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['quantity']; ?>" class="quantity-input w-16 text-center font-bold text-lg border-0 focus:outline-none" readonly>
                                <button onclick="increaseQty()" class="px-4 py-3 hover:bg-gray-100 transition">
                                    <i class="fas fa-plus text-gray-600"></i>
                                </button>
                            </div>
                            <span class="text-sm text-gray-600">
                                <?php echo $product['quantity']; ?> units available
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-4 mb-6">
                        <button onclick="addToCartDetails()" class="w-full py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-lg font-bold rounded-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-cart-plus mr-2"></i>Add to Cart
                        </button>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <button onclick="buyNow()" class="py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition">
                                <i class="fas fa-bolt mr-2"></i>Buy Now
                            </button>
                            <button onclick="addToWishlist(<?php echo $product_id; ?>)" class="py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition">
                                <i class="far fa-heart mr-2"></i>Wishlist
                            </button>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-6">
                        <h3 class="font-bold text-gray-900 mb-4">Product Features:</h3>
                        <ul class="space-y-3">
                            <li class="flex items-start space-x-3">
                                <i class="fas fa-check-circle text-green-600 mt-1"></i>
                                <span class="text-gray-700">Fast-acting formula for quick relief</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <i class="fas fa-check-circle text-green-600 mt-1"></i>
                                <span class="text-gray-700">Suitable for adults and children 12+</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <i class="fas fa-check-circle text-green-600 mt-1"></i>
                                <span class="text-gray-700">No prescription required</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <i class="fas fa-check-circle text-green-600 mt-1"></i>
                                <span class="text-gray-700">FDA approved and clinically tested</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Tabs -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-12 fade-in">
            <!-- Tab Headers -->
            <div class="flex border-b border-gray-200 mb-6">
                <button class="tab-button active px-6 py-4 font-semibold" onclick="switchTab('description')">
                    Description
                </button>
                <button class="tab-button px-6 py-4 font-semibold" onclick="switchTab('usage')">
                    Usage & Dosage
                </button>
                <button class="tab-button px-6 py-4 font-semibold" onclick="switchTab('ingredients')">
                    Ingredients
                </button>
                <button class="tab-button px-6 py-4 font-semibold" onclick="switchTab('reviews')">
                    Reviews (247)
                </button>
            </div>

            <!-- Tab Content -->
            <div id="description" class="tab-content active">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Product Description</h3>
                <div class="prose max-w-none">
                    <p class="text-gray-700 leading-relaxed mb-4">
                        <?php echo htmlspecialchars($product['item_name']); ?> is a trusted over-the-counter medication designed to provide fast and effective relief from various types of pain and discomfort. Whether you're dealing with headaches, muscle aches, joint pain, or fever, this medication offers a reliable solution backed by years of clinical research and real-world use.
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Our formula is carefully crafted to deliver maximum efficacy while minimizing potential side effects. Each tablet contains precisely measured active ingredients that work synergistically to target pain at its source, providing relief that can last for hours.
                    </p>
                    <h4 class="text-xl font-bold text-gray-900 mb-3 mt-6">Key Benefits:</h4>
                    <ul class="list-disc list-inside space-y-2 text-gray-700">
                        <li>Rapid onset of action - relief starts within 15-30 minutes</li>
                        <li>Long-lasting effect - up to 6 hours of pain relief</li>
                        <li>Gentle on the stomach when taken as directed</li>
                        <li>Suitable for multiple types of pain</li>
                        <li>Available without prescription</li>
                    </ul>
                </div>
            </div>

            <div id="usage" class="tab-content">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Usage Instructions & Dosage</h3>
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mr-3 mt-1"></i>
                        <p class="text-sm text-yellow-800">
                            <strong>Important:</strong> Always read the label and follow the directions. If symptoms persist, consult your healthcare professional.
                        </p>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <h4 class="text-lg font-bold text-gray-900 mb-3">Adults and Children 12 years and over:</h4>
                        <ul class="list-disc list-inside space-y-2 text-gray-700">
                            <li>Take 1-2 tablets every 4-6 hours as needed</li>
                            <li>Do not exceed 8 tablets in 24 hours</li>
                            <li>Swallow whole with water</li>
                            <li>Can be taken with or without food</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-bold text-gray-900 mb-3">Children under 12 years:</h4>
                        <p class="text-gray-700">Consult a doctor before use.</p>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-bold text-gray-900 mb-3">Precautions:</h4>
                        <ul class="list-disc list-inside space-y-2 text-gray-700">
                            <li>Do not use if you are allergic to any of the ingredients</li>
                            <li>Consult your doctor if you are pregnant or breastfeeding</li>
                            <li>Avoid alcohol while taking this medication</li>
                            <li>Do not drive or operate machinery if you feel drowsy</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="ingredients" class="tab-content">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Active Ingredients</h3>
                <div class="bg-blue-50 rounded-lg p-6 mb-6">
                    <h4 class="font-bold text-gray-900 mb-3">Each Tablet Contains:</h4>
                    <ul class="space-y-2">
                        <li class="flex justify-between py-2 border-b border-blue-200">
                            <span class="font-semibold text-gray-900">Paracetamol</span>
                            <span class="text-gray-700">500mg</span>
                        </li>
                    </ul>
                </div>
                
                <h4 class="text-lg font-bold text-gray-900 mb-3">Inactive Ingredients:</h4>
                <p class="text-gray-700">
                    Microcrystalline cellulose, starch, povidone, stearic acid, magnesium stearate, purified water.
                </p>
                
                <div class="mt-6 bg-gray-50 rounded-lg p-6">
                    <h4 class="text-lg font-bold text-gray-900 mb-3">Allergen Information:</h4>
                    <p class="text-gray-700">
                        This product does not contain lactose, gluten, sugar, artificial colors, or preservatives.
                    </p>
                </div>
            </div>

            <div id="reviews" class="tab-content">
                <div class="flex flex-col lg:flex-row gap-8 mb-8">
                    <!-- Rating Overview -->
                    <div class="lg:w-1/3">
                        <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-6 text-center">
                            <div class="text-5xl font-bold text-gray-900 mb-2">4.5</div>
                            <div class="flex justify-center text-yellow-400 text-xl mb-2">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                            <p class="text-gray-600">Based on 247 reviews</p>
                            
                            <div class="mt-6 space-y-2">
                                <div class="flex items-center text-sm">
                                    <span class="w-12 text-gray-600">5★</span>
                                    <div class="flex-1 h-2 bg-gray-200 rounded-full mx-2">
                                        <div class="h-2 bg-green-500 rounded-full" style="width: 70%"></div>
                                    </div>
                                    <span class="w-12 text-right text-gray-600">173</span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <span class="w-12 text-gray-600">4★</span>
                                    <div class="flex-1 h-2 bg-gray-200 rounded-full mx-2">
                                        <div class="h-2 bg-green-400 rounded-full" style="width: 20%"></div>
                                    </div>
                                    <span class="w-12 text-right text-gray-600">49</span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <span class="w-12 text-gray-600">3★</span>
                                    <div class="flex-1 h-2 bg-gray-200 rounded-full mx-2">
                                        <div class="h-2 bg-yellow-400 rounded-full" style="width: 6%"></div>
                                    </div>
                                    <span class="w-12 text-right text-gray-600">15</span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <span class="w-12 text-gray-600">2★</span>
                                    <div class="flex-1 h-2 bg-gray-200 rounded-full mx-2">
                                        <div class="h-2 bg-orange-400 rounded-full" style="width: 3%"></div>
                                    </div>
                                    <span class="w-12 text-right text-gray-600">7</span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <span class="w-12 text-gray-600">1★</span>
                                    <div class="flex-1 h-2 bg-gray-200 rounded-full mx-2">
                                        <div class="h-2 bg-red-400 rounded-full" style="width: 1%"></div>
                                    </div>
                                    <span class="w-12 text-right text-gray-600">3</span>
                                </div>
                            </div>
                        </div>
                        
                        <button onclick="showReviewForm()" class="w-full mt-4 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition">
                            <i class="fas fa-pencil-alt mr-2"></i>Write a Review
                        </button>
                    </div>
                    
                    <!-- Reviews List -->
                    <div class="lg:w-2/3 space-y-6">
                        <!-- Review 1 -->
                        <div class="bg-white border border-gray-200 rounded-xl p-6">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                        JD
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900">John Davis</h4>
                                        <p class="text-sm text-gray-500">Verified Purchase</p>
                                    </div>
                                </div>
                                <span class="text-sm text-gray-500">2 days ago</span>
                            </div>
                            <div class="flex text-yellow-400 mb-3">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <h5 class="font-bold text-gray-900 mb-2">Excellent product, fast relief!</h5>
                            <p class="text-gray-700 mb-3">
                                This medication worked wonders for my headache. I felt relief within 20 minutes and it lasted for several hours. Very impressed with the quality and effectiveness. Will definitely purchase again!
                            </p>
                            <div class="flex items-center space-x-4 text-sm">
                                <button class="text-gray-600 hover:text-blue-600">
                                    <i class="far fa-thumbs-up mr-1"></i>Helpful (24)
                                </button>
                                <button class="text-gray-600 hover:text-blue-600">
                                    <i class="far fa-comment mr-1"></i>Reply
                                </button>
                            </div>
                        </div>

                        <!-- Review 2 -->
                        <div class="bg-white border border-gray-200 rounded-xl p-6">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                                        SM
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900">Sarah Martinez</h4>
                                        <p class="text-sm text-gray-500">Verified Purchase</p>
                                    </div>
                                </div>
                                <span class="text-sm text-gray-500">1 week ago</span>
                            </div>
                            <div class="flex text-yellow-400 mb-3">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="far fa-star"></i>
                            </div>
                            <h5 class="font-bold text-gray-900 mb-2">Good value for money</h5>
                            <p class="text-gray-700 mb-3">
                                Works well for general pain relief. The price is reasonable and it's effective. Delivery was also very fast. One star less because the packaging could be better.
                            </p>
                            <div class="flex items-center space-x-4 text-sm">
                                <button class="text-gray-600 hover:text-blue-600">
                                    <i class="far fa-thumbs-up mr-1"></i>Helpful (15)
                                </button>
                                <button class="text-gray-600 hover:text-blue-600">
                                    <i class="far fa-comment mr-1"></i>Reply
                                </button>
                            </div>
                        </div>

                        <!-- Review 3 -->
                        <div class="bg-white border border-gray-200 rounded-xl p-6">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-500 rounded-full flex items-center justify-center text-white font-bold">
                                        EW
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900">Emily Wilson</h4>
                                        <p class="text-sm text-gray-500">Verified Purchase</p>
                                    </div>
                                </div>
                                <span class="text-sm text-gray-500">2 weeks ago</span>
                            </div>
                            <div class="flex text-yellow-400 mb-3">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <h5 class="font-bold text-gray-900 mb-2">Highly recommended!</h5>
                            <p class="text-gray-700 mb-3">
                                I always keep this in my medicine cabinet. It's reliable, effective, and works exactly as described. The customer service from PharmaCare was also excellent when I had questions about dosage.
                            </p>
                            <div class="flex items-center space-x-4 text-sm">
                                <button class="text-gray-600 hover:text-blue-600">
                                    <i class="far fa-thumbs-up mr-1"></i>Helpful (31)
                                </button>
                                <button class="text-gray-600 hover:text-blue-600">
                                    <i class="far fa-comment mr-1"></i>Reply
                                </button>
                            </div>
                        </div>

                        <!-- Load More Button -->
                        <button class="w-full py-3 border-2 border-gray-300 rounded-xl hover:bg-gray-50 transition font-semibold">
                            Load More Reviews
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <div class="mb-12">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-3xl font-bold text-gray-900">Related Products</h2>
                <a href="shop.php" class="text-blue-600 hover:text-blue-700 font-semibold">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php while ($related = $related_products->fetch_assoc()): 
                    $rel_discount = rand(10, 25);
                    $rel_price = $related['price'] * (1 - $rel_discount / 100);
                ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition">
                    <div class="relative">
                        <img src="https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=400" alt="<?php echo htmlspecialchars($related['item_name']); ?>" class="w-full h-48 object-cover">
                        <span class="absolute top-4 left-4 px-3 py-1 bg-red-500 text-white text-xs font-bold rounded-full">
                            <?php echo $rel_discount; ?>% OFF
                        </span>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-lg mb-2 text-gray-800">
                            <?php echo htmlspecialchars($related['item_name']); ?>
                        </h3>
                        <div class="flex text-yellow-400 text-sm mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <span class="text-xl font-bold text-blue-600">$<?php echo number_format($rel_price, 2); ?></span>
                                <span class="text-sm text-gray-500 line-through ml-2">$<?php echo number_format($related['price'], 2); ?></span>
                            </div>
                        </div>
                        <a href="product_details.php?id=<?php echo $related['item_id']; ?>" class="block w-full py-2 bg-blue-600 text-white text-center rounded-lg hover:bg-blue-700 transition font-semibold">
                            View Product
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
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

    <!-- Review Form Modal -->
    <div id="reviewModal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto m-4">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 flex justify-between items-center rounded-t-2xl">
                <h2 class="text-2xl font-bold">Write a Review</h2>
                <button onclick="closeReviewForm()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
            </div>
            <form class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Your Rating *</label>
                    <div class="flex space-x-2 text-3xl">
                        <i class="review-star fas fa-star" onclick="setRating(1)"></i>
                        <i class="review-star fas fa-star" onclick="setRating(2)"></i>
                        <i class="review-star fas fa-star" onclick="setRating(3)"></i>
                        <i class="review-star fas fa-star" onclick="setRating(4)"></i>
                        <i class="review-star fas fa-star" onclick="setRating(5)"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Review Title *</label>
                    <input type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Sum up your experience">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Your Review *</label>
                    <textarea required rows="5" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Tell us about your experience with this product"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Your Name *</label>
                    <input type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Enter your name">
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="verifiedPurchase" class="rounded text-blue-600">
                    <label for="verifiedPurchase" class="ml-2 text-sm text-gray-700">I am a verified purchaser</label>
                </div>
                <div class="flex space-x-4">
                    <button type="button" onclick="closeReviewForm()" class="flex-1 py-3 border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition font-semibold">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let cart = JSON.parse(localStorage.getItem('pharmacare_cart') || '[]');
        const maxQuantity = <?php echo $product['quantity']; ?>;
        const productId = <?php echo $product_id; ?>;
        const productName = <?php echo json_encode($product['item_name']); ?>;
        const productPrice = <?php echo $discounted_price; ?>;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateCartDisplay();
            updateCartCount();
        });

        // Image Gallery
        function changeImage(thumb, imageUrl) {
            document.querySelectorAll('.image-gallery-thumb').forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
            document.getElementById('mainImage').src = imageUrl;
        }

        // Quantity Controls
        function increaseQty() {
            const input = document.getElementById('quantity');
            const current = parseInt(input.value);
            if (current < maxQuantity) {
                input.value = current + 1;
            }
        }

        function decreaseQty() {
            const input = document.getElementById('quantity');
            const current = parseInt(input.value);
            if (current > 1) {
                input.value = current - 1;
            }
        }

        // Tab Switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // Cart Functions
        function addToCartDetails() {
            const quantity = parseInt(document.getElementById('quantity').value);
            const existingItem = cart.find(item => item.id === productId);
            
            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                cart.push({ 
                    id: productId, 
                    name: productName, 
                    price: productPrice, 
                    quantity: quantity 
                });
            }
            
            localStorage.setItem('pharmacare_cart', JSON.stringify(cart));
            updateCartDisplay();
            updateCartCount();
            showNotification(`Added ${quantity} item(s) to cart!`, 'success');
            toggleCart();
        }

        function buyNow() {
            addToCartDetails();
            window.location.href = 'checkout.php';
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
                        <p class="text-sm text-gray-600">${item.price.toFixed(2)} × ${item.quantity}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="font-bold text-blue-600">${(item.price * item.quantity).toFixed(2)}</span>
                        <button onclick="removeFromCart(${item.id})" class="w-8 h-8 bg-red-100 text-red-600 rounded-lg hover:bg-red-200">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            `).join('');

            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            document.getElementById('cartTotal').textContent = `${total.toFixed(2)}`;
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            localStorage.setItem('pharmacare_cart', JSON.stringify(cart));
            updateCartDisplay();
            updateCartCount();
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

        // Review Functions
        function showReviewForm() {
            document.getElementById('reviewModal').classList.remove('hidden');
            document.getElementById('reviewModal').classList.add('flex');
        }

        function closeReviewForm() {
            document.getElementById('reviewModal').classList.add('hidden');
            document.getElementById('reviewModal').classList.remove('flex');
        }

        function setRating(rating) {
            const stars = document.querySelectorAll('.review-star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('filled');
                } else {
                    star.classList.remove('filled');
                }
            });
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-24 right-6 px-6 py-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white font-medium fade-in`;
            notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'times'}-circle mr-2"></i>${message}`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    </script>
</body>
</html>
