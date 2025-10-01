<?php
/**
 * Enhanced API Handler for PharmaCare Management System
 * Fully integrated with database operations
 */

require_once 'db.php';

// Set JSON response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verify CSRF token for state-changing operations
function verifyCSRF() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verifyCSRFToken($token)) {
            sendResponse(false, 'Invalid CSRF token', null, 403);
        }
    }
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Verify CSRF for non-GET requests
verifyCSRF();

// Response helper function
function sendResponse($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Check authentication
function checkAuth($userType = null) {
    if (!isset($_SESSION['user_type'])) {
        sendResponse(false, 'Authentication required', null, 401);
    }
    if ($userType && $_SESSION['user_type'] !== $userType) {
        sendResponse(false, 'Insufficient permissions', null, 403);
    }
    return true;
}

// Route the request
try {
    switch ($action) {
        
        // ============ INVENTORY MANAGEMENT ============
        case 'get_inventory':
            checkAuth('pharmacist');
            getInventory();
            break;
            
        case 'add_item':
            checkAuth('pharmacist');
            addInventoryItem();
            break;
            
        case 'update_item':
            checkAuth('pharmacist');
            updateInventoryItem();
            break;
            
        case 'delete_item':
            checkAuth('pharmacist');
            deleteInventoryItem();
            break;
            
        case 'search_items':
            searchInventoryItems();
            break;
            
        case 'low_stock_alert':
            checkAuth('pharmacist');
            getLowStockItems();
            break;
            
        // ============ SALES & TRANSACTIONS ============
        case 'create_sale':
            checkAuth('pharmacist');
            createSale();
            break;
            
        case 'get_sales':
            checkAuth('pharmacist');
            getSalesHistory();
            break;
            
        case 'get_sale_details':
            getSaleDetails();
            break;
            
        // ============ CUSTOMER MANAGEMENT ============
        case 'get_customers':
            checkAuth('pharmacist');
            getCustomers();
            break;
            
        case 'get_customer_details':
            getCustomerDetails();
            break;
            
        case 'get_customer_receipts':
            getCustomerReceipts();
            break;
            
        // ============ PRESCRIPTION/CONSULTATION ============
        case 'create_consultation':
            createConsultation();
            break;
            
        case 'recommend_drugs':
            recommendDrugs();
            break;
            
        case 'get_consultations':
            getConsultations();
            break;
            
        // ============ ANALYTICS & REPORTS ============
        case 'get_dashboard_stats':
            checkAuth('pharmacist');
            getDashboardStats();
            break;
            
        case 'get_customer_stats':
            checkAuth('customer');
            getCustomerStats();
            break;
            
        default:
            sendResponse(false, 'Invalid action', null, 400);
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    sendResponse(false, 'An error occurred: ' . $e->getMessage(), null, 500);
}

// ============ INVENTORY FUNCTIONS ============

function getInventory() {
    $conn = getDatabaseConnection();
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = ($page - 1) * $limit;
    
    $search = isset($_GET['search']) ? '%' . sanitizeInput($_GET['search']) . '%' : '%';
    
    $query = "SELECT 
                item_id,
                item_name,
                description,
                quantity,
                price,
                (quantity * price) as total_value,
                created_at,
                CASE 
                    WHEN quantity = 0 THEN 'Out of Stock'
                    WHEN quantity < 20 THEN 'Low Stock'
                    WHEN quantity < 50 THEN 'Medium Stock'
                    ELSE 'In Stock'
                END as stock_status
              FROM items 
              WHERE (item_name LIKE ? OR description LIKE ?)
              ORDER BY item_name ASC LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssii", $search, $search, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM items WHERE (item_name LIKE ? OR description LIKE ?)";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("ss", $search, $search);
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['total'];
    
    $stmt->close();
    $count_stmt->close();
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Inventory retrieved successfully', [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function addInventoryItem() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['item_name']) || !isset($data['quantity']) || !isset($data['price'])) {
        sendResponse(false, 'Missing required fields', null, 400);
    }
    
    $conn = getDatabaseConnection();
    
    // Check if item already exists
    $check_stmt = $conn->prepare("SELECT item_id FROM items WHERE LOWER(item_name) = LOWER(?)");
    $check_stmt->bind_param("s", $data['item_name']);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $check_stmt->close();
        closeDatabaseConnection($conn);
        sendResponse(false, 'Item already exists in inventory', null, 409);
    }
    $check_stmt->close();
    
    // Insert new item
    $stmt = $conn->prepare("INSERT INTO items (item_name, description, quantity, price, created_at) VALUES (?, ?, ?, ?, NOW())");
    $description = $data['description'] ?? '';
    $stmt->bind_param("ssid", 
        $data['item_name'],
        $description,
        $data['quantity'],
        $data['price']
    );
    
    if ($stmt->execute()) {
        $item_id = $conn->insert_id;
        
        // Log activity
        logActivity($conn, 'inventory_add', "Added item: " . $data['item_name']);
        
        $stmt->close();
        closeDatabaseConnection($conn);
        sendResponse(true, 'Item added successfully', ['item_id' => $item_id]);
    } else {
        $stmt->close();
        closeDatabaseConnection($conn);
        sendResponse(false, 'Failed to add item', null, 500);
    }
}

