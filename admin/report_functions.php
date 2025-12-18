<?php
require_once 'db_connection.php';
function getTotalSalesPreviousMonth() {
    $conn = getDBConnection();

    $query = "SELECT SUM(total_amount) as total_sales
              FROM `Order`
              WHERE YEAR(order_date) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
                AND MONTH(order_date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)";
    
    $result = mysqli_query($conn, $query);
    closeDBConnection($conn);
    return mysqli_fetch_assoc($result);
}

function getTotalSalesByDate($date) {
    $conn = getDBConnection();
    $date = mysqli_real_escape_string($conn, $date);
    
    $query = "SELECT SUM(total_amount) as total_sales
              FROM `Order`
              WHERE DATE(order_date) = '$date'";
    
    $result = mysqli_query($conn, $query);
    closeDBConnection($conn);
    return mysqli_fetch_assoc($result);
}

function getTopCustomersLast3Months() {
    $conn = getDBConnection();
    $query = "SELECT c.customer_id, c.first_name, c.last_name, 
                     SUM(o.total_amount) as total_spent
              FROM `Order` o
              JOIN Customer c ON o.customer_id = c.customer_id
              WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
              GROUP BY c.customer_id
              ORDER BY total_spent DESC
              LIMIT 5";
    
    $result = mysqli_query($conn, $query);
    $customers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $customers[] = $row;
    }
    closeDBConnection($conn);
    return $customers;
}

function getTopSellingBooksLast3Months() {
    $conn = getDBConnection();
    $query = "SELECT b.ISBN, b.title, SUM(oi.quantity) as total_sold
              FROM Order_Item oi
              JOIN `Order` o ON oi.order_id = o.order_id
              JOIN Book b ON oi.ISBN = b.ISBN
              WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
              GROUP BY b.ISBN
              ORDER BY total_sold DESC
              LIMIT 10";
    
    $result = mysqli_query($conn, $query);
    $books = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $books[] = $row;
    }
    closeDBConnection($conn);
    return $books;
}

function getBookReorderCount($isbn) {
    $conn = getDBConnection();
    $isbn = mysqli_real_escape_string($conn, $isbn);
    
    $query = "SELECT COUNT(*) as times_reordered
              FROM Replenishment_Order
              WHERE ISBN = '$isbn'";
    
    $result = mysqli_query($conn, $query);
    closeDBConnection($conn);
    return mysqli_fetch_assoc($result);
}
?>