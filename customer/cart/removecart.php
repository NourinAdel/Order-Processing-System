<?php
require_once '../order_fn.php';
header('Content-Type: application/json');
$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$isbn = $data['ISBN'] ?? '';

echo json_encode(removeFromCart($customer_id, $isbn));

?>
