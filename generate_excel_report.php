<?php
// Initialize the session
session_start();
 
// Check if the user is logged in and is an admin, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'Admin'){
    header("location: login.php");
    exit;
}

// Include the central configuration file.
include("conf.php");

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=sales_report_' . date('Y-m-d') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, array('Sold Date', 'Food Name', 'Item Name', 'Quantity Sold', 'Total Price'));

// Fetch inventory and sales report data
if (isset($conn)) {
    $sql = "SELECT s.id, s.sold_date, f.food_name, i.item_name, s.quantity, s.total_price 
            FROM sales AS s
            LEFT JOIN food f ON s.food_id = f.food_id 
            LEFT JOIN items i ON s.item_id = i.item_id 
            ORDER BY s.sold_date DESC, s.id DESC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // Loop over the rows, outputting them
        while($row = $result->fetch_assoc()) {
            $csv_row = [
                $row['sold_date'],
                $row['food_name'] ?? 'N/A', // Use null coalescing operator for safety
                $row['item_name'] ?? 'N/A',
                $row['quantity'],
                number_format($row['total_price'], 2)
            ];
            fputcsv($output, $csv_row);
        }
    }
}

// Close the database connection if it's open
if(isset($conn)) { 
    $conn->close(); 
}

exit();
?>