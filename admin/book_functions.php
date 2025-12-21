<?php
require_once 'db_connection.php';
function addNewBook($bookData) {
    $conn = getDBConnection();
    
    // Extract and sanitize data
    $isbn = mysqli_real_escape_string($conn, $bookData['isbn']);
    $title = mysqli_real_escape_string($conn, $bookData['title']);
    
    // Handle year - can be NULL
    if ($bookData['year'] === null || $bookData['year'] === '') {
        $year_sql = "NULL";
    } else {
        $year = (int)$bookData['year'];
        $year_sql = "'$year'";
    }
    
    $price = (float)$bookData['price'];
    $category = mysqli_real_escape_string($conn, $bookData['category']);
    $stock = (int)$bookData['stock'];
    $threshold = (int)$bookData['threshold'];
    $publisher_id = (int)$bookData['publisher_id'];
    $authors = $bookData['authors'] ?? [];
    
    // Build SQL query
    $query = "INSERT INTO Book (ISBN, title, publication_year, price, category, 
              stock_quantity, threshold, publisher_id) 
              VALUES ('$isbn', '$title', $year_sql, $price, '$category', 
              $stock, $threshold, $publisher_id)";
    
    // Execute query
    if (mysqli_query($conn, $query)) {
        // Add authors if provided
        if (!empty($authors)) {
            foreach ($authors as $author_id) {
                $author_id = (int)$author_id;
                $author_query = "INSERT INTO Book_Author (ISBN, author_id) 
                               VALUES ('$isbn', '$author_id')";
                mysqli_query($conn, $author_query);
            }
        }
        
        closeDBConnection($conn);
        return ['success' => true, 'message' => 'Book added successfully'];
    } else {
        $error = mysqli_error($conn);
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'Database error: ' . $error];
    }
}

function updateBookStock($isbn, $newQuantity, $conn = null) {
    // Handle connection
    if ($conn === null) {
        $conn = getDBConnection();
        $closeConnection = true;
    } else {
        $closeConnection = false;
    }
    
    $isbn = mysqli_real_escape_string($conn, $isbn);
    $newQuantity = (int)$newQuantity;
    
    // The trigger will prevent negative stock
    $query = "UPDATE Book SET stock_quantity = $newQuantity WHERE ISBN = '$isbn'";
    
    try {
        if (mysqli_query($conn, $query)) {
            if ($closeConnection) {
                closeDBConnection($conn);
            }
            return ['success' => true, 'message' => 'Stock updated successfully'];
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        
        if ($closeConnection) {
            closeDBConnection($conn);
        }
        
        // Check if it's the negative stock trigger error
        if (strpos($error, 'cannot be negative') !== false) {
            return ['success' => false, 'error' => 'Stock quantity cannot be negative (trigger prevented it)'];
        }
        
        return ['success' => false, 'error' => 'Update failed: ' . $error];
    }
}

function searchBooks($search_type, $keyword) {
    $conn = getDBConnection();
    $keyword = mysqli_real_escape_string($conn, $keyword);
    
    switch($search_type) {
        case 'isbn':
            $query = "SELECT b.*, p.name as publisher_name 
                     FROM Book b 
                     JOIN Publisher p ON b.publisher_id = p.publisher_id 
                     WHERE b.ISBN LIKE '%$keyword%'";
            break;
        case 'title':
            $query = "SELECT b.*, p.name as publisher_name 
                     FROM Book b 
                     JOIN Publisher p ON b.publisher_id = p.publisher_id 
                     WHERE b.title LIKE '%$keyword%'";
            break;
        case 'category':
            $query = "SELECT b.*, p.name as publisher_name 
                     FROM Book b 
                     JOIN Publisher p ON b.publisher_id = p.publisher_id 
                     WHERE b.category = '$keyword'";
            break;
        case 'author':
            $query = "SELECT DISTINCT b.*, p.name as publisher_name 
                     FROM Book b 
                     JOIN Publisher p ON b.publisher_id = p.publisher_id 
                     JOIN Book_Author ba ON b.ISBN = ba.ISBN 
                     JOIN Author a ON ba.author_id = a.author_id 
                     WHERE a.name LIKE '%$keyword%'";
            break;
        case 'publisher':
            $query = "SELECT b.*, p.name as publisher_name 
                     FROM Book b 
                     JOIN Publisher p ON b.publisher_id = p.publisher_id 
                     WHERE p.name LIKE '%$keyword%'";
            break;
        default:
            closeDBConnection($conn);
            return [];
    }
    
    $result = mysqli_query($conn, $query);
    $books = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // === NEW: Get authors for this book ===
            $isbn = $row['ISBN'];
            $author_query = "SELECT a.name 
                           FROM Author a 
                           JOIN Book_Author ba ON a.author_id = ba.author_id 
                           WHERE ba.ISBN = '$isbn'";
            $author_result = mysqli_query($conn, $author_query);
            
            $authors = [];
            if ($author_result) {
                while ($author_row = mysqli_fetch_assoc($author_result)) {
                    $authors[] = $author_row['name'];
                }
            }
            
            $row['authors'] = $authors;
            // === END NEW CODE ===
            
            $books[] = $row;
        }
    }
    
    closeDBConnection($conn);
    return $books;
}
?>