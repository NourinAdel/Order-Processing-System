<?php
header('Content-Type: application/json');
session_start();
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['customer_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    $response = updateCustomerProfile(
        $_SESSION['customer_id'],
         $_POST['username'] ?? '',
        $_POST['first_name'] ?? '',
        $_POST['last_name'] ?? '',
        $_POST['email'] ?? '',
        $_POST['phone'] ?? '',
        $_POST['shipping_address'] ?? ''
    );
    echo json_encode($response);
}
?>
