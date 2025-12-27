<?php
header('Content-Type: application/json');

require_once '../db_connection.php';
require_once '../auth_functions.php';
require_once '../order_functions.php';

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit();
}

$pendingOrders = getPendingOrders();

echo json_encode([
    'success' => true,
    'orders' => $pendingOrders,
    'count' => count($pendingOrders)
]);
?>