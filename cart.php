<?php
session_start();
require 'DBconnect.php';

if (!isset($_SESSION['users_id'])) { header("Location: signin.php"); exit(); }

$user_id = $_SESSION['users_id'];

// Fetch Cart Items
// We join PC_Part to get names and images
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
    <title>My Cart</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .navbar { background: #333; padding: 15px; color: white; display: flex; justify-content: space-between; align-items: center;}
        .navbar a { color: white; text-decoration: none; margin-left: 20px; }
        .container { max-width: 1200px; margin: 30px auto; display: flex; gap: 30px; padding: 0 20px; }
        
        /* Left: Cart List */
        .cart-list { flex: 2; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .cart-header { display: flex; justify-content: space-between; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        
        .cart-item { display: flex; align-items: center; border-bottom: 1px solid #eee; padding: 15px 0; }
        .cart-item img { width: 80px; height: 80px; object-fit: contain; margin: 0 20px; }
        .item-details { flex-grow: 1; }
        
        /* Buttons */
        .qty-box { display: flex; align-items: center; border: 1px solid #ddd; width: fit-content; }
        .qty-btn { background: #eee; border: none; padding: 5px 10px; cursor: pointer; font-size: 1.2em; }
        .qty-num { padding: 0 10px; font-weight: bold; }

        /* Right: Summary */
        .price-summary { flex: 1; background: white; padding: 20px; border-radius: 5px; height: fit-content; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .total-row { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 2px dashed #ddd; font-weight: bold; font-size: 1.2em; }
        
        .btn-checkout { width: 100%; background: #28a745; color: white; padding: 15px; border: none; font-size: 1.1em; cursor: pointer; margin-top: 20px; }
        .btn-continue { width: 100%; background: white; color: #333; border: 1px solid #333; padding: 10px; margin-top: 10px; cursor: pointer; }
    </style>
</head>
<body>

<div class="navbar">
    <div style="font-weight:bold; font-size: 1.2em;">PC Shop</div>
    <div>
        <a href="index.php">Home</a>
        <a href="account.php">Account</a>
    </div>
</div>

<div class="container">
    
    <div class="cart-list">
        <div class="cart-header">
            <h3>Shopping Cart</h3>
            <span><?php echo $result->num_rows; ?> Items</span>
        </div>
        
        <label><input type="checkbox" checked disabled> Select All</label>
        <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">

        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $subtotal = $row['price'] * $row['quantity'];
                $total_price += $subtotal;
                $total_items += $row['quantity'];
            ?>
            <div class="cart-item">
                <input type="checkbox" checked disabled> <img src="images/<?php echo htmlspecialchars($row['image']); ?>" 
                     onerror="this.onerror=null; this.src='https://via.placeholder.com/80';" alt="Img">
                
                <div class="item-details">
                    <h4><?php echo htmlspecialchars($row['name']); ?></h4>
                    <p style="color: #28a745; font-weight: bold;">$<?php echo number_format($row['price'], 2); ?></p>
                </div>

                <div class="qty-box">
                    <form action="cart_actions.php" method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="update_qty">
                        <input type="hidden" name="part_id" value="<?php echo $row['part_id']; ?>">
                        <input type="hidden" name="direction" value="decrease">
                        <button type="submit" class="qty-btn">-</button>
                    </form>

                    <span class="qty-num"><?php echo $row['quantity']; ?></span>

                    <form action="cart_actions.php" method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="update_qty">
                        <input type="hidden" name="part_id" value="<?php echo $row['part_id']; ?>">
                        <input type="hidden" name="direction" value="increase">
                        <button type="submit" class="qty-btn">+</button>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; padding: 40px;">Your cart is empty.</p>
        <?php endif; ?>
    </div>

    <div class="price-summary">
        <h3>Price Details</h3>
        <div class="summary-row">
            <span>Price (<?php echo $total_items; ?> items)</span>
            <span>$<?php echo number_format($total_price, 2); ?></span>
        </div>
        <div class="summary-row">
            <span>Discount</span>
            <span style="color: green;">-$0.00</span>
        </div>
        <div class="summary-row">
            <span>Delivery Charges</span>
            <span style="color: green;">Free</span>
        </div>
        
        <div class="total-row">
            <span>Total Amount</span>
            <span>$<?php echo number_format($total_price, 2); ?></span>
        </div>

        <button class="btn-checkout" onclick="alert('Proceeding to payment methods...');">Checkout</button>
        <button class="btn-continue" onclick="window.location.href='index.php'">Continue Shopping</button>
    </div>

</div>

</body>
</html>