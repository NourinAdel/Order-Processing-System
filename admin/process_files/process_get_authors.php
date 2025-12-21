<?php
header('Content-Type: application/json');

require_once '../db_connection.php';

$conn = getDBConnection();
$query = "SELECT author_id, name FROM Author ORDER BY name ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Database query failed']);
    closeDBConnection($conn);
    exit();
}

$authors = [];
while ($row = mysqli_fetch_assoc($result)) {
    $authors[] = $row;
}

echo json_encode([
    'success' => true,
    'authors' => $authors
]);

closeDBConnection($conn);
?>