function updateInventoryItem() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['item_id'])) {
        sendResponse(false, 'Item ID is required', null, 400);
    }
    
    $conn = getDatabaseConnection();
    
    $updates = [];
    $types = "";
    $values = [];
    
    if (isset($data['item_name'])) {
        $updates[] = "item_name = ?";
        $types .= "s";
        $values[] = $data['item_name'];
    }
    if (isset($data['description'])) {
        $updates[] = "description = ?";
        $types .= "s";
        $values[] = $data['description'];
    }
    if (isset($data['quantity'])) {
        $updates[] = "quantity = ?";
        $types .= "i";
        $values[] = $data['quantity'];
    }
    if (isset($data['price'])) {
        $updates[] = "price = ?";
        $types .= "d";
        $values[] = $data['price'];
    }
    
    if (empty($updates)) {
        closeDatabaseConnection($conn);
        sendResponse(false, 'No fields to update', null, 400);
    }
    
    $query = "UPDATE items SET " . implode(", ", $updates) . ", updated_at = NOW() WHERE item_id = ?";
    $types .= "i";
    $values[] = $data['item_id'];
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        logActivity($conn, 'inventory_update', "Updated item ID: " . $data['item_id']);
        $stmt->close();
        closeDatabaseConnection($conn);
        sendResponse(true, 'Item updated successfully');
    } else {
        $stmt->close();
        closeDatabaseConnection($conn);
        sendResponse(false, 'Failed to update item', null, 500);
    }
}

function deleteInventoryItem() {
    $item_id = $_GET['item_id'] ?? null;
    
    if (!$item_id) {
        sendResponse(false, 'Item ID is required', null, 400);
    }
    
    $conn = getDatabaseConnection();
    
    // Get item name for logging
    $select_stmt = $conn->prepare("SELECT item_name FROM items WHERE item_id = ?");
    $select_stmt->bind_param("i", $item_id);
    $select_stmt->execute();
    $result = $select_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $select_stmt->close();
        closeDatabaseConnection($conn);
        sendResponse(false, 'Item not found', null, 404);
    }
    
    $item = $result->fetch_assoc();
    $select_stmt->close();
    
    // Delete item
    $stmt = $conn->prepare("DELETE FROM items WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    
    if ($stmt->execute()) {
        logActivity($conn, 'inventory_delete', "Deleted item: " . $item['item_name']);
        $stmt->close();
        closeDatabaseConnection($conn);
        sendResponse(true, 'Item deleted successfully');
    } else {
        $stmt->close();
        closeDatabaseConnection($conn);
        sendResponse(false, 'Failed to delete item', null, 500);
    }
}

function searchInventoryItems() {
    $search = $_GET['search'] ?? '';
    
    if (empty($search)) {
        sendResponse(false, 'Search query is required', null, 400);
    }
    
    $conn = getDatabaseConnection();
    
    $searchTerm = '%' . sanitizeInput($search) . '%';
    
    $query = "SELECT item_id, item_name, description, quantity, price 
              FROM items 
              WHERE item_name LIKE ? OR description LIKE ?
              ORDER BY item_name ASC LIMIT 20";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Search results retrieved', ['items' => $items]);
}

function getLowStockItems() {
    $conn = getDatabaseConnection();
    
    $threshold = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 20;
    
    $stmt = $conn->prepare("SELECT item_id, item_name, quantity, price FROM items WHERE quantity < ? ORDER BY quantity ASC");
    $stmt->bind_param("i", $threshold);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Low stock items retrieved', [
        'items' => $items,
        'count' => count($items),
        'threshold' => $threshold
    ]);
}

// ============ SALES FUNCTIONS ============

