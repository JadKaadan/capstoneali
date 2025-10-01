<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced POS System - PharmaCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .receipt-paper {
            background: linear-gradient(to bottom, #ffffff 0%, #f9f9f9 100%);
            font-family: 'Courier New', monospace;
        }
        @media print {
            .no-print { display: none; }
            .receipt-paper { box-shadow: none; }
        }
        .item-added { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-purple-50 min-h-screen">
    <!-- Header -->
    <nav class="bg-white shadow-lg border-b sticky top-0 z-50">
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-cash-register text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold">Point of Sale System</h1>
                        <p class="text-xs text-gray-500">Fast & Secure Transactions</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-semibold">Cashier: Dr. John Doe</p>
                        <p class="text-xs text-gray-500">Terminal #001</p>
                    </div>
                    <button onclick="window.location.href='pharmacist_dashboard.php'" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-arrow-left mr-2"></i>Dashboard
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Section - Product Selection -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Search and Quick Actions -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex gap-4 mb-6">
                        <div class="flex-1 relative">
                            <input 
                                type="text" 
                                id="productSearch" 
                                placeholder="Search product by name, barcode, or scan..." 
                                class="w-full pl-12 pr-4 py-4 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                                onkeyup="searchProducts(this.value)"
                                onkeypress="handleSearchEnter(event)"
                            >
                            <i class="fas fa-search absolute left-4 top-5 text-gray-400 text-xl"></i>
                        </div>
                        <button onclick="openBarcodeScanner()" class="px-6 py-4 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg hover:shadow-lg transition">
                            <i class="fas fa-barcode text-2xl"></i>
                        </button>
                    </div>

                    <!-- Quick Action Buttons -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <button onclick="filterByCategory('pain relief')" class="px-4 py-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition font-medium">
                            <i class="fas fa-pills mr-2"></i>Pain Relief
                        </button>
                        <button onclick="filterByCategory('antibiotic')" class="px-4 py-3 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition font-medium">
                            <i class="fas fa-capsules mr-2"></i>Antibiotics
                        </button>
                        <button onclick="filterByCategory('vitamin')" class="px-4 py-3 bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 transition font-medium">
                            <i class="fas fa-tablets mr-2"></i>Vitamins
                        </button>
                        <button onclick="showAllProducts()" class="px-4 py-3 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 transition font-medium">
                            <i class="fas fa-th mr-2"></i>All Products
                        </button>
                    </div>
                </div>

                <!-- Product Grid -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold mb-4">Available Products</h3>
                    <div id="productGrid" class="grid grid-cols-2 md:grid-cols-3 gap-4 max-h-[600px] overflow-y-auto">
                        <!-- Product Cards -->
                        <div class="product-card border-2 border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-lg transition cursor-pointer" onclick="addToCart(1, 'Paracetamol 500mg', 2.99, 247)">
                            <div class="w-16 h-16 mx-auto mb-3 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-pills text-blue-600 text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-center mb-1">Paracetamol 500mg</h4>
                            <p class="text-xs text-gray-500 text-center mb-2">Pain Relief</p>
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-green-600">$2.99</span>
                                <span class="text-xs text-gray-500">Stock: 247</span>
                            </div>
                        </div>

                        <div class="product-card border-2 border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-lg transition cursor-pointer" onclick="addToCart(2, 'Aspirin 100mg', 3.49, 18)">
                            <div class="w-16 h-16 mx-auto mb-3 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-capsules text-green-600 text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-center mb-1">Aspirin 100mg</h4>
                            <p class="text-xs text-gray-500 text-center mb-2">Pain Relief</p>
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-green-600">$3.49</span>
                                <span class="text-xs text-orange-500">Stock: 18</span>
                            </div>
                        </div>

                        <div class="product-card border-2 border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-lg transition cursor-pointer" onclick="addToCart(3, 'Amoxicillin 250mg', 5.99, 156)">
                            <div class="w-16 h-16 mx-auto mb-3 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-tablets text-purple-600 text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-center mb-1">Amoxicillin 250mg</h4>
                            <p class="text-xs text-gray-500 text-center mb-2">Antibiotic</p>
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-green-600">$5.99</span>
                                <span class="text-xs text-gray-500">Stock: 156</span>
                            </div>
                        </div>

                        <div class="product-card border-2 border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-lg transition cursor-pointer" onclick="addToCart(4, 'Ibuprofen 400mg', 4.29, 89)">
                            <div class="w-16 h-16 mx-auto mb-3 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-pills text-red-600 text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-center mb-1">Ibuprofen 400mg</h4>
                            <p class="text-xs text-gray-500 text-center mb-2">Pain Relief</p>
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-green-600">$4.29</span>
                                <span class="text-xs text-gray-500">Stock: 89</span>
                            </div>
                        </div>

                        <div class="product-card border-2 border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-lg transition cursor-pointer" onclick="addToCart(5, 'Vitamin C 1000mg', 8.99, 234)">
                            <div class="w-16 h-16 mx-auto mb-3 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-tablets text-yellow-600 text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-center mb-1">Vitamin C 1000mg</h4>
                            <p class="text-xs text-gray-500 text-center mb-2">Vitamin</p>
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-green-600">$8.99</span>
                                <span class="text-xs text-gray-500">Stock: 234</span>
                            </div>
                        </div>

                        <div class="product-card border-2 border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-lg transition cursor-pointer" onclick="addToCart(6, 'Cetirizine 10mg', 3.29, 167)">
                            <div class="w-16 h-16 mx-auto mb-3 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-capsules text-indigo-600 text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-center mb-1">Cetirizine 10mg</h4>
                            <p class="text-xs text-gray-500 text-center mb-2">Allergy</p>
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-green-600">$3.29</span>
                                <span class="text-xs text-gray-500">Stock: 167</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Section - Cart & Checkout -->
            <div class="space-y-6">
                <!-- Customer Selection -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold mb-4">Customer Information</h3>
                    <div class="space-y-3">
                        <select id="customerSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Walk-in Customer</option>
                            <option value="1">John Smith - john@email.com</option>
                            <option value="2">Sarah Johnson - sarah@email.com</option>
                            <option value="3">Michael Brown - michael@email.com</option>
                        </select>
                        <button onclick="addNewCustomer()" class="w-full px-4 py-2 border-2 border-blue-500 text-blue-600 rounded-lg hover:bg-blue-50 transition font-medium">
                            <i class="fas fa-user-plus mr-2"></i>Add New Customer
                        </button>
                    </div>
                </div>

                <!-- Shopping Cart -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Shopping Cart</h3>
                        <button onclick="clearCart()" class="text-red-600 hover:text-red-700 text-sm font-medium">
                            <i class="fas fa-trash mr-1"></i>Clear All
                        </button>
                    </div>
                    
                    <div id="cartItems" class="space-y-3 max-h-[300px] overflow-y-auto mb-4">
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                            <p>Cart is empty</p>
                        </div>
                    </div>

                    <!-- Discount Code -->
                    <div class="border-t pt-4 mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Discount Code</label>
                        <div class="flex gap-2">
                            <input 
                                type="text" 
                                id="discountCode" 
                                placeholder="Enter code" 
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                            <button onclick="applyDiscount()" class="px-6 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition font-medium">
                                Apply
                            </button>
                        </div>
                        <div id="discountMessage" class="mt-2 text-sm"></div>
                    </div>

                    <!-- Totals -->
                    <div class="border-t pt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal:</span>
                            <span id="subtotal" class="font-semibold">$0.00</span>
                        </div>
                        <div id="discountRow" class="flex justify-between text-sm text-green-600" style="display: none;">
                            <span>Discount (<span id="discountPercent">0</span>%):</span>
                            <span id="discountAmount">-$0.00</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">VAT (20%):</span>
                            <span id="vatAmount" class="font-semibold">$0.00</span>
                        </div>
                        <div class="flex justify-between text-xl font-bold pt-2 border-t">
                            <span>Total:</span>
                            <span id="totalAmount" class="text-green-600">$0.00</span>
                        </div>
                    </div>

                    <!-- Checkout Buttons -->
                    <div class="mt-6 space-y-3">
                        <button onclick="processCheckout()" class="w-full px-6 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:shadow-xl transition text-lg font-bold">
                            <i class="fas fa-check-circle mr-2"></i>Complete Sale
                        </button>
                        <div class="grid grid-cols-2 gap-3">
                            <button onclick="holdTransaction()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-medium">
                                <i class="fas fa-pause mr-1"></i>Hold
                            </button>
                            <button onclick="printReceipt()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition font-medium">
                                <i class="fas fa-print mr-1"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto m-4">
            <div class="bg-gradient-to-r from-green-600 to-blue-600 text-white p-6 flex justify-between items-center rounded-t-xl">
                <h2 class="text-2xl font-bold"><i class="fas fa-receipt mr-2"></i>Transaction Receipt</h2>
                <button onclick="closeReceiptModal()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
            </div>
            <div id="receiptContent" class="p-8 receipt-paper">
                <!-- Receipt content will be generated here -->
            </div>
            <div class="p-6 bg-gray-50 flex gap-3 rounded-b-xl no-print">
                <button onclick="window.print()" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    <i class="fas fa-print mr-2"></i>Print Receipt
                </button>
                <button onclick="emailReceipt()" class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                    <i class="fas fa-envelope mr-2"></i>Email
                </button>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        let discountRate = 0;
        const VAT_RATE = 0.20;

        function addToCart(id, name, price, stock) {
            const existingItem = cart.find(item => item.id === id);
            
            if (existingItem) {
                if (existingItem.quantity >= stock) {
                    alert('Insufficient stock available!');
                    return;
                }
                existingItem.quantity++;
            } else {
                cart.push({ id, name, price, quantity: 1, stock });
            }
            
            updateCart();
            showNotification('Item added to cart!', 'success');
        }

        function updateCart() {
            const cartContainer = document.getElementById('cartItems');
            
            if (cart.length === 0) {
                cartContainer.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                        <p>Cart is empty</p>
                    </div>
                `;
                updateTotals();
                return;
            }

            cartContainer.innerHTML = cart.map(item => `
                <div class="item-added flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900">${item.name}</p>
                        <p class="text-sm text-gray-500">${item.price.toFixed(2)} each</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="decreaseQuantity(${item.id})" class="w-8 h-8 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition">
                            <i class="fas fa-minus text-xs"></i>
                        </button>
                        <span class="font-bold w-8 text-center">${item.quantity}</span>
                        <button onclick="increaseQuantity(${item.id})" class="w-8 h-8 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                        <button onclick="removeFromCart(${item.id})" class="w-8 h-8 bg-gray-200 text-gray-600 rounded-lg hover:bg-gray-300 transition ml-2">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            `).join('');

            updateTotals();
        }

        function increaseQuantity(id) {
            const item = cart.find(item => item.id === id);
            if (item && item.quantity < item.stock) {
                item.quantity++;
                updateCart();
            } else {
                alert('Maximum stock reached!');
            }
        }

        function decreaseQuantity(id) {
            const item = cart.find(item => item.id === id);
            if (item) {
                item.quantity--;
                if (item.quantity === 0) {
                    removeFromCart(id);
                } else {
                    updateCart();
                }
            }
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            updateCart();
        }

        function clearCart() {
            if (confirm('Are you sure you want to clear the cart?')) {
                cart = [];
                discountRate = 0;
                document.getElementById('discountCode').value = '';
                document.getElementById('discountMessage').innerHTML = '';
                document.getElementById('discountRow').style.display = 'none';
                updateCart();
            }
        }

        function updateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const discountAmount = subtotal * (discountRate / 100);
            const amountAfterDiscount = subtotal - discountAmount;
            const vat = amountAfterDiscount * VAT_RATE;
            const total = amountAfterDiscount + vat;

            document.getElementById('subtotal').textContent = ' + subtotal.toFixed(2);
            document.getElementById('vatAmount').textContent = ' + vat.toFixed(2);
            document.getElementById('totalAmount').textContent = ' + total.toFixed(2);

            if (discountRate > 0) {
                document.getElementById('discountRow').style.display = 'flex';
                document.getElementById('discountPercent').textContent = discountRate;
                document.getElementById('discountAmount').textContent = '- + discountAmount.toFixed(2);
            }
        }

        function applyDiscount() {
            const code = document.getElementById('discountCode').value.trim();
            const messageDiv = document.getElementById('discountMessage');
            
            if (!code) {
                messageDiv.innerHTML = '<span class="text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>Please enter a discount code</span>';
                return;
            }

            // Simulate API call - in real app, this would validate with backend
            if (code.toLowerCase() === 'yey code') {
                discountRate = 10;
                messageDiv.innerHTML = '<span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>10% discount applied!</span>';
                updateTotals();
            } else {
                messageDiv.innerHTML = '<span class="text-red-600"><i class="fas fa-times-circle mr-1"></i>Invalid discount code</span>';
                discountRate = 0;
                updateTotals();
            }
        }

        function processCheckout() {
            if (cart.length === 0) {
                alert('Please add items to cart before checkout!');
                return;
            }

            const customerSelect = document.getElementById('customerSelect');
            const customerId = customerSelect.value || 'walk-in';

            // Generate receipt
            generateReceipt(customerId);
            
            // Show success message
            showNotification('Sale completed successfully!', 'success');
        }

        function generateReceipt(customerId) {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const discountAmount = subtotal * (discountRate / 100);
            const amountAfterDiscount = subtotal - discountAmount;
            const vat = amountAfterDiscount * VAT_RATE;
            const total = amountAfterDiscount + vat;

            const receiptHTML = `
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold">PHARMACARE</h2>
                    <p class="text-sm">Advanced Pharmacy Management</p>
                    <p class="text-xs text-gray-600 mt-1">123 Healthcare Ave, Medical District</p>
                    <p class="text-xs text-gray-600">Phone: +1 (555) 123-4567</p>
                    <div class="border-t-2 border-dashed border-gray-300 my-4"></div>
                </div>

                <div class="mb-4 text-sm">
                    <p><strong>Receipt #:</strong> ${Math.floor(Math.random() * 10000)}</p>
                    <p><strong>Date:</strong> ${new Date().toLocaleString()}</p>
                    <p><strong>Cashier:</strong> Dr. John Doe</p>
                    <p><strong>Customer:</strong> ${customerId === 'walk-in' ? 'Walk-in Customer' : 'Registered Customer'}</p>
                </div>

                <div class="border-t-2 border-dashed border-gray-300 my-4"></div>

                <table class="w-full text-sm mb-4">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Price</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${cart.map(item => `
                            <tr class="border-b">
                                <td class="py-2">${item.name}</td>
                                <td class="text-center">${item.quantity}</td>
                                <td class="text-right">${item.price.toFixed(2)}</td>
                                <td class="text-right">${(item.price * item.quantity).toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <div class="border-t-2 border-dashed border-gray-300 my-4"></div>

                <div class="text-sm space-y-1">
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span>${subtotal.toFixed(2)}</span>
                    </div>
                    ${discountRate > 0 ? `
                        <div class="flex justify-between text-green-600">
                            <span>Discount (${discountRate}%):</span>
                            <span>-${discountAmount.toFixed(2)}</span>
                        </div>
                    ` : ''}
                    <div class="flex justify-between">
                        <span>VAT (20%):</span>
                        <span>${vat.toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between text-xl font-bold pt-2 border-t-2 border-gray-800 mt-2">
                        <span>TOTAL:</span>
                        <span>${total.toFixed(2)}</span>
                    </div>
                </div>

                <div class="border-t-2 border-dashed border-gray-300 my-4"></div>

                <div class="text-center text-sm">
                    <p class="mb-2">Thank you for your business!</p>
                    <p class="text-xs text-gray-600">Please keep this receipt for your records</p>
                    <p class="text-xs text-gray-600 mt-2">For any queries, contact us at support@pharmacare.com</p>
                </div>
            `;

            document.getElementById('receiptContent').innerHTML = receiptHTML;
            document.getElementById('receiptModal').classList.remove('hidden');
            document.getElementById('receiptModal').classList.add('flex');
        }

        function closeReceiptModal() {
            document.getElementById('receiptModal').classList.add('hidden');
            document.getElementById('receiptModal').classList.remove('flex');
            
            // Clear cart after closing receipt
            if (confirm('Clear cart and start new transaction?')) {
                clearCart();
            }
        }

        function holdTransaction() {
            if (cart.length === 0) {
                alert('Cart is empty!');
                return;
            }
            
            const savedTransactions = JSON.parse(localStorage.getItem('heldTransactions') || '[]');
            savedTransactions.push({
                id: Date.now(),
                cart: [...cart],
                discountRate: discountRate,
                timestamp: new Date().toISOString()
            });
            localStorage.setItem('heldTransactions', JSON.stringify(savedTransactions));
            
            showNotification('Transaction held successfully!', 'success');
            clearCart();
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-6 px-6 py-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white font-medium`;
            notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'times'}-circle mr-2"></i>${message}`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        function searchProducts(query) {
            // In production, this would search via API
            console.log('Searching for:', query);
        }

        function handleSearchEnter(event) {
            if (event.key === 'Enter') {
                const query = event.target.value;
                // Simulate barcode scan or product search
                console.log('Search/Scan:', query);
            }
        }

        function filterByCategory(category) {
            showNotification(`Filtering by ${category}`, 'success');
        }

        function showAllProducts() {
            showNotification('Showing all products', 'success');
        }

        function openBarcodeScanner() {
            const barcode = prompt('Enter or scan barcode:');
            if (barcode) {
                showNotification('Searching for product...', 'success');
            }
        }

        function addNewCustomer() {
            alert('Add new customer functionality would open registration form');
        }

        function printReceipt() {
            window.print();
        }

        function emailReceipt() {
            alert('Email receipt functionality would be implemented here');
        }
    </script>
</body>
</html>
