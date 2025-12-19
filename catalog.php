<?php
session_start();
require 'DBconnect.php';

// --- 1. HANDLE FILTERS & SORTING ---

// Initialize base query components
$where_clauses = [];
$params = [];
$types = ""; 

// A. Filter by Type 
if (isset($_GET['type']) && !empty($_GET['type'])) {
    $selected_types = is_array($_GET['type']) ? $_GET['type'] : [$_GET['type']];
    $placeholders = implode(',', array_fill(0, count($selected_types), '?'));
    $where_clauses[] = "type IN ($placeholders)";
    foreach ($selected_types as $t) {
        $params[] = $t;
        $types .= "s";
    }
}

// B. Filter by Brand
if (isset($_GET['brand']) && !empty($_GET['brand'])) {
    $selected_brands = $_GET['brand']; 
    $placeholders = implode(',', array_fill(0, count($selected_brands), '?'));
    $where_clauses[] = "brand IN ($placeholders)";
    foreach ($selected_brands as $b) {
        $params[] = $b;
        $types .= "s";
    }
}

// Build the WHERE string
$sql_where = "";
if (count($where_clauses) > 0) {
    $sql_where = "WHERE " . implode(' AND ', $where_clauses);
}

// C. Sorting
$sort_option = isset($_GET['sort']) ? $_GET['sort'] : 'price_asc';
$sql_order = "ORDER BY price ASC"; // Default

if ($sort_option == 'price_desc') {
    $sql_order = "ORDER BY price DESC";
} elseif ($sort_option == 'brand_asc') {
    $sql_order = "ORDER BY brand ASC";
}

// --- 2. EXECUTE QUERY ---
$sql = "SELECT part_id, name, brand, price, image, type FROM PC_Part $sql_where $sql_order";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// --- 3. FETCH DATA FOR SIDEBAR (Distinct Brands/Types) ---
$brands_result = $conn->query("SELECT DISTINCT brand FROM PC_Part ORDER BY brand ASC");
$all_types = ['CPU', 'GPU', 'RAM', 'Motherboard', 'Storage', 'PSU', 'Casing', 'Cooler'];

// Helper to keep checkboxes checked
function isChecked($param, $value) {
    if (isset($_GET[$param])) {
        if (is_array($_GET[$param]) && in_array($value, $_GET[$param])) return 'checked';
        if (!is_array($_GET[$param]) && $_GET[$param] == $value) return 'checked';
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Catalog - PC Shop</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        
        /* Navbar */
        .navbar { display: flex; justify-content: space-between; background-color: #333; color: white; padding: 15px 20px; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; font-weight: bold; }
        
        /* Layout Grid */
        .main-container { display: flex; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 10px; }
        
        /* Left Sidebar (Filters) */
        .sidebar { flex: 1; min-width: 200px; background: white; padding: 20px; border-radius: 5px; height: fit-content; }
        .filter-group { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .filter-group h4 { margin-top: 0; margin-bottom: 10px; }
        .filter-group label { display: block; margin-bottom: 5px; cursor: pointer; }
        .apply-btn { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; }
        
        /* Right Content */
        .content { flex: 3; }
        
        /* Toolbar */
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 10px 15px; border-radius: 5px; }
        
        /* Grid View */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        
        /* List View */
        .products-list { display: flex; flex-direction: column; gap: 15px; }
        .products-list .card { display: flex; align-items: center; text-align: left; padding: 15px; }
        .products-list .card img { width: 120px; height: 100px; margin-right: 20px; }
        .products-list .card-info { flex-grow: 1; }
        
        /* Card Styles */
        .card { background: white; padding: 15px; border: 1px solid #ddd; border-radius: 5px; text-align: center; transition: box-shadow 0.2s; }
        .card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .card img { width: 100%; height: 150px; object-fit: contain; margin-bottom: 10px; }
        .price { color: #28a745; font-weight: bold; font-size: 1.1em; }
        .add-btn { margin-top: 10px; padding: 8px 15px; background: #28a745; color: white; border: none; cursor: pointer; width: 100%; }

        /* Footer */
        .footer { background: #eee; text-align: center; padding: 20px; margin-top: 40px; border-top: 2px solid #333; }

        /* --- SIDEBAR CART CSS --- */
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
        </div>
    </div>

    <div class="main-container">
        
        <form class="sidebar" action="catalog.php" method="GET">
            <h3>Filters</h3>
            
            <div class="filter-group">
                <h4>Part Type</h4>
                <?php foreach ($all_types as $type): ?>
                    <label>
                        <input type="checkbox" name="type[]" value="<?php echo $type; ?>" 
                        <?php echo isChecked('type', $type); ?>> 
                        <?php echo $type; ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="filter-group">
                <h4>Brand</h4>
                <?php while($b = $brands_result->fetch_assoc()): ?>
                    <label>
                        <input type="checkbox" name="brand[]" value="<?php echo $b['brand']; ?>"
                        <?php echo isChecked('brand', $b['brand']); ?>> 
                        <?php echo $b['brand']; ?>
                    </label>
                <?php endwhile; ?>
            </div>

            <input type="hidden" name="sort" value="<?php echo $sort_option; ?>">

            <button type="submit" class="apply-btn">Apply Filters</button>
        </form>


        <div class="content">
            
            <div class="toolbar">
                <form id="sortForm" action="catalog.php" method="GET" style="margin:0;">
                    <?php 
                    foreach ($_GET as $key => $val) {
                        if ($key == 'sort') continue;
                        if (is_array($val)) {
                            foreach ($val as $v) echo "<input type='hidden' name='{$key}[]' value='$v'>";
                        } else {
                            echo "<input type='hidden' name='$key' value='$val'>";
                        }
                    }
                    ?>
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
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="card">
                            <img src="images/<?php echo htmlspecialchars($row['image']); ?>" 
                                 onerror="this.src='https://via.placeholder.com/150';" alt="Part">
                            
                            <div class="card-info">
                                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                <p style="color: #666;"><?php echo htmlspecialchars($row['brand']); ?> | <?php echo $row['type']; ?></p>
                                <p class="price">$<?php echo number_format($row['price'], 2); ?></p>
                                
                                <form action="cart_actions.php" method="POST">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="part_id" value="<?php echo $row['part_id']; ?>">
                                    <button type="submit" class="add-btn">Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No products found matching your criteria.</p>
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
            // MINI CART PHP LOGIC
            if (isset($_SESSION['users_id'])) {
                $sb_uid = $_SESSION['users_id'];
                $sb_sql = "SELECT p.name, p.price, ci.quantity 
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
                        echo "<div>$" . number_format($line_total, 2) . "</div>";
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
                <span>$<?php echo isset($sb_total) ? number_format($sb_total, 2) : '0.00'; ?></span>
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