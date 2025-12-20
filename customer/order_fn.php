<?php
require_once 'connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function searchBooks($search_type, $search_value) {
    global $conn;

    $query = "SELECT b.ISBN, b.title, b.publication_year, b.price, b.category, b.stock_quantity,
              p.name as publisher_name, GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as authors
              FROM Book b
              LEFT JOIN Publisher p ON b.publisher_id = p.publisher_id
              LEFT JOIN Book_Author ba ON b.ISBN = ba.ISBN
              LEFT JOIN Author a ON ba.author_id = a.author_id";

    $params = [];
    switch ($search_type) {
        case 'isbn':

            $query .= " WHERE b.ISBN = ?"; 
            $params[] = $search_value; 
            break;
        case 'title': 
            $query .= " WHERE b.title LIKE ?"; 
            $params[] = "%$search_value%"; 
            break;
        case 'author': 
            $query .= " WHERE a.name LIKE ?"; 
            $params[] = "%$search_value%"; 
            break;
        case 'category': 
            $query .= " WHERE b.category = ?"; 
            $params[] = $search_value; break;
        case 'publisher': $query .= " WHERE p.name LIKE ?"; $params[] = "%$search_value%"; break;
    }

    $query .= " GROUP BY b.ISBN";
    $stmt = $conn->prepare($query);
    if (!empty($params)) $stmt->bind_param(str_repeat("s", count($params)), ...$params);

    $stmt->execute();
    $result = $stmt->get_result();
    $books = [];
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }

    return ['success' => true, 'books' => $books];
}

function getBookDetails($isbn) {
    global $conn;
    $stmt = $conn->prepare("SELECT b.*, p.name as publisher_name, GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as authors
                           FROM Book b
                           LEFT JOIN Publisher p ON b.publisher_id = p.publisher_id
                           LEFT JOIN Book_Author ba ON b.ISBN = ba.ISBN
                           LEFT JOIN Author a ON ba.author_id = a.author_id
                           WHERE b.ISBN = ?
                           GROUP BY b.ISBN");
    $stmt->bind_param("s", $isbn);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Book not found'];
    }
    return ['success' => true, 'book' => $result->fetch_assoc()];
}

#SHOPPING CART

function getOrCreateCart($customer_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT cart_id FROM Shopping_Cart WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0){
         return $result->fetch_assoc()['cart_id'];
    }

    $stmt = $conn->prepare("INSERT INTO Shopping_Cart (customer_id) VALUES (?)");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    return $conn->insert_id;
}

function addToCart($customer_id, $isbn, $quantity) {
    global $conn;
    $stmt = $conn->prepare("SELECT stock_quantity FROM Book WHERE ISBN = ?");
    $stmt->bind_param("s", $isbn);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    if (!$book) 
        {
            return ['success' => false, 'message' => 'Book not found'];
        }

    if ($book['stock_quantity'] < $quantity) return ['success' => false, 'message' => 'Insufficient stock'];

    $cart_id = getOrCreateCart($customer_id);

    $stmt = $conn->prepare("SELECT quantity FROM Cart_Item WHERE cart_id = ? AND ISBN = ?");
    $stmt->bind_param("is", $cart_id, $isbn);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $new_qty = $result->fetch_assoc()['quantity'] + $quantity;
        if ($book['stock_quantity'] < $new_qty){
             return ['success' => false, 'message' => 'Not enough stock'];
        }
        $stmt = $conn->prepare("UPDATE Cart_Item SET quantity = ? WHERE cart_id = ? AND ISBN = ?");
        $stmt->bind_param("iis", $new_qty, $cart_id, $isbn);
    } else {
        $stmt = $conn->prepare("INSERT INTO Cart_Item (cart_id, ISBN, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $cart_id, $isbn, $quantity);
    }
    $stmt->execute();
    return ['success' => true];
}

function viewCart($customer_id) {
    global $conn;
    $cart_id = getOrCreateCart($customer_id);
    $stmt = $conn->prepare("SELECT ci.ISBN, b.title, b.price, ci.quantity, b.category,
                           (b.price * ci.quantity) as item_total, b.stock_quantity,
                           GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as authors
                           FROM Cart_Item ci
                           JOIN Book b ON ci.ISBN = b.ISBN
                           LEFT JOIN Book_Author ba ON b.ISBN = ba.ISBN
                           LEFT JOIN Author a ON ba.author_id = a.author_id
                           WHERE ci.cart_id = ?
                           GROUP BY ci.ISBN");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
        $total += $row['item_total'];
    }
    return ['success' => true, 'items' => $items, 'total' => $total];
}

function updateCartQuantity($customer_id, $isbn, $quantity) {
    global $conn;
    if ($quantity <= 0){
         return removeFromCart($customer_id, $isbn);
    }
    $stmt = $conn->prepare("SELECT stock_quantity FROM Book WHERE ISBN = ?");
    $stmt->bind_param("s", $isbn);
    $stmt->execute();
    $stock = $stmt->get_result()->fetch_assoc()['stock_quantity'];
    if ($stock < $quantity){ 
        return ['success' => false, 'message' => 'Insufficient stock'];
    }
    $cart_id = getOrCreateCart($customer_id);
    $stmt = $conn->prepare("UPDATE Cart_Item SET quantity = ? WHERE cart_id = ? AND ISBN = ?");
    $stmt->bind_param("iis", $quantity, $cart_id, $isbn);
    $stmt->execute();
    return ['success' => true];
}

