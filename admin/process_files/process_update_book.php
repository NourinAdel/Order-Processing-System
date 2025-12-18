<?php
require_once '../db_connection.php';
require_once '../auth_functions.php';
require_once '../book_functions.php';

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (empty($_POST['isbn']) || !isset($_POST['new_stock'])) {
        echo json_encode(['success' => false, 'error' => 'ISBN and new stock are required']);
        exit();
    }
    
    $isbn = $_POST['isbn'];
    $newStock = (int)$_POST['new_stock'];
    
    // Call the function
    $result = updateBookStock($isbn, $newStock);
    
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>