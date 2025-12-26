<?php
// customer/cart/order_history.php
require_once '../order_fn.php';

// Start session if not already started (order_fn.php should handle this, but just in case)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output, but log them
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
    
    // Return success response
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