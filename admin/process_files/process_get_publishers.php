<?php
// get_publishers.php
header('Content-Type: application/json');

// Adjust path based on your folder structure
require_once '../db_connection.php';  // ONE level up

$conn = getDBConnection();

$query = "SELECT publisher_id, name FROM Publisher ORDER BY name ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        'success' => false,
        'error' => 'Database query failed: ' . mysqli_error($conn)
    ]);
    closeDBConnection($conn);
    exit();
}

$publishers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $publishers[] = $row;
}

echo json_encode([
    'success' => true,
    'publishers' => $publishers
]);

closeDBConnection($conn);
?>