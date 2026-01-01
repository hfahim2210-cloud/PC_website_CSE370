<?php
session_start();
require 'DBconnect.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['users_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: signin.php");
    exit();
}

$msg = "";

// 2. HANDLE STATUS UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];
    
    $update_sql = "UPDATE Orders SET status = ? WHERE order_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        $msg = "Order #$order_id updated to $new_status.";
    } else {
        $msg = "Error updating order.";
    }
}

// 3. FETCH ORDERS (With Filtering)
$filter = isset($_GET['status']) ? $_GET['status'] : 'All';

$sql = "SELECT o.order_id, u.name, u.email, o.total_amount, o.status, o.created_at 
        FROM Orders o 
        JOIN Users u ON o.users_id = u.users_id";

if ($filter != 'All') {
    $sql .= " WHERE o.status = '$filter'";
}

$sql .= " ORDER BY o.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; padding: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        /* Header & Nav */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 15px; }
        h2 { margin: 0; color: #2c3e50; }
        .back-btn { text-decoration: none; color: #555; font-weight: bold; }
        .back-btn:hover { color: #000; }

        /* Filter Form */
        .filter-box { margin-bottom: 20px; background: #f9f9f9; padding: 15px; border-radius: 5px; border: 1px solid #ddd; display: flex; align-items: center; gap: 10px; }
        select { padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
        .btn-filter { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #343a40; color: white; }
        tr:hover { background-color: #f8f9fa; }

        /* Status Badges */
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.85em; font-weight: bold; color: white; }
        .bg-pending { background-color: #ffc107; color: black; }
        .bg-paid { background-color: #28a745; }
        .bg-shipped { background-color: #17a2b8; }
        .bg-canceled { background-color: #6c757d; }

        /* Update Form in Table */
        .action-form { display: flex; gap: 5px; }
        .btn-update { background-color: #333; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 0.9em; }
        .btn-update:hover { background-color: #555; }

        .alert { padding: 10px; background-color: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header">
        <h2>Full Order History</h2>
        <a href="admin_dashboard.php" class="back-btn">&larr; Back to Dashboard</a>
    </div>

    <?php if ($msg): ?>
        <div class="alert"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="filter-box">
        <form method="GET">
            <label><strong>Filter by Status:</strong></label>
            <select name="status">
                <option value="All" <?php if($filter == 'All') echo 'selected'; ?>>Show All</option>
                <option value="Pending" <?php if($filter == 'Pending') echo 'selected'; ?>>Pending</option>
                <option value="Paid" <?php if($filter == 'Paid') echo 'selected'; ?>>Paid</option>
                <option value="Shipped" <?php if($filter == 'Shipped') echo 'selected'; ?>>Shipped</option>
                <option value="Canceled" <?php if($filter == 'Canceled') echo 'selected'; ?>>Canceled</option>
            </select>
            <button type="submit" class="btn-filter">Filter</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date Placed</th>
                <th>Total</th>
                <th>Current Status</th>
                <th>Update Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    
                    <?php 
                        // Determine Badge Color
                        $badgeClass = 'bg-pending';
                        if($row['status'] == 'Paid') $badgeClass = 'bg-paid';
                        if($row['status'] == 'Shipped') $badgeClass = 'bg-shipped';
                        if($row['status'] == 'Canceled') $badgeClass = 'bg-canceled';
                    ?>

                    <tr>
                        <td><strong>#<?php echo $row['order_id']; ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($row['name']); ?><br>
                            <small style="color:#666;"><?php echo htmlspecialchars($row['email']); ?></small>
                        </td>
                        <td><?php echo date("M j, Y, g:i a", strtotime($row['created_at'])); ?></td>
                        <td><strong>Tk<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                        
                        <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $row['status']; ?></span></td>
                        
                        <td>
                            <form method="POST" class="action-form">
                                <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
                                <select name="new_status" style="padding: 4px; font-size: 0.9em;">
                                    <option value="Pending" <?php if($row['status']=='Pending') echo 'selected'; ?>>Pending</option>
                                    <option value="Paid" <?php if($row['status']=='Paid') echo 'selected'; ?>>Paid</option>
                                    <option value="Shipped" <?php if($row['status']=='Shipped') echo 'selected'; ?>>Shipped</option>
                                    <option value="Canceled" <?php if($row['status']=='Canceled') echo 'selected'; ?>>Canceled</option>
                                </select>
                                <button type="submit" class="btn-update">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding: 20px;">No orders found with this status.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>