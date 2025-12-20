<?php
require_once '../order_fn.php';
header('Content-Type: application/json');
$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

echo json_encode(getOrderHistory($customer_id));
?>
