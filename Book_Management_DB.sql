CREATE DATABASE Book_Store_Management;
USE  Book_Store_Management;

CREATE TABLE Admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE Customer (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    shipping_address VARCHAR(255)
);

CREATE TABLE Publisher (
    publisher_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    phone VARCHAR(20)
);

CREATE TABLE Book (
    ISBN VARCHAR(13) PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    publication_year INT,
    price DECIMAL(8,2) NOT NULL CHECK (price > 0),
    category ENUM('Science','Art','Religion','History','Geography') NOT NULL,
    stock_quantity INT NOT NULL CHECK (stock_quantity >= 0),
    threshold INT NOT NULL CHECK (threshold >= 0),
    publisher_id INT NOT NULL,
    FOREIGN KEY (publisher_id) REFERENCES Publisher(publisher_id)
);

CREATE TABLE Author (
    author_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE Book_Author (
    ISBN VARCHAR(13),
    author_id INT,
    PRIMARY KEY (ISBN, author_id),
    FOREIGN KEY (ISBN) REFERENCES Book(ISBN) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES Author(author_id) ON DELETE CASCADE
);

CREATE TABLE `Order` (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL CHECK (total_amount >= 0),
    customer_id INT NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE
);

CREATE TABLE Order_Item (
    order_id INT,
    ISBN VARCHAR(13),
    quantity INT NOT NULL CHECK (quantity > 0),
    price_at_purchase DECIMAL(8,2) NOT NULL CHECK (price_at_purchase > 0),
    PRIMARY KEY (order_id, ISBN),
    FOREIGN KEY (order_id) REFERENCES `Order`(order_id) ON DELETE CASCADE,
    FOREIGN KEY (ISBN) REFERENCES Book(ISBN)
);

CREATE TABLE Shopping_Cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNIQUE,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE
);

CREATE TABLE Cart_Item (
    cart_id INT,
    ISBN VARCHAR(13),
    quantity INT NOT NULL CHECK (quantity > 0),
    PRIMARY KEY (cart_id, ISBN),
    FOREIGN KEY (cart_id) REFERENCES Shopping_Cart(cart_id) ON DELETE CASCADE,
    FOREIGN KEY (ISBN) REFERENCES Book(ISBN) 
);

CREATE TABLE Replenishment_Order (
    reorder_id INT AUTO_INCREMENT PRIMARY KEY,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    quantity INT NOT NULL CHECK (quantity > 0),
    status ENUM('Pending','Confirmed') DEFAULT 'Pending',
    ISBN VARCHAR(13),
    publisher_id INT,
    admin_id INT,
    FOREIGN KEY (ISBN) REFERENCES Book(ISBN),
    FOREIGN KEY (publisher_id) REFERENCES Publisher(publisher_id),
    FOREIGN KEY (admin_id) REFERENCES Admin(admin_id)
);


DELIMITER $$

CREATE TRIGGER prevent_negative_stock          -- To Prevent the stock quantity of a book from becoming negative.
BEFORE UPDATE ON Book
FOR EACH ROW                                   -- for each row being updated
BEGIN
    IF NEW.stock_quantity < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock quantity cannot be negative';
    END IF;
END$$


CREATE TRIGGER auto_reorder                    -- Auto reorder when stock < threshold 
AFTER UPDATE ON Book
FOR EACH ROW
BEGIN
    IF NEW.stock_quantity < NEW.threshold THEN
        INSERT INTO Replenishment_Order (ISBN, publisher_id, quantity, status, admin_id)
        VALUES (NEW.ISBN, NEW.publisher_id, 10, 'Pending', 1);
    END IF;
END$$


CREATE TRIGGER confirm_replenishment           -- Increase stock when reorder is confirmed 
AFTER UPDATE ON Replenishment_Order
FOR EACH ROW
BEGIN
    IF OLD.status = 'Pending' AND NEW.status = 'Confirmed' THEN
        UPDATE Book
        SET stock_quantity = stock_quantity + NEW.quantity
        WHERE ISBN = NEW.ISBN;
    END IF;
END$$


CREATE TRIGGER deduct_stock_after_order        -- Deduct stock after order item insertion 
AFTER INSERT ON Order_Item
FOR EACH ROW
BEGIN
    IF (SELECT stock_quantity FROM Book WHERE ISBN = NEW.ISBN) < NEW.quantity THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock for this book';
    ELSE
        UPDATE Book
        SET stock_quantity = stock_quantity - NEW.quantity
        WHERE ISBN = NEW.ISBN;
    END IF;
END$$

DELIMITER ;
