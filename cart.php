<?php
session_start();
require 'DBconnect.php';

if (!isset($_SESSION['users_id'])) { header("Location: signin.php"); exit(); }
$user_id = $_SESSION['users_id'];

// Fetch Cart Data
$sql = "SELECT ci.part_id, ci.quantity, p.name, p.price, p.image 
        FROM Cart_Item ci 
        JOIN Cart c ON ci.cart_id = c.cart_id 
        JOIN PC_Part p ON ci.part_id = p.part_id 
        WHERE c.users_id = '$user_id'";
$result = $conn->query($sql);

$total_price = 0;
$total_items = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Shopping Cart</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; display: flex; flex-direction: column; min-height: 100vh; }
        
        /* --- NAVBAR STYLES --- */
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
        .search-container input { width: 60%; padding: 5px; height: 30px; border: none; }
        .search-btn { height: 32px; cursor: pointer; background: #ddd; border: none; padding: 0 10px; }

        /* Sub-Navbar */
        .sub-navbar {
            background-color: #444; 
            padding: 10px 20px; 
            display: flex; 
            justify-content: center; 
            gap: 20px; 
            flex-wrap: wrap;
        }
        .sub-navbar a { color: #ddd; text-decoration: none; }
        .sub-navbar a:hover { color: white; }

        /* --- CART PAGE SPECIFIC STYLES (Preserved from your code) --- */
        .container { max-width: 1200px; margin: 30px auto; display: flex; gap: 30px; padding: 0 20px; flex-grow: 1; width: 100%; box-sizing: border-box; }
        
        /* Left Section: Cart Items */
        .cart-list { flex: 2; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .cart-header { border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        
        .cart-item { display: flex; align-items: center; border-bottom: 1px solid #eee; padding: 15px 0; }
        .cart-item img { width: 80px; height: 80px; object-fit: contain; margin: 0 20px; }
        .item-details { flex-grow: 1; }
        
        .qty-box { display: flex; align-items: center; border: 1px solid #ddd; width: fit-content; }
        .qty-btn { background: #eee; border: none; padding: 5px 10px; cursor: pointer; }
        .qty-num { padding: 0 10px; font-weight: bold; }

        /* Right Section: Summary */
        .price-summary { flex: 1; background: white; padding: 20px; border-radius: 5px; height: fit-content; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .total-row { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 2px dashed #ddd; font-weight: bold; font-size: 1.2em; }
        
        .btn-checkout { width: 100%; background: #28a745; color: white; padding: 15px; border: none; font-size: 1.1em; cursor: pointer; margin-top: 20px; }
        .btn-continue { width: 100%; background: white; color: #333; border: 1px solid #333; padding: 10px; margin-top: 10px; cursor: pointer; display: block; text-align: center; text-decoration: none; box-sizing: border-box; }

        /* --- FOOTER STYLE --- */
        .footer { background: #f1f1f1; text-align: center; padding: 20px; border-top: 2px solid #333; margin-top: auto; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="index.php" style="font-size: 1.2em;">Home</a>
    
    <div class="search-container">
        <form action="search_results.php" method="GET" style="margin: 0; display: flex; justify-content: center;">
            <input type="text" name="query" placeholder="Search bar...">
            <button type="submit" class="search-btn">🔍</button>
        </form>
    </div>

    <div class="nav-links">
        <a href="pc_builder.php">PC Builder</a>
        <a href="account.php">Account</a>
        <a href="logout.php" style="color: #ff6b6b;">Logout</a>
    </div>
</div>

<div class="sub-navbar">
    <a href="catalog.php">All Parts</a>
    <a href="catalog.php?type=CPU">CPU</a>
    <a href="catalog.php?type=GPU">GPU</a>
    <a href="catalog.php?type=RAM">RAM</a>
    <a href="catalog.php?type=Motherboard">Motherboard</a>
    <a href="catalog.php?type=Storage">Storage</a>
    <a href="catalog.php?type=PSU">PSU</a>
    <a href="catalog.php?type=Casing">Casing</a>
    <a href="catalog.php?type=Cooler">Cooler</a>
</div>

<div class="container">
    <div class="cart-list">
        <div class="cart-header">
            <h3>Shopping Cart (<?php echo $result->num_rows; ?> Items)</h3>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $subtotal = $row['price'] * $row['quantity'];
                $total_price += $subtotal;
            ?>
            <div class="cart-item">
                <img src="images/<?php echo htmlspecialchars($row['image']); ?>" alt="Img" onerror="this.src='https://via.placeholder.com/150';">
                <div class="item-details">
                    <h4><?php echo htmlspecialchars($row['name']); ?></h4>
                    <p style="color: #28a745; font-weight: bold;">$<?php echo number_format($row['price'], 2); ?></p>
                </div>
                
                <div class="qty-box">
                    <form action="cart_actions.php" method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="update_qty">
                        <input type="hidden" name="part_id" value="<?php echo $row['part_id']; ?>">
                        <input type="hidden" name="direction" value="decrease">
                        <button class="qty-btn">-</button>
                    </form>
                    
                    <span class="qty-num"><?php echo $row['quantity']; ?></span>
                    
                    <form action="cart_actions.php" method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="update_qty">
                        <input type="hidden" name="part_id" value="<?php echo $row['part_id']; ?>">
                        <input type="hidden" name="direction" value="increase">
                        <button class="qty-btn">+</button>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="padding: 20px;">Your cart is empty.</p>
        <?php endif; ?>
    </div>

    <div class="price-summary">
        <h3>Price Details</h3>
        <div class="summary-row"><span>Subtotal</span><span>$<?php echo number_format($total_price, 2); ?></span></div>
        <div class="summary-row"><span>Discount</span><span>$0.00</span></div>
        <div class="total-row"><span>Total</span><span>$<?php echo number_format($total_price, 2); ?></span></div>

        <button class="btn-checkout" onclick="window.location.href='checkout.php'">Checkout</button>
        <a href="index.php" class="btn-continue">Continue Shopping</a>
    </div>
</div>

<div class="footer">
    <p>Contact us: support@pcwebsite.com | Phone: +123 456 789</p>
    <p>&copy; 2025 PC Shop Project</p>
</div>

</body>
</html>