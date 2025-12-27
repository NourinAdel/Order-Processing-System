<?php
require_once '../db_connection.php';
require_once '../auth_functions.php';
require_once '../book_functions.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$required = ['isbn', 'title', 'authors', 'publisher_id', 'price', 'stock'];
foreach ($required as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'error' => "$field is required"]);
        exit();
    }
}

$isbn = $_POST['isbn'];
$title = $_POST['title'];
$authors = $_POST['authors']; 
$publisher_id = (int)$_POST['publisher_id'];
$price = (float)$_POST['price'];
$stock = (int)$_POST['stock'];

$conn = getDBConnection();

try {
    mysqli_begin_transaction($conn);

    $stmt = $conn->prepare("UPDATE Book SET title=?, publisher_id=?, price=?, stock_quantity=? WHERE ISBN=?");
    $stmt->bind_param("siids", $title, $publisher_id, $price, $stock, $isbn);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM Book_Author WHERE ISBN=?");
    $stmt->bind_param("s", $isbn);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO Book_Author (ISBN, author_id) VALUES (?, ?)");
    foreach ($authors as $author_id) {
        $author_id = (int)$author_id;
        $stmt->bind_param("si", $isbn, $author_id);
        $stmt->execute();
    }
    $stmt->close();

    mysqli_commit($conn);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

closeDBConnection($conn);
?>
