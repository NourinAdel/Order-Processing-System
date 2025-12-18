<?php
require_once 'db_connection.php';
function getPendingOrders() {
    $conn = getDBConnection();
    $query = "SELECT ro.*, b.title, p.name as publisher_name 
              FROM Replenishment_Order ro 
              JOIN Book b ON ro.ISBN = b.ISBN 
              JOIN Publisher p ON ro.publisher_id = p.publisher_id 
              WHERE ro.status = 'Pending' 
              ORDER BY ro.order_date DESC";
    
    $result = mysqli_query($conn, $query);
    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    closeDBConnection($conn);
    return $orders;
}

function confirmReplenishmentOrder($reorder_id) {
    $conn = getDBConnection();
    $reorder_id = (int)$reorder_id;
    
    // Update status to Confirmed (trigger will handle stock update)
    $query = "UPDATE Replenishment_Order SET status = 'Confirmed' 
              WHERE reorder_id = $reorder_id AND status = 'Pending'";
    
    if (mysqli_query($conn, $query)) {
        closeDBConnection($conn);
        return ['success' => true];
    }
    closeDBConnection($conn);
    return ['success' => false, 'error' => mysqli_error($conn)];
}
?>