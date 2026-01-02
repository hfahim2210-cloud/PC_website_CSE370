<?php
session_start();
require 'DBconnect.php';

if (!isset($_SESSION['users_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['users_id'];
$action = $_POST['action'] ?? '';

// 1. Get the LATEST Active Cart
$cart_query = $conn->query("SELECT cart_id FROM Cart WHERE users_id = '$user_id' ORDER BY cart_id DESC LIMIT 1");

if ($cart_query->num_rows > 0) {
    $cart_id = $cart_query->fetch_assoc()['cart_id'];
} else {
    $conn->query("INSERT INTO Cart (users_id, created_at) VALUES ('$user_id', NOW())");
    $cart_id = $conn->insert_id;
}

// Helper Function: Get Available Stock
function getStock($conn, $part_id) {
    $sql = "SELECT stock FROM PC_Part WHERE part_id = '$part_id'";
    $res = $conn->query($sql);
    if ($res->num_rows > 0) {
        return (int)$res->fetch_assoc()['stock'];
    }
    return 0;
}

// 2. Handle ADD Item (From Catalog)
if ($action == 'add') {
    $part_id = intval($_POST['part_id']);
    $stock = getStock($conn, $part_id);
    
    // Check if item exists in cart
    $check = $conn->query("SELECT quantity FROM Cart_Item WHERE cart_id='$cart_id' AND part_id='$part_id'");
    $current_qty_in_cart = 0;
    
    if ($check->num_rows > 0) {
        $current_qty_in_cart = (int)$check->fetch_assoc()['quantity'];
    }

    // STOCK CHECK: Can we add 1 more?
    if ($current_qty_in_cart + 1 <= $stock) {
        if ($current_qty_in_cart > 0) {
            $conn->query("UPDATE Cart_Item SET quantity = quantity + 1 WHERE cart_id='$cart_id' AND part_id='$part_id'");
        } else {
            $conn->query("INSERT INTO Cart_Item (cart_id, part_id, quantity) VALUES ('$cart_id', '$part_id', 1)");
        }
    } else {
        // Optional: Set a session message here like "Not enough stock!"
    }
    
    header("Location: " . $_SERVER['HTTP_REFERER']); 
    exit();
}

// 3. Handle UPDATE Quantity (The + and - buttons)
if ($action == 'update_qty') {
    $part_id = intval($_POST['part_id']);
    $direction = $_POST['direction'];

    // Get current quantity in cart
    $q_res = $conn->query("SELECT quantity FROM Cart_Item WHERE cart_id='$cart_id' AND part_id='$part_id'");
    
    if ($q_res->num_rows > 0) {
        $curr = (int)$q_res->fetch_assoc()['quantity'];

        if ($direction == 'increase') {
            $stock = getStock($conn, $part_id);
            
            // STOCK CHECK: Only increase if we have stock
            if ($curr + 1 <= $stock) {
                $conn->query("UPDATE Cart_Item SET quantity = quantity + 1 WHERE cart_id='$cart_id' AND part_id='$part_id'");
            } else {
                 // Stock limit reached - do nothing (or alert user)
            }
        } 
        elseif ($direction == 'decrease') {
            if ($curr > 1) {
                $conn->query("UPDATE Cart_Item SET quantity = quantity - 1 WHERE cart_id='$cart_id' AND part_id='$part_id'");
            } else {
                $conn->query("DELETE FROM Cart_Item WHERE cart_id='$cart_id' AND part_id='$part_id'");
            }
        }
    }
    header("Location: cart.php"); 
    exit();
}

// 4. Handle REMOVE Item
if ($action == 'remove') {
    $part_id = intval($_POST['part_id']);
    $conn->query("DELETE FROM Cart_Item WHERE cart_id='$cart_id' AND part_id='$part_id'");
    header("Location: cart.php");
    exit();
}
?>