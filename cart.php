<?php
session_start();
require 'DBconnect.php';

// 1. Security Check
if (!isset($_SESSION['users_id'])) { header("Location: signin.php"); exit(); }
$user_id = $_SESSION['users_id'];

$cart_id = 0;
$total_price = 0;
$result = null;

// 2. Fetch the LATEST Active Cart ID
$cart_stmt = $conn->prepare("SELECT cart_id FROM Cart WHERE users_id = ? ORDER BY cart_id DESC LIMIT 1");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_res = $cart_stmt->get_result();

if ($cart_res->num_rows > 0) {
    $cart_id = $cart_res->fetch_assoc()['cart_id'];

    // 3. Fetch Items
    $sql = "SELECT ci.part_id, ci.quantity, p.name, p.price, p.image 
            FROM Cart_Item ci 
            JOIN PC_Part p ON ci.part_id = p.part_id 
            WHERE ci.cart_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Shopping Cart</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: #f4f4f4; display: flex; flex-direction: column; min-height: 100vh; }
        
        /* NAVBAR */
        .navbar { display: flex; align-items: center; justify-content: space-between; background-color: #333; color: white; padding: 15px 20px; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; font-weight: bold; }
        .search-container { flex-grow: 1; margin: 0 20px; text-align: center; }
        .search-container input { width: 60%; padding: 5px; height: 30px; border: none; border-radius: 3px; }
        .search-btn { height: 32px; cursor: pointer; background: #ddd; border: none; padding: 0 10px; border-radius: 3px; }

        /* SUB-NAVBAR */
        .sub-navbar { background-color: #444; padding: 10px 20px; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
        .sub-navbar a { color: #ddd; text-decoration: none; font-size: 0.9em; }
        .sub-navbar a:hover { color: white; }

        /* CART STYLES */
        .container { max-width: 1200px; margin: 30px auto; display: flex; gap: 30px; padding: 0 20px; flex-grow: 1; width: 100%; box-sizing: border-box; }
        
        .cart-list { flex: 2; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .cart-header { border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        
        .cart-item { display: flex; align-items: center; border-bottom: 1px solid #eee; padding: 15px 0; }
        .cart-item:last-child { border-bottom: none; }
        .cart-item img { width: 80px; height: 80px; object-fit: contain; margin: 0 20px; border: 1px solid #eee; padding: 5px; border-radius: 4px; }
        
        .item-details { flex-grow: 1; }
        .item-details h4 { margin: 0 0 5px 0; color: #333; }
        
        .qty-box { display: flex; align-items: center; border: 1px solid #ddd; width: fit-content; border-radius: 4px; overflow: hidden; }
        .qty-btn { background: #f8f9fa; border: none; padding: 8px 12px; cursor: pointer; transition: 0.2s; font-weight: bold; }
        .qty-btn:hover { background: #e2e6ea; }
        .qty-num { padding: 0 15px; font-weight: bold; min-width: 20px; text-align: center; }

        .btn-remove { 
            color: #dc3545; background: none; border: none; font-size: 1.5em; cursor: pointer; margin-left: 20px; padding: 0 10px; 
            transition: 0.2s;
        }
        .btn-remove:hover { color: #ff0000; transform: scale(1.1); }

        .price-summary { flex: 1; background: white; padding: 20px; border-radius: 5px; height: fit-content; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; color: #555; }
        .total-row { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 2px dashed #ddd; font-weight: bold; font-size: 1.3em; color: #333; }
        
        .btn-checkout { width: 100%; background: #28a745; color: white; padding: 15px; border: none; font-size: 1.1em; cursor: pointer; margin-top: 20px; border-radius: 4px; font-weight: bold; }
        .btn-checkout:hover { background-color: #218838; }
        
        .btn-continue { width: 100%; background: white; color: #555; border: 1px solid #ddd; padding: 12px; margin-top: 10px; cursor: pointer; display: block; text-align: center; text-decoration: none; box-sizing: border-box; border-radius: 4px; }
        .btn-continue:hover { background-color: #f8f9fa; }

        .footer { background: #f1f1f1; text-align: center; padding: 20px; border-top: 1px solid #ddd; margin-top: auto; color: #666; font-size: 0.9em; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="index.php" style="font-size: 1.2em;">Home</a>
    <div class="search-container">
        <form action="catalog.php" method="GET" style="margin: 0; display: flex; justify-content: center;">
            <input type="text" name="query" placeholder="Search products...">
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
            <h3>Shopping Cart</h3>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $subtotal = $row['price'] * $row['quantity'];
                $total_price += $subtotal;
            ?>
            <div class="cart-item">
                <img src="images/<?php echo htmlspecialchars($row['image']); ?>" alt="Img" onerror="this.src='https://via.placeholder.com/150'">
                
                <div class="item-details">
                    <h4><?php echo htmlspecialchars($row['name']); ?></h4>
                    <p style="color: #28a745; font-weight: bold;">Tk<?php echo number_format($row['price'], 2); ?></p>
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

                <form action="cart_actions.php" method="POST" style="margin:0;">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="part_id" value="<?php echo $row['part_id']; ?>">
                    <button type="submit" class="btn-remove" title="Remove Item">&times;</button>
                </form>

            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <h3>Your cart is empty!</h3>
                <p>Looks like you haven't added anything yet.</p>
                <a href="catalog.php" style="color: #007bff; text-decoration: none; font-weight: bold;">Go to Catalog</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="price-summary">
        <h3>Price Details</h3>
        <div class="summary-row"><span>Subtotal</span><span>Tk<?php echo number_format($total_price, 2); ?></span></div>
        <div class="summary-row"><span>Discount</span><span>Tk0.00</span></div>
        <div class="total-row"><span>Total</span><span>Tk<?php echo number_format($total_price, 2); ?></span></div>

        <?php if ($total_price > 0): ?>
            <button class="btn-checkout" onclick="window.location.href='checkout.php'">Proceed to Checkout</button>
        <?php else: ?>
            <button class="btn-checkout" style="background-color: #ccc; cursor: not-allowed;" disabled>Proceed to Checkout</button>
        <?php endif; ?>
        
        <a href="index.php" class="btn-continue">Continue Shopping</a>
    </div>
</div>

<div class="footer">
    <p>Contact us: support@pcwebsite.com | Phone: +123 456 789</p>
    <p>&copy; 2025 PC Shop Project</p>
</div>

</body>
</html>