<?php
session_start();
require 'DBconnect.php';

// --- 1. SECURITY CHECK ---
// If the user is NOT logged in OR is NOT an Admin, redirect them to Sign In
if (!isset($_SESSION['users_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: signin.php");
    exit();
}

// --- 2. FETCH ALL USERS ---
// We select everything EXCEPT the password (security best practice)
$sql = "SELECT users_id, name, email, role, address, manager_id FROM Users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }
        
        /* Table Styles */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #007bff; color: white; }
        tr:hover { background-color: #f1f1f1; }
        
        .btn-logout { display: inline-block; padding: 10px 20px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 5px; float: right; }
        .btn-logout:hover { background-color: #c82333; }
    </style>
</head>
<body>

<div class="container">
    <a href="logout.php" class="btn-logout">Logout</a>
    <h1>Admin Dashboard</h1>
    <h3>User List</h3>

    <?php if ($result->num_rows > 0): ?>
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
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['users_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <span style="color: <?php echo ($row['role'] == 'Admin') ? 'red' : 'green'; ?>; font-weight: bold;">
                            <?php echo $row['role']; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    <td><?php echo $row['manager_id'] ? $row['manager_id'] : 'None'; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center;">No users found in the database.</p>
    <?php endif; ?>

</div>

</body>
</html>