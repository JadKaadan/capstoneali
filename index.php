<?php
require_once 'db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'pharmacist') {
        header("Location: pharmacist_dashboard.php");
        exit();
    } elseif ($_SESSION['user_type'] === 'customer') {
        header("Location: customer_dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PharmaCare - Advanced Pharmacy Management System for modern healthcare">
    <meta name="keywords" content="pharmacy, management, healthcare, prescriptions, drugs, medication">
    <meta name="author" content="PharmaCare Development Team">
    <title>PharmaCare - Advanced Pharmacy Management System</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }
        
        .animate-slide-in {
            animation: slideIn 0.8s ease-out;
        }
        
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        .feature-card {
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-bg {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }

        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-purple-50 min-h-screen">
    <!-- Navigation Bar -->
    <nav class="bg-white/80 backdrop-blur-md shadow-md fixed w-full z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center shadow-lg">
                        <i class="fas fa-pills text-white text-xl"></i>
                    </div>
                    <div>
                        <span class="text-2xl font-bold gradient-text">PharmaCare</span>
                        <p class="text-xs text-gray-500 hidden sm:block">Advanced Management System</p>
                    </div>
                </div>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-700 hover:text-blue-600 transition font-medium">Features</a>
                    <a href="#about" class="text-gray-700 hover:text-blue-600 transition font-medium">About</a>
                    <a href="#stats" class="text-gray-700 hover:text-blue-600 transition font-medium">Statistics</a>
                    <a href="#contact" class="text-gray-700 hover:text-blue-600 transition font-medium">Contact</a>
                </div>

                <div class="flex items-center space-x-3">
                    <a href="pharmacist_login.php" class="hidden sm:block px-4 py-2 text-blue-600 hover:bg-blue-50 rounded-lg transition font-medium">
                        <i class="fas fa-user-md mr-2"></i>Staff Login
                    </a>
                    <a href="customer_login.php" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:shadow-lg transition font-medium">
                        <i class="fas fa-user mr-2"></i>Patient Portal
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8 hero-bg">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="animate-slide-in">
                    <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6 leading-tight">
                        Modern Pharmacy
                        <span class="block gradient-text mt-2">Management System</span>
                    </h1>
                    <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                        Revolutionize your pharmacy operations with our cutting-edge digital solution. 
                        Streamline inventory, process prescriptions efficiently, and provide exceptional patient care—all from one powerful platform.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="customer_register.php" class="px-8 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:shadow-xl transition flex items-center justify-center space-x-2 text-lg font-semibold">
                            <i class="fas fa-user-plus"></i>
                            <span>Register as Patient</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="pharmacist_login.php" class="px-8 py-4 bg-white text-blue-600 rounded-lg hover:bg-gray-50 transition border-2 border-blue-600 text-lg font-semibold flex items-center justify-center">
                            <i class="fas fa-user-shield mr-2"></i>
                            <span>Pharmacist Access</span>
                        </a>
                    </div>
                    
                    <!-- Trust Indicators -->
                    <div class="mt-12 flex items-center space-x-8">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-blue-600">500+</p>
                            <p class="text-sm text-gray-600">Medications</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-green-600">1000+</p>
                            <p class="text-sm text-gray-600">Patients</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-purple-600">99.9%</p>
                            <p class="text-sm text-gray-600">Uptime</p>
                        </div>
                    </div>
                </div>
                
                <div class="relative animate-fade-in-up hidden lg:block">
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-purple-400 rounded-3xl blur-3xl opacity-30 animate-float"></div>
                        <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=800" alt="Pharmacy" class="relative rounded-3xl shadow-2xl w-full">
                    </div>
                    
                    <!-- Floating Cards -->
                    <div class="absolute -top-6 -left-6 bg-white p-4 rounded-xl shadow-xl animate-float">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">Real-time Updates</p>
                                <p class="text-xs text-gray-500">Instant inventory sync</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="absolute -bottom-6 -right-6 bg-white p-4 rounded-xl shadow-xl animate-float" style="animation-delay: 1s;">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">Secure & Compliant</p>
                                <p class="text-xs text-gray-500">HIPAA certified</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Powerful Features</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Everything you need to run a modern, efficient pharmacy</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature Cards -->
                <div class="feature-card p-8 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200">
                    <div class="w-16 h-16 bg-blue-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-boxes text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Inventory Management</h3>
                    <p class="text-gray-700 leading-relaxed">
                        Advanced inventory tracking with real-time stock updates, low-stock alerts, barcode scanning, and batch management. Never run out of essential medications.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li><i class="fas fa-check text-blue-600 mr-2"></i>Real-time stock tracking</li>
                        <li><i class="fas fa-check text-blue-600 mr-2"></i>Barcode scanner integration</li>
                        <li><i class="fas fa-check text-blue-600 mr-2"></i>Automated reorder alerts</li>
                    </ul>
                </div>

                <div class="feature-card p-8 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border border-green-200">
                    <div class="w-16 h-16 bg-green-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Patient Management</h3>
                    <p class="text-gray-700 leading-relaxed">
                        Comprehensive patient profiles with purchase history, prescription tracking, and personalized medication recommendations for better care.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li><i class="fas fa-check text-green-600 mr-2"></i>Complete patient records</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i>Purchase history tracking</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i>Digital receipts</li>
                    </ul>
                </div>

                <div class="feature-card p-8 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border border-purple-200">
                    <div class="w-16 h-16 bg-purple-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-prescription text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Smart Prescriptions</h3>
                    <p class="text-gray-700 leading-relaxed">
                        AI-powered symptom analysis to recommend appropriate medications. Quick prescription processing with drug interaction warnings.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li><i class="fas fa-check text-purple-600 mr-2"></i>Symptom-based recommendations</li>
                        <li><i class="fas fa-check text-purple-600 mr-2"></i>Drug interaction alerts</li>
                        <li><i class="fas fa-check text-purple-600 mr-2"></i>Digital prescriptions</li>
                    </ul>
                </div>

                <div class="feature-card p-8 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl border border-orange-200">
                    <div class="w-16 h-16 bg-orange-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-cash-register text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Advanced POS System</h3>
                    <p class="text-gray-700 leading-relaxed">
                        Lightning-fast point of sale with discount codes, VAT calculation, multiple payment methods, and instant receipt generation.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li><i class="fas fa-check text-orange-600 mr-2"></i>Quick checkout process</li>
                        <li><i class="fas fa-check text-orange-600 mr-2"></i>Discount code support</li>
                        <li><i class="fas fa-check text-orange-600 mr-2"></i>Automatic VAT calculation</li>
                    </ul>
                </div>

                <div class="feature-card p-8 bg-gradient-to-br from-red-50 to-red-100 rounded-xl border border-red-200">
                    <div class="w-16 h-16 bg-red-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Analytics & Reports</h3>
                    <p class="text-gray-700 leading-relaxed">
                        Comprehensive analytics dashboard with sales trends, revenue reports, inventory insights, and customer behavior analysis.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li><i class="fas fa-check text-red-600 mr-2"></i>Revenue analytics</li>
                        <li><i class="fas fa-check text-red-600 mr-2"></i>Sales reports</li>
                        <li><i class="fas fa-check text-red-600 mr-2"></i>Customer insights</li>
                    </ul>
                </div>

                <div class="feature-card p-8 bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl border border-indigo-200">
                    <div class="w-16 h-16 bg-indigo-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Security & Compliance</h3>
                    <p class="text-gray-700 leading-relaxed">
                        Enterprise-grade security with encrypted data, role-based access control, audit logs, and full healthcare compliance.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li><i class="fas fa-check text-indigo-600 mr-2"></i>Data encryption</li>
                        <li><i class="fas fa-check text-indigo-600 mr-2"></i>Role-based access</li>
                        <li><i class="fas fa-check text-indigo-600 mr-2"></i>HIPAA compliant</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 bg-gradient-to-br from-blue-600 to-purple-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl font-bold mb-6">About PharmaCare</h2>
                    <p class="text-xl text-blue-100 mb-6 leading-relaxed">
                        PharmaCare is a state-of-the-art pharmacy management system designed to revolutionize 
                        how pharmacies operate in the digital age. Built with modern technologies and healthcare 
                        best practices, we provide a comprehensive solution for both pharmacists and patients.
                    </p>
                    
                    <div class="space-y-6">
                        <div class="flex items-start space-x-4">
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-hospital text-2xl"></i>
                            </div>
                            <div>
                                <h4 class="text-xl font-semibold mb-2">For Pharmacists</h4>
                                <p class="text-blue-100">
                                    Streamline your entire operation with our intuitive dashboard. Manage inventory, 
                                    process sales, handle prescriptions, and access powerful analytics—all in one place.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-user-md text-2xl"></i>
                            </div>
                            <div>
                                <h4 class="text-xl font-semibold mb-2">For Patients</h4>
                                <p class="text-blue-100">
                                    Take control of your health with easy access to purchase history, digital receipts, 
                                    prescription tracking, and symptom-based medication recommendations.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8">
                    <h3 class="text-2xl font-bold mb-6">System Capabilities</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-4 bg-white/10 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-database text-2xl"></i>
                                <span class="font-medium">Database Status</span>
                            </div>
                            <span class="flex items-center">
                                <i class="fas fa-circle text-green-400 text-xs mr-2"></i>Active
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-white/10 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-server text-2xl"></i>
                                <span class="font-medium">API Status</span>
                            </div>
                            <span class="flex items-center">
                                <i class="fas fa-circle text-green-400 text-xs mr-2"></i>Online
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-white/10 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-shield-alt text-2xl"></i>
                                <span class="font-medium">Security</span>
                            </div>
                            <span class="flex items-center">
                                <i class="fas fa-circle text-green-400 text-xs mr-2"></i>Encrypted
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-white/10 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-cloud text-2xl"></i>
                                <span class="font-medium">Backup</span>
                            </div>
                            <span class="text-sm">Last: 2 hours ago</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section id="stats" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Trusted by Healthcare Professionals</h2>
                <p class="text-xl text-gray-600">Join the growing community of pharmacies using PharmaCare</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="text-5xl font-bold gradient-text mb-2">500+</div>
                    <p class="text-gray-600 font-medium">Medications Managed</p>
                </div>
                <div class="text-center">
                    <div class="text-5xl font-bold gradient-text mb-2">1,000+</div>
                    <p class="text-gray-600 font-medium">Active Patients</p>
                </div>
                <div class="text-center">
                    <div class="text-5xl font-bold gradient-text mb-2">10,000+</div>
                    <p class="text-gray-600 font-medium">Transactions Processed</p>
                </div>
                <div class="text-center">
                    <div class="text-5xl font-bold gradient-text mb-2">99.9%</div>
                    <p class="text-gray-600 font-medium">System Uptime</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-gray-900 mb-6">Ready to Transform Your Pharmacy?</h2>
            <p class="text-xl text-gray-600 mb-10">
                Join hundreds of pharmacies already using PharmaCare to streamline operations and improve patient care.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="customer_register.php" class="px-8 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:shadow-xl transition text-lg font-semibold">
                    <i class="fas fa-user-plus mr-2"></i>Register as Patient
                </a>
                <a href="pharmacist_login.php" class="px-8 py-4 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition text-lg font-semibold">
                    <i class="fas fa-user-shield mr-2"></i>Access Pharmacist Portal
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div class="col-span-2">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-pills text-white"></i>
                        </div>
                        <span class="text-2xl font-bold">PharmaCare</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Advanced pharmacy management for the digital age. Streamline operations, 
                        improve patient care, and grow your business with our comprehensive platform.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-blue-600 transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-blue-400 transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-pink-600 transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-blue-700 transition">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold text-lg mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-400 hover:text-white transition">Features</a></li>
                        <li><a href="#about" class="text-gray-400 hover:text-white transition">About Us</a></li>
                        <li><a href="pharmacist_login.php" class="text-gray-400 hover:text-white transition">Pharmacist Login</a></li>
                        <li><a href="customer_login.php" class="text-gray-400 hover:text-white transition">Patient Portal</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Terms of Service</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-lg mb-4">Contact Us</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-2"></i>
                            <span>123 Healthcare Avenue<br>Medical District, MD 12345</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone mr-2"></i>
                            <span>+1 (555) 123-4567</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-2"></i>
                            <span>support@pharmacare.com</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-clock mr-2"></i>
                            <span>24/7 Support Available</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400">
                    &copy; <?php echo date('Y'); ?> PharmaCare. All rights reserved. | 
                    <a href="#" class="hover:text-white transition">Privacy Policy</a> | 
                    <a href="#" class="hover:text-white transition">Terms of Service</a>
                </p>
                <p class="text-gray-500 text-sm mt-2">
                    Developed by PharmaCare Development Team | Version 2.0.0
                </p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="scrollTopBtn" onclick="scrollToTop()" class="fixed bottom-8 right-8 w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-full shadow-lg hover:shadow-xl transition opacity-0 pointer-events-none">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Scroll to top button
        const scrollTopBtn = document.getElementById('scrollTopBtn');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollTopBtn.style.opacity = '1';
                scrollTopBtn.style.pointerEvents = 'auto';
            } else {
                scrollTopBtn.style.opacity = '0';
                scrollTopBtn.style.pointerEvents = 'none';
            }
        });

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Navbar scroll effect
        const nav = document.querySelector('nav');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 50) {
                nav.classList.add('shadow-lg');
            } else {
                nav.classList.remove('shadow-lg');
            }
        });

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease-out';
            observer.observe(card);
        });

        // Console welcome message
        console.log('%c🏥 PharmaCare Management System', 'color: #4F46E5; font-size: 20px; font-weight: bold;');
        console.log('%cAdvanced Pharmacy Management for Modern Healthcare', 'color: #6B7280; font-size: 14px;');
        console.log('%cVersion 2.0.0 | Developed for Senior Project', 'color: #6B7280; font-size: 12px;');
    </script>
</body>
</html>
