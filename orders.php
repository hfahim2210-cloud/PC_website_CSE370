<?php
session_start();
require 'DBconnect.php';

// 1. CHECK LOGIN
if (!isset($_SESSION['users_id'])) {
    header("Location: signin.php"); 
    exit();
}

$user_id = $_SESSION['users_id'];

// 2. FETCH ORDERS
// Updated column names based on your screenshot:
// - 'created_at' instead of 'order_date'
// - added 'payment_method' since you have it available
$sql = "SELECT order_id, created_at, total_amount, status, payment_method 
        FROM Orders 
        WHERE users_id = '$user_id' 
        ORDER BY created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders - PC Shop</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; color: #333; }
        
        /* Navbar */
        .navbar { background-color: #333; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; font-weight: bold; }

        /* Container */
        .container { max-width: 1000px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { border-bottom: 2px solid #333; padding-bottom: 10px; margin-top: 0; }

        /* Order Table */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; color: #555; }
        
        /* Status Badges */
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.85em; font-weight: bold; }
        .status-Pending { background-color: #fff3cd; color: #856404; }
        .status-Processing { background-color: #cce5ff; color: #004085; }
        .status-Shipped { background-color: #d1ecf1; color: #0c5460; }
        .status-Delivered { background-color: #d4edda; color: #155724; }
        .status-Cancelled { background-color: #f8d7da; color: #721c24; }

        /* View Button */
        .view-btn { text-decoration: none; background-color: #333; color: white; padding: 8px 15px; border-radius: 4px; font-size: 0.9em; transition: 0.2s; }
        .view-btn:hover { background-color: #555; }
        
        .empty-state { text-align: center; padding: 40px; color: #777; }
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
    <div class="container">
        <h2>My Orders</h2>

        <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date Ordered</th>
                        <th>Payment</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo str_pad($row['order_id'], 6, '0', STR_PAD_LEFT); ?></td>
                            
                            <td><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
                            
                            <td><?php echo htmlspecialchars($row['payment_method']); ?></td>

                            <td><strong>Tk <?php echo number_format($row['total_amount'], 2); ?></strong></td>
                            
                            <td>
                                <span class="badge status-<?php echo $row['status']; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            
                            <td>
                                <a href="order_details.php?id=<?php echo $row['order_id']; ?>" class="view-btn">View Details</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <h3>No orders found</h3>
                <p>Looks like you haven't bought anything yet.</p>
                <a href="catalog.php" class="view-btn" style="background:#28a745;">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>