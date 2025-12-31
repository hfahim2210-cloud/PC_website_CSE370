<?php
session_start();
require 'DBconnect.php';

// 1. CHECK LOGIN
if (!isset($_SESSION['users_id'])) {
    header("Location: signin.php"); 
    exit();
}

$user_id = $_SESSION['users_id'];

// 2. FETCH USER'S LISTINGS
$sql = "SELECT * FROM Listing WHERE users_id = '$user_id' ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Listings - PC Shop</title>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        
        /* Navbar Styles (Same as others) */
        .navbar { display: flex; align-items: center; justify-content: space-between; background-color: #333; color: white; padding: 15px 20px; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; font-weight: bold; }
        
        .container { max-width: 1000px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        h2 { border-bottom: 2px solid #333; padding-bottom: 10px; }
        
        /* Table Styles */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        
        .img-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
        
        .status-active { color: green; font-weight: bold; }
        .status-sold { color: red; font-weight: bold; }
        
        .action-btn { text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 0.9em; margin-right: 5px; }
        .edit-btn { background-color: #ffc107; color: #333; }
        .edit-btn:hover { background-color: #e0a800; }
        .view-btn { background-color: #17a2b8; color: white; }
        
        .create-btn { display: inline-block; background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; float: right; }
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
        <a href="create_listing.php" class="create-btn">+ Create New Listing</a>
        <h2>My Listings</h2>

        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="images/<?php echo htmlspecialchars($row['image']); ?>" class="img-thumb" onerror="this.src='https://via.placeholder.com/60'">
                            </td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td>Tk <?php echo number_format($row['price']); ?></td>
                            <td class="<?php echo ($row['status']=='Active')?'status-active':'status-sold'; ?>">
                                <?php echo $row['status']; ?>
                            </td>
                            <td><?php echo $row['category']; ?></td>
                            <td>
                                <a href="edit_listing.php?id=<?php echo $row['listing_id']; ?>" class="action-btn edit-btn">Edit</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You haven't posted any listings yet.</p>
        <?php endif; ?>
    </div>

</body>
</html>