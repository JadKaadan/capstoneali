<?php
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $license_number = sanitizeInput($_POST['license_number'] ?? '');
    $qualifications = sanitizeInput($_POST['qualifications'] ?? '');
    
    // Validation
    if (empty($username) || empty($password) || empty($full_name) || empty($email) || empty($license_number)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload your CV (Required for verification).';
    } else {
        // Handle CV upload
        $cv_upload = handleFileUpload($_FILES['cv']);
        
        if (!$cv_upload['success']) {
            $error = $cv_upload['message'];
        } else {
            // Simple CV text extraction and verification (free method)
            $cv_text = extractTextFromFile($cv_upload['filepath']);
            $verification = verifyPharmacistCV($cv_text, $full_name, $license_number);
            
            if (!$verification['is_valid']) {
                $error = $verification['message'];
                unlink($cv_upload['filepath']); // Delete uploaded file
            } else {
                $conn = getDatabaseConnection();
                
                // Check if username or email exists
                $check_stmt = $conn->prepare("SELECT pharmacist_id FROM pharmacists WHERE username = ? OR email = ?");
                $check_stmt->bind_param("ss", $username, $email);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error = 'Username or email already exists.';
                    unlink($cv_upload['filepath']);
                } else {
                    // Create pending registration
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $verification_token = bin2hex(random_bytes(32));
                    
                    $stmt = $conn->prepare("INSERT INTO pharmacist_registrations (username, password, full_name, email, phone, license_number, qualifications, cv_filename, verification_score, verification_token, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
                    $stmt->bind_param("ssssssssis", 
                        $username, 
                        $hashed_password, 
                        $full_name, 
                        $email, 
                        $phone, 
                        $license_number, 
                        $qualifications,
                        $cv_upload['filename'],
                        $verification['score'],
                        $verification_token
                    );
                    
                    if ($stmt->execute()) {
                        $success = 'Registration submitted successfully! Your application is under review. You will receive an email notification once verified (typically within 24-48 hours).';
                        
                        // Log activity
                        logActivity($conn, 'pharmacist_registration_submitted', "User: $username, Email: $email", null, 'system');
                    } else {
                        $error = 'Registration failed. Please try again.';
                        unlink($cv_upload['filepath']);
                    }
                    
                    $stmt->close();
                }
                
                $check_stmt->close();
                closeDatabaseConnection($conn);
            }
        }
    }
}

// Free CV verification function using simple text analysis
function extractTextFromFile($filepath) {
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $text = '';
    
    if ($ext === 'pdf') {
        // Simple PDF text extraction (basic method)
        exec("pdftotext '$filepath' -", $output);
        $text = implode("\n", $output);
    } elseif (in_array($ext, ['doc', 'docx'])) {
        // For DOCX, we can extract from XML
        if ($ext === 'docx') {
            $zip = new ZipArchive;
            if ($zip->open($filepath) === TRUE) {
                $xml = $zip->getFromName('word/document.xml');
                $text = strip_tags($xml);
                $zip->close();
            }
        }
    }
    
    return strtolower($text);
}

