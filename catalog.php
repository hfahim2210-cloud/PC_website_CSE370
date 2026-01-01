<?php
session_start();
require 'DBconnect.php';

// --- 1. NEW FILTER LOGIC (Matches Listings Page) ---

// Initialize variables
// [FIX] Capture the search query
$search_query = isset($_GET['query']) ? trim($_GET['query']) : ""; 

$type_filter = isset($_GET['type']) ? $_GET['type'] : "All";
$max_price   = isset($_GET['max_price']) ? intval($_GET['max_price']) : 200000; // Default max 200k
$sort_option = isset($_GET['sort']) ? $_GET['sort'] : 'price_asc';

// Start Building Query
$sql = "SELECT * FROM PC_Part WHERE 1=1";

// [FIX] Apply Search Filter if query exists
if (!empty($search_query)) {
    $safe_search = $conn->real_escape_string($search_query);
    // Search in Name, Brand, or Model
    $sql .= " AND (name LIKE '%$safe_search%' OR brand LIKE '%$safe_search%' OR model LIKE '%$safe_search%')";
}

// A. Filter by Type (Single Selection)
if ($type_filter != "All" && !empty($type_filter)) {
    $safe_type = $conn->real_escape_string($type_filter);
    $sql .= " AND type = '$safe_type'";
}

// B. Filter by Price
$sql .= " AND price <= $max_price";

// C. Sorting
switch ($sort_option) {
    case 'price_desc': $sql_order = " ORDER BY price DESC"; break;
    case 'brand_asc':  $sql_order = " ORDER BY brand ASC"; break;
    default:           $sql_order = " ORDER BY price ASC"; break;
}
$sql .= $sql_order;

// Execute Query
$result = $conn->query($sql);