function removeFromCart($customer_id, $isbn) {
    global $conn;
    $cart_id = getOrCreateCart($customer_id);
    $stmt = $conn->prepare("DELETE FROM Cart_Item WHERE cart_id = ? AND ISBN = ?");
    $stmt->bind_param("is", $cart_id, $isbn);
    $stmt->execute();
    return ['success' => true];
}

function clearCart($customer_id) {
    global $conn;
    $cart_id = getOrCreateCart($customer_id);
    $stmt = $conn->prepare("DELETE FROM Cart_Item WHERE cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    return ['success' => true];
}
#CHECKOUT
function validateCreditCardSimple($card_number, $expiry_date) {
    // Remove all non-digits
    $card_number = preg_replace('/\D/', '', $card_number);
    
    // Basic length check (most cards are 16 digits)
    if (strlen($card_number) != 16) {
        return false;
    }
    
    // Check all characters are digits
    if (!ctype_digit($card_number)) {
        return false;
    }
    
    // Check expiry date format (MM/YY)
    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry_date)) {
        return false;
    }
    
    // Parse expiry date
    list($month, $year) = explode('/', $expiry_date);
    $expiry_year = '20' . $year; // Convert YY to YYYY
    $expiry_month = (int)$month;
    
    // Check if expiry date is in the future
    $current_year = (int)date('Y');
    $current_month = (int)date('m');
    
    if ($expiry_year < $current_year) {
        return false;
    }
    
    if ($expiry_year == $current_year && $expiry_month < $current_month) {
        return false;
    }
    
    return true;
}

function checkoutSimplified($customer_id, $card_number, $expiry_date) {
    global $conn;
    
    // SIMPLE VALIDATION - Perfect for student project
    $card_number = preg_replace('/\s+/', '', $card_number);
    $clean_card = preg_replace('/\D/', '', $card_number);
    
    // 1. Basic card validation
    if (strlen($clean_card) !== 16 || !is_numeric($clean_card)) {
        return ['success' => false, 'message' => 'Invalid card number (must be 16 digits)'];
    }
    
    // 2. Basic expiry validation
    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry_date, $matches)) {
        return ['success' => false, 'message' => 'Invalid expiry date (use MM/YY format)'];
    }
    
    $month = (int)$matches[1];
    $year = (int)('20' . $matches[2]);
    $current_year = (int)date('Y');
    $current_month = (int)date('m');
    
    if ($year < $current_year || ($year == $current_year && $month < $current_month)) {
        return ['success' => false, 'message' => 'Card has expired'];
    }
    
    // 3. Check cart
    $cart_data = viewCart($customer_id);
    if (empty($cart_data['items'])) {
        return ['success' => false, 'message' => 'Cart is empty'];
    }
    
    // 4. Process transaction
    $conn->begin_transaction();
    
    try {
        $total = $cart_data['total'];
        
        // Create order
        $stmt = $conn->prepare("INSERT INTO `Order` (customer_id, total_amount) VALUES (?, ?)");
        $stmt->bind_param("id", $customer_id, $total);
        $stmt->execute();
        $order_id = $conn->insert_id;
        
        // Insert order items (stock deducted by trigger)
        $stmt = $conn->prepare("INSERT INTO Order_Item (order_id, ISBN, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
        foreach ($cart_data['items'] as $item) {
            $stmt->bind_param("isid", $order_id, $item['ISBN'], $item['quantity'], $item['price']);
            $stmt->execute();
            $updateStock = $conn->prepare(
                "UPDATE Book 
                 SET stock_quantity = stock_quantity - ? 
                 WHERE ISBN = ? AND stock_quantity >= ?");
            $updateStock->bind_param("iss", $item['quantity'], $item['ISBN'], $item['quantity']);
            $updateStock->execute();

            // Check if stock deduction failed (insufficient stock)
            if ($updateStock->affected_rows === 0) {
                throw new Exception("Insufficient stock for ISBN: " . $item['ISBN']);
            }      
        }
        
        // Clear cart
        clearCart($customer_id);
        
        $conn->commit();
        
        return [
            'success' => true, 
            'order_id' => $order_id,
            'total' => $total,
            'message' => 'Order placed successfully!'
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Checkout failed: ' . $e->getMessage()];
    }
}

#ORDER HISTORY 
function getOrderHistory($customer_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT o.order_id, o.order_date, o.total_amount,
                           oi.ISBN, b.title, oi.quantity, oi.price_at_purchase,
                           (oi.quantity * oi.price_at_purchase) as item_total,
                           GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as authors
                           FROM `Order` o
                           JOIN Order_Item oi ON o.order_id = oi.order_id
                           JOIN Book b ON oi.ISBN = b.ISBN
                           LEFT JOIN Book_Author ba ON b.ISBN = ba.ISBN
                           LEFT JOIN Author a ON ba.author_id = a.author_id
                           WHERE o.customer_id = ?
                           GROUP BY o.order_id, oi.ISBN
                           ORDER BY o.order_date DESC");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $order_id = $row['order_id'];
        if (!isset($orders[$order_id])) {
            $orders[$order_id] = [
                'order_id' => $order_id,
                'order_date' => $row['order_date'],
                'total_amount' => $row['total_amount'],
                'items' => []
            ];
        }
        $orders[$order_id]['items'][] = [
            'ISBN' => $row['ISBN'],
            'title' => $row['title'],
            'authors' => $row['authors'],
            'quantity' => $row['quantity'],
            'price_at_purchase' => $row['price_at_purchase'],
            'item_total' => $row['item_total']
        ];
    }

    return ['success' => true, 'orders' => array_values($orders)];
}
?>
