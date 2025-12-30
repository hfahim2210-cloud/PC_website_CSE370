<?php
session_start();
// Database Connection
$conn = new mysqli('localhost', 'root', '', 'pc_website');
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// --- 1. HANDLE FILTERS & SORTING (PHP) ---

// Base Query
$sql = "SELECT l.*, u.name as seller_name FROM Listing l JOIN Users u ON l.users_id = u.users_id WHERE l.status = 'Active'";

// A. Filter by Type
if (isset($_GET['part_type']) && !empty($_GET['part_type'])) {
    $type = $conn->real_escape_string($_GET['part_type']);
    $sql .= " AND l.type = '$type'";
}

// B. Filter by Price (Max Price)
$max_price = 200000; // Default max
if (isset($_GET['max_price'])) {
    $max_price = (int)$_GET['max_price'];
    $sql .= " AND l.price <= $max_price";
}

// C. Sort By
$sort_order = "ORDER BY l.listing_id DESC"; // Default: Newest first
if (isset($_GET['sort'])) {
    if ($_GET['sort'] == 'price_high') {
        $sort_order = "ORDER BY l.price DESC";
    } elseif ($_GET['sort'] == 'price_low') {
        $sort_order = "ORDER BY l.price ASC";
    } elseif ($_GET['sort'] == 'date') {
        $sort_order = "ORDER BY l.listing_id DESC";
    }
}
$sql .= " " . $sort_order;

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Listings - PC Shop</title>
    <style>
        /* --- GLOBAL STYLES --- */
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; color: #333; }
        
        /* Navbar */
        .navbar { 
            background-color: #333; 
            color: white; 
            padding: 15px 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .navbar a { color: white; text-decoration: none; font-weight: bold; font-size: 1.1em; }
        
        /* Search Bar Styles */
        .search-container { flex-grow: 1; margin: 0 40px; text-align: center; }
        .search-container form { display: flex; justify-content: center; }
        .search-container input { 
            width: 60%; 
            padding: 8px; 
            border: none; 
            border-radius: 4px 0 0 4px; 
            outline: none;
        }
        .search-container button { 
            padding: 8px 15px; 
            border: none; 
            background-color: #555; 
            color: white; 
            border-radius: 0 4px 4px 0; 
            cursor: pointer; 
        }
        .search-container button:hover { background-color: #777; }

        /* Nav Links Group */
        .nav-links { display: flex; align-items: center; }
        .nav-links a { margin-left: 20px; }

        /* Main Layout Grid */
        .main-container {
            display: flex;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
            gap: 30px;
        }

        /* --- SIDEBAR (FILTERS) --- */
        .sidebar {
            width: 250px;
            background: white;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            height: fit-content;
        }
        .sidebar h3 { margin-top: 0; border-bottom: 2px solid #333; padding-bottom: 5px; }
        
        /* Price Slider */
        .slider-container { margin-bottom: 25px; }
        .slider { width: 100%; cursor: pointer; }
        .price-label { font-weight: bold; color: #28a745; display: block; margin-top: 5px; }

        /* Circular Checkboxes */
        .radio-group label {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
        }
        .radio-group input[type="radio"] {
            margin-right: 10px;
            transform: scale(1.2);
            cursor: pointer;
        }

        /* --- MAIN CONTENT --- */
        .content-area { flex-grow: 1; }

        /* Header & Controls */
        .listing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .create-btn {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .create-btn:hover { background-color: #555; }

        .controls-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: #fff;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .view-btns button {
            background: none;
            border: 1px solid #ccc;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 16px;
        }
        .view-btns button.active { background: #333; color: white; }

        /* --- LISTING CARDS (GRID VIEW DEFAULT) --- */
        .listings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); 
            gap: 20px;
        }

        .card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
        }
        .card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        
        .card-img {
            width: 100%;
            height: 180px;
            object-fit: contain;
            background: #f9f9f9;
            border-bottom: 1px solid #eee;
        }
        
        .card-body { padding: 15px; flex-grow: 1; }
        .card-title { font-size: 1.1em; margin: 0 0 10px 0; color: #333; }
        .card-price { color: #28a745; font-weight: bold; font-size: 1.2em; margin-bottom: 10px; }
        .card-seller { font-size: 0.9em; color: #666; margin-bottom: 5px; }
        .card-status { 
            background: #007bff; color: white; 
            padding: 5px 10px; 
            font-size: 0.85em; 
            border-radius: 3px; 
            display: inline-block;
            margin-top: 10px;
        }

        /* --- LIST VIEW STYLES --- */
        .listings-grid.list-view {
            display: flex;
            flex-direction: column;
        }
        .listings-grid.list-view .card {
            flex-direction: row;
            height: 120px;
            align-items: center;
        }
        .listings-grid.list-view .card-img {
            width: 120px;
            height: 100%;
            border-bottom: none;
            border-right: 1px solid #eee;
        }
        .listings-grid.list-view .card-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 0 20px;
        }
        .listings-grid.list-view .card-info { text-align: left; }
        .listings-grid.list-view .card-actions { text-align: right; }

        /* New CSS for the button group */
        .header-buttons {
            display: flex;
            gap: 15px; /* Space between the two buttons */
        }

        .my-listings-btn {
            background-color: #17a2b8; /* Blue color */
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .my-listings-btn:hover { background-color: #138496; }

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

<div class="main-container">

    <div class="sidebar">
        <form id="filterForm" method="GET" action="listing.php">
            
            <h3>Filter by Price</h3>
            <div class="slider-container">
                <input type="range" min="0" max="200000" step="1000" value="<?php echo $max_price; ?>" class="slider" name="max_price" oninput="document.getElementById('priceDisp').innerText = this.value">
                <span class="price-label">Max: $<span id="priceDisp"><?php echo $max_price; ?></span></span>
            </div>

            <h3>Parts</h3>
            <div class="radio-group">
                <label><input type="radio" name="part_type" value="" <?php if(!isset($_GET['part_type']) || $_GET['part_type']=='') echo 'checked'; ?> onclick="this.form.submit()"> All Categories</label>
                <label><input type="radio" name="part_type" value="CPU" <?php if(isset($_GET['part_type']) && $_GET['part_type']=='CPU') echo 'checked'; ?> onclick="this.form.submit()"> CPU</label>
                <label><input type="radio" name="part_type" value="GPU" <?php if(isset($_GET['part_type']) && $_GET['part_type']=='GPU') echo 'checked'; ?> onclick="this.form.submit()"> GPU</label>
                <label><input type="radio" name="part_type" value="RAM" <?php if(isset($_GET['part_type']) && $_GET['part_type']=='RAM') echo 'checked'; ?> onclick="this.form.submit()"> RAM</label>
                <label><input type="radio" name="part_type" value="Motherboard" <?php if(isset($_GET['part_type']) && $_GET['part_type']=='Motherboard') echo 'checked'; ?> onclick="this.form.submit()"> Motherboard</label>
                <label><input type="radio" name="part_type" value="Storage" <?php if(isset($_GET['part_type']) && $_GET['part_type']=='Storage') echo 'checked'; ?> onclick="this.form.submit()"> Storage</label>
                <label><input type="radio" name="part_type" value="PSU" <?php if(isset($_GET['part_type']) && $_GET['part_type']=='PSU') echo 'checked'; ?> onclick="this.form.submit()"> PSU</label>
                <label><input type="radio" name="part_type" value="Casing" <?php if(isset($_GET['part_type']) && $_GET['part_type']=='Casing') echo 'checked'; ?> onclick="this.form.submit()"> Casing</label>
                <label><input type="radio" name="part_type" value="Cooler" <?php if(isset($_GET['part_type']) && $_GET['part_type']=='Cooler') echo 'checked'; ?> onclick="this.form.submit()"> Cooler</label>
            </div>

            <button type="submit" style="width:100%; padding:10px; background:#28a745; color:white; border:none; cursor:pointer; margin-top:10px;">Apply Filters</button>
        </form>
    </div>

    <div class="content-area">
        
        <div class="listing-header">
    <h2 style="margin:0;">Listings</h2>
    <div class="header-buttons">
        <a href="my_lists.php" class="my-listings-btn">My Listings</a>
        <a href="create_listing.php" class="create-btn">+ Create a Listing</a>
    </div>
</div>

        <div class="controls-bar">
            <div class="sort-box">
                <label>Sort by: </label>
                <select name="sort" form="filterForm" onchange="this.form.submit()" style="padding: 5px;">
                    <option value="date" <?php if(isset($_GET['sort']) && $_GET['sort']=='date') echo 'selected'; ?>>Date Added</option>
                    <option value="price_low" <?php if(isset($_GET['sort']) && $_GET['sort']=='price_low') echo 'selected'; ?>>Price: Low to High</option>
                    <option value="price_high" <?php if(isset($_GET['sort']) && $_GET['sort']=='price_high') echo 'selected'; ?>>Price: High to Low</option>
                </select>
            </div>

            <div class="view-btns">
                <button onclick="setView('grid')" id="btnGrid" class="active">Grid</button>
                <button onclick="setView('list')" id="btnList">List</button>
            </div>
        </div>

        <div id="listingsContainer" class="listings-grid">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $status_text = ($row['type'] == 'Sell' || $row['type'] == 'Sale') ? "Sold" : "Exchanged";
                    $display_status = "Yet to be " . $status_text;

                    echo '
                    <div class="card">
                        <img src="images/'.htmlspecialchars($row['image']).'" class="card-img" alt="Item Image" onerror="this.src=\'https://via.placeholder.com/200?text=No+Image\'">
                        
                        <div class="card-body">
                            <div class="card-info">
                                <h3 class="card-title">'.htmlspecialchars($row['title']).'</h3>
                                <div class="card-seller">Seller: '.htmlspecialchars($row['seller_name']).'</div>
                                <div class="card-status">'.$display_status.'</div>
                            </div>
                            
                            <div class="card-actions">
                                <div class="card-price">$'.number_format($row['price']).'</div>
                                <a href="listing_details.php?id='.$row['listing_id'].'" style="color:#007bff; text-decoration:none; font-size:0.9em;">View Details &rarr;</a>
                            </div>
                        </div>
                    </div>
                    ';
                }
            } else {
                echo "<p>No listings found matching your criteria.</p>";
            }
            ?>
        </div>

    </div>
</div>

<script>
    function setView(view) {
        const container = document.getElementById('listingsContainer');
        const btnGrid = document.getElementById('btnGrid');
        const btnList = document.getElementById('btnList');

        if (view === 'list') {
            container.classList.add('list-view');
            btnList.classList.add('active');
            btnGrid.classList.remove('active');
        } else {
            container.classList.remove('list-view');
            btnGrid.classList.add('active');
            btnList.classList.remove('active');
        }
    }
</script>

</body>
</html>