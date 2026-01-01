<?php
session_start();
// Database Connection
$conn = new mysqli('localhost', 'root', '', 'pc_website');
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// 1. CHECK LOGIN
if (!isset($_SESSION['users_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['users_id'];
$msg = "";

// 2. HANDLE STATUS CHANGE (Active <-> Sold)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_id'])) {
    $listing_id = intval($_POST['toggle_id']);
    $current_status = $_POST['current_status'];
    
    // Switch status
    $new_status = ($current_status == 'Active') ? 'Sold' : 'Active';
    
    // Update DB (Check user_id to ensure they own the item!)
    $update_sql = "UPDATE Listing SET status = '$new_status' WHERE listing_id = $listing_id AND users_id = $user_id";
    
    if ($conn->query($update_sql)) {
        $msg = "Item marked as " . $new_status;
    } else {
        $msg = "Error updating status.";
    }
}

// 3. FETCH USER'S LISTINGS
$sql = "SELECT * FROM Listing WHERE users_id = $user_id ORDER BY listing_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Listings</title>
    <style>
        /* Reusing Global Styles */
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; color: #333; }
        
        /* Navbar */
        .navbar { background-color: #333; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; font-weight: bold; font-size: 1.1em; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .search-container { flex-grow: 1; margin: 0 40px; text-align: center; }
        .search-container form { display: flex; justify-content: center; }
        .search-container input { width: 60%; padding: 8px; border: none; border-radius: 4px 0 0 4px; outline: none; }
        .search-container button { padding: 8px 15px; border: none; background-color: #555; color: white; border-radius: 0 4px 4px 0; cursor: pointer; }

        /* Container */
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        h2 { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }

        /* Grid */
        .listings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 20px;
        }

        /* Card Styles */
        .card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: 0.3s;
        }
        
        /* Style for Sold items */
        .card.sold-item {
            opacity: 0.7;
            background-color: #e9ecef;
            border: 1px solid #ccc;
        }
        
        .card-img {
            width: 100%;
            height: 180px;
            object-fit: contain;
            background: #f9f9f9;
            border-bottom: 1px solid #eee;
        }
        
        .card-body { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; }
        .card-title { font-size: 1.1em; margin: 0 0 10px 0; }
        .card-price { color: #28a745; font-weight: bold; font-size: 1.2em; margin-bottom: 10px; }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            color: white;
            margin-bottom: 15px;
            width: fit-content;
        }
        .status-active { background-color: #28a745; }
        .status-sold { background-color: #6c757d; }

        /* Action Buttons */
        .card-actions { margin-top: auto; border-top: 1px solid #eee; padding-top: 15px; }
        
        .btn-toggle {
            width: 100%;
            padding: 10px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            border-radius: 4px;
            color: white;
            box-sizing: border-box; /* Ensures padding doesn't break width */
        }
        .btn-mark-sold { background-color: #dc3545; } /* Red */
        .btn-mark-sold:hover { background-color: #c82333; }
        
        .btn-mark-active { background-color: #007bff; } /* Blue */
        .btn-mark-active:hover { background-color: #0069d9; }

        /* --- ADDED: Edit Button Style --- */
        .btn-edit {
            background-color: #ffc107; /* Amber/Yellow */
            color: #333;
            text-decoration: none;
            display: block; /* Important for <a> tag to fill width */
            text-align: center;
            margin-bottom: 10px; /* Space between Edit and Toggle */
        }
        .btn-edit:hover { background-color: #e0a800; }

        .back-btn { display: inline-block; margin-bottom: 20px; color: #333; text-decoration: none; }
        .back-btn:hover { text-decoration: underline; }

        .alert { background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="index.php" class="logo">Home</a> 
    <div class="search-container">
        <form action="search_results.php" method="GET">
            <input type="text" name="query" placeholder="Search...">
            <button type="submit">🔍</button>
        </form>
    </div>
    <div class="nav-links">
        <a href="account.php">Account</a>
        <a href="logout.php" style="color: #ff6b6b;">Logout</a>
    </div>
</div>

<div class="container">
    <a href="listing.php" class="back-btn">&larr; Back to All Listings</a>
    
    <h2>My Listings Management</h2>

    <?php if($msg): ?>
        <div class="alert"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="listings-grid">
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $is_sold = ($row['status'] == 'Sold');
                $card_class = $is_sold ? "card sold-item" : "card";
                $status_label = $is_sold ? "Sold" : "Active";
                $status_class = $is_sold ? "status-sold" : "status-active";
                
                echo '
                <div class="'.$card_class.'">
                    <img src="images/'.htmlspecialchars($row['image']).'" class="card-img" alt="Item">
                    
                    <div class="card-body">
                        <h3 class="card-title">'.htmlspecialchars($row['title']).'</h3>
                        <div class="card-price">Tk'.number_format($row['price']).'</div>
                        
                        <div class="status-badge '.$status_class.'">'.$status_label.'</div>
                        
                        <div class="card-actions">
                            <a href="edit_listing.php?id='.$row['listing_id'].'" class="btn-toggle btn-edit">Edit Details</a>

                            <form method="POST">
                                <input type="hidden" name="toggle_id" value="'.$row['listing_id'].'">
                                <input type="hidden" name="current_status" value="'.$row['status'].'">
                                ';
                                
                                if(!$is_sold) {
                                    echo '<button type="submit" class="btn-toggle btn-mark-sold">Mark as Sold</button>';
                                } else {
                                    echo '<button type="submit" class="btn-toggle btn-mark-active">Re-activate Listing</button>';
                                }
                                
                echo '
                            </form>
                        </div>
                    </div>
                </div>
                ';
            }
        } else {
            echo "<p>You haven't posted any listings yet.</p>";
        }
        ?>
    </div>
</div>

</body>
</html>