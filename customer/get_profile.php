<?php
header('Content-Type: application/json');
session_start();
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['customer_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    $profile = getCustomerProfile($_SESSION['customer_id']);
    echo json_encode(['success' => true, 'profile' => $profile]);
}
?>
