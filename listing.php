<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'pc_website');
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// --- 1. INITIALIZE FILTER VARIABLES ---
$search    = isset($_GET['query']) ? $conn->real_escape_string($_GET['query']) : "";
$category  = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : "All";
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : 200000;
$sort      = isset($_GET['sort']) ? $_GET['sort'] : "date_desc";

// --- 2. BUILD SQL QUERY ---
// We Join Users table to get the Seller's Name
$sql = "SELECT l.*, u.name as seller_name 
        FROM Listing l 
        JOIN Users u ON l.users_id = u.users_id 
        WHERE l.status = 'Active'";

// A. Search Logic
if (!empty($search)) {
    $sql .= " AND (l.title LIKE '%$search%' OR l.description LIKE '%$search%')";
}

// B. Category Logic
if ($category != "All" && !empty($category)) {
    $sql .= " AND l.category = '$category'";
}

// C. Price Logic
$sql .= " AND l.price <= $max_price";

// D. Sorting Logic
switch ($sort) {
    case 'price_asc':  $sql .= " ORDER BY l.price ASC"; break;
    case 'price_desc': $sql .= " ORDER BY l.price DESC"; break;
    case 'date_asc':   $sql .= " ORDER BY l.created_at ASC"; break;
    default:           $sql .= " ORDER BY l.created_at DESC"; break; // Default: Newest
}

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
        .navbar { background-color: #333; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; font-weight: bold; font-size: 1.1em; }
        
        /* Search Bar */
        .search-container { flex-grow: 1; margin: 0 40px; text-align: center; }
        .search-container form { display: flex; justify-content: center; }
        .search-container input { width: 60%; padding: 8px; border: none; border-radius: 4px 0 0 4px; outline: none; }
        .search-container button { padding: 8px 15px; border: none; background-color: #555; color: white; border-radius: 0 4px 4px 0; cursor: pointer; }
        .search-container button:hover { background-color: #777; }

        .nav-links { display: flex; gap: 20px; }

        /* --- MAIN LAYOUT --- */
        .main-container {
            display: flex;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
            gap: 30px;
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 250px;
            background: white;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            height: fit-content;
        }
        .sidebar h3 { margin-top: 0; border-bottom: 2px solid #28a745; padding-bottom: 5px; }
        
        .slider-container { margin-bottom: 25px; }
        .slider { width: 100%; cursor: pointer; }
        .price-label { font-weight: bold; color: #28a745; display: block; margin-top: 5px; }

        .radio-group label { display: flex; align-items: center; margin-bottom: 8px; cursor: pointer; font-size: 0.95em; }
        .radio-group input[type="radio"] { margin-right: 10px; transform: scale(1.1); cursor: pointer; }
        
        .apply-btn { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 15px; }
        .apply-btn:hover { background: #218838; }

        /* --- CONTENT AREA --- */
        .content-area { flex-grow: 1; }

        /* Header Area */
        .listing-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px;
        }
        .header-buttons { display: flex; gap: 15px; }
        .create-btn { background-color: #333; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .my-listings-btn { background-color: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        
        /* Controls (Sort & Toggle) */
        .controls-bar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; background: #fff; padding: 10px;
            border: 1px solid #ddd; border-radius: 5px;
        }
        .sort-box select { padding: 6px; border-radius: 4px; border: 1px solid #ccc; }
        
        .view-btns button { background: none; border: 1px solid #ccc; padding: 6px 12px; cursor: pointer; border-radius: 4px; }
        .view-btns button.active { background: #333; color: white; border-color: #333; }

        /* --- GRID VIEW (Default) --- */
        .listings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }

        .card {
            background: white; border: 1px solid #ddd; border-radius: 5px;
            overflow: hidden; transition: transform 0.2s;
            display: flex; flex-direction: column;
        }
        .card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        
        .card-img { width: 100%; height: 180px; object-fit: contain; background: #f9f9f9; border-bottom: 1px solid #eee; }
        
        .card-body { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; }
        .card-title { font-size: 1.1em; margin: 0 0 5px 0; color: #333; }
        .card-seller { font-size: 0.85em; color: #666; margin-bottom: 10px; }
        
        .card-price { color: #28a745; font-weight: bold; font-size: 1.3em; margin-bottom: 10px; }
        
        /* Badges */
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; color: white; margin-bottom: 10px; width: fit-content; }
        .badge-sell { background-color: #007bff; }
        .badge-exchange { background-color: #fd7e14; }

        .view-details-link { color: #007bff; text-decoration: none; font-weight: bold; margin-top: auto; display: block; }
        .view-details-link:hover { text-decoration: underline; }

        /* --- LIST VIEW OVERRIDES --- */
        .listings-grid.list-view { display: flex; flex-direction: column; }
        .listings-grid.list-view .card { flex-direction: row; height: 140px; align-items: center; }
        .listings-grid.list-view .card-img { width: 140px; height: 100%; border-bottom: none; border-right: 1px solid #eee; }
        .listings-grid.list-view .card-body { flex-direction: row; justify-content: space-between; align-items: center; width: 100%; padding: 0 25px; }
        .listings-grid.list-view .card-info { text-align: left; }
        .listings-grid.list-view .card-actions { text-align: right; min-width: 100px; }
        .listings-grid.list-view .card-price { margin-bottom: 5px; font-size: 1.5em; }

        .no-results { text-align: center; margin-top: 50px; font-size: 1.2em; color: #777; width: 100%; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="index.php" class="logo">Home</a> 
    
    <div class="search-container">
        <form action="listing.php" method="GET">
            <input type="text" name="query" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search parts...">
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
            <input type="hidden" name="query" value="<?php echo htmlspecialchars($search); ?>">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">

            <h3>Filter by Price</h3>
            <div class="slider-container">
                <input type="range" min="0" max="200000" step="1000" value="<?php echo $max_price; ?>" class="slider" name="max_price" oninput="document.getElementById('priceDisp').innerText = this.value">
                <span class="price-label">Max: Tk<span id="priceDisp"><?php echo $max_price; ?></span></span>
            </div>

            <h3>Categories</h3>
            <div class="radio-group">
                <?php 
                $cats = ["All", "CPU", "GPU", "RAM", "Motherboard", "Storage", "PSU", "Casing", "Cooler", "Monitor", "Other"];
                foreach($cats as $c) {
                    $val = ($c == "All") ? "All" : $c;
                    $checked = ($category == $val) ? "checked" : "";
                    // Auto-submit form when clicked
                    echo "<label><input type='radio' name='category' value='$val' $checked onclick='this.form.submit()'> $c</label>";
                }
                ?>
            </div>

            <button type="submit" class="apply-btn">Apply Filters</button>
        </form>
    </div>

    <div class="content-area">
        
        <div class="listing-header">
            <h2 style="margin:0;">
                <?php echo ($search) ? "Results for \"$search\"" : "All Listings"; ?>
            </h2>
            <div class="header-buttons">
                <a href="my_lists.php" class="my-listings-btn">My Listings</a>
                <a href="create_listing.php" class="create-btn">+ Create a Listing</a>
            </div>
        </div>

        <div class="controls-bar">
            <div class="sort-box">
                <label>Sort by: </label>
                <select name="sort" form="filterForm" onchange="this.form.submit()">
                    <option value="date_desc" <?php if($sort=='date_desc') echo 'selected'; ?>>Newest First</option>
                    <option value="date_asc" <?php if($sort=='date_asc') echo 'selected'; ?>>Oldest First</option>
                    <option value="price_asc" <?php if($sort=='price_asc') echo 'selected'; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php if($sort=='price_desc') echo 'selected'; ?>>Price: High to Low</option>
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
                    // Logic for Badge Color
                    $badgeClass = ($row['type'] == 'Sell') ? 'badge-sell' : 'badge-exchange';
                    
                    echo '
                    <div class="card">
                        <img src="images/'.htmlspecialchars($row['image']).'" class="card-img" alt="Item Image" onerror="this.src=\'https://via.placeholder.com/200?text=No+Image\'">
                        
                        <div class="card-body">
                            <div class="card-info">
                                <h3 class="card-title">'.htmlspecialchars($row['title']).'</h3>
                                <div class="card-seller">Seller: '.htmlspecialchars($row['seller_name']).'</div>
                                <span class="badge '.$badgeClass.'">For '.$row['type'].'</span>
                            </div>
                            
                            <div class="card-actions">
                                <div class="card-price">Tk'.number_format($row['price']).'</div>
                                <a href="listing_details.php?id='.$row['listing_id'].'" class="view-details-link">View Details &rarr;</a>
                            </div>
                        </div>
                    </div>
                    ';
                }
            } else {
                echo '<p class="no-results">No listings found matching your criteria.</p>';
            }
            ?>
        </div>

    </div>
</div>

<script>
    // Simple JS to toggle CSS classes for Grid/List view
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