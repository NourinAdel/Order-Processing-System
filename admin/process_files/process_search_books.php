<?php
header('Content-Type: application/json');

require_once '../db_connection.php';
require_once '../auth_functions.php';
require_once '../book_functions.php';

$search_type = $_GET['type'] ?? $_POST['type'] ?? 'title'; 
$keyword = $_GET['keyword'] ?? $_POST['keyword'] ?? '';

if (empty($keyword)) {
    echo json_encode(['success' => false, 'error' => 'Search keyword is required']);
    exit();
}

$valid_types = ['isbn', 'title', 'category', 'author', 'publisher'];
if (!in_array($search_type, $valid_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid search type']);
    exit();
}

$books = searchBooks($search_type, $keyword);

echo json_encode([
    'success' => true,
    'search_type' => $search_type,
    'keyword' => $keyword,
    'count' => count($books),
    'books' => $books
]);
?>