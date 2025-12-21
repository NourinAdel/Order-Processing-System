<?php
header('Content-Type: application/json');

require_once '../db_connection.php';
require_once '../auth_functions.php';
require_once '../book_functions.php';

$isbn = $_GET['isbn'] ?? $_POST['isbn'] ?? '';

if (empty($isbn)) {
    echo json_encode(['success' => false, 'error' => 'ISBN is required']);
    exit();
}

$result = getBookDetails($isbn);
echo json_encode($result);
?>