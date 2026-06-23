<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pc_website"; // open the database named pc_website

//take the four information above & connect to the database.
$conn = new mysqli($servername, $username, $password, $dbname);
//saving this active connection into a variable named $conn

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>