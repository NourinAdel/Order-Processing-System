<?php
require_once 'order_fn.php'; 
header('Content-Type: application/json');
session_start();

$search_type = $_GET['type'] ?? '';
$search_value = $_GET['value'] ?? '';
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
if (!$search_type || !$search_value) {
    echo json_encode(['success' => false, 'message' => 'Missing search type or value']);
    exit;
}

echo json_encode(searchBooks($search_type, $search_value));
?>
