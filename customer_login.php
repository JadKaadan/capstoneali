<?php
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $conn = getDatabaseConnection();
        $stmt = $conn->prepare("SELECT customer_id, username, password, full_name, email FROM customers WHERE username = ? AND is_active = TRUE");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $customer = $result->fetch_assoc();
            
            if (password_verify($password, $customer['password'])) {
                // Update last login
                $update_stmt = $conn->prepare("UPDATE customers SET last_login = NOW() WHERE customer_id = ?");
                $update_stmt->bind_param("i", $customer['customer_id']);
                $update_stmt->execute();
                
                // Set session variables
                $_SESSION['customer_id'] = $customer['customer_id'];
                $_SESSION['customer_username'] = $customer['username'];
                $_SESSION['customer_name'] = $customer['full_name'];
                $_SESSION['customer_email'] = $customer['email'];
                $_SESSION['user_type'] = 'customer';
                
                header("Location: customer_dashboard.php");
                exit();
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
        
        $stmt->close();
        closeDatabaseConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Login - PharmaCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo and Title -->
            <div class="text-center">
                <div class="flex justify-center mb-4">
                    <svg class="w-16 h-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900">Patient Portal</h2>
                <p class="mt-2 text-gray-600">Sign in to access your health records</p>
            </div>

            <!-- Login Form -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <?php if ($error): ?>
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            placeholder="Enter your username"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            placeholder="Enter your password"
                        >
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="remember" 
                                name="remember"
                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                            >
                            <label for="remember" class="ml-2 block text-sm text-gray-700">
                                Remember me
                            </label>
                        </div>
                        <a href="#" class="text-sm text-green-600 hover:text-green-500">
                            Forgot password?
                        </a>
                    </div>

                    <button 
                        type="submit"
                        class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition font-semibold shadow-lg hover:shadow-xl"
                    >
                        Sign In
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Don't have an account? 
                        <a href="customer_register.php" class="text-green-600 hover:text-green-500 font-medium">
                            Register Now
                        </a>
                    </p>
                    <p class="mt-2 text-sm text-gray-600">
                        Are you a pharmacist? 
                        <a href="pharmacist_login.php" class="text-blue-600 hover:text-blue-500 font-medium">
                            Staff Login
                        </a>
                    </p>
                    <p class="mt-2 text-sm text-gray-600">
                        <a href="index.php" class="text-gray-500 hover:text-gray-700">
                            ← Back to Home
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
