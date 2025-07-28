<?php
// File: db_connect.php
// Description: Handles the database connection for the application.

$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password
$dbname = "test"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to format date and time
function formatDateTime($datetime) {
    return date("F j, Y, g:i a", strtotime($datetime));
}

?>