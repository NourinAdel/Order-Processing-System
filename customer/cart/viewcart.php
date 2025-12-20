<?php
require_once '../order_fn.php';
header('Content-Type: application/json');
#session_start();

$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

echo json_encode(viewCart($customer_id));

?>
