<?php
session_start();
header('Content-Type: application/json');
require_once 'functions.php'; // make sure this includes loginCustomer()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Use $_POST because we're sending form-data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username or password missing']);
        exit;
    }

    $response = loginCustomer($username, $password);

    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
