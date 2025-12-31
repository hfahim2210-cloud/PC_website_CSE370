<?php
session_start();
require 'DBconnect.php';

// 1. CHECK LOGIN
if (!isset($_SESSION['users_id'])) {
    header("Location: signin.php"); 
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$user_id = $_SESSION['users_id'];
$order_id = intval($_GET['id']);

// 2. FETCH ORDER INFO
$sql_order = "SELECT * FROM Orders WHERE order_id = '$order_id' AND users_id = '$user_id'";
$result_order = $conn->query($sql_order);

if ($result_order->num_rows == 0) {
    echo "Order not found.";
    exit();
}

$order = $result_order->fetch_assoc();

// 3. FETCH ORDER ITEMS (Linked to PC_Part)
// We use 'price_at_purchase' from the Order_Items table, not the current price in PC_Part
$sql_items = "SELECT oi.*, p.name AS part_name, p.image AS part_image 
              FROM Order_Items oi 
              JOIN PC_Part p ON oi.part_id = p.part_id 
              WHERE oi.order_id = '$order_id'";

$result_items = $conn->query($sql_items);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Details</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; color: #333; }
        .navbar { display: flex; align-items: center; background-color: #333; padding: 10px 20px; }
        .navbar a { color: white; text-decoration: none; margin: 0 10px; font-weight: bold; }
        
        .container { max-width: 900px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .order-header { border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .img-thumb { width: 60px; height: 60px; object-fit: contain; border: 1px solid #ddd; border-radius: 4px; background: #fff; }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="index.php">Home</a>
        <div style="flex-grow: 1; text-align: center;">
             </div>
        <div>
            <a href="pc_builder.php">PC Builder</a>
            <a href="account.php" style="color: #007bff;">Account</a>
            <a href="logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="order-header">
            <div>
                <h2>Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></h2>
                <span>Placed on <?php echo date("M d, Y", strtotime($order['created_at'])); ?></span>
            </div>
            <a href="orders.php" style="text-decoration:none; color:#007bff;">&larr; Back to Orders</a>
        </div>

        <p><strong>Status:</strong> <?php echo $order['status']; ?></p>
        <p><strong>Payment:</strong> <?php echo $order['payment_method']; ?></p>

        <h3>Items Purchased</h3>
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Part Name</th>
                    <th>Price Paid</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_items && $result_items->num_rows > 0): ?>
                    <?php while($item = $result_items->fetch_assoc()): ?>
                        <?php $subtotal = $item['price_at_purchase'] * $item['quantity']; ?>
                        <tr>
                            <td>
                                <img src="images/<?php echo htmlspecialchars($item['part_image']); ?>" class="img-thumb" alt="Part Image">
                            </td>
                            <td><?php echo htmlspecialchars($item['part_name']); ?></td>
                            <td>Tk <?php echo number_format($item['price_at_purchase'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>Tk <?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">No items found in this order record.</td></tr>
                <?php endif; ?>
                
                <tr>
                    <td colspan="4" style="text-align: right; font-weight: bold;">Grand Total:</td>
                    <td style="font-weight: bold;">Tk <?php echo number_format($order['total_amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

</body>
</html>