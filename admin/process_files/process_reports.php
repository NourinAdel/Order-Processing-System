<?php
require_once '../db_connection.php';
require_once '../auth_functions.php';
require_once '../report_functions.php';

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit();
}

$conn = mysqli_connect('localhost', 'root', '', 'Book_Store_Management');

$report_type = $_GET['report_type'] ?? '';

switch($report_type) {
    case 'sales_previous_month':
        $data = getTotalSalesPreviousMonth($conn);
        break;
        
    case 'sales_by_date':
        $date = $_GET['date'];
        $data = getTotalSalesByDate($date, $conn);
        break;
        
    case 'top_customers':
        $data = getTopCustomersLast3Months($conn);
        break;
        
    case 'top_books':
        $data = getTopSellingBooksLast3Months($conn);
        break;
        
    case 'book_reorders':
        $isbn = $_GET['isbn'];
        $data = getBookReorderCount($isbn, $conn);
        break;
        
    default:
        $data = ['error' => 'Invalid report type'];
}

echo json_encode($data);
?>