<?php
/**
 * Advanced API Handler for PharmaCare Management System
 * Handles all AJAX requests with proper security, validation, and error handling
 */

require_once 'db.php';

// Set JSON response header
header('Content-Type: application/json');

// Enable CORS for development (remove in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

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

// Validation helper
function validateRequired($fields, $data) {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            sendResponse(false, "Field '$field' is required", null, 400);
        }
    }
}

// Sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Check authentication
function checkAuth($userType = null) {
    if (!isset($_SESSION['user_type'])) {
        sendResponse(false, 'Authentication required', null, 401);
    }
    if ($userType && $_SESSION['user_type'] !== $userType) {
        sendResponse(false, 'Insufficient permissions', null, 403);
    }
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
            
        case 'validate_discount_code':
            validateDiscountCode();
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
            
        case 'add_customer':
            checkAuth('pharmacist');
            addCustomer();
            break;
            
        // ============ PRESCRIPTION/CONSULTATION ============
        case 'create_consultation':
            createConsultation();
            break;
            
        case 'recommend_drugs':
            recommendDrugs();
            break;
            
        // ============ ANALYTICS & REPORTS ============
        case 'get_dashboard_stats':
            checkAuth('pharmacist');
            getDashboardStats();
            break;
            
        case 'get_revenue_analytics':
            checkAuth('pharmacist');
            getRevenueAnalytics();
            break;
            
        case 'get_sales_report':
            checkAuth('pharmacist');
            getSalesReport();
            break;
            
        case 'export_inventory':
            checkAuth('pharmacist');
            exportInventory();
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
    $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;
    
    $query = "SELECT 
                item_id,
                item_name,
                description,
                quantity,
                price,
                (quantity * price) as total_value,
                created_at,
                updated_at,
                CASE 
                    WHEN quantity = 0 THEN 'Out of Stock'
                    WHEN quantity < 20 THEN 'Low Stock'
                    WHEN quantity < 50 THEN 'Medium Stock'
                    ELSE 'In Stock'
                END as stock_status
              FROM items 
              WHERE (item_name LIKE ? OR description LIKE ?)";
    
    if ($category) {
        $query .= " AND description LIKE ?";
    }
    
    $query .= " ORDER BY item_name ASC LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    
    if ($category) {
        $categorySearch = '%' . $category . '%';
        $stmt->bind_param("sssii", $search, $search, $categorySearch, $limit, $offset);
    } else {
        $stmt->bind_param("ssii", $search, $search, $limit, $offset);
    }
    
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
    
    validateRequired(['item_name', 'quantity', 'price'], $data);
    
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
    $stmt = $conn->prepare("INSERT INTO items (item_name, description, quantity, price) VALUES (?, ?, ?, ?)");
    $description = $data['description'] ?? '';
    $stmt->bind_param("ssid", 
        $data['item_name'],
        $description,
        $data['quantity'],
        $data['price']
    );
    
    if ($stmt->execute()) {
        $item_id = $conn->insert_id;
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
    
    validateRequired(['item_id'], $data);
    
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
    
    $query = "UPDATE items SET " . implode(", ", $updates) . " WHERE item_id = ?";
    $types .= "i";
    $values[] = $data['item_id'];
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
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
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete item
        $stmt = $conn->prepare("DELETE FROM items WHERE item_id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception('Item not found');
        }
        
        $stmt->close();
        $conn->commit();
        closeDatabaseConnection($conn);
        
        sendResponse(true, 'Item deleted successfully');
        
    } catch (Exception $e) {
        $conn->rollback();
        closeDatabaseConnection($conn);
        sendResponse(false, $e->getMessage(), null, 500);
    }
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
    
    sendResponse(true, 'Low stock items retrieved', ['items' => $items, 'count' => count($items)]);
}

// ============ SALES FUNCTIONS ============