function createSale() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['customer_id']) || !isset($data['items']) || empty($data['items'])) {
        sendResponse(false, 'Invalid sale data', null, 400);
    }
    
    $conn = getDatabaseConnection();
    $conn->begin_transaction();
    
    try {
        $total_amount = 0;
        $discount_rate = $data['discount_rate'] ?? 0;
        $vat_rate = 0.20; // 20% VAT
        
        // Calculate total amount
        foreach ($data['items'] as $item) {
            $total_amount += $item['quantity'] * $item['price'];
        }
        
        $discount_amount = $total_amount * ($discount_rate / 100);
        $amount_after_discount = $total_amount - $discount_amount;
        $vat_amount = $amount_after_discount * $vat_rate;
        $final_amount = $amount_after_discount + $vat_amount;
        
        // Insert sale record
        $pharmacist_id = $_SESSION['pharmacist_id'] ?? null;
        $sale_stmt = $conn->prepare("INSERT INTO sales (customer_id, pharmacist_id, total_amount, discount_amount, vat_amount, final_amount, sale_date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $sale_stmt->bind_param("iidddd", 
            $data['customer_id'],
            $pharmacist_id,
            $total_amount,
            $discount_amount,
            $vat_amount,
            $final_amount
        );
        $sale_stmt->execute();
        $sale_id = $conn->insert_id;
        $sale_stmt->close();
        
        // Insert sale items and update inventory
        $item_stmt = $conn->prepare("INSERT INTO sale_items (sale_id, item_id, quantity, price_per_unit, subtotal) VALUES (?, ?, ?, ?, ?)");
        $update_stmt = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE item_id = ? AND quantity >= ?");
        
        foreach ($data['items'] as $item) {
            $subtotal = $item['quantity'] * $item['price'];
            
            // Check stock availability
            $update_stmt->bind_param("iii", $item['quantity'], $item['item_id'], $item['quantity']);
            $update_stmt->execute();
            
            if ($update_stmt->affected_rows === 0) {
                throw new Exception("Insufficient stock for item ID: " . $item['item_id']);
            }
            
            // Insert sale item
            $item_stmt->bind_param("iiidd", 
                $sale_id,
                $item['item_id'],
                $item['quantity'],
                $item['price'],
                $subtotal
            );
            $item_stmt->execute();
        }
        
        $item_stmt->close();
        $update_stmt->close();
        
        // Create receipt
        $receipt_content = generateReceipt($sale_id, $data, $final_amount);
        $receipt_stmt = $conn->prepare("INSERT INTO receipts (customer_id, sale_id, receipt_content, created_at) VALUES (?, ?, ?, NOW())");
        $receipt_stmt->bind_param("iis", $data['customer_id'], $sale_id, $receipt_content);
        $receipt_stmt->execute();
        $receipt_id = $conn->insert_id;
        $receipt_stmt->close();
        
        $conn->commit();
        
        logActivity($conn, 'sale_created', "Sale #$sale_id completed for $$final_amount");
        
        closeDatabaseConnection($conn);
        
        sendResponse(true, 'Sale completed successfully', [
            'sale_id' => $sale_id,
            'receipt_id' => $receipt_id,
            'final_amount' => $final_amount
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        closeDatabaseConnection($conn);
        sendResponse(false, 'Sale failed: ' . $e->getMessage(), null, 500);
    }
}

function generateReceipt($sale_id, $data, $final_amount) {
    $receipt = "========== PHARMACARE RECEIPT ==========\n";
    $receipt .= "Sale ID: #" . str_pad($sale_id, 6, '0', STR_PAD_LEFT) . "\n";
    $receipt .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $receipt .= "=========================================\n\n";
    
    foreach ($data['items'] as $item) {
        $subtotal = $item['quantity'] * $item['price'];
        $receipt .= $item['item_name'] . "\n";
        $receipt .= "  " . $item['quantity'] . " x $" . number_format($item['price'], 2) . " = $" . number_format($subtotal, 2) . "\n\n";
    }
    
    $receipt .= "=========================================\n";
    $receipt .= "Total: $" . number_format($final_amount, 2) . "\n";
    $receipt .= "=========================================\n";
    $receipt .= "Thank you for your business!\n";
    
    return $receipt;
}

// ============ CUSTOMER FUNCTIONS ============

function getCustomers() {
    $conn = getDatabaseConnection();
    
    $query = "SELECT customer_id, username, full_name, email, phone, created_at, last_login 
              FROM customers 
              WHERE is_active = TRUE 
              ORDER BY full_name ASC";
    
    $result = $conn->query($query);
    $customers = $result->fetch_all(MYSQLI_ASSOC);
    
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Customers retrieved successfully', ['customers' => $customers]);
}

function getCustomerDetails() {
    $customer_id = $_GET['customer_id'] ?? $_SESSION['customer_id'] ?? null;
    
    if (!$customer_id) {
        sendResponse(false, 'Customer ID is required', null, 400);
    }
    
    $conn = getDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT customer_id, username, full_name, email, phone, address, created_at, last_login 
                            FROM customers 
                            WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        closeDatabaseConnection($conn);
        sendResponse(false, 'Customer not found', null, 404);
    }
    
    $customer = $result->fetch_assoc();
    $stmt->close();
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Customer details retrieved', ['customer' => $customer]);
}

