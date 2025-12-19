<?php
session_start();
require 'DBconnect.php'; 

// 1. SECURITY: Check if user is logged in
if (!isset($_SESSION['users_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['users_id'];

// 2. FETCH USER DETAILS 
$user_sql = "SELECT name, email, address, role FROM Users WHERE users_id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 3. FETCH INVENTORY PARTS 
$inventory_sql = "SELECT part_id, name, price, image, brand FROM PC_Part LIMIT 10"; 
$inventory_result = $conn->query($inventory_sql);

// 4. FETCH USER LISTINGS 
$listing_sql = "SELECT listing_id, title, price, type, status FROM Listing WHERE status = 'Active' LIMIT 10";
$listing_result = $conn->query($listing_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - PC Shop</title>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
        
        /* Navbar */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #333;
            color: white;
            padding: 15px 20px;
        }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; font-weight: bold; }
        .search-container { flex-grow: 1; margin: 0 20px; text-align: center; }
        .search-container input { width: 60%; padding: 5px; }

        /* General Layout */
        .container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        h2 { border-bottom: 2px solid #333; padding-bottom: 10px; margin-top: 30px; }

        /* Section 1: User Details Box */
        .user-details {
            border: 2px solid #000;
            padding: 20px;
            background-color: #f9f9f9;
            margin-bottom: 20px;
        }

        /* Section 2 & 3: Horizontal Scroll Grid */
        .scroll-container {
            display: flex;
            overflow-x: auto;
            gap: 20px;
            padding: 10px 0;
            border: 1px solid #ddd;
            background: #fff;
        }
        .card {
            min-width: 200px;
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            background-color: white;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
        }
        .card img { width: 100%; height: 120px; object-fit: contain; background: #eee; }
        .price { color: #28a745; font-weight: bold; }
        .type-badge { background: #007bff; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; }

        /* Footer */
        .footer {
            border-top: 2px solid #000;
            margin-top: 40px;
            padding: 20px;
            text-align: center;
            background-color: #f1f1f1;
        }

        /* --- SIDEBAR CSS --- */
        .cart-sidebar {
            height: 100%;
            width: 350px;
            position: fixed;
            z-index: 9999;
            top: 0;
            right: -350px; /* Hidden by default */
            background-color: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.2);
            transition: 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 20px;
            background: #333;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sidebar-content {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #ddd;
            background: #f9f9f9;
        }
        
        .mini-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            font-size: 14px;
        }
        
        .view-cart-btn {
            width: 100%;
            background: #333;
            color: white;
            padding: 12px;
            text-align: center;
            text-decoration: none;
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        .close-btn { cursor: pointer; font-size: 24px; }
    </style>
</head>
<body>
    
    <div class="navbar">
        <div class="logo">Home</div> 
        
        <div class="search-container">
            <form action="search_results.php" method="GET">
                <input type="text" name="query" placeholder="Search bar...">
                <button type="submit">🔍</button>
            </form>
        </div>

        <div class="nav-links">
            <a href="pc_builder.php">PC Builder</a>
            
            <a href="javascript:void(0)" onclick="toggleCart()">Cart</a>
            
            <a href="account.php">Account</a>
            <a href="logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div> 
    
    <div style="background-color: #444; padding: 10px 20px; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
        <a href="catalog.php" style="color: white; text-decoration: none; font-weight: bold;">All Parts</a>
        <a href="catalog.php?type=CPU" style="color: #ddd; text-decoration: none;">CPU</a>
        <a href="catalog.php?type=GPU" style="color: #ddd; text-decoration: none;">GPU</a>
        <a href="catalog.php?type=RAM" style="color: #ddd; text-decoration: none;">RAM</a>
        <a href="catalog.php?type=Motherboard" style="color: #ddd; text-decoration: none;">Motherboard</a>
        <a href="catalog.php?type=Storage" style="color: #ddd; text-decoration: none;">Storage</a>
        <a href="catalog.php?type=PSU" style="color: #ddd; text-decoration: none;">PSU</a>
        <a href="catalog.php?type=Casing" style="color: #ddd; text-decoration: none;">Casing</a>
        <a href="catalog.php?type=Cooler" style="color: #ddd; text-decoration: none;">Cooler</a>
    </div>

    <div class="container">

        <h2>Section 1: User Details</h2>
        <div class="user-details">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($user_data['name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
            <p><strong>Role:</strong> <?php echo htmlspecialchars($user_data['role']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($user_data['address']); ?></p>
        </div>

        <h2>Section 2: Listing of PC Parts (Inventory)</h2>
        <div class="scroll-container">
            <?php if ($inventory_result->num_rows > 0): ?>
                <?php while($row = $inventory_result->fetch_assoc()): ?>
                    <div class="card">
                        <img src="<?php echo $row['image'] ? $row['image'] : 'https://via.placeholder.com/150'; ?>" alt="Part">
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                        <p class="price">$<?php echo $row['price']; ?></p>
                        <p><?php echo htmlspecialchars($row['brand']); ?></p>
                        <form action="cart_actions.php" method="POST">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="part_id" value="<?php echo $row['part_id']; ?>">
                            <button type="submit">Add to Cart</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="padding: 20px;">No parts found in inventory.</p>
            <?php endif; ?>
        </div>

        <h2>Section 3: Listing of PC Parts (User Listings)</h2>
        <div class="scroll-container">
            <?php if ($listing_result->num_rows > 0): ?>
                <?php while($row = $listing_result->fetch_assoc()): ?>
                    <div class="card" style="border-color: #007bff;">
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <span class="type-badge"><?php echo $row['type']; ?></span>
                        <p class="price">$<?php echo $row['price']; ?></p>
                        <p>Status: <?php echo $row['status']; ?></p>
                        <button>View Details</button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="padding: 20px;">No active listings found.</p>
            <?php endif; ?>
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
            // MINI CART PHP LOGIC
            if (isset($_SESSION['users_id'])) {
                $sb_uid = $_SESSION['users_id'];
                $sb_sql = "SELECT p.name, p.price, ci.quantity 
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
                        echo "<div>$" . number_format($line_total, 2) . "</div>";
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
                <span>$<?php echo isset($sb_total) ? number_format($sb_total, 2) : '0.00'; ?></span>
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