<?php
// customer/cart/order_history.php
require_once '../order_fn.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1);

// Check if customer is logged in
$customer_id = $_SESSION['customer_id'] ?? null;

if (!$customer_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in. Please log in first.']);
    exit;
}

try {
    // Get order history
    $orders = getOrderHistory($customer_id);
    
    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log('Order history error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

?>
