<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Inventory Management - PharmaCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal { display: none; }
        .modal.active { display: flex; }
        .table-hover tbody tr:hover { background-color: #f9fafb; }
        @keyframes scan {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }
        .scanning { animation: scan 1.5s infinite; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50">
    <!-- Navigation (same as dashboard) -->
    <nav class="bg-white shadow-lg border-b sticky top-0 z-50">
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-pills text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold">Inventory Management</h1>
                        <p class="text-xs text-gray-500">Advanced Drug Database System</p>
                    </div>
                </div>
                <button onclick="window.location.href='pharmacist_dashboard.php'" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </button>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8">
        <!-- Action Bar -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <div class="flex flex-wrap gap-4 items-center justify-between">
                <div class="flex flex-wrap gap-3">
                    <button onclick="openAddModal()" class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:shadow-lg transition font-medium">
                        <i class="fas fa-plus mr-2"></i>Add New Drug
                    </button>
                    <button onclick="openBarcodeScanner()" class="px-6 py-3 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg hover:shadow-lg transition font-medium">
                        <i class="fas fa-barcode mr-2"></i>Scan Barcode
                    </button>
                    <button onclick="bulkUpload()" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:shadow-lg transition font-medium">
                        <i class="fas fa-upload mr-2"></i>Bulk Upload
                    </button>
                    <button onclick="exportData()" class="px-6 py-3 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-lg hover:shadow-lg transition font-medium">
                        <i class="fas fa-download mr-2"></i>Export CSV
                    </button>
                </div>
                
                <div class="flex gap-3">
                    <button onclick="openFilters()" class="px-4 py-3 border-2 border-blue-500 text-blue-600 rounded-lg hover:bg-blue-50 transition font-medium">
                        <i class="fas fa-filter mr-2"></i>Advanced Filters
                    </button>
                    <select id="sortBy" onchange="sortInventory()" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="name">Sort by Name</option>
                        <option value="quantity">Sort by Quantity</option>
                        <option value="price">Sort by Price</option>
                        <option value="expiry">Sort by Expiry</option>
                    </select>
                </div>
            </div>

            <!-- Search and Quick Stats -->
            <div class="mt-6 grid grid-cols-1 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-2">
                    <div class="relative">
                        <input type="text" id="searchInput" onkeyup="searchInventory()" placeholder="Search by name, category, barcode, supplier..." class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-search absolute left-4 top-4 text-gray-400"></i>
                    </div>
                </div>
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-orange-600 font-medium">Low Stock Items</p>
                        <p class="text-2xl font-bold text-orange-700">12</p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-orange-500 text-2xl"></i>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-green-600 font-medium">Total Items</p>
                        <p class="text-2xl font-bold text-green-700">347</p>
                    </div>
                    <i class="fas fa-boxes text-green-500 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Advanced Inventory Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full table-hover" id="inventoryTable">
                    <thead class="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left">
                                <input type="checkbox" onchange="selectAll(this)" class="rounded">
                            </th>
                            <th class="px-6 py-4 text-left font-semibold">Drug Name</th>
                            <th class="px-6 py-4 text-left font-semibold">Category</th>
                            <th class="px-6 py-4 text-left font-semibold">Barcode</th>
                            <th class="px-6 py-4 text-left font-semibold">Stock</th>
                            <th class="px-6 py-4 text-left font-semibold">Unit Price</th>
                            <th class="px-6 py-4 text-left font-semibold">Total Value</th>
                            <th class="px-6 py-4 text-left font-semibold">Expiry Date</th>
                            <th class="px-6 py-4 text-left font-semibold">Status</th>
                            <th class="px-6 py-4 text-left font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryBody" class="divide-y divide-gray-200">
                        <!-- Sample Data -->
                        <tr class="inventory-row" data-name="paracetamol" data-category="pain relief">
                            <td class="px-6 py-4"><input type="checkbox" class="rounded row-checkbox"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-pills text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">Paracetamol 500mg</p>
                                        <p class="text-xs text-gray-500">Acetaminophen</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">Pain Relief</span>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-600">8901234567890</td>
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-semibold text-gray-900">247 units</p>
                                    <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: 82%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900">$2.99</td>
                            <td class="px-6 py-4 font-semibold text-green-600">$738.53</td>
                            <td class="px-6 py-4 text-sm text-gray-600">2026-08-15</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">In Stock</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <button onclick="viewDetails(1)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editItem(1)" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteItem(1)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button onclick="printBarcode(1)" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition" title="Print Barcode">
                                        <i class="fas fa-barcode"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr class="inventory-row" data-name="aspirin" data-category="pain relief">
                            <td class="px-6 py-4"><input type="checkbox" class="rounded row-checkbox"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-capsules text-green-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">Aspirin 100mg</p>
                                        <p class="text-xs text-gray-500">Acetylsalicylic Acid</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">Pain Relief</span>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-600">8901234567891</td>
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-semibold text-gray-900">18 units</p>
                                    <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                        <div class="bg-orange-500 h-2 rounded-full" style="width: 18%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900">$3.49</td>
                            <td class="px-6 py-4 font-semibold text-green-600">$62.82</td>
                            <td class="px-6 py-4 text-sm text-gray-600">2025-12-20</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-semibold">Low Stock</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <button onclick="viewDetails(2)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editItem(2)" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteItem(2)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button onclick="printBarcode(2)" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition">
                                        <i class="fas fa-barcode"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr class="inventory-row" data-name="amoxicillin" data-category="antibiotic">
                            <td class="px-6 py-4"><input type="checkbox" class="rounded row-checkbox"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-tablets text-purple-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">Amoxicillin 250mg</p>
                                        <p class="text-xs text-gray-500">Antibiotic Capsules</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">Antibiotic</span>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-600">8901234567892</td>
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-semibold text-gray-900">156 units</p>
                                    <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: 62%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900">$5.99</td>
                            <td class="px-6 py-4 font-semibold text-green-600">$934.44</td>
                            <td class="px-6 py-4 text-sm text-gray-600">2026-03-10</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">In Stock</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <button onclick="viewDetails(3)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editItem(3)" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteItem(3)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button onclick="printBarcode(3)" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition">
                                        <i class="fas fa-barcode"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t">
                <div class="text-sm text-gray-600">
                    Showing <span class="font-semibold">1</span> to <span class="font-semibold">3</span> of <span class="font-semibold">347</span> results
                </div>
                <div class="flex space-x-2">
                    <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">Previous</button>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg">1</button>
                    <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">2</button>
                    <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">3</button>
                    <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">Next</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Item Modal -->
    <div id="itemModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto m-4">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 flex justify-between items-center rounded-t-xl">
                <h2 class="text-2xl font-bold"><i class="fas fa-plus-circle mr-2"></i>Add New Drug</h2>
                <button onclick="closeModal('itemModal')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
            </div>
            <form class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Drug Name *</label>
                        <input type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="e.g., Paracetamol 500mg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Generic Name</label>
                        <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="e.g., Acetaminophen">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                        <select required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Category</option>
                            <option>Pain Relief</option>
                            <option>Antibiotic</option>
                            <option>Vitamin</option>
                            <option>Cold & Flu</option>
                            <option>Cardiovascular</option>
                            <option>Diabetes</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Barcode</label>
                        <div class="flex space-x-2">
                            <input type="text" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="890XXXXXXXXXX">
                            <button type="button" onclick="generateBarcode()" class="px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                <i class="fas fa-barcode"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity *</label>
                        <input type="number" required min="0" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Unit Price ($) *</label>
                        <input type="number" required min="0" step="0.01" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="2.99">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reorder Level</label>
                        <input type="number" min="0" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="20">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date *</label>
                        <input type="date" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                        <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Supplier Name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Batch Number</label>
                        <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="BATCH-XXXXX">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description/Indications</label>
                        <textarea rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Drug description and usage instructions"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Side Effects</label>
                        <textarea rows="2" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="List common side effects"></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" onclick="closeModal('itemModal')" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium">Cancel</button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:shadow-lg transition font-medium">
                        <i class="fas fa-save mr-2"></i>Save Drug
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Barcode Scanner Modal -->
    <div id="barcodeModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md m-4">
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-6 flex justify-between items-center rounded-t-xl">
                <h2 class="text-2xl font-bold"><i class="fas fa-barcode mr-2"></i>Barcode Scanner</h2>
                <button onclick="closeModal('barcodeModal')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
            </div>
            <div class="p-8 text-center">
                <div class="w-48 h-48 mx-auto mb-6 border-4 border-dashed border-purple-300 rounded-lg flex items-center justify-center scanning">
                    <i class="fas fa-barcode text-purple-400 text-6xl"></i>
                </div>
                <p class="text-gray-600 mb-4">Position barcode within the frame</p>
                <input type="text" id="barcodeInput" placeholder="Or enter barcode manually" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 mb-4">
                <button onclick="searchByBarcode()" class="w-full px-6 py-3 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg hover:shadow-lg transition font-medium">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
            </div>
        </div>
    </div>

    <script>
        function searchInventory() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.inventory-row');
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const category = row.getAttribute('data-category');
                const text = row.textContent.toLowerCase();
                
                if(text.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function sortInventory() {
            alert('Sorting functionality would be implemented with backend');
        }

        function openAddModal() {
            document.getElementById('itemModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function openBarcodeScanner() {
            document.getElementById('barcodeModal').classList.add('active');
        }

        function searchByBarcode() {
            const barcode = document.getElementById('barcodeInput').value;
            if(barcode) {
                alert('Searching for drug with barcode: ' + barcode);
                closeModal('barcodeModal');
            }
        }

        function generateBarcode() {
            const randomBarcode = '890' + Math.floor(Math.random() * 10000000000);
            event.target.closest('div').querySelector('input').value = randomBarcode;
        }

        function selectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }

        function viewDetails(id) {
            alert('Viewing detailed information for drug ID: ' + id);
        }

        function editItem(id) {
            alert('Editing drug ID: ' + id);
            openAddModal();
        }

        function deleteItem(id) {
            if(confirm('Are you sure you want to delete this drug from inventory?')) {
                alert('Drug ID ' + id + ' deleted successfully');
            }
        }

        function printBarcode(id) {
            alert('Printing barcode for drug ID: ' + id);
        }

        function bulkUpload() {
            alert('Bulk upload via CSV would open here');
        }

        function exportData() {
            alert('Exporting inventory data to CSV...');
        }

        function openFilters() {
            alert('Advanced filters panel would open here');
        }
    </script>
</body>
</html>
