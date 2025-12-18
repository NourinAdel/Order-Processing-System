<?php
session_start();
require_once 'db_connection.php';  

function adminLogin($username, $password) {
    $conn = getDBConnection();  // Get connection
    
    $username = mysqli_real_escape_string($conn, $username);
    
    $query = "SELECT * FROM Admin WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);
        if ($password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            closeDBConnection($conn);  // Close connection
            return ['success' => true];
        }
    }
    
    closeDBConnection($conn);  // Close connection
    return ['success' => false, 'error' => 'Invalid credentials'];
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function getAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

function adminLogout() {
    session_destroy();
    return ['success' => true];
}
?>