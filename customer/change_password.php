<?php
header('Content-Type: application/json');
session_start();
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['customer_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    $response = changePassword(
        $_SESSION['customer_id'],
        $_POST['old_password'] ?? '',
        $_POST['new_password'] ?? ''
    );
    echo json_encode($response);
}
?>
