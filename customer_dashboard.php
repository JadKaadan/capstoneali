<?php
require_once 'db.php';

// Check authentication
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'] ?? 'Customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - PharmaCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .loader { border-top-color: #3b82f6; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .modal { display: none; }
        .modal.active { display: flex; }
        .fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen">
    <!-- Top Navigation -->
    <nav class="bg-white shadow-lg border-b sticky top-0 z-50">
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-heartbeat text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Patient Portal</h1>
                        <p class="text-xs text-gray-500">Welcome back, <?php echo htmlspecialchars($customer_name); ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="showNotifications()" class="relative">
                        <i class="fas fa-bell text-gray-600 text-xl"></i>
                        <span id="notificationCount" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center hidden">0</span>
                    </button>
                    <button onclick="logout()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8">
        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <button onclick="openConsultation()" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-stethoscope text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900">New Consultation</h3>
                <p class="text-sm text-gray-500 mt-1">Get medication recommendations</p>
            </button>

            <button onclick="viewPrescriptions()" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-prescription-bottle text-blue-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900">My Prescriptions</h3>
                <p class="text-sm text-gray-500 mt-1">View active prescriptions</p>
            </button>

            <button onclick="viewReceipts()" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition text-center">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-receipt text-purple-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900">Purchase History</h3>
                <p class="text-sm text-gray-500 mt-1">View receipts & invoices</p>
            </button>

            <button onclick="viewProfile()" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition text-center">
                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-circle text-orange-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900">My Profile</h3>
                <p class="text-sm text-gray-500 mt-1">Update your information</p>
            </button>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Purchases -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Recent Purchases</h2>
                        <button onclick="loadMorePurchases()" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                    <div id="purchasesList" class="space-y-4">
                        <div class="flex items-center justify-center py-8 text-gray-500">
                            <div class="loader border-4 border-gray-200 rounded-full w-8 h-8 mr-3"></div>
                            Loading purchases...
                        </div>
                    </div>
                </div>

                <!-- Health Statistics -->
                <div class="bg-white rounded-xl shadow-lg p-6 mt-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Your Health Insights</h2>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <canvas id="medicationChart"></canvas>
                        </div>
                        <div>
                            <canvas id="spendingChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Active Medications -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Active Medications</h3>
                    <div id="activeMedications" class="space-y-3">
                        <div class="text-gray-500 text-center py-4">Loading medications...</div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                    <h3 class="text-lg font-bold mb-4">Your Statistics</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span>Total Prescriptions</span>
                            <span id="totalPrescriptions" class="font-bold">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span>This Month's Spending</span>
                            <span id="monthSpending" class="font-bold">$0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Loyalty Points</span>
                            <span id="loyaltyPoints" class="font-bold">0</span>
                        </div>
                    </div>
                </div>

                <!-- Reminders -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Reminders</h3>
                    <div id="reminders" class="space-y-2 text-sm">
                        <div class="p-3 bg-yellow-50 border-l-4 border-yellow-500 rounded">
                            <p class="font-semibold text-yellow-800">Refill Due</p>
                            <p class="text-yellow-600">Vitamin D - 3 days left</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Consultation Modal -->
    <div id="consultationModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto m-4">
            <div class="bg-gradient-to-r from-green-600 to-blue-600 text-white p-6 flex justify-between items-center rounded-t-xl">
                <h2 class="text-2xl font-bold"><i class="fas fa-stethoscope mr-2"></i>Symptom-Based Consultation</h2>
                <button onclick="closeModal('consultationModal')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
            </div>
            <div class="p-6">
                <form id="consultationForm" onsubmit="submitConsultation(event)">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Primary Symptom*</label>
                            <select id="primarySymptom" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Select symptom</option>
                                <option value="headache">Headache</option>
                                <option value="fever">Fever</option>
                                <option value="cold">Cold & Flu</option>
                                <option value="cough">Cough</option>
                                <option value="allergy">Allergies</option>
                                <option value="stomach">Stomach Pain</option>
                                <option value="joint_pain">Joint Pain</option>
                                <option value="skin">Skin Issues</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Duration</label>
                            <input type="text" id="duration" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="e.g., 2 days">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Symptoms</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="additionalSymptoms" value="nausea" class="rounded text-blue-600">
                                    <span>Nausea</span>
                                </label>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="additionalSymptoms" value="dizziness" class="rounded text-blue-600">
                                    <span>Dizziness</span>
                                </label>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="additionalSymptoms" value="fatigue" class="rounded text-blue-600">
                                    <span>Fatigue</span>
                                </label>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="additionalSymptoms" value="loss_appetite" class="rounded text-blue-600">
                                    <span>Loss of Appetite</span>
                                </label>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="additionalSymptoms" value="body_aches" class="rounded text-blue-600">
                                    <span>Body Aches</span>
                                </label>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="additionalSymptoms" value="chills" class="rounded text-blue-600">
                                    <span>Chills</span>
                                </label>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Describe your symptoms in detail</label>
                            <textarea id="description" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Provide additional details about your symptoms..."></textarea>
                        </div>
                    </div>
                    
                    <div id="recommendationResults" class="hidden mt-6 p-6 bg-blue-50 rounded-lg">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Recommended Medications</h3>
                        <div id="recommendationsList" class="space-y-3"></div>
                    </div>

                    <div class="flex justify-end space-x-4 mt-6">
                        <button type="button" onclick="closeModal('consultationModal')" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium">Cancel</button>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:shadow-lg transition font-medium">
                            <i class="fas fa-search mr-2"></i>Get Recommendations
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto m-4">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 flex justify-between items-center rounded-t-xl">
                <h2 class="text-2xl font-bold"><i class="fas fa-receipt mr-2"></i>Receipt Details</h2>
                <button onclick="closeModal('receiptModal')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
            </div>
            <div id="receiptContent" class="p-6">
                <!-- Receipt content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // API Configuration
        const API_BASE = 'api.php';
        const CUSTOMER_ID = <?php echo json_encode($customer_id); ?>;
        const CSRF_TOKEN = '<?php echo $_SESSION['csrf_token']; ?>';

        // Initialize dashboard on load
        document.addEventListener('DOMContentLoaded', function() {
            loadPurchases();
            loadActiveMedications();
            loadStatistics();
            initializeCharts();
            checkNotifications();
        });

        // API Helper Function
        async function apiCall(action, method = 'GET', data = null) {
            const url = new URL(API_BASE, window.location.origin);
            url.searchParams.append('action', action);
            
            const options = {
                method: method,
                headers: {
                    'X-CSRF-Token': CSRF_TOKEN
                }
            };

            if (method === 'GET' && data) {
                Object.keys(data).forEach(key => url.searchParams.append(key, data[key]));
            } else if (method !== 'GET' && data) {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(data);
            }

            try {
                const response = await fetch(url, options);
                const result = await response.json();
                
                if (!result.success && response.status === 401) {
                    window.location.href = 'customer_login.php';
                    return null;
                }
                
                return result;
            } catch (error) {
                console.error('API Error:', error);
                showNotification('Network error. Please try again.', 'error');
                return null;
            }
        }

        // Load Recent Purchases
        async function loadPurchases() {
            const purchasesList = document.getElementById('purchasesList');
            purchasesList.innerHTML = '<div class="text-center py-4"><div class="loader border-4 border-gray-200 rounded-full w-8 h-8 mx-auto"></div></div>';

            const result = await apiCall('get_customer_receipts', 'GET', { customer_id: CUSTOMER_ID });
            
            if (result && result.success && result.data.receipts) {
                const receipts = result.data.receipts;
                
                if (receipts.length === 0) {
                    purchasesList.innerHTML = '<div class="text-center text-gray-500 py-8">No purchases yet</div>';
                } else {
                    purchasesList.innerHTML = receipts.slice(0, 5).map(receipt => `
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition cursor-pointer" onclick="viewReceipt(${receipt.receipt_id})">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-shopping-bag text-blue-600 text-xl"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">Receipt #${receipt.receipt_id}</p>
                                    <p class="text-sm text-gray-500">${formatDate(receipt.created_at)}</p>
                                </div>
                            </div>
                            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                                View Details
                            </button>
                        </div>
                    `).join('');
                }
            } else {
                purchasesList.innerHTML = '<div class="text-center text-red-500 py-8">Failed to load purchases</div>';
            }
        }

        // Load Active Medications
        async function loadActiveMedications() {
            const medicationsDiv = document.getElementById('activeMedications');
            
            // Simulated data - would come from API
            const medications = [
                { name: 'Vitamin D3', dosage: '1000 IU', frequency: 'Daily', remaining: 15 },
                { name: 'Aspirin', dosage: '100mg', frequency: 'As needed', remaining: 30 }
            ];
            
            if (medications.length === 0) {
                medicationsDiv.innerHTML = '<div class="text-center text-gray-500 py-4">No active medications</div>';
            } else {
                medicationsDiv.innerHTML = medications.map(med => `
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-gray-900">${med.name}</p>
                                <p class="text-xs text-gray-500">${med.dosage} - ${med.frequency}</p>
                            </div>
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                                ${med.remaining} left
                            </span>
                        </div>
                    </div>
                `).join('');
            }
        }

        // Load Statistics
        async function loadStatistics() {
            // These would be fetched from the API
            document.getElementById('totalPrescriptions').textContent = '24';
            document.getElementById('monthSpending').textContent = '$347.50';
            document.getElementById('loyaltyPoints').textContent = '1,250';
        }

        // Initialize Charts
        function initializeCharts() {
            // Medication Category Chart
            const medCtx = document.getElementById('medicationChart').getContext('2d');
            new Chart(medCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pain Relief', 'Vitamins', 'Antibiotics', 'Other'],
                    datasets: [{
                        data: [35, 25, 20, 20],
                        backgroundColor: [
                            'rgb(59, 130, 246)',
                            'rgb(16, 185, 129)',
                            'rgb(245, 158, 11)',
                            'rgb(139, 92, 246)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        title: {
                            display: true,
                            text: 'Medication Categories'
                        }
                    }
                }
            });

            // Spending Trend Chart
            const spendCtx = document.getElementById('spendingChart').getContext('2d');
            new Chart(spendCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Monthly Spending',
                        data: [120, 190, 150, 210, 180, 240],
                        borderColor: 'rgb(99, 102, 241)',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Spending Trend ($)'
                        }
                    }
                }
            });
        }

        // Consultation Functions
        function openConsultation() {
            document.getElementById('consultationModal').classList.add('active');
        }

        async function submitConsultation(event) {
            event.preventDefault();
            
            const primarySymptom = document.getElementById('primarySymptom').value;
            const duration = document.getElementById('duration').value;
            const description = document.getElementById('description').value;
            
            const additionalSymptoms = Array.from(document.querySelectorAll('input[name="additionalSymptoms"]:checked'))
                .map(cb => cb.value);
            
            const symptoms = [primarySymptom, ...additionalSymptoms].join(', ');
            
            // Show loading state
            const resultsDiv = document.getElementById('recommendationResults');
            const listDiv = document.getElementById('recommendationsList');
            resultsDiv.classList.remove('hidden');
            listDiv.innerHTML = '<div class="text-center py-4"><div class="loader border-4 border-blue-200 rounded-full w-8 h-8 mx-auto"></div></div>';
            
            // Call API
            const result = await apiCall('recommend_drugs', 'POST', {
                symptoms: symptoms,
                description: description
            });
            
            if (result && result.success) {
                // Display recommendations
                listDiv.innerHTML = `
                    <div class="space-y-3">
                        <div class="p-4 bg-white rounded-lg border border-blue-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold text-gray-900">Paracetamol 500mg</h4>
                                    <p class="text-sm text-gray-600 mt-1">For headache and fever relief</p>
                                    <p class="text-xs text-gray-500 mt-2">Dosage: 1-2 tablets every 4-6 hours</p>
                                </div>
                                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                                    Available
                                </span>
                            </div>
                        </div>
                        <div class="p-4 bg-white rounded-lg border border-blue-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold text-gray-900">Ibuprofen 400mg</h4>
                                    <p class="text-sm text-gray-600 mt-1">Anti-inflammatory pain relief</p>
                                    <p class="text-xs text-gray-500 mt-2">Dosage: 1 tablet every 8 hours with food</p>
                                </div>
                                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                                    Available
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            These are general recommendations. Please consult with a pharmacist for proper medical advice.
                        </p>
                    </div>
                `;
                
                // Save consultation
                await apiCall('create_consultation', 'POST', {
                    customer_id: CUSTOMER_ID,
                    symptoms: symptoms,
                    recommended_items: 'Paracetamol 500mg, Ibuprofen 400mg'
                });
            } else {
                listDiv.innerHTML = '<div class="text-red-500 text-center py-4">Failed to get recommendations. Please try again.</div>';
            }
        }

        // View Receipt Details
        async function viewReceipt(receiptId) {
            const modal = document.getElementById('receiptModal');
            const content = document.getElementById('receiptContent');
            
            content.innerHTML = '<div class="text-center py-8"><div class="loader border-4 border-gray-200 rounded-full w-8 h-8 mx-auto"></div></div>';
            modal.classList.add('active');
            
            // Fetch receipt details
            const result = await apiCall('get_sale_details', 'GET', { sale_id: receiptId });
            
            if (result && result.success) {
                // Display receipt - using simulated data for now
                content.innerHTML = `
                    <div class="space-y-6">
                        <div class="text-center border-b pb-4">
                            <h3 class="text-2xl font-bold">PHARMACARE</h3>
                            <p class="text-sm text-gray-600">Receipt #${receiptId}</p>
                            <p class="text-sm text-gray-600">${new Date().toLocaleString()}</p>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between py-2 border-b">
                                <div>
                                    <p class="font-semibold">Paracetamol 500mg</p>
                                    <p class="text-sm text-gray-600">2 × $2.99</p>
                                </div>
                                <span class="font-semibold">$5.98</span>
                            </div>
                            <div class="flex justify-between py-2 border-b">
                                <div>
                                    <p class="font-semibold">Vitamin C 1000mg</p>
                                    <p class="text-sm text-gray-600">1 × $8.99</p>
                                </div>
                                <span class="font-semibold">$8.99</span>
                            </div>
                        </div>
                        
                        <div class="border-t pt-4 space-y-2">
                            <div class="flex justify-between">
                                <span>Subtotal:</span>
                                <span>$14.97</span>
                            </div>
                            <div class="flex justify-between">
                                <span>VAT (20%):</span>
                                <span>$2.99</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total:</span>
                                <span class="text-green-600">$17.96</span>
                            </div>
                        </div>
                        
                        <div class="flex gap-3 pt-4">
                            <button onclick="downloadReceipt(${receiptId})" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-download mr-2"></i>Download PDF
                            </button>
                            <button onclick="emailReceipt(${receiptId})" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                <i class="fas fa-envelope mr-2"></i>Email Receipt
                            </button>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = '<div class="text-center text-red-500 py-8">Failed to load receipt details</div>';
            }
        }

        // Other Dashboard Functions
        function viewPrescriptions() {
            showNotification('Loading prescriptions...', 'info');
            // Would navigate to prescriptions page or load in modal
        }

        function viewReceipts() {
            loadMorePurchases();
        }

        function viewProfile() {
            showNotification('Loading profile...', 'info');
            // Would navigate to profile page or load in modal
        }

        async function loadMorePurchases() {
            const result = await apiCall('get_customer_receipts', 'GET', { 
                customer_id: CUSTOMER_ID,
                limit: 20 
            });
            
            if (result && result.success) {
                // Display all purchases in a new view
                showNotification('Loading all purchases...', 'info');
            }
        }

        function checkNotifications() {
            // Check for new notifications
            const notifications = [
                { type: 'refill', message: 'Vitamin D refill due in 3 days' },
                { type: 'discount', message: 'New 10% discount available' }
            ];
            
            const count = notifications.length;
            if (count > 0) {
                document.getElementById('notificationCount').classList.remove('hidden');
                document.getElementById('notificationCount').textContent = count;
            }
        }

        function showNotifications() {
            showNotification('You have 2 new notifications', 'info');
        }

        function downloadReceipt(receiptId) {
            showNotification('Downloading receipt...', 'success');
            // Implement PDF download
        }

        function emailReceipt(receiptId) {
            showNotification('Sending receipt to your email...', 'success');
            // Implement email functionality
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const bgColor = type === 'error' ? 'bg-red-500' : type === 'success' ? 'bg-green-500' : 'bg-blue-500';
            
            notification.className = `fixed top-20 right-6 px-6 py-4 ${bgColor} text-white rounded-lg shadow-lg z-50 fade-in`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    </script>
</body>
</html>
