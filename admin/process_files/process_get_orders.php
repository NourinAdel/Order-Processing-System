<?php
// process_get_orders.php
header('Content-Type: application/json');

require_once '../db_connection.php';
require_once '../auth_functions.php';
require_once '../order_functions.php';

// Check authentication
if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit();
}

// Get pending orders
$pendingOrders = getPendingOrders();

echo json_encode([
    'success' => true,
    'orders' => $pendingOrders,
    'count' => count($pendingOrders)
]);
?>