function verifyPharmacistCV($cv_text, $full_name, $license_number) {
    $score = 0;
    $reasons = [];
    
    // Check for name presence
    $name_parts = explode(' ', strtolower($full_name));
    $name_found = 0;
    foreach ($name_parts as $part) {
        if (strlen($part) > 2 && strpos($cv_text, $part) !== false) {
            $name_found++;
        }
    }
    if ($name_found >= count($name_parts) * 0.5) {
        $score += 20;
    } else {
        $reasons[] = 'Name verification failed';
    }
    
    // Check for license number
    if (!empty($license_number) && strpos($cv_text, strtolower($license_number)) !== false) {
        $score += 20;
    }
    
    // Check for pharmacy-related keywords
    $pharmacy_keywords = [
        'pharmac', 'pharm.d', 'doctor of pharmacy', 'pharmaceutical', 
        'pharmacology', 'drug', 'medication', 'prescription', 'clinical',
        'licensed pharmacist', 'registered pharmacist', 'pharmacy degree',
        'bachelor of pharmacy', 'master of pharmacy', 'pharmaceutical sciences'
    ];
    
    $keywords_found = 0;
    foreach ($pharmacy_keywords as $keyword) {
        if (strpos($cv_text, $keyword) !== false) {
            $keywords_found++;
        }
    }
    
    if ($keywords_found >= 5) {
        $score += 30;
    } elseif ($keywords_found >= 3) {
        $score += 20;
    } elseif ($keywords_found >= 1) {
        $score += 10;
    } else {
        $reasons[] = 'Insufficient pharmacy-related qualifications found';
    }
    
    // Check for education section
    $education_keywords = ['education', 'degree', 'university', 'college', 'qualification', 'bachelor', 'master', 'phd'];
    foreach ($education_keywords as $keyword) {
        if (strpos($cv_text, $keyword) !== false) {
            $score += 5;
            break;
        }
    }
    
    // Check for experience section
    $experience_keywords = ['experience', 'work history', 'employment', 'worked', 'hospital', 'clinic', 'pharmacy'];
    foreach ($experience_keywords as $keyword) {
        if (strpos($cv_text, $keyword) !== false) {
            $score += 5;
            break;
        }
    }
    
    // Check for professional certifications
    $cert_keywords = ['certified', 'certification', 'license', 'accredited', 'registered'];
    foreach ($cert_keywords as $keyword) {
        if (strpos($cv_text, $keyword) !== false) {
            $score += 10;
            break;
        }
    }
    
    // Determine if valid
    $is_valid = $score >= 50; // Minimum 50% score required
    
    if (!$is_valid) {
        $message = 'CV verification failed. ' . implode('. ', $reasons) . '. Please ensure your CV clearly shows pharmacy qualifications and matches the provided information.';
    } else {
        $message = 'CV verified successfully!';
    }
    
    return [
        'is_valid' => $is_valid,
        'score' => $score,
        'message' => $message
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacist Registration - PharmaCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-slide-up {
            animation: slideInUp 0.6s ease-out;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        
        .file-upload-area {
            transition: all 0.3s ease;
        }
        
        .file-upload-area:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .file-upload-area.dragging {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: white;
        }
        
        .progress-step {
            transition: all 0.4s ease;
        }
        
        .progress-step.active {
            transform: scale(1.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-purple-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white/80 backdrop-blur-md shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="index.php" class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-pills text-white text-xl"></i>
                    </div>
                    <span class="text-xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">PharmaCare</span>
                </a>
                <a href="index.php" class="text-gray-600 hover:text-gray-900">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Home
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-12">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12 animate-fade-in">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full mb-4 shadow-lg">
                    <i class="fas fa-user-md text-white text-3xl"></i>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-3">Pharmacist Registration</h1>
                <p class="text-gray-600 text-lg">Join our team of healthcare professionals</p>
            </div>

            <!-- Progress Steps -->
            <div class="flex items-center justify-center mb-12 animate-slide-up">
                <div class="flex items-center space-x-4">
                    <div class="progress-step active flex flex-col items-center">
                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold mb-2">
                            1
                        </div>
                        <span class="text-sm font-medium text-blue-600">Personal Info</span>
                    </div>
                    <div class="w-16 h-1 bg-gray-300"></div>
                    <div class="progress-step flex flex-col items-center">
                        <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold mb-2">
                            2
                        </div>
                        <span class="text-sm font-medium text-gray-500">Credentials</span>
                    </div>
                    <div class="w-16 h-1 bg-gray-300"></div>
                    <div class="progress-step flex flex-col items-center">
                        <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold mb-2">
                            3
                        </div>
                        <span class="text-sm font-medium text-gray-500">Verification</span>
                    </div>
                </div>
            </div>

            <!-- Registration Form -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-12 animate-slide-up">
                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg animate-fade-in">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3 mt-1"></i>
                            <div>
                                <h3 class="text-red-800 font-semibold">Registration Error</h3>
                                <p class="text-red-700 text-sm mt-1"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-6 p-6 bg-green-50 border-l-4 border-green-500 rounded-lg animate-fade-in">
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 text-2xl mr-3"></i>
                            <div>
                                <h3 class="text-green-800 font-bold text-lg mb-2">Application Submitted!</h3>
                                <p class="text-green-700 mb-4"><?php echo htmlspecialchars($success); ?></p>
                                <a href="pharmacist_login.php" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                                    <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>

                <form method="POST" action="" enctype="multipart/form-data" class="space-y-8" id="registrationForm">
                    <!-- Personal Information -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-user text-blue-600"></i>
                            </div>
                            Personal Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="full_name" 
                                    required
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="Dr. John Smith"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    required
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="john.smith@example.com"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Phone Number
                                </label>
                                <input 
                                    type="tel" 
                                    name="phone"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="+1 (555) 000-0000"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    License Number <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="license_number" 
                                    required
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="RPh123456"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Account Security -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-lock text-purple-600"></i>
                            </div>
                            Account Security
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Username <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="username" 
                                    required
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="Choose a unique username"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Qualifications
                                </label>
                                <input 
                                    type="text" 
                                    name="qualifications"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="Pharm.D, RPh, etc."
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input 
                                        type="password" 
                                        name="password" 
                                        required
                                        minlength="8"
                                        id="password"
                                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="Min. 8 characters"
                                    >
                                    <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-3 text-gray-500 hover:text-gray-700">
                                        <i class="fas fa-eye" id="password-icon"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Confirm Password <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input 
                                        type="password" 
                                        name="confirm_password" 
                                        required
                                        id="confirm_password"
                                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="Confirm your password"
                                    >
                                    <button type="button" onclick="togglePassword('confirm_password')" class="absolute right-3 top-3 text-gray-500 hover:text-gray-700">
                                        <i class="fas fa-eye" id="confirm_password-icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CV Upload -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-file-medical text-green-600"></i>
                            </div>
                            Professional Verification
                        </h3>
                        
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-lg">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-600 text-xl mr-3 mt-1"></i>
                                <div>
                                    <h4 class="font-semibold text-blue-900 mb-2">Why we need your CV:</h4>
                                    <ul class="text-sm text-blue-800 space-y-1">
                                        <li>• Verify your pharmacy qualifications and license</li>
                                        <li>• Ensure compliance with healthcare regulations</li>
                                        <li>• Maintain the highest standards of patient safety</li>
                                        <li>• Your CV is securely stored and never shared</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="file-upload-area border-3 border-dashed border-gray-300 rounded-xl p-8 text-center bg-gradient-to-br from-gray-50 to-blue-50" id="dropZone">
                            <input 
                                type="file" 
                                name="cv" 
                                id="cvInput" 
                                required
                                accept=".pdf,.doc,.docx"
                                class="hidden"
                                onchange="handleFileSelect(event)"
                            >
                            <label for="cvInput" class="cursor-pointer">
                                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full mb-4 shadow-lg">
                                    <i class="fas fa-cloud-upload-alt text-white text-3xl"></i>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 mb-2">Upload Your CV/Resume <span class="text-red-500">*</span></h4>
                                <p class="text-gray-600 mb-4">Drag and drop or click to browse</p>
                                <p class="text-sm text-gray-500">Accepted formats: PDF, DOC, DOCX (Max 5MB)</p>
                            </label>
                            <div id="fileInfo" class="mt-4 hidden">
                                <div class="inline-flex items-center px-6 py-3 bg-green-100 text-green-800 rounded-lg">
                                    <i class="fas fa-file-check mr-2"></i>
                                    <span id="fileName"></span>
                                    <button type="button" onclick="removeFile()" class="ml-4 text-red-600 hover:text-red-800">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input 
                                type="checkbox" 
                                required
                                class="mt-1 w-5 h-5 text-blue-600 border-2 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                            >
                            <span class="text-sm text-gray-700">
                                I confirm that all information provided is accurate and I agree to the 
                                <a href="#" class="text-blue-600 hover:text-blue-700 font-semibold">Terms of Service</a> 
                                and 
                                <a href="#" class="text-blue-600 hover:text-blue-700 font-semibold">Privacy Policy</a>. 
                                I understand that false information may result in rejection or termination of my account.
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button 
                            type="submit"
                            class="flex-1 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-lg font-bold rounded-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105"
                        >
                            <i class="fas fa-user-plus mr-2"></i>Submit Application
                        </button>
                        <a 
                            href="pharmacist_login.php"
                            class="flex-1 py-4 border-2 border-gray-300 text-gray-700 text-lg font-bold rounded-xl hover:bg-gray-50 transition text-center"
                        >
                            Already Registered? Login
                        </a>
                    </div>
                </form>

                <?php endif; ?>
            </div>

            <!-- Additional Info -->
            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 animate-fade-in">
                <div class="bg-white rounded-xl shadow-lg p-6 text-center transform hover:scale-105 transition">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-check text-blue-600 text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">Secure Verification</h4>
                    <p class="text-sm text-gray-600">Your data is encrypted and securely processed</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center transform hover:scale-105 transition">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-clock text-green-600 text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">Quick Review</h4>
                    <p class="text-sm text-gray-600">Most applications reviewed within 24-48 hours</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center transform hover:scale-105 transition">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-headset text-purple-600 text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">24/7 Support</h4>
                    <p class="text-sm text-gray-600">Need help? Our team is here to assist you</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password toggle
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // File upload handling
        const dropZone = document.getElementById('dropZone');
        const cvInput = document.getElementById('cvInput');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');

        // Drag and drop
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragging');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragging');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragging');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                cvInput.files = files;
                handleFileSelect({ target: cvInput });
            }
        });

        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload a PDF, DOC, or DOCX file.');
                    cvInput.value = '';
                    return;
                }
                
                if (file.size > maxSize) {
                    alert('File size must be less than 5MB.');
                    cvInput.value = '';
                    return;
                }
                
                fileName.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
                fileInfo.classList.remove('hidden');
                dropZone.style.borderColor = '#10b981';
                dropZone.style.backgroundColor = '#f0fdf4';
            }
        }

        function removeFile() {
            cvInput.value = '';
            fileInfo.classList.add('hidden');
            dropZone.style.borderColor = '';
            dropZone.style.backgroundColor = '';
        }

        // Form validation
        document.getElementById('registrationForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
            
            if (!cvInput.files || cvInput.files.length === 0) {
                e.preventDefault();
                alert('Please upload your CV!');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing Application...';
        });

        // Smooth animations on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.animate-fade-in, .animate-slide-up').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease-out';
            observer.observe(el);
        });
    </script>
</body>
</html>
