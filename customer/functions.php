<?php
require_once 'connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================= REGISTER ================= */
function registerCustomer($username, $password, $first_name, $last_name, $email, $phone, $shipping_address) {
    global $conn;

    if (empty($username) || empty($password) || empty($email)) {
        return ['success' => false, 'message' => 'Required fields missing'];
    }

    $stmt = $conn->prepare(
        "SELECT customer_id FROM Customer WHERE username = ? OR email = ?"
    );
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO Customer (username, password, first_name, last_name, email, phone, shipping_address)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "sssssss",
        $username,
        $hashed_password,
        $first_name,
        $last_name,
        $email,
        $phone,
        $shipping_address
    );
    $stmt->execute();

    return ['success' => true, 'customer_id' => $conn->insert_id];
}

/* ================= LOGIN ================= */
function loginCustomer($username, $password) {
    global $conn;

    $stmt = $conn->prepare(
        "SELECT customer_id, password, first_name FROM Customer WHERE username = ?"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    $customer = $result->fetch_assoc();
    if (!password_verify($password, $customer['password'])) {
        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    $_SESSION['customer_id'] = $customer['customer_id'];
    $_SESSION['username'] = $username;
    $_SESSION['first_name'] = $customer['first_name'];

    return ['success' => true];
}

/* ================= PROFILE ================= */
function getCustomerProfile($customer_id) {
    global $conn;

    $stmt = $conn->prepare(
        "SELECT username, first_name, last_name, email, phone, shipping_address
         FROM Customer WHERE customer_id = ?"
    );
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

/* ================= UPDATE PROFILE ================= */
function updateCustomerProfile(
    $customer_id,
    $username,
    $first_name,
    $last_name,
    $email,
    $phone,
    $shipping_address
) {
    global $conn;

    //Get current data
    $stmt = $conn->prepare(
        "SELECT username, first_name, last_name, email, phone, shipping_address
         FROM Customer
         WHERE customer_id = ?"
    );
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();

    if (!$current) {
        return ['success' => false, 'message' => 'Customer not found'];
    }

    // Keep old values if fields are empty
    $username         = trim($username)         ?: $current['username'];
    $first_name       = trim($first_name)       ?: $current['first_name'];
    $last_name        = trim($last_name)        ?: $current['last_name'];
    $email            = trim($email)            ?: $current['email'];
    $phone            = trim($phone)            ?: $current['phone'];
    $shipping_address = trim($shipping_address) ?: $current['shipping_address'];

    //Check uniqueness 
    $stmt = $conn->prepare(
        "SELECT customer_id
         FROM Customer
         WHERE (username = ? OR email = ?)
         AND customer_id != ?"
    );
    $stmt->bind_param("ssi", $username, $email, $customer_id);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Username or email already in use'];
    }

    //Update
    $stmt = $conn->prepare(
        "UPDATE Customer
         SET username = ?, first_name = ?, last_name = ?, email = ?, phone = ?, shipping_address = ?
         WHERE customer_id = ?"
    );
    $stmt->bind_param(
        "ssssssi",
        $username,
        $first_name,
        $last_name,
        $email,
        $phone,
        $shipping_address,
        $customer_id
    );
    $stmt->execute();

    return ['success' => true];
}


/* ================= CHANGE PASSWORD ================= */
function changePassword($customer_id, $old_password, $new_password) {
    global $conn;

    $stmt = $conn->prepare(
        "SELECT password FROM Customer WHERE customer_id = ?"
    );
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || !password_verify($old_password, $row['password'])) {
        return ['success' => false, 'message' => 'Current password incorrect'];
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        "UPDATE Customer SET password = ? WHERE customer_id = ?"
    );
    $stmt->bind_param("si", $hashed_password, $customer_id);
    $stmt->execute();

    return ['success' => true];
}

/* ================= LOGOUT ================= */
function logoutCustomer() {
    session_unset();
    session_destroy();
    return ['success' => true];
}

/* ================= API HANDLER ================= 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'register':
            echo json_encode(registerCustomer(
                $_POST['username'],
                $_POST['password'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['shipping_address']
            ));
            break;

        case 'login':
            echo json_encode(loginCustomer($_POST['username'], $_POST['password']));
            break;

        case 'get_profile':
            if (!isset($_SESSION['customer_id'])) {
                echo json_encode(['success' => false, 'message' => 'Not logged in']);
                break;
            }
            echo json_encode([
                'success' => true,
                'profile' => getCustomerProfile($_SESSION['customer_id'])
            ]);
            break;

        case 'update_profile':
            if (!isset($_SESSION['customer_id'])) {
                echo json_encode(['success' => false, 'message' => 'Not logged in']);
                break;
            }
            echo json_encode(updateCustomerProfile(
                $_SESSION['customer_id'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['shipping_address']
            ));
            break;

        case 'change_password':
            if (!isset($_SESSION['customer_id'])) {
                echo json_encode(['success' => false, 'message' => 'Not logged in']);
                break;
            }
            echo json_encode(changePassword(
                $_SESSION['customer_id'],
                $_POST['old_password'],
                $_POST['new_password']
            ));
            break;

        case 'logout':
            echo json_encode(logoutCustomer());
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
    */
?>
