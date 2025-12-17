<?php
session_start();
require 'DBconnect.php';

// --- 1. SECURITY CHECK ---
if (!isset($_SESSION['users_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: signin.php");
    exit();
}

// --- 2. FETCH DATA ---

// A. Users (Section 1)
$user_sql = "SELECT users_id, name, email, role, address, manager_id FROM Users";
$user_result = $conn->query($user_sql);

// B. Suppliers (Section 2)
$supplier_sql = "SELECT * FROM Supplier";
$supplier_result = $conn->query($supplier_sql);

// C. Orders (Section 3)
// 1. Get Total Order Count (All time)
$count_sql = "SELECT COUNT(*) as total FROM Orders";
$count_result = $conn->query($count_sql);
$total_orders = $count_result->fetch_assoc()['total'];

// 2. Get Active Orders (Pending or Paid) - Joined with Users to show who bought it
// We assume 'Shipped' is history, so 'Pending' and 'Paid' are active.
$order_sql = "SELECT o.order_id, u.name as user_name, o.total_amount, o.status, o.created_at 
              FROM Orders o 
              JOIN Users u ON o.users_id = u.users_id 
              WHERE o.status IN ('Pending', 'Paid') 
              ORDER BY o.created_at DESC";
$order_result = $conn->query($order_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background-color: #f4f4f4; color: #333; }
        .container { max-width: 1100px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        
        h1 { text-align: center; margin-bottom: 30px; color: #2c3e50; }
        h3 { border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-top: 40px; color: #007bff; }
        
        /* Table Styles */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.95em; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #343a40; color: white; }
        tr:hover { background-color: #f8f9fa; }
        
        /* Status Badges */
        .status-pending { background-color: #ffc107; color: #000; padding: 3px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold; }
        .status-paid { background-color: #28a745; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold; }
        
        /* Buttons */
        .btn-logout { display: inline-block; padding: 8px 15px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 5px; float: right; font-weight: bold; }
        .btn-logout:hover { background-color: #c82333; }

        /* Order History Box (Bottom Right) */
        .history-container {
            display: flex;
            justify-content: flex-end; /* Pushes content to the right */
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .history-box {
            background-color: #6c757d;
            color: white;
            padding: 15px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: background 0.3s;
        }
        .history-box:hover { background-color: #5a6268; }
        .stat-box { font-size: 1.1em; font-weight: bold; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="container">
    <a href="logout.php" class="btn-logout">Logout</a>
    <h1>Admin Dashboard</h1>

    <h3>Section 1: User Management</h3>
    <?php if ($user_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Address</th>
                    <th>Manager ID</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $user_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['users_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <span style="color: <?php echo ($row['role'] == 'Admin') ? '#dc3545' : '#28a745'; ?>; font-weight: bold;">
                            <?php echo $row['role']; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    <td><?php echo $row['manager_id'] ? $row['manager_id'] : '-'; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No users found.</p>
    <?php endif; ?>


    <h3>Section 2: Suppliers List</h3>
    <?php if ($supplier_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Supplier Name</th>
                    <th>Contact Info</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $supplier_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No suppliers found in database.</p>
    <?php endif; ?>


    <h3>Section 3: Order Management</h3>
    
    <div class="stat-box">
        Total Orders Placed (All Time): <?php echo $total_orders; ?>
    </div>

    <h4>Active Orders (Pending & Paid)</h4>
    <?php if ($order_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Date Placed</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $order_result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['order_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                    <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                    <td>
                        <span class="<?php echo ($row['status'] == 'Pending') ? 'status-pending' : 'status-paid'; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                    <td><?php echo date("F j, Y, g:i a", strtotime($row['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No active orders at the moment.</p>
    <?php endif; ?>

    <div class="history-container">
        <a href="#" class="history-box" onclick="alert('This will link to order_history.php later!');">
            View Order History ➜
            <br>
            <span style="font-size: 0.8em; font-weight: normal;">(Shipped & Canceled Orders)</span>
        </a>
    </div>

</div>

</body>
</html>