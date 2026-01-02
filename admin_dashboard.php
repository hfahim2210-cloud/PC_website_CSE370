<?php
session_start();
require 'DBconnect.php';

// --- 1. SECURITY CHECK ---
if (!isset($_SESSION['users_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: signin.php");
    exit();
}

$msg = "";

// --- 2. HANDLE ACTIONS (DELETE USER) ---
if (isset($_GET['delete_user'])) {
    $delete_id = intval($_GET['delete_user']);
    
    // Prevent Admin from deleting themselves
    if ($delete_id == $_SESSION['users_id']) {
        $msg = "Error: You cannot delete your own account while logged in.";
    } else {
        // STEP 1: Delete related Orders first
        // This removes the link to the Cart, fixing your Foreign Key Error.
        $conn->query("DELETE FROM Orders WHERE users_id = $delete_id");

        // STEP 2: Delete related Listings (Posts)
        // This prevents errors if the user has items for sale.
        $conn->query("DELETE FROM Listing WHERE users_id = $delete_id");

        // STEP 3: Now it is safe to delete the User
        // (The Cart will automatically be deleted by the database)
        $del_sql = "DELETE FROM Users WHERE users_id = $delete_id";
        
        if ($conn->query($del_sql)) {
            $msg = "User and all related data deleted successfully.";
        } else {
            $msg = "Error deleting user: " . $conn->error;
        }
    }
}
// --- 3. FETCH DATA ---

// A. Users (Section 1)
$user_sql = "SELECT users_id, name, email, role, address, manager_id FROM Users";
$user_result = $conn->query($user_sql);

// B. Orders (Section 2 - Renumbered)
// 1. Get Total Order Count
$count_sql = "SELECT COUNT(*) as total FROM Orders";
$count_result = $conn->query($count_sql);
$total_orders = $count_result->fetch_assoc()['total'];

// 2. Get Active Orders
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
        
        /* Alerts */
        .alert { padding: 10px; background-color: #d1ecf1; color: #0c5460; border-radius: 5px; margin-bottom: 20px; border: 1px solid #bee5eb; }

        /* Table Styles */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.95em; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #343a40; color: white; }
        tr:hover { background-color: #f8f9fa; }
        
        /* Status Badges */
        .status-pending { background-color: #ffc107; color: #000; padding: 3px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold; }
        .status-paid { background-color: #28a745; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold; }
        
        /* Action Bar (Top) */
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }

        /* Buttons */
        .btn { padding: 10px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; }
        .btn-logout { background-color: #dc3545; color: white; }
        .btn-logout:hover { background-color: #c82333; }
        
        .btn-add { background-color: #28a745; color: white; } /* Green for Add Item */
        .btn-add:hover { background-color: #218838; }

        .btn-delete { 
            background-color: #dc3545; color: white; padding: 5px 10px; 
            text-decoration: none; border-radius: 3px; font-size: 0.8em; 
        }
        .btn-delete:hover { background-color: #bd2130; }

        /* Order History Box */
        .history-container {
            display: flex; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;
        }
        .history-box {
            background-color: #6c757d; color: white; padding: 15px 25px; text-decoration: none;
            border-radius: 5px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: background 0.3s;
        }
        .history-box:hover { background-color: #5a6268; }
        .stat-box { font-size: 1.1em; font-weight: bold; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="action-bar">
        <a href="add_item.php" class="btn btn-add">+ Add Item to Inventory</a>
        <a href="logout.php" class="btn btn-logout">Logout</a>
    </div>

    <h1>Admin Dashboard</h1>

    <?php if ($msg): ?>
        <div class="alert"><?php echo $msg; ?></div>
    <?php endif; ?>

    <h3>User Management</h3>
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
                    <th>Action</th> </tr>
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
                    <td>
                        <?php if($row['users_id'] != $_SESSION['users_id']): ?>
                            <a href="?delete_user=<?php echo $row['users_id']; ?>" 
                               class="btn-delete"
                               onclick="return confirm('Are you sure you want to permanently delete this user?');">
                                X
                            </a>
                        <?php else: ?>
                            <span style="color:#ccc; font-size:0.8em;">(You)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No users found.</p>
    <?php endif; ?>

    <h3>Order Management</h3>
    
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
                    <td>Tk<?php echo number_format($row['total_amount'], 2); ?></td>
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
        <a href="order_history.php" class="history-box">
            View Order History ➜
            <br>
            <span style="font-size: 0.8em; font-weight: normal;">(Shipped & Canceled Orders)</span>
        </a>
    </div>

</div>

</body>
</html>