// Define Types for Sidebar (Radio Buttons)
$all_types = ['CPU', 'GPU', 'RAM', 'Motherboard', 'Storage', 'PSU', 'Casing', 'Cooler'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Catalog - PC Shop</title>
    <style>
        /* --- YOUR ORIGINAL STYLES --- */
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        
        .navbar { display: flex; justify-content: space-between; background-color: #333; color: white; padding: 15px 20px; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; font-weight: bold; }
        
        .main-container { display: flex; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 10px; }
        
        .sidebar { flex: 1; min-width: 200px; background: white; padding: 20px; border-radius: 5px; height: fit-content; }
        .filter-group { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .filter-group h4 { margin-top: 0; margin-bottom: 10px; }
        
        /* Updated Sidebar Styles for Radio & Slider */
        .filter-group label { display: flex; align-items: center; margin-bottom: 8px; cursor: pointer; }
        .filter-group input[type="radio"] { margin-right: 10px; }
        input[type=range] { width: 100%; cursor: pointer; }
        .price-label { color: #28a745; font-weight: bold; display: block; margin-top: 5px; }

        .apply-btn { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; }
        
        .content { flex: 3; }
        
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 10px 15px; border-radius: 5px; }
        
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        
        .products-list { display: flex; flex-direction: column; gap: 15px; }
        .products-list .card { display: flex; align-items: center; text-align: left; padding: 15px; }
        .products-list .card img { width: 120px; height: 100px; margin-right: 20px; }
        .products-list .card-info { flex-grow: 1; }
        
        .card { background: white; padding: 15px; border: 1px solid #ddd; border-radius: 5px; text-align: center; transition: box-shadow 0.2s; }
        .card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .card img { width: 100%; height: 150px; object-fit: contain; margin-bottom: 10px; }
        .price { color: #28a745; font-weight: bold; font-size: 1.1em; }
        .add-btn { margin-top: 10px; padding: 8px 15px; background: #28a745; color: white; border: none; cursor: pointer; width: 100%; }

        .footer { background: #eee; text-align: center; padding: 20px; margin-top: 40px; border-top: 2px solid #333; }

        .cart-sidebar {
            height: 100%; width: 350px; position: fixed; z-index: 9999;
            top: 0; right: -350px; background-color: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.2); transition: 0.3s;
            display: flex; flex-direction: column;
        }
        .sidebar-header { padding: 20px; background: #333; color: white; display: flex; justify-content: space-between; align-items: center; }
        .sidebar-content { flex-grow: 1; padding: 20px; overflow-y: auto; }
        .sidebar-footer { padding: 20px; border-top: 1px solid #ddd; background: #f9f9f9; }
        .mini-item { display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; font-size: 14px; }
        .view-cart-btn { width: 100%; background: #333; color: white; padding: 12px; text-align: center; text-decoration: none; display: block; margin-top: 10px; font-weight: bold; }
        .close-btn { cursor: pointer; font-size: 24px; }
        /* Delete Button Style */
        .delete-btn {
            background-color: #ff4d4d;
            color: white;
            border: none;
            border-radius: 4px;
            width: 24px;
            height: 24px;
            cursor: pointer;
            margin-left: 10px;
            font-size: 16px;
            line-height: 24px; /* Centers the X vertically */
            text-align: center;
            padding: 0;
            font-weight: bold;
            transition: background 0.2s;
        }
        .delete-btn:hover {
            background-color: #cc0000;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="index.php">Home</a>
        
        <div style="flex-grow: 1; text-align: center;">
            <form action="catalog.php" method="GET" style="display: inline-block; width: 50%;">
                <div style="display: flex;">
                    <input type="text" name="query" placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 100%; padding: 5px;">
                    <button type="submit" style="padding: 5px; cursor: pointer;">🔍</button>
                </div>
            </form>
        </div>

        <div>
            <a href="pc_builder.php">PC Builder</a>
            <a href="javascript:void(0)" onclick="toggleCart()">Cart</a>
            <a href="account.php">Account</a>
            <a href="logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="main-container">
        
        <form class="sidebar" action="catalog.php" method="GET">
            <input type="hidden" name="query" value="<?php echo htmlspecialchars($search_query); ?>">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_option); ?>">

            <h3>Filters</h3>
            
            <div class="filter-group">
                <h4>Price Limit</h4>
                <input type="range" name="max_price" min="0" max="200000" step="1000" 
                       value="<?php echo $max_price; ?>" 
                       oninput="document.getElementById('priceDisp').innerText = this.value">
                <span class="price-label">Max: Tk<span id="priceDisp"><?php echo $max_price; ?></span></span>
            </div>

            <div class="filter-group">
                <h4>Part Type</h4>
                <label>
                    <input type="radio" name="type" value="All" 
                    <?php echo ($type_filter == 'All') ? 'checked' : ''; ?> 
                    onclick="this.form.submit()"> All Categories
                </label>
                
                <?php foreach ($all_types as $t): ?>
                    <label>
                        <input type="radio" name="type" value="<?php echo $t; ?>" 
                        <?php echo ($type_filter == $t) ? 'checked' : ''; ?> 
                        onclick="this.form.submit()"> 
                        <?php echo $t; ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="apply-btn">Apply Filters</button>
        </form>

        <div class="content">
            
            <div class="toolbar">
                <form id="sortForm" action="catalog.php" method="GET" style="margin:0;">
                    <input type="hidden" name="query" value="<?php echo htmlspecialchars($search_query); ?>">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_filter); ?>">
                    <input type="hidden" name="max_price" value="<?php echo $max_price; ?>">

                    <label>Sort by: </label>
                    <select name="sort" onchange="this.form.submit()">
                        <option value="price_asc" <?php if($sort_option=='price_asc') echo 'selected'; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php if($sort_option=='price_desc') echo 'selected'; ?>>Price: High to Low</option>
                        <option value="brand_asc" <?php if($sort_option=='brand_asc') echo 'selected'; ?>>Brand: A-Z</option>
                    </select>
                </form>

                <div>
                    <button onclick="setView('grid')">Grid</button>
                    <button onclick="setView('list')">List</button>
                </div>
            </div>

            <div id="productContainer" class="products-grid">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="card">
                            <img src="images/<?php echo htmlspecialchars($row['image']); ?>" 
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/150';" alt="Part">
                            
                            <div class="card-info">
                                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                <p style="color: #666;"><?php echo htmlspecialchars($row['brand']); ?> | <?php echo $row['type']; ?></p>
                                <p class="price">Tk<?php echo number_format($row['price'], 2); ?></p>
                                
                                <form action="cart_actions.php" method="POST">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="part_id" value="<?php echo $row['part_id']; ?>">
                                    <button type="submit" class="add-btn">Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No products found matching your search.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <div class="footer">
        <p>Contact us: support@pcwebsite.com</p>
    </div>

    <div id="mySidebar" class="cart-sidebar">
    <div class="sidebar-header">
        <span>Your Cart</span>
        <span class="close-btn" onclick="toggleCart()">×</span>
    </div>

    <div class="sidebar-content">
            <?php
            if (isset($_SESSION['users_id'])) {
                $sb_uid = $_SESSION['users_id'];
                
                $sb_sql = "SELECT p.part_id, p.name, p.price, ci.quantity 
                        FROM Cart_Item ci 
                        JOIN Cart c ON ci.cart_id = c.cart_id 
                        JOIN PC_Part p ON ci.part_id = p.part_id 
                        WHERE c.users_id = '$sb_uid'";
                $sb_res = $conn->query($sb_sql);
                
                $sb_total = 0;

                if ($sb_res && $sb_res->num_rows > 0) {
                    while($item = $sb_res->fetch_assoc()) {
                        $line_total = $item['price'] * $item['quantity'];
                        $sb_total += $line_total;
                        
                        echo "<div class='mini-item'>";
                        echo "<div><strong>{$item['name']}</strong><br>Qty: {$item['quantity']}</div>";
                        
                        echo "<div style='display:flex; align-items:center;'>";
                        echo "<div>Tk " . number_format($line_total, 2) . "</div>";
                        
                        echo "<form action='cart_actions.php' method='POST' style='margin:0;'>";
                        echo "<input type='hidden' name='action' value='remove'>";
                        echo "<input type='hidden' name='part_id' value='{$item['part_id']}'>";
                        echo "<button type='submit' class='delete-btn' title='Remove Item'>&times;</button>";
                        echo "</form>";
                        
                        echo "</div>"; 
                        echo "</div>"; 
                    }
                } else {
                    echo "<p style='text-align:center;'>Cart is empty.</p>";
                }
            } else {
                echo "<p>Please login to view cart.</p>";
            }
            ?>
        </div>

    <div class="sidebar-footer">
        <div style="display:flex; justify-content:space-between; font-weight:bold; margin-bottom:10px;">
            <span>Subtotal:</span>
            <span>Tk <?php echo isset($sb_total) ? number_format($sb_total, 2) : '0.00'; ?></span>
        </div>
        <a href="cart.php" class="view-cart-btn">VIEW CART</a>
    </div>
</div>

    <script>
        function setView(view) {
            const container = document.getElementById('productContainer');
            if (view === 'list') {
                container.className = 'products-list';
            } else {
                container.className = 'products-grid';
            }
        }

        function toggleCart() {
            var sb = document.getElementById("mySidebar");
            if (sb.style.right === "0px") {
                sb.style.right = "-350px";
            } else {
                sb.style.right = "0px";
            }
        }
    </script>

</body>
</html>