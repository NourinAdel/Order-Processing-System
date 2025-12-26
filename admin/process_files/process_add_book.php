<?php
require_once '../db_connection.php';
require_once '../auth_functions.php';
require_once '../book_functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

// Validate required fields
$required_fields = ['isbn', 'title', 'price', 'category', 'stock', 'threshold', 'publisher_id'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        echo json_encode(['success' => false, 'error' => "Required field '$field' is missing"]);
        exit();
    }
}

// Prepare book data with proper validation
$book_data = [
    'isbn' => trim($_POST['isbn']),
    'title' => trim($_POST['title']),
    'year' => isset($_POST['year']) && $_POST['year'] !== '' ? (int)$_POST['year'] : null,
    'price' => (float)$_POST['price'],
    'category' => trim($_POST['category']),
    'stock' => (int)$_POST['stock'],
    'threshold' => (int)$_POST['threshold'],
    'publisher_id' => (int)$_POST['publisher_id'],
    'authors' => isset($_POST['authors']) ? (array)$_POST['authors'] : []
];

// Additional validation
if ($book_data['price'] <= 0) {
    echo json_encode(['success' => false, 'error' => 'Price must be greater than 0']);
    exit();
}

if ($book_data['stock'] < 0) {
    echo json_encode(['success' => false, 'error' => 'Stock cannot be negative']);
    exit();
}

if ($book_data['threshold'] < 0) {
    echo json_encode(['success' => false, 'error' => 'Threshold cannot be negative']);
    exit();
}

// Check if ISBN already exists
$conn = getDBConnection();
$isbn_check = mysqli_real_escape_string($conn, $book_data['isbn']);
$check_query = "SELECT ISBN FROM Book WHERE ISBN = '$isbn_check'";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    closeDBConnection($conn);
    echo json_encode(['success' => false, 'error' => 'ISBN already exists']);
    exit();
}

// Call the function to add the book
$result = addNewBook($book_data);


if ($result['success']) {

    $stock = $book_data['stock'];
    $threshold = $book_data['threshold'];
    $isbn = $book_data['isbn'];
    $publisher_id = $book_data['publisher_id'];
    $admin_id = $_SESSION['admin_id'];

    if ($stock < $threshold) {

        $reorderQty = max(10, $threshold * 2);

        $conn = getDBConnection();

        $stmt = $conn->prepare("
            INSERT INTO replenishment_order
            (order_date, quantity, status, ISBN, publisher_id, admin_id)
            VALUES (NOW(), ?, 'Pending', ?, ?, ?)
        ");

        $stmt->bind_param(
            "isii",
            $reorderQty,
            $isbn,
            $publisher_id,
            $admin_id
        );

        $stmt->execute();
        $stmt->close();

        closeDBConnection($conn);
    }
}

// Return the result
echo json_encode($result);
?>