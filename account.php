<?php
session_start();
require 'DBconnect.php';

// 1. SECURITY
if (!isset($_SESSION['users_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['users_id'];
$message = "";
$msg_type = "";

// 2. HANDLE FORM SUBMISSIONS

// A. Update Info
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_info'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $email = $_POST['email']; 
    
    // Step 1: Update main User table (removed phone column from here)
    $upd_sql = "UPDATE Users SET name=?, address=?, email=? WHERE users_id=?";
    $stmt = $conn->prepare($upd_sql);
    $stmt->bind_param("sssi", $name, $address, $email, $user_id);
    
    if ($stmt->execute()) {
        // Step 2: Handle Phone Number (Delete old -> Insert new)
        // Since this is a single profile field, we replace whatever was there.
        $del_phone = $conn->query("DELETE FROM users_Phone WHERE users_id = '$user_id'");
        
        if (!empty($phone)) {
            $ins_phone = $conn->prepare("INSERT INTO users_Phone (users_id, phone) VALUES (?, ?)");
            $ins_phone->bind_param("is", $user_id, $phone);
            $ins_phone->execute();
        }

        $message = "Account information updated successfully.";
        $msg_type = "success";
    } else {
        $message = "Error updating information.";
        $msg_type = "error";
    }
    $stmt->close();
}

// B. Update Password (Unchanged)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_pass'])) {
    $old_pass = $_POST['old_pass'];
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    $pass_sql = "SELECT password FROM Users WHERE users_id = ?";
    $stmt = $conn->prepare($pass_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    
    if ($new_pass !== $confirm_pass) {
        $message = "New passwords do not match.";
        $msg_type = "error";
    } elseif ($old_pass == $user['password']) { // Plain text check based on your signup logic
        $upd_pass_sql = "UPDATE Users SET password=? WHERE users_id=?";
        $stmt2 = $conn->prepare($upd_pass_sql);
        $stmt2->bind_param("si", $new_pass, $user_id);
        if($stmt2->execute()){
             $message = "Password changed successfully.";
             $msg_type = "success";
        }
    } else {
        $message = "Incorrect old password.";
        $msg_type = "error";
    }
}

// 3. FETCH USER DATA
// Get main data
$sql = "SELECT * FROM Users WHERE users_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Get Phone Number separately
$phone_sql = "SELECT phone FROM users_Phone WHERE users_id = ? LIMIT 1";
$stmt_p = $conn->prepare($phone_sql);
$stmt_p->bind_param("i", $user_id);
$stmt_p->execute();
$res_p = $stmt_p->get_result();
$phone_data = $res_p->fetch_assoc();
$user_phone = $phone_data ? $phone_data['phone'] : "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Account - PC Shop</title>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        /* Copied Navbar/Footer Styles */
        .navbar { display: flex; align-items: center; justify-content: space-between; background-color: #333; color: white; padding: 15px 20px; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; font-weight: bold; }
        .search-container { flex-grow: 1; margin: 0 20px; text-align: center; }
        .search-container input { width: 60%; padding: 5px; }
        .footer { border-top: 2px solid #000; margin-top: 40px; padding: 20px; text-align: center; background-color: #f1f1f1; }

        /* Account Specific */
        .account-container { max-width: 1200px; margin: 30px auto; display: flex; gap: 20px; padding: 0 20px; }
        .acc-sidebar { flex: 1; min-width: 250px; background: white; border: 1px solid #ddd; border-radius: 5px; height: fit-content; }
        .user-summary { padding: 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px; }
        .user-icon { width: 50px; height: 50px; background: #333; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .acc-menu { list-style: none; padding: 0; margin: 0; }
        .acc-menu li { border-bottom: 1px solid #eee; }
        .acc-menu a { display: block; padding: 15px 20px; color: #333; text-decoration: none; transition: 0.2s; font-weight: bold; }
        .acc-menu a:hover { background-color: #f9f9f9; color: #007bff; }
        .acc-menu a.active { background-color: #333; color: white; }

        .acc-content { flex: 3; background: white; border: 1px solid #ddd; border-radius: 5px; padding: 30px; }
        h3 { margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .form-group { flex: 1; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9em; color: #555; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        
        .update-btn { background-color: #333; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .update-btn:hover { background-color: #555; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Sidebar Cart Styles */
        .cart-sidebar { height: 100%; width: 350px; position: fixed; z-index: 9999; top: 0; right: -350px; background-color: white; box-shadow: -2px 0 10px rgba(0,0,0,0.2); transition: 0.3s; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; background: #333; color: white; display: flex; justify-content: space-between; align-items: center; }
        .sidebar-content { flex-grow: 1; padding: 20px; overflow-y: auto; }
        .sidebar-footer { padding: 20px; border-top: 1px solid #ddd; background: #f9f9f9; }
        .mini-item { display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; font-size: 14px; }
        .view-cart-btn { width: 100%; background: #333; color: white; padding: 12px; text-align: center; text-decoration: none; display: block; margin-top: 10px; font-weight: bold; }
        .close-btn { cursor: pointer; font-size: 24px; }
        .delete-btn { background-color: #ff4d4d; color: white; border: none; border-radius: 4px; width: 24px; height: 24px; cursor: pointer; margin-left: 10px; font-size: 16px; line-height: 24px; text-align: center; padding: 0; font-weight: bold; transition: background 0.2s; }
        .delete-btn:hover { background-color: #cc0000; }
    </style>
</head>
<body>

 <div class="navbar">
        <a href="index.php">Home</a>
        <div style="flex-grow: 1; text-align: center;">
            <input type="text" placeholder="Search..." style="width: 50%; padding: 5px;">
        </div>
        <div>
            <a href="pc_builder.php">PC Builder</a>
            <a href="javascript:void(0)" onclick="toggleCart()">Cart</a>
            <a href="account.php">Account</a>
            <a href="logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>
</div>

    <div class="account-container">
        
        <div class="acc-sidebar">
            <div class="user-summary">
                <div class="user-icon">👤</div>
                <div>
                    <strong><?php echo htmlspecialchars($user_data['name']); ?></strong><br>
                    <small><?php echo htmlspecialchars($user_data['email']); ?></small>
                </div>
            </div>
            <ul class="acc-menu">
                <li><a href="account.php" class="active">Profile</a></li>
                <li><a href="my_listings.php">My Listings</a></li>
                <li><a href="orders.php">Store Purchase History</a></li>
            </ul>
        </div>

        <div class="acc-content">
            
            <?php if ($message): ?>
                <div class="alert <?php echo $msg_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <h3>Account Information</h3>
            <form action="account.php" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone No</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user_phone); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($user_data['address']); ?>">
                    </div>
                </div>

                <button type="submit" name="update_info" class="update-btn">Update Information</button>
            </form>

            <br><br>

            <h3>Password</h3>
            <form action="account.php" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Old Password</label>
                        <input type="password" name="old_pass" placeholder="Enter Old Password">
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_pass" placeholder="Enter New Password">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_pass" placeholder="Confirm New Password">
                    </div>
                </div>
                <button type="submit" name="update_pass" class="update-btn">Update Password</button>
            </form>

        </div>
    </div>

    <div class="footer">
        <p>Contact us: support@pcwebsite.com | Phone: +123 456 789</p>
        <p>&copy; 2025 PC Shop Project</p>
    </div>

    <div id="mySidebar" class="cart-sidebar">
        <div class="sidebar-header">
            <span>Your Cart</span>
            <span class="close-btn" onclick="toggleCart()">×</span>
        </div>

        <div class="sidebar-content">
            <?php
            if (isset($_SESSION['users_id'])) {
                $sb_uid = $_SESSION['users_id'];
                $sb_sql = "SELECT p.part_id, p.name, p.price, ci.quantity 
                           FROM Cart_Item ci 
                           JOIN Cart c ON ci.cart_id = c.cart_id 
                           JOIN PC_Part p ON ci.part_id = p.part_id 
                           WHERE c.users_id = '$sb_uid'";
                $sb_res = $conn->query($sb_sql);
                
                $sb_total = 0;

                if ($sb_res && $sb_res->num_rows > 0) {
                    while($item = $sb_res->fetch_assoc()) {
                        $line_total = $item['price'] * $item['quantity'];
                        $sb_total += $line_total;
                        
                        echo "<div class='mini-item'>";
                        echo "<div><strong>{$item['name']}</strong><br>Qty: {$item['quantity']}</div>";
                        echo "<div style='display:flex; align-items:center;'>";
                        echo "<div>Tk " . number_format($line_total, 2) . "</div>";
                        echo "<form action='cart_actions.php' method='POST' style='margin:0;'>";
                        echo "<input type='hidden' name='action' value='remove'>";
                        echo "<input type='hidden' name='part_id' value='{$item['part_id']}'>";
                        echo "<button type='submit' class='delete-btn'>&times;</button>";
                        echo "</form>";
                        echo "</div>";
                        echo "</div>";
                    }
                } else {
                    echo "<p style='text-align:center;'>Cart is empty.</p>";
                }
            } else {
                echo "<p>Please login to view cart.</p>";
            }
            ?>
        </div>

        <div class="sidebar-footer">
            <div style="display:flex; justify-content:space-between; font-weight:bold; margin-bottom:10px;">
                <span>Subtotal:</span>
                <span>Tk <?php echo isset($sb_total) ? number_format($sb_total, 2) : '0.00'; ?></span>
            </div>
            <a href="cart.php" class="view-cart-btn">VIEW CART</a>
        </div>
    </div>

    <script>
        function toggleCart() {
            var sb = document.getElementById("mySidebar");
            if (sb.style.right === "0px") {
                sb.style.right = "-350px";
            } else {
                sb.style.right = "0px";
            }
        }
    </script>

</body>
</html>