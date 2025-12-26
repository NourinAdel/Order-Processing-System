<?php
require_once '../order_fn.php';
session_start();
header('Content-Type: application/json');
$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$orders= getOrderHistory($customer_id);
echo json_encode([
    'success' =>true,
    'orders' => $orders
]);
?>
