<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pc_website"; // Make sure this matches your PHPMyAdmin database name exactly

// FIX: Pass $dbname as the 4th parameter here
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>