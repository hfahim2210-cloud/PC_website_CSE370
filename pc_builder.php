<?php
session_start();
require 'DBconnect.php';

// Enable error reporting for debugging (Remove this line when live)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Ensure user is logged in
if (!isset($_SESSION['users_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['users_id'];
$build_id = null;

// --- 1. GET OR CREATE BUILD ---
$check_build = $conn->prepare("SELECT build_id FROM PC_Builder WHERE users_id = ? ORDER BY created_at DESC LIMIT 1");
$check_build->bind_param("i", $user_id);
$check_build->execute();
$result = $check_build->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $build_id = $row['build_id'];
} else {
    $create_build = $conn->prepare("INSERT INTO PC_Builder (users_id, total_price, total_watts) VALUES (?, 0, 0)");
    $create_build->bind_param("i", $user_id);
    $create_build->execute();
    $build_id = $conn->insert_id;
}
$check_build->close();

// --- 2. LOGIC: REMOVE ITEM FROM BUILD ---
if (isset($_GET['remove_item'])) {
    $item_id = $_GET['remove_item'];
    $delete_stmt = $conn->prepare("DELETE FROM Build_Items WHERE build_item_id = ?");
    $delete_stmt->bind_param("i", $item_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    header("Location: pc_builder.php");
    exit();
}

// --- 3. LOGIC: ADD ALL TO CART (FINAL FIX: SELF-HEALING & STRICT CHECKS) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_all_to_cart'])) {
    
    // A. Get or Create Cart ID
    $cart_id = 0;
    $check_cart = $conn->prepare("SELECT cart_id FROM Cart WHERE users_id = ?");
    $check_cart->bind_param("i", $user_id);
    $check_cart->execute();
    $res_cart = $check_cart->get_result();

    if ($res_cart->num_rows > 0) {
        $c_row = $res_cart->fetch_assoc();
        $cart_id = (int)$c_row['cart_id'];
    } else {
        $mk_cart = $conn->prepare("INSERT INTO Cart (users_id, created_at) VALUES (?, NOW())");
        $mk_cart->bind_param("i", $user_id);
        $mk_cart->execute();
        $cart_id = $conn->insert_id;
        $mk_cart->close();
    }
    $check_cart->close();

    // === B. SELF-HEALING FIX (CRITICAL) ===
    // This line deletes the bad "1-0" entry that is causing your Fatal Error.
    $conn->query("DELETE FROM Cart_Item WHERE part_id = 0"); 
    // ======================================

    // C. Fetch Items from Build
    // We select ALL items, but we will filter them strictly in the loop below
    $fetch_stmt = $conn->prepare("SELECT part_id, quantity FROM Build_Items WHERE build_id = ?");
    $fetch_stmt->bind_param("i", $build_id);
    $fetch_stmt->execute();
    $result_set = $fetch_stmt->get_result();

    $items_to_process = [];
    while ($row = $result_set->fetch_assoc()) {
        $items_to_process[] = $row;
    }
    $fetch_stmt->close();

    // D. Loop and Insert (Strict logic to prevent crashes)
    foreach ($items_to_process as $item) {
        $pid = (int)$item['part_id'];
        $qty = (int)$item['quantity'];

        // 1. STRICT FILTER: If Part ID is 0, SKIP IT.
        // This ensures we never try to insert '0' again.
        if ($pid <= 0) {
            continue; 
        }

        // 2. CHECK IF ITEM EXISTS IN CART ALREADY
        // We use a fresh query here to be absolutely sure of the state
        $check_exist = $conn->query("SELECT * FROM Cart_Item WHERE cart_id = $cart_id AND part_id = $pid");

        if ($check_exist->num_rows > 0) {
            // 3. UPDATE existing item (Prevents 'Duplicate entry' error on valid parts)
            $conn->query("UPDATE Cart_Item SET quantity = quantity + $qty WHERE cart_id = $cart_id AND part_id = $pid");
        } else {
            // 4. INSERT new item
            $conn->query("INSERT INTO Cart_Item (cart_id, part_id, quantity) VALUES ($cart_id, $pid, $qty)");
        }
    }

    // E. Redirect
    echo "<script>window.location.href='cart.php';</script>";
    exit();
}
// --- 4. FETCH DATA FOR DISPLAY ---
$sql = "SELECT bi.build_item_id, bi.quantity, p.part_id, p.name, p.type, p.price, p.watts, p.image 
        FROM Build_Items bi 
        JOIN PC_Part p ON bi.part_id = p.part_id 
        WHERE bi.build_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $build_id);
$stmt->execute();
$items_result = $stmt->get_result();

$selected_parts = [];
$total_price = 0;
$total_watts = 0;

while ($row = $items_result->fetch_assoc()) {
    $selected_parts[$row['type']] = $row;
    $total_price += ($row['price'] * $row['quantity']);
    $total_watts += ($row['watts'] * $row['quantity']);
}
$stmt->close();

// Update DB Totals
$update_totals = $conn->prepare("UPDATE PC_Builder SET total_price = ?, total_watts = ? WHERE build_id = ?");
$update_totals->bind_param("dii", $total_price, $total_watts, $build_id);
$update_totals->execute();
$update_totals->close();

$component_slots = ['CPU', 'Motherboard', 'RAM', 'Storage', 'GPU', 'PSU', 'Casing', 'Cooler'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PC Builder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; background-color: #f9f9f9; color: #333; display: flex; flex-direction: column; min-height: 100vh; }
        
        /* NAVBAR */
        .navbar { background-color: #333; height: 60px; display: flex; align-items: center; justify-content: space-between; padding: 0 40px; color: white; }
        .nav-left a { color: #fff; text-decoration: none; font-size: 18px; margin-right: 20px; font-weight: bold; }
        
        .nav-center { flex-grow: 1; display: flex; justify-content: center; }
        .search-container { display: flex; width: 60%; }
        .search-container input { width: 100%; padding: 8px 15px; border: none; outline: none; border-radius: 4px 0 0 4px; }
        .search-container button { padding: 8px 15px; background: #eee; border: none; cursor: pointer; border-radius: 0 4px 4px 0; }
        
        .nav-right a { color: #fff; text-decoration: none; font-size: 16px; margin-left: 25px; font-weight: 500; cursor: pointer; }
        .nav-right a:hover { color: #28a745; }
        .nav-right .logout { color: #ff6b6b; }

        /* MAIN CONTENT */
        .container { max-width: 1300px; margin: 40px auto; background: white; padding: 40px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); flex: 1; }
        
        h1 { margin-top: 0; color: #333; font-size: 28px; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 25px; }

        /* SUMMARY BAR */
        .summary-bar { background: #333; color: white; padding: 20px 30px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; border-radius: 4px; }
        
        .summary-info { font-size: 1.1em; display: flex; align-items: center; }
        .summary-info span.green-text { color: #28a745; font-weight: bold; }
        
        /* BUTTON STYLES */
        .btn-add-all-submit { 
            background: transparent; 
            border: 1px solid white; 
            color: white; 
            padding: 5px 15px; 
            font-size: 12px;   
            text-transform: uppercase; 
            cursor: pointer; 
            transition: 0.2s;
            margin-left: 60px; 
            letter-spacing: 1px;
        }
        .btn-add-all-submit:hover { background: white; color: #333; }

        /* Component List */
        .component-row { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #eee; padding: 20px 0; }
        
        .comp-icon { width: 60px; height: 60px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; margin-right: 25px; border-radius: 4px; border: 1px solid #eee; }
        .comp-icon img { width: 80%; height: 80%; object-fit: contain; }
        
        .comp-details { flex-grow: 1; }
        .comp-type { font-weight: bold; font-size: 15px; display: block; margin-bottom: 5px; color: #333; }
        .comp-name { color: #555; font-size: 14px; }
        .comp-name.active { color: #000; font-weight: 500; }
        
        .btn-select { border: 1px solid #007bff; color: #007bff; background: white; padding: 8px 30px; border-radius: 30px; text-decoration: none; font-size: 14px; font-weight: bold; transition: 0.2s; }
        .btn-select:hover { background: #007bff; color: white; }
        
        .btn-remove { color: #999; font-weight: bold; margin-left: 15px; cursor: pointer; text-decoration: none; font-size: 20px; padding: 0 10px; }
        .btn-remove:hover { color: #dc3545; }

        /* FOOTER */
        .footer { background-color: #eee; color: #333; text-align: center; padding: 30px 0; margin-top: auto; border-top: 1px solid #ddd; }
        .footer p { margin: 5px 0; font-size: 14px; color: #555; }

    </style>
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <a href="index.php">Home</a>
    </div>
    
    <div class="nav-center">
        <div class="search-container">
            <input type="text" placeholder="Search bar...">
            <button><i class="fas fa-search"></i></button>
        </div>
    </div>
    
    <div class="nav-right">
        <a href="cart.php">Cart</a>
        <a href="account.php">Account</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</div>

<div class="container">
    <h1>Build Your PC</h1>
    
    <div class="summary-bar">
        <div class="summary-info">
            Total Price: <span class="green-text">$<?php echo number_format($total_price, 2); ?></span> &nbsp;|&nbsp; 
            Estimated Power: <span class="green-text"><?php echo $total_watts; ?>W</span>
        </div>
        
        <form method="POST" style="margin:0;">
            <button type="submit" name="add_all_to_cart" class="btn-add-all-submit">
                ADD ALL TO CART
            </button>
        </form>
    </div>

    <?php foreach ($component_slots as $category): ?>
        <div class="component-row">
            <div style="display:flex; align-items:center; width:100%;">
                <div class="comp-icon">
                    <?php if (isset($selected_parts[$category])): ?>
                        <img src="images/<?php echo $selected_parts[$category]['image']; ?>" alt="img">
                    <?php else: ?>
                        <span>?</span>
                    <?php endif; ?>
                </div>
                
                <div class="comp-details">
                    <span class="comp-type"><?php echo $category; ?></span>
                    <?php if (isset($selected_parts[$category])): ?>
                        <div class="comp-name active">
                            <?php echo $selected_parts[$category]['name']; ?> 
                            <span style="float:right; font-weight:bold; margin-right:25px;">
                                $<?php echo number_format($selected_parts[$category]['price'], 2); ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <span class="comp-name">No component selected</span>
                    <?php endif; ?>
                </div>

                <div>
                    <?php if (isset($selected_parts[$category])): ?>
                        <a href="pc_builder.php?remove_item=<?php echo $selected_parts[$category]['build_item_id']; ?>" class="btn-remove">✕</a>
                    <?php else: ?>
                        <a href="select_part.php?category=<?php echo $category; ?>&build_id=<?php echo $build_id; ?>" class="btn-select">Select</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="footer">
    <p>Contact us: support@pcwebsite.com | Phone: +123 456 789</p>
    <p>&copy; 2025 PC Shop Project</p>
</div>

</body>
</html>