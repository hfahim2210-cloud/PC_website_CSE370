<?php
session_start();
require 'DBconnect.php';

if (!isset($_SESSION['users_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['users_id'];
$action = $_POST['action'] ?? '';

// 1. Get or Create Cart for User
$cart_query = $conn->query("SELECT cart_id FROM Cart WHERE users_id = '$user_id'");

if ($cart_query->num_rows > 0) {
    $cart_id = $cart_query->fetch_assoc()['cart_id'];
} else {
    // Insert new cart if one doesn't exist
    $conn->query("INSERT INTO Cart (users_id, date_created) VALUES ('$user_id', NOW())");
    $cart_id = $conn->insert_id;
}

// 2. Handle ADD Item
if ($action == 'add') {
    $part_id = $_POST['part_id'];
    
    // Check if item exists
    $check = $conn->query("SELECT quantity FROM Cart_Item WHERE cart_id='$cart_id' AND part_id='$part_id'");
    
    if ($check->num_rows > 0) {
        $conn->query("UPDATE Cart_Item SET quantity = quantity + 1 WHERE cart_id='$cart_id' AND part_id='$part_id'");
    } else {
        // MANUAL ID CALCULATION (Crucial for your schema)
        $max_sql = "SELECT MAX(cart_item_id) as max_id FROM Cart_Item WHERE cart_id='$cart_id'";
        $row = $conn->query($max_sql)->fetch_assoc();
        $next_item_id = ($row['max_id'] !== null) ? $row['max_id'] + 1 : 1;

        $conn->query("INSERT INTO Cart_Item (cart_id, cart_item_id, part_id, quantity) 
                      VALUES ('$cart_id', '$next_item_id', '$part_id', 1)");
    }
    // Return to the previous page so the user can keep shopping
    header("Location: " . $_SERVER['HTTP_REFERER']); 
    exit();
}

// 3. Handle UPDATE Quantity (Increase/Decrease)
if ($action == 'update_qty') {
    $part_id = $_POST['part_id'];
    $direction = $_POST['direction'];

    if ($direction == 'increase') {
        $conn->query("UPDATE Cart_Item SET quantity = quantity + 1 WHERE cart_id='$cart_id' AND part_id='$part_id'");
    } elseif ($direction == 'decrease') {
        $q_sql = "SELECT quantity FROM Cart_Item WHERE cart_id='$cart_id' AND part_id='$part_id'";
        $curr = $conn->query($q_sql)->fetch_assoc()['quantity'];
        
        if ($curr > 1) {
            $conn->query("UPDATE Cart_Item SET quantity = quantity - 1 WHERE cart_id='$cart_id' AND part_id='$part_id'");
        } else {
            $conn->query("DELETE FROM Cart_Item WHERE cart_id='$cart_id' AND part_id='$part_id'");
        }
    }
    header("Location: cart.php"); // Updates usually happen on the full cart page
    exit();
}
?>