function getCustomerReceipts() {
    $customer_id = $_GET['customer_id'] ?? $_SESSION['customer_id'] ?? null;
    
    if (!$customer_id) {
        sendResponse(false, 'Customer ID is required', null, 400);
    }
    
    $conn = getDatabaseConnection();
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    $stmt = $conn->prepare("SELECT r.receipt_id, r.sale_id, r.receipt_content, r.created_at,
                                   s.final_amount
                            FROM receipts r
                            LEFT JOIN sales s ON r.sale_id = s.sale_id
                            WHERE r.customer_id = ? 
                            ORDER BY r.created_at DESC
                            LIMIT ?");
    $stmt->bind_param("ii", $customer_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $receipts = $result->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Receipts retrieved successfully', ['receipts' => $receipts]);
}

// ============ CONSULTATION FUNCTIONS ============

function createConsultation() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['customer_id']) || !isset($data['symptoms'])) {
        sendResponse(false, 'Invalid consultation data', null, 400);
    }
    
    $conn = getDatabaseConnection();
    
    $stmt = $conn->prepare("INSERT INTO consultations (customer_id, symptoms, recommended_items, consultation_date) VALUES (?, ?, ?, NOW())");
    $recommended_items = $data['recommended_items'] ?? '';
    $stmt->bind_param("iss", 
        $data['customer_id'],
        $data['symptoms'],
        $recommended_items
    );
    
    if ($stmt->execute()) {
        $consultation_id = $conn->insert_id;
        $stmt->close();
        closeDatabaseConnection($conn);
        sendResponse(true, 'Consultation saved successfully', ['consultation_id' => $consultation_id]);
    } else {
        $stmt->close();
        closeDatabaseConnection($conn);
        sendResponse(false, 'Failed to save consultation', null, 500);
    }
}

function recommendDrugs() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['symptoms'])) {
        sendResponse(false, 'Symptoms are required', null, 400);
    }
    
    $conn = getDatabaseConnection();
    
    // Simple symptom-to-medication mapping (in production, this would be more sophisticated)
    $symptomMappings = [
        'headache' => ['Paracetamol 500mg', 'Ibuprofen 400mg', 'Aspirin 100mg'],
        'fever' => ['Paracetamol 500mg', 'Ibuprofen 400mg'],
        'cold' => ['Cetirizine 10mg', 'Paracetamol 500mg', 'Vitamin C 1000mg'],
        'cough' => ['Dextromethorphan 15mg', 'Guaifenesin 200mg'],
        'allergy' => ['Cetirizine 10mg', 'Loratadine 10mg'],
        'stomach' => ['Omeprazole 20mg', 'Antacid'],
        'joint_pain' => ['Ibuprofen 400mg', 'Diclofenac 50mg'],
        'skin' => ['Hydrocortisone Cream', 'Antibiotic Ointment']
    ];
    
    $symptoms = strtolower($data['symptoms']);
    $recommendations = [];
    
    // Find matching medications
    foreach ($symptomMappings as $symptom => $medications) {
        if (strpos($symptoms, $symptom) !== false) {
            $recommendations = array_merge($recommendations, $medications);
        }
    }
    
    // Remove duplicates
    $recommendations = array_unique($recommendations);
    
    // Get medication details from database
    if (!empty($recommendations)) {
        $placeholders = implode(',', array_fill(0, count($recommendations), '?'));
        $query = "SELECT item_id, item_name, description, price, quantity 
                  FROM items 
                  WHERE item_name IN ($placeholders)
                  AND quantity > 0";
        
        $stmt = $conn->prepare($query);
        $types = str_repeat('s', count($recommendations));
        $stmt->bind_param($types, ...$recommendations);
        $stmt->execute();
        $result = $stmt->get_result();
        $medications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $medications = [];
    }
    
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Recommendations generated', [
        'medications' => $medications,
        'disclaimer' => 'These are general recommendations. Please consult with a pharmacist for proper medical advice.'
    ]);
}

