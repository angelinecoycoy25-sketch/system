<?php
$servername = "localhost"; // or your server IP address
$username = "root";        // your database username
$password = "";            // your database password
$dbname = "inventory_system"; // your database name

// Create connection to MySQL server to check for/create the database
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it does not exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!$conn->query($sql)) {
    die("Error creating database: " . $conn->error);
}

// Now, connect to the specific database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection after switching to the new database
if ($conn->connect_error) {
    die("Connection to database failed: " . $conn->connect_error);
}

// SQL to create users table if it doesn't exist
$table_sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(30) NOT NULL,
    last_name VARCHAR(30) NOT NULL,
    role VARCHAR(50) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$conn->query($table_sql);

// Add username and password columns if they don't exist, to handle migrations
$check_columns_sql = "
    ALTER TABLE users
    ADD COLUMN IF NOT EXISTS username VARCHAR(50),
    ADD COLUMN IF NOT EXISTS password VARCHAR(255);
";
$conn->query($check_columns_sql);

// After adding the column, attempt to add the unique index. This will fail gracefully if there are duplicates, allowing manual correction without crashing the app.
$add_unique_index_sql = "ALTER TABLE users ADD UNIQUE IF NOT EXISTS (username)";
$conn->query($add_unique_index_sql);

// Hash passwords for default users for better security
$admin_password_hash = password_hash('admin', PASSWORD_DEFAULT);
$user_password_hash = password_hash('user', PASSWORD_DEFAULT);

// Ensure the 'admin' user exists for login.
$admin_check_sql = "INSERT INTO users (first_name, last_name, username, password, role) SELECT 'Admin', 'User', 'admin', '{$admin_password_hash}', 'Admin' FROM DUAL WHERE NOT EXISTS (SELECT * FROM users WHERE username = 'admin')";
$conn->query($admin_check_sql);

// Ensure the 'user' user exists for login.
$user_check_sql = "INSERT INTO users (first_name, last_name, username, password, role) SELECT 'Regular', 'User', 'user', '{$user_password_hash}', 'User' FROM DUAL WHERE NOT EXISTS (SELECT * FROM users WHERE username = 'user')";
$conn->query($user_check_sql);

// SQL to create food table if it doesn't exist
$food_table_sql = "CREATE TABLE IF NOT EXISTS food (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    food_name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($food_table_sql);

// SQL to create items table if it doesn't exist
$items_table_sql = "CREATE TABLE IF NOT EXISTS items (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    location VARCHAR(100),
    price DECIMAL(10, 2) NOT NULL,
    stocks INT(10) NOT NULL,
    issued_date DATE,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($items_table_sql);

// SQL to create sales table if it doesn't exist
$sales_table_sql = "CREATE TABLE IF NOT EXISTS sales (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sold_date DATE NOT NULL,
    food_id INT(6) UNSIGNED,
    item_id INT(6) UNSIGNED,
    quantity INT(10) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (food_id) REFERENCES food(id) ON DELETE SET NULL,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL
)";

$conn->query($sales_table_sql);

// Check if sales table is empty, if so, add some dummy data
$result = $conn->query("SELECT id FROM sales LIMIT 1");
if ($result->num_rows == 0) {
    $insert_sales_sql = "INSERT INTO sales (sold_date, quantity, total_price) VALUES 
    ('2023-10-01', 10, 150.00),
    ('2023-10-02', 15, 225.50),
    ('2023-10-03', 8, 120.75),
    ('2023-10-04', 20, 300.00),
    ('2023-10-05', 12, 180.25)";
    
    $conn->query($insert_sales_sql);
}

?>