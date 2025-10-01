<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Pharmacist Dashboard - PharmaCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-in { animation: slideIn 0.3s ease-out; }
        .card-hover { transition: all 0.3s; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
        .sidebar-active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .notification-badge { animation: pulse 2s infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .chart-container { position: relative; height: 300px; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 min-h-screen">
    <!-- Top Navigation Bar -->
    <nav class="bg-white shadow-lg border-b border-gray-200 sticky top-0 z-50">
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-pills text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">PharmaCare Pro</h1>
                            <p class="text-xs text-gray-500">Advanced Management System</p>
                        </div>
                    </div>
                    <div class="ml-8 hidden md:flex items-center space-x-2 bg-gray-100 rounded-lg px-4 py-2">
                        <i class="fas fa-search text-gray-400"></i>
                        <input type="text" placeholder="Search drugs, patients, reports..." class="bg-transparent border-none focus:outline-none w-64 text-sm">
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <button onclick="toggleNotifications()" class="relative">
                        <i class="fas fa-bell text-gray-600 text-xl"></i>
                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center notification-badge">5</span>
                    </button>
                    <button onclick="toggleSettings()" class="text-gray-600 hover:text-blue-600">
                        <i class="fas fa-cog text-xl"></i>
                    </button>
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                            JD
                        </div>
                        <div class="hidden md:block">
                            <p class="text-sm font-semibold text-gray-900">Dr. John Doe</p>
                            <p class="text-xs text-gray-500">Senior Pharmacist</p>
                        </div>
                    </div>
                    <button onclick="logout()" class="px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:shadow-lg transition text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Advanced Sidebar -->
        <aside class="w-64 bg-white shadow-xl min-h-screen sticky top-16">
            <div class="p-6">
                <div class="space-y-2">
                    <a href="#" onclick="showSection('dashboard')" class="sidebar-item sidebar-active flex items-center space-x-3 px-4 py-3 rounded-lg">
                        <i class="fas fa-chart-line text-lg"></i>
                        <span class="font-medium">Dashboard</span>
                    </a>
                    <a href="#" onclick="showSection('inventory')" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition">
                        <i class="fas fa-boxes text-lg"></i>
                        <span class="font-medium">Inventory</span>
                        <span class="ml-auto bg-orange-500 text-white text-xs px-2 py-1 rounded-full">12</span>
                    </a>
                    <a href="#" onclick="showSection('sales')" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition">
                        <i class="fas fa-cash-register text-lg"></i>
                        <span class="font-medium">Sales & POS</span>
                    </a>
                    <a href="#" onclick="showSection('customers')" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition">
                        <i class="fas fa-users text-lg"></i>
                        <span class="font-medium">Patients</span>
                    </a>
                    <a href="#" onclick="showSection('prescriptions')" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition">
                        <i class="fas fa-prescription text-lg"></i>
                        <span class="font-medium">Prescriptions</span>
                        <span class="ml-auto bg-blue-500 text-white text-xs px-2 py-1 rounded-full">3</span>
                    </a>
                    <a href="#" onclick="showSection('analytics')" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition">
                        <i class="fas fa-chart-pie text-lg"></i>
                        <span class="font-medium">Analytics</span>
                    </a>
                    <a href="#" onclick="showSection('reports')" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition">
                        <i class="fas fa-file-alt text-lg"></i>
                        <span class="font-medium">Reports</span>
                    </a>
                    <a href="#" onclick="showSection('suppliers')" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition">
                        <i class="fas fa-truck text-lg"></i>
                        <span class="font-medium">Suppliers</span>
                    </a>
                    <a href="#" onclick="showSection('settings')" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition">
                        <i class="fas fa-cog text-lg"></i>
                        <span class="font-medium">Settings</span>
                    </a>
                </div>

                <div class="mt-8 p-4 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg text-white">
                    <h4 class="font-semibold mb-2">System Status</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Database</span>
                            <span class="flex items-center"><i class="fas fa-circle text-green-300 text-xs mr-1"></i>Active</span>
                        </div>
                        <div class="flex justify-between">
                            <span>API</span>
                            <span class="flex items-center"><i class="fas fa-circle text-green-300 text-xs mr-1"></i>Online</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Backup</span>
                            <span class="text-xs">2 hours ago</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 p-8">
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="animate-slide-in">
                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-lg p-6 card-hover border-l-4 border-blue-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm text-gray-600 font-medium mb-2">Total Revenue</p>
                                <h3 class="text-3xl font-bold text-gray-900">$124,580</h3>
                                <p class="text-sm text-green-600 mt-2 flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i> +12.5% from last month
                                </p>
                            </div>
                            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-dollar-sign text-blue-600 text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex justify-between text-xs text-gray-600">
                                <span>Today: $3,240</span>
                                <span>Yesterday: $2,890</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-6 card-hover border-l-4 border-green-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm text-gray-600 font-medium mb-2">Prescriptions</p>
                                <h3 class="text-3xl font-bold text-gray-900">1,834</h3>
                                <p class="text-sm text-green-600 mt-2 flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i> +8.2% this week
                                </p>
                            </div>
                            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-prescription-bottle text-green-600 text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex justify-between text-xs text-gray-600">
                                <span>Pending: 23</span>
                                <span>Completed: 1,811</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-6 card-hover border-l-4 border-orange-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm text-gray-600 font-medium mb-2">Low Stock Alerts</p>
                                <h3 class="text-3xl font-bold text-gray-900">12</h3>
                                <p class="text-sm text-orange-600 mt-2 flex items-center">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Needs attention
                                </p>
                            </div>
                            <div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-exclamation-circle text-orange-600 text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <button class="text-xs text-orange-600 hover:text-orange-700 font-medium">View Details →</button>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-6 card-hover border-l-4 border-purple-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm text-gray-600 font-medium mb-2">Active Patients</p>
                                <h3 class="text-3xl font-bold text-gray-900">8,547</h3>
                                <p class="text-sm text-purple-600 mt-2 flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i> +156 new this month
                                </p>
                            </div>
                            <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-friends text-purple-600 text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex justify-between text-xs text-gray-600">
                                <span>Visits Today: 47</span>
                                <span>Avg/Day: 52</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-gray-900">Revenue Analytics</h3>
                            <select class="text-sm border border-gray-300 rounded-lg px-3 py-1">
                                <option>Last 7 Days</option>
                                <option>Last 30 Days</option>
                                <option>Last 3 Months</option>
                            </select>
                        </div>
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-gray-900">Drug Category Distribution</h3>
                            <button class="text-sm text-blue-600 hover:text-blue-700">View All</button>
                        </div>
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Advanced Tables -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-gray-900">Top Selling Drugs</h3>
                            <span class="text-sm text-gray-500">This Month</span>
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-pills text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">Paracetamol 500mg</p>
                                        <p class="text-xs text-gray-500">Sold: 1,247 units</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-600">$3,741</p>
                                    <p class="text-xs text-gray-500">+15%</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-capsules text-green-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">Aspirin 100mg</p>
                                        <p class="text-xs text-gray-500">Sold: 892 units</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-600">$2,676</p>
                                    <p class="text-xs text-gray-500">+8%</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-tablets text-purple-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">Ibuprofen 400mg</p>
                                        <p class="text-xs text-gray-500">Sold: 756 units</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-600">$2,268</p>
                                    <p class="text-xs text-gray-500">+12%</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-gray-900">Recent Transactions</h3>
                            <button class="text-sm text-blue-600 hover:text-blue-700">View All</button>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 border-l-4 border-green-500 bg-gray-50 rounded">
                                <div>
                                    <p class="font-semibold text-gray-900">John Smith</p>
                                    <p class="text-xs text-gray-500">2 minutes ago</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-600">+$127.50</p>
                                    <p class="text-xs text-gray-500">Invoice #1234</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-3 border-l-4 border-green-500 bg-gray-50 rounded">
                                <div>
                                    <p class="font-semibold text-gray-900">Sarah Johnson</p>
                                    <p class="text-xs text-gray-500">15 minutes ago</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-600">+$85.00</p>
                                    <p class="text-xs text-gray-500">Invoice #1233</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-3 border-l-4 border-blue-500 bg-gray-50 rounded">
                                <div>
                                    <p class="font-semibold text-gray-900">Supplier: MediCo</p>
                                    <p class="text-xs text-gray-500">1 hour ago</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-red-600">-$1,250.00</p>
                                    <p class="text-xs text-gray-500">Purchase Order</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Chart.js Configuration
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Revenue',
                    data: [12000, 19000, 15000, 21000, 18000, 24000, 20000],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });

        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pain Relief', 'Antibiotics', 'Vitamins', 'Cold & Flu', 'Other'],
                datasets: [{
                    data: [30, 25, 20, 15, 10],
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(139, 92, 246)',
                        'rgb(107, 114, 128)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        function showSection(section) {
            const items = document.querySelectorAll('.sidebar-item');
            items.forEach(item => item.classList.remove('sidebar-active'));
            event.target.closest('.sidebar-item').classList.add('sidebar-active');
        }

        function toggleNotifications() {
            alert('Notifications panel would open here with real-time updates');
        }

        function toggleSettings() {
            alert('Settings panel would open here');
        }

        function logout() {
            if(confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>