function getConsultations() {
    $customer_id = $_GET['customer_id'] ?? $_SESSION['customer_id'] ?? null;
    
    if (!$customer_id) {
        sendResponse(false, 'Customer ID is required', null, 400);
    }
    
    $conn = getDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT consultation_id, symptoms, recommended_items, consultation_date 
                            FROM consultations 
                            WHERE customer_id = ? 
                            ORDER BY consultation_date DESC 
                            LIMIT 20");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $consultations = $result->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Consultations retrieved', ['consultations' => $consultations]);
}

// ============ ANALYTICS FUNCTIONS ============

function getDashboardStats() {
    $conn = getDatabaseConnection();
    
    // Total revenue this month
    $revenue_query = "SELECT COALESCE(SUM(final_amount), 0) as total 
                      FROM sales 
                      WHERE MONTH(sale_date) = MONTH(CURRENT_DATE()) 
                      AND YEAR(sale_date) = YEAR(CURRENT_DATE())";
    $revenue_result = $conn->query($revenue_query);
    $total_revenue = $revenue_result->fetch_assoc()['total'];
    
    // Total prescriptions this week
    $prescriptions_query = "SELECT COUNT(*) as total 
                           FROM sales 
                           WHERE WEEK(sale_date) = WEEK(CURRENT_DATE())
                           AND YEAR(sale_date) = YEAR(CURRENT_DATE())";
    $prescriptions_result = $conn->query($prescriptions_query);
    $total_prescriptions = $prescriptions_result->fetch_assoc()['total'];
    
    // Low stock count
    $low_stock_query = "SELECT COUNT(*) as total FROM items WHERE quantity < 20";
    $low_stock_result = $conn->query($low_stock_query);
    $low_stock_count = $low_stock_result->fetch_assoc()['total'];
    
    // Active customers
    $customers_query = "SELECT COUNT(*) as total FROM customers WHERE is_active = TRUE";
    $customers_result = $conn->query($customers_query);
    $active_customers = $customers_result->fetch_assoc()['total'];
    
    // Top selling items
    $top_items_query = "SELECT i.item_name, SUM(si.quantity) as total_sold, SUM(si.subtotal) as revenue
                        FROM sale_items si
                        JOIN items i ON si.item_id = i.item_id
                        JOIN sales s ON si.sale_id = s.sale_id
                        WHERE MONTH(s.sale_date) = MONTH(CURRENT_DATE())
                        GROUP BY si.item_id
                        ORDER BY total_sold DESC
                        LIMIT 5";
    $top_items_result = $conn->query($top_items_query);
    $top_items = $top_items_result->fetch_all(MYSQLI_ASSOC);
    
    // Recent transactions
    $recent_sales_query = "SELECT s.sale_id, s.final_amount, s.sale_date, c.full_name as customer_name
                          FROM sales s
                          LEFT JOIN customers c ON s.customer_id = c.customer_id
                          ORDER BY s.sale_date DESC
                          LIMIT 10";
    $recent_sales_result = $conn->query($recent_sales_query);
    $recent_sales = $recent_sales_result->fetch_all(MYSQLI_ASSOC);
    
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Dashboard stats retrieved', [
        'total_revenue' => $total_revenue,
        'total_prescriptions' => $total_prescriptions,
        'low_stock_count' => $low_stock_count,
        'active_customers' => $active_customers,
        'top_selling_items' => $top_items,
        'recent_transactions' => $recent_sales
    ]);
}

