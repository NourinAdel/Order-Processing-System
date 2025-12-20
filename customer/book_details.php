<?php
header('Content-Type: application/json');
require_once 'order_fn.php';
$isbn = $_GET['isbn'] ?? '';
if (!$isbn) {
    echo json_encode(['success' => false, 'message' => 'ISBN is required']);
    exit;
}

echo json_encode(getBookDetails($isbn));
?>
