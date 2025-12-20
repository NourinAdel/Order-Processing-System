<?php
require_once '../order_fn.php';
header('Content-Type: application/json');
$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

echo json_encode(
    checkoutSimplified(
        $customer_id,
        $data['card_number'] ?? '',
        $data['expiry_date'] ?? ''
    )
);
?>