function getCustomerStats() {
    $customer_id = $_SESSION['customer_id'] ?? null;
    
    if (!$customer_id) {
        sendResponse(false, 'Authentication required', null, 401);
    }
    
    $conn = getDatabaseConnection();
    
    // Total prescriptions
    $prescriptions_query = "SELECT COUNT(*) as total FROM sales WHERE customer_id = ?";
    $stmt = $conn->prepare($prescriptions_query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $total_prescriptions = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // This month's spending
    $spending_query = "SELECT COALESCE(SUM(final_amount), 0) as total 
                      FROM sales 
                      WHERE customer_id = ? 
                      AND MONTH(sale_date) = MONTH(CURRENT_DATE())
                      AND YEAR(sale_date) = YEAR(CURRENT_DATE())";
    $stmt = $conn->prepare($spending_query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $month_spending = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Loyalty points (simple calculation: $1 = 10 points)
    $total_spending_query = "SELECT COALESCE(SUM(final_amount), 0) as total FROM sales WHERE customer_id = ?";
    $stmt = $conn->prepare($total_spending_query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $total_spending = $stmt->get_result()->fetch_assoc()['total'];
    $loyalty_points = floor($total_spending * 10);
    $stmt->close();
    
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Customer stats retrieved', [
        'total_prescriptions' => $total_prescriptions,
        'month_spending' => $month_spending,
        'loyalty_points' => $loyalty_points
    ]);
}

function getSaleDetails() {
    $sale_id = $_GET['sale_id'] ?? null;
    
    if (!$sale_id) {
        sendResponse(false, 'Sale ID is required', null, 400);
    }
    
    $conn = getDatabaseConnection();
    
    // Get sale information
    $sale_query = "SELECT s.*, c.full_name as customer_name, p.full_name as pharmacist_name
                   FROM sales s
                   LEFT JOIN customers c ON s.customer_id = c.customer_id
                   LEFT JOIN pharmacists p ON s.pharmacist_id = p.pharmacist_id
                   WHERE s.sale_id = ?";
    
    $stmt = $conn->prepare($sale_query);
    $stmt->bind_param("i", $sale_id);
    $stmt->execute();
    $sale_result = $stmt->get_result();
    
    if ($sale_result->num_rows === 0) {
        $stmt->close();
        closeDatabaseConnection($conn);
        sendResponse(false, 'Sale not found', null, 404);
    }
    
    $sale = $sale_result->fetch_assoc();
    $stmt->close();
    
    // Get sale items
    $items_query = "SELECT si.*, i.item_name, i.description
                    FROM sale_items si
                    JOIN items i ON si.item_id = i.item_id
                    WHERE si.sale_id = ?";
    
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param("i", $sale_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    $items = $items_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Sale details retrieved', [
        'sale' => $sale,
        'items' => $items
    ]);
}

function getSalesHistory() {
    $conn = getDatabaseConnection();
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = ($page - 1) * $limit;
    
    // Date filters
    $start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default to start of current month
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    
    $query = "SELECT 
                s.sale_id,
                s.total_amount,
                s.discount_amount,
                s.vat_amount,
                s.final_amount,
                s.sale_date,
                c.full_name as customer_name,
                p.full_name as pharmacist_name
              FROM sales s
              LEFT JOIN customers c ON s.customer_id = c.customer_id
              LEFT JOIN pharmacists p ON s.pharmacist_id = p.pharmacist_id
              WHERE s.sale_date BETWEEN ? AND ?
              ORDER BY s.sale_date DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $end_date_time = $end_date . ' 23:59:59';
    $stmt->bind_param("ssii", $start_date, $end_date_time, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $sales = $result->fetch_all(MYSQLI_ASSOC);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM sales WHERE sale_date BETWEEN ? AND ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("ss", $start_date, $end_date_time);
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['total'];
    
    // Calculate summary statistics
    $summary_query = "SELECT 
                        COUNT(*) as total_sales,
                        SUM(final_amount) as total_revenue,
                        AVG(final_amount) as avg_sale_value,
                        SUM(discount_amount) as total_discounts
                      FROM sales 
                      WHERE sale_date BETWEEN ? AND ?";
    $summary_stmt = $conn->prepare($summary_query);
    $summary_stmt->bind_param("ss", $start_date, $end_date_time);
    $summary_stmt->execute();
    $summary = $summary_stmt->get_result()->fetch_assoc();
    
    $stmt->close();
    $count_stmt->close();
    $summary_stmt->close();
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Sales history retrieved', [
        'sales' => $sales,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ],
        'summary' => $summary,
        'filters' => [
            'start_date' => $start_date,
            'end_date' => $end_date
        ]
    ]);
}

// ============ HELPER FUNCTIONS ============

function logActivity($conn, $action, $details) {
    $user_id = $_SESSION['pharmacist_id'] ?? $_SESSION['customer_id'] ?? null;
    $user_type = $_SESSION['user_type'] ?? 'system';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, action, details, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $user_id, $user_type, $action, $details);
    $stmt->execute();
    $stmt->close();
}

?>
