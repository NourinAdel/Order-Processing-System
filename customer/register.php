<?php
header('Content-Type: application/json');
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = registerCustomer(
        $_POST['username'] ?? '',
        $_POST['password'] ?? '',
        $_POST['first_name'] ?? '',
        $_POST['last_name'] ?? '',
        $_POST['email'] ?? '',
        $_POST['phone'] ?? '',
        $_POST['shipping_address'] ?? ''
    );
    echo json_encode($response);
}
?>
