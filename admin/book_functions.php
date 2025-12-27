<?php
require_once 'db_connection.php';
function addNewBook($bookData) {
    $conn = getDBConnection();
    
    $isbn = mysqli_real_escape_string($conn, $bookData['isbn']);
    $title = mysqli_real_escape_string($conn, $bookData['title']);
    
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
    
    $query = "INSERT INTO Book (ISBN, title, publication_year, price, category, 
              stock_quantity, threshold, publisher_id) 
              VALUES ('$isbn', '$title', $year_sql, $price, '$category', 
              $stock, $threshold, $publisher_id)";
    
    if (mysqli_query($conn, $query)) {
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
    if ($conn === null) {
        $conn = getDBConnection();
        $closeConnection = true;
    } else {
        $closeConnection = false;
    }
    
    $isbn = mysqli_real_escape_string($conn, $isbn);
    $newQuantity = (int)$newQuantity;
    
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
            
            $books[] = $row;
        }
    }
    
    closeDBConnection($conn);
    return $books;
}

function getBookDetails($isbn) {
    $conn = getDBConnection();
    $isbn = mysqli_real_escape_string($conn, $isbn);
    
    $query = "SELECT b.*, p.name as publisher_name, p.address as publisher_address, p.phone as publisher_phone
              FROM Book b 
              JOIN Publisher p ON b.publisher_id = p.publisher_id 
              WHERE b.ISBN = '$isbn'";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        closeDBConnection($conn);
        return ['success' => false, 'error' => 'Book not found'];
    }
    
    $book = mysqli_fetch_assoc($result);
    
    $author_query = "SELECT a.author_id, a.name 
                     FROM Author a 
                     JOIN Book_Author ba ON a.author_id = ba.author_id 
                     WHERE ba.ISBN = '$isbn'";
    $author_result = mysqli_query($conn, $author_query);
    
    $authors = [];
    $author_ids = [];
    if ($author_result) {
        while ($author_row = mysqli_fetch_assoc($author_result)) {
            $authors[] = $author_row;
            $author_ids[] = (int)$author_row['author_id'];
        }
    }
    
    $book['authors'] = $authors;       
    $book['author_ids'] = $author_ids; 
    
    closeDBConnection($conn);
    
    return ['success' => true, 'book' => $book];
}
?>