<?php
require_once '../db_connection.php';
require_once '../auth_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $result = adminLogin($username, $password);
    
    echo json_encode($result);
}
?>