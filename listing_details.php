<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'pc_website');
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// 1. Get the Listing ID from the URL
if (isset($_GET['id'])) {
    $listing_id = intval($_GET['id']);
    
    // 2. Fetch Listing + Seller Details
    $sql = "SELECT l.*, u.name AS seller_name, u.email AS seller_email 
            FROM Listing l 
            JOIN Users u ON l.users_id = u.users_id 
            WHERE l.listing_id = $listing_id";
            
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
    } else {
        echo "Listing not found.";
        exit();
    }
} else {
    echo "No listing ID provided.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($item['title']); ?> - Details</title>
    <style>
        /* --- GLOBAL STYLES --- */
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; color: #333; }
        
        /* Navbar */
        .navbar { background-color: #333; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; font-weight: bold; font-size: 1.1em; }
        
        /* Search Bar */
        .search-container { flex-grow: 1; margin: 0 40px; text-align: center; }
        .search-container form { display: flex; justify-content: center; }
        .search-container input { width: 60%; padding: 8px; border: none; border-radius: 4px 0 0 4px; outline: none; }
        .search-container button { padding: 8px 15px; border: none; background-color: #555; color: white; border-radius: 0 4px 4px 0; cursor: pointer; }
        
        .nav-links { display: flex; gap: 20px; }

        /* --- PRODUCT DETAILS CONTAINER --- */
        .details-container {
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 40px;
        }

        /* Left Side: Image */
        .image-section { flex: 1; text-align: center; }
        .image-section img {
            max-width: 100%;
            border-radius: 5px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* Right Side: Info */
        .info-section { flex: 1; display: flex; flex-direction: column; }
        
        .product-title { font-size: 2em; margin-top: 0; margin-bottom: 10px; color: #333; }
        .product-price { font-size: 1.8em; color: #28a745; font-weight: bold; margin-bottom: 20px; }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
            color: white;
            margin-bottom: 20px;
            width: fit-content;
        }
        .badge-sell { background-color: #007bff; }
        .badge-exchange { background-color: #fd7e14; }

        .seller-info {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #333;
            margin-bottom: 20px;
        }
        
        .description { line-height: 1.6; color: #555; margin-bottom: 30px; white-space: pre-wrap; }

        .action-buttons { margin-top: auto; display: flex; gap: 15px; }
        
        .btn { padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; text-align: center; flex: 1; cursor: pointer; border: none; font-size: 1em; }
        .btn-primary { background-color: #28a745; color: white; }
        .btn-primary:hover { background-color: #218838; }
        
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-secondary:hover { background-color: #5a6268; }

        .footer { border-top: 2px solid #333; margin-top: 50px; padding: 20px; text-align: center; background-color: #fff; color: #555; }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="index.php" class="logo">Home</a> 
        <div class="search-container">
            <form action="search_results.php" method="GET">
                <input type="text" name="query" placeholder="Search bar...">
                <button type="submit">🔍</button>
            </form>
        </div>
        <div class="nav-links">
            <a href="account.php">Account</a>
            <a href="logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="details-container">
        
        <div class="image-section">
            <img src="images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" onerror="this.src='https://via.placeholder.com/400?text=No+Image'">
        </div>

        <div class="info-section">
            <h1 class="product-title"><?php echo htmlspecialchars($item['title']); ?></h1>
            
            <div class="product-price">$<?php echo number_format($item['price']); ?></div>

            <div>
                <?php if($item['type'] == 'Sell'): ?>
                    <span class="badge badge-sell">For Sale</span>
                <?php else: ?>
                    <span class="badge badge-exchange">For Exchange</span>
                <?php endif; ?>
            </div>

            <div class="seller-info">
                <strong>Seller:</strong> <?php echo htmlspecialchars($item['seller_name']); ?><br>
                <strong>Email:</strong> <?php echo htmlspecialchars($item['seller_email']); ?><br>
                <strong>Posted:</strong> <?php echo date("F j, Y", strtotime($item['created_at'])); ?>
            </div>

            <h3>Description</h3>
            <div class="description">
                <?php echo nl2br(htmlspecialchars($item['description'])); ?>
            </div>

            <div class="action-buttons">
                <a href="checkout.php?listing_id=<?php echo $item['listing_id']; ?>" class="btn btn-primary">Buy Now</a>
                <a href="listing.php" class="btn btn-secondary">Back to Listings</a>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 PC Shop Project</p>
    </div>

</body>
</html>