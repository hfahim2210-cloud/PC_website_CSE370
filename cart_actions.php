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
// Your schema uses 'date_created', not 'created_at'
$cart_query = $conn->query("SELECT cart_id FROM Cart WHERE users_id = '$user_id'");

if ($cart_query->num_rows > 0) {
    $cart_id = $cart_query->fetch_assoc()['cart_id'];
} else {
    // Insert new cart
    $conn->query("INSERT INTO Cart (users_id, date_created) VALUES ('$user_id', NOW())");
    $cart_id = $conn->insert_id;
}

// 2. Handle ADD Item
if ($action == 'add') {
    $part_id = $_POST['part_id'];
    
    // Check if this part is already in the cart
    $check = $conn->query("SELECT quantity FROM Cart_Item WHERE cart_id='$cart_id' AND part_id='$part_id'");
    
    if ($check->num_rows > 0) {
        // Item exists: Just increase quantity
        $conn->query("UPDATE Cart_Item SET quantity = quantity + 1 WHERE cart_id='$cart_id' AND part_id='$part_id'");
    } else {
        // Item is new: We must generate a cart_item_id manually because your DB doesn't auto-increment it
        $max_sql = "SELECT MAX(cart_item_id) as max_id FROM Cart_Item WHERE cart_id='$cart_id'";
        $max_res = $conn->query($max_sql);
        $row = $max_res->fetch_assoc();
        $next_item_id = ($row['max_id'] !== null) ? $row['max_id'] + 1 : 1;

        // Insert with the manually calculated ID
        $conn->query("INSERT INTO Cart_Item (cart_id, cart_item_id, part_id, quantity) 
                      VALUES ('$cart_id', '$next_item_id', '$part_id', 1)");
    }
    // Return to previous page
    header("Location: " . $_SERVER['HTTP_REFERER']); 
    exit();
}

// 3. Handle UPDATE Quantity (+/-)
if ($action == 'update_qty') {
    $part_id = $_POST['part_id']; // We use part_id to identify the row now
    $direction = $_POST['direction'];

    if ($direction == 'increase') {
        $conn->query("UPDATE Cart_Item SET quantity = quantity + 1 WHERE cart_id='$cart_id' AND part_id='$part_id'");
    } elseif ($direction == 'decrease') {
        // Check current quantity
        $q_sql = "SELECT quantity FROM Cart_Item WHERE cart_id='$cart_id' AND part_id='$part_id'";
        $current_qty = $conn->query($q_sql)->fetch_assoc()['quantity'];
        
        if ($current_qty > 1) {
            $conn->query("UPDATE Cart_Item SET quantity = quantity - 1 WHERE cart_id='$cart_id' AND part_id='$part_id'");
        } else {
            // If qty is 1, remove the item entirely
            $conn->query("DELETE FROM Cart_Item WHERE cart_id='$cart_id' AND part_id='$part_id'");
        }
    }
    header("Location: cart.php");
    exit();
}
?>