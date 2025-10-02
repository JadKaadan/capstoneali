<?php
require_once 'db.php';

// Check if user is admin (you can add proper admin authentication)
if (!isset($_SESSION['pharmacist_id']) || $_SESSION['pharmacist_id'] != 1) {
    header("Location: pharmacist_login.php");
    exit();
}

$conn = getDatabaseConnection();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $registration_id = (int)$_POST['registration_id'];
    $action = $_POST['action'];
    $admin_notes = sanitizeInput($_POST['admin_notes'] ?? '');
    
    if ($action === 'approve') {
        // Get registration details
        $stmt = $conn->prepare("SELECT * FROM pharmacist_registrations WHERE registration_id = ?");
        $stmt->bind_param("i", $registration_id);
        $stmt->execute();
        $registration = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($registration) {
            // Create pharmacist account
            $insert_stmt = $conn->prepare("INSERT INTO pharmacists (username, password, full_name, email, phone, license_number, qualifications, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, TRUE, NOW())");
            $insert_stmt->bind_param("sssssss",
                $registration['username'],
                $registration['password'],
                $registration['full_name'],
                $registration['email'],
                $registration['phone'],
                $registration['license_number'],
                $registration['qualifications']
            );
            
            if ($insert_stmt->execute()) {
                // Update registration status
                $update_stmt = $conn->prepare("UPDATE pharmacist_registrations SET status = 'approved', reviewed_at = NOW(), reviewed_by = ?, admin_notes = ? WHERE registration_id = ?");
                $update_stmt->bind_param("isi", $_SESSION['pharmacist_id'], $admin_notes, $registration_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                logActivity($conn, 'pharmacist_approved', "Approved registration for: " . $registration['username']);
                $success = "Application approved successfully! User can now login.";
            }
            $insert_stmt->close();
        }
    } elseif ($action === 'reject') {
        $update_stmt = $conn->prepare("UPDATE pharmacist_registrations SET status = 'rejected', reviewed_at = NOW(), reviewed_by = ?, admin_notes = ? WHERE registration_id = ?");
        $update_stmt->bind_param("isi", $_SESSION['pharmacist_id'], $admin_notes, $registration_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        logActivity($conn, 'pharmacist_rejected', "Rejected registration ID: " . $registration_id);
        $success = "Application rejected.";
    }
}

// Fetch pending applications
$pending_query = "SELECT * FROM pharmacist_registrations WHERE status = 'pending' ORDER BY created_at DESC";
$pending_result = $conn->query($pending_query);

// Fetch reviewed applications
$reviewed_query = "SELECT pr.*, p.full_name as reviewer_name 
                  FROM pharmacist_registrations pr
                  LEFT JOIN pharmacists p ON pr.reviewed_by = p.pharmacist_id
                  WHERE pr.status IN ('approved', 'rejected')
                  ORDER BY pr.reviewed_at DESC
                  LIMIT 50";
$reviewed_result = $conn->query($reviewed_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - PharmaCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .animate-slide-in { animation: slideIn 0.3s ease-out; }
        .modal { display: none; }
        .modal.active { display: flex; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b sticky top-0 z-50">
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user-shield text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Admin Panel</h1>
                        <p class="text-xs text-gray-500">Pharmacist Application Review</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="pharmacist_dashboard.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                    <button onclick="logout()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8">
        <?php if (isset($success)): ?>
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg animate-slide-in">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                    <p class="text-green-700 font-medium"><?php echo htmlspecialchars($success); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 font-medium mb-2">Pending Applications</p>
                        <h3 class="text-4xl font-bold"><?php echo $pending_result->num_rows; ?></h3>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-3xl"></i>
                    </div>
                </div>
            </div>

            <?php
            $approved_count = $conn->query("SELECT COUNT(*) as count FROM pharmacist_registrations WHERE status = 'approved'")->fetch_assoc()['count'];
            $rejected_count = $conn->query("SELECT COUNT(*) as count FROM pharmacist_registrations WHERE status = 'rejected'")->fetch_assoc()['count'];
            ?>

            <div class="bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 font-medium mb-2">Approved</p>
                        <h3 class="text-4xl font-bold"><?php echo $approved_count; ?></h3>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-red-500 to-pink-500 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-100 font-medium mb-2">Rejected</p>
                        <h3 class="text-4xl font-bold"><?php echo $rejected_count; ?></h3>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-times-circle text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Applications -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-hourglass-half text-yellow-500 mr-3"></i>
                Pending Applications
            </h2>

            <?php if ($pending_result->num_rows === 0): ?>
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-inbox text-6xl mb-4"></i>
                    <p class="text-lg">No pending applications</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php while ($app = $pending_result->fetch_assoc()): ?>
                        <div class="border-2 border-gray-200 rounded-xl p-6 hover:border-blue-500 transition animate-slide-in">
                            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                                <!-- Applicant Info -->
                                <div class="lg:col-span-2">
                                    <div class="flex items-start space-x-4">
                                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-2xl flex-shrink-0">
                                            <?php echo strtoupper(substr($app['full_name'], 0, 2)); ?>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($app['full_name']); ?></h3>
                                            <p class="text-gray-600"><i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($app['username']); ?></p>
                                            <p class="text-gray-600"><i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($app['email']); ?></p>
                                            <?php if ($app['phone']): ?>
                                                <p class="text-gray-600"><i class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($app['phone']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Credentials -->
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-3">Credentials</h4>
                                    <p class="text-sm text-gray-700 mb-2">
                                        <span class="font-medium">License:</span><br>
                                        <span class="font-mono bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($app['license_number']); ?></span>
                                    </p>
                                    <?php if ($app['qualifications']): ?>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium">Qualifications:</span><br>
                                            <?php echo htmlspecialchars($app['qualifications']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Verification Score -->
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-3">Verification</h4>
                                    <div class="mb-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm text-gray-600">CV Score:</span>
                                            <span class="font-bold <?php echo $app['verification_score'] >= 70 ? 'text-green-600' : ($app['verification_score'] >= 50 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                                <?php echo $app['verification_score']; ?>%
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full <?php echo $app['verification_score'] >= 70 ? 'bg-green-500' : ($app['verification_score'] >= 50 ? 'bg-yellow-500' : 'bg-red-500'); ?>" style="width: <?php echo $app['verification_score']; ?>%"></div>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-3">
                                        <i class="fas fa-calendar mr-1"></i>
                                        Applied: <?php echo date('M d, Y', strtotime($app['created_at'])); ?>
                                    </p>
                                    <a href="uploads/cvs/<?php echo htmlspecialchars($app['cv_filename']); ?>" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition text-sm font-medium">
                                        <i class="fas fa-file-download mr-2"></i>View CV
                                    </a>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-6 pt-6 border-t flex gap-4">
                                <button onclick="openReviewModal(<?php echo $app['registration_id']; ?>, '<?php echo addslashes($app['full_name']); ?>', 'approve')" class="flex-1 px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:shadow-lg transition font-semibold">
                                    <i class="fas fa-check mr-2"></i>Approve
                                </button>
                                <button onclick="openReviewModal(<?php echo $app['registration_id']; ?>, '<?php echo addslashes($app['full_name']); ?>', 'reject')" class="flex-1 px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:shadow-lg transition font-semibold">
                                    <i class="fas fa-times mr-2"></i>Reject
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Reviewed Applications -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-history text-gray-500 mr-3"></i>
                Recent Reviews
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">License</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reviewed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($review = $reviewed_result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($review['full_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($review['email']); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($review['license_number']); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold <?php echo $review['verification_score'] >= 70 ? 'text-green-600' : ($review['verification_score'] >= 50 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                        <?php echo $review['verification_score']; ?>%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($review['status'] === 'approved'): ?>
                                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
                                            <i class="fas fa-check-circle mr-1"></i>Approved
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">
                                            <i class="fas fa-times-circle mr-1"></i>Rejected
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($review['reviewed_at'])); ?>
                                    <?php if ($review['reviewer_name']): ?>
                                        <br><span class="text-xs">by <?php echo htmlspecialchars($review['reviewer_name']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo htmlspecialchars($review['admin_notes'] ?: '-'); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md m-4">
            <div id="modalHeader" class="p-6 rounded-t-xl">
                <h2 class="text-2xl font-bold text-white" id="modalTitle"></h2>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="registration_id" id="modalRegistrationId">
                <input type="hidden" name="action" id="modalAction">
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Admin Notes
                    </label>
                    <textarea 
                        name="admin_notes" 
                        rows="4" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Add any notes or feedback..."
                    ></textarea>
                </div>

                <div class="flex gap-4">
                    <button type="button" onclick="closeModal()" class="flex-1 px-6 py-3 border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition font-semibold">
                        Cancel
                    </button>
                    <button type="submit" id="modalSubmitBtn" class="flex-1 px-6 py-3 text-white rounded-lg hover:shadow-lg transition font-semibold">
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReviewModal(registrationId, name, action) {
            const modal = document.getElementById('reviewModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalHeader = document.getElementById('modalHeader');
            const modalAction = document.getElementById('modalAction');
            const modalRegistrationId = document.getElementById('modalRegistrationId');
            const submitBtn = document.getElementById('modalSubmitBtn');
            
            modalRegistrationId.value = registrationId;
            modalAction.value = action;
            
            if (action === 'approve') {
                modalTitle.textContent = 'Approve Application: ' + name;
                modalHeader.className = 'p-6 rounded-t-xl bg-gradient-to-r from-green-500 to-green-600';
                submitBtn.className = 'flex-1 px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:shadow-lg transition font-semibold';
                submitBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Approve Application';
            } else {
                modalTitle.textContent = 'Reject Application: ' + name;
                modalHeader.className = 'p-6 rounded-t-xl bg-gradient-to-r from-red-500 to-red-600';
                submitBtn.className = 'flex-1 px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:shadow-lg transition font-semibold';
                submitBtn.innerHTML = '<i class="fas fa-times mr-2"></i>Reject Application';
            }
            
            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('reviewModal').classList.remove('active');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>
<?php
closeDatabaseConnection($conn);
?>