function createSale() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    validateRequired(['customer_id', 'items'], $data);
    
    if (empty($data['items'])) {
        sendResponse(false, 'At least one item is required', null, 400);
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
        $sale_stmt = $conn->prepare("INSERT INTO sales (customer_id, pharmacist_id, total_amount, discount_rate, discount_amount, vat_amount, final_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $sale_stmt->bind_param("iiddddd", 
            $data['customer_id'],
            $pharmacist_id,
            $total_amount,
            $discount_rate,
            $discount_amount,
            $vat_amount,
            $final_amount
        );
        $sale_stmt->execute();
        $sale_id = $conn->insert_id;
        $sale_stmt->close();
        
        // Insert sale items and update inventory
        $item_stmt = $conn->prepare("INSERT INTO sale_items (sale_id, item_id, item_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        $update_stmt = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE item_id = ?");
        
        foreach ($data['items'] as $item) {
            $subtotal = $item['quantity'] * $item['price'];
            $item_stmt->bind_param("iisidd", 
                $sale_id,
                $item['item_id'],
                $item['item_name'],
                $item['quantity'],
                $item['price'],
                $subtotal
            );
            $item_stmt->execute();
            
            // Update inventory
            $update_stmt->bind_param("ii", $item['quantity'], $item['item_id']);
            $update_stmt->execute();
        }
        
        $item_stmt->close();
        $update_stmt->close();
        
        // Create receipt
        $receipt_content = generateReceipt($sale_id, $data);
        $receipt_stmt = $conn->prepare("INSERT INTO receipts (customer_id, sale_id, receipt_content) VALUES (?, ?, ?)");
        $receipt_stmt->bind_param("iis", $data['customer_id'], $sale_id, $receipt_content);
        $receipt_stmt->execute();
        $receipt_stmt->close();
        
        $conn->commit();
        closeDatabaseConnection($conn);
        
        sendResponse(true, 'Sale completed successfully', [
            'sale_id' => $sale_id,
            'final_amount' => $final_amount,
            'receipt' => $receipt_content
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        closeDatabaseConnection($conn);
        sendResponse(false, 'Sale failed: ' . $e->getMessage(), null, 500);
    }
}

function generateReceipt($sale_id, $data) {
    $receipt = "========== PHARMACARE RECEIPT ==========\n";
    $receipt .= "Sale ID: #" . $sale_id . "\n";
    $receipt .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $receipt .= "=========================================\n\n";
    
    foreach ($data['items'] as $item) {
        $subtotal = $item['quantity'] * $item['price'];
        $receipt .= $item['item_name'] . "\n";
        $receipt .= "  " . $item['quantity'] . " x $" . number_format($item['price'], 2) . " = $" . number_format($subtotal, 2) . "\n\n";
    }
    
    $receipt .= "=========================================\n";
    $receipt .= "Subtotal: $" . number_format($data['total_amount'] ?? 0, 2) . "\n";
    
    if (isset($data['discount_rate']) && $data['discount_rate'] > 0) {
        $receipt .= "Discount (" . $data['discount_rate'] . "%): -$" . number_format($data['discount_amount'] ?? 0, 2) . "\n";
    }
    
    $receipt .= "VAT (20%): $" . number_format($data['vat_amount'] ?? 0, 2) . "\n";
    $receipt .= "TOTAL: $" . number_format($data['final_amount'] ?? 0, 2) . "\n";
    $receipt .= "=========================================\n";
    $receipt .= "Thank you for your business!\n";
    
    return $receipt;
}

function getSalesHistory() {
    $conn = getDatabaseConnection();
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = ($page - 1) * $limit;
    
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
              ORDER BY s.sale_date DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $sales = $result->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Sales history retrieved', ['sales' => $sales]);
}

function getDashboardStats() {
    $conn = getDatabaseConnection();
    
    // Total revenue
    $revenue_result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURRENT_DATE())");
    $total_revenue = $revenue_result->fetch_assoc()['total'];
    
    // Total prescriptions
    $prescriptions_result = $conn->query("SELECT COUNT(*) as total FROM sales WHERE WEEK(sale_date) = WEEK(CURRENT_DATE())");
    $total_prescriptions = $prescriptions_result->fetch_assoc()['total'];
    
    // Low stock count
    $low_stock_result = $conn->query("SELECT COUNT(*) as total FROM items WHERE quantity < 20");
    $low_stock_count = $low_stock_result->fetch_assoc()['total'];
    
    // Active patients
    $patients_result = $conn->query("SELECT COUNT(*) as total FROM customers WHERE is_active = TRUE");
    $active_patients = $patients_result->fetch_assoc()['total'];
    
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Dashboard stats retrieved', [
        'total_revenue' => $total_revenue,
        'total_prescriptions' => $total_prescriptions,
        'low_stock_count' => $low_stock_count,
        'active_patients' => $active_patients
    ]);
}

function validateDiscountCode() {
    $code = $_GET['code'] ?? '';
    
    if (empty($code)) {
        sendResponse(false, 'Discount code is required', null, 400);
    }
    
    $conn = getDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT discount_percentage FROM discount_codes WHERE code = ? AND is_active = TRUE");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $discount = $result->fetch_assoc();
        $stmt->close();
        closeDatabaseConnection($conn);
        sendResponse(true, 'Valid discount code', ['discount_percentage' => $discount['discount_percentage']]);
    } else {
        $stmt->close();
        closeDatabaseConnection($conn);
        sendResponse(false, 'Invalid or expired discount code', null, 404);
    }
}

function getCustomers() {
    $conn = getDatabaseConnection();
    
    $query = "SELECT customer_id, username, full_name, email, phone, created_at, last_login FROM customers WHERE is_active = TRUE ORDER BY full_name ASC";
    $result = $conn->query($query);
    $customers = $result->fetch_all(MYSQLI_ASSOC);
    
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Customers retrieved successfully', ['customers' => $customers]);
}

function getCustomerReceipts() {
    $customer_id = $_GET['customer_id'] ?? null;
    
    if (!$customer_id) {
        sendResponse(false, 'Customer ID is required', null, 400);
    }
    
    $conn = getDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT receipt_id, receipt_content, created_at FROM receipts WHERE customer_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $receipts = $result->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    closeDatabaseConnection($conn);
    
    sendResponse(true, 'Receipts retrieved successfully', ['receipts' => $receipts]);
}

?>
