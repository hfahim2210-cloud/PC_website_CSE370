<?php
session_start();
require 'DBconnect.php';

// --- 1. HANDLE FILTERS & SORTING ---

// Initialize base query components
$where_clauses = [];
$params = [];
$types = ""; // For bind_param

// A. Filter by Type (from URL or Sidebar)
if (isset($_GET['type']) && !empty($_GET['type'])) {
    // If multiple types selected (sidebar), or single (navbar)
    $selected_types = is_array($_GET['type']) ? $_GET['type'] : [$_GET['type']];
    
    // Create placeholders like (?, ?)
    $placeholders = implode(',', array_fill(0, count($selected_types), '?'));
    $where_clauses[] = "type IN ($placeholders)";
    
    foreach ($selected_types as $t) {
        $params[] = $t;
        $types .= "s";
    }
}

// B. Filter by Brand
if (isset($_GET['brand']) && !empty($_GET['brand'])) {
    $selected_brands = $_GET['brand']; // Array from checkboxes
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
        
        /* Navbar (Same as Index) */
        .navbar { display: flex; justify-content: space-between; background-color: #333; color: white; padding: 15px 20px; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; font-weight: bold; }
        
        /* Layout Grid */
        .main-container { display: flex; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 10px; }
        
        /* Left Sidebar */
        .sidebar { flex: 1; min-width: 200px; background: white; padding: 20px; border-radius: 5px; height: fit-content; }
        .filter-group { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .filter-group h4 { margin-top: 0; margin-bottom: 10px; }
        .filter-group label { display: block; margin-bottom: 5px; cursor: pointer; }
        .apply-btn { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; }
        
        /* Right Content */
        .content { flex: 3; }
        
        /* Toolbar (Sort + View) */
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 10px 15px; border-radius: 5px; }
        
        /* Grid View (Default) */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        
        /* List View (Toggled via JS) */
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
            <a href="cart.php">Cart</a>
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
                    // Loop through GET params to keep types/brands active when sorting
                    foreach ($_GET as $key => $val) {
                        if ($key == 'sort') continue; // Don't duplicate sort
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

    <script>
        function setView(view) {
            const container = document.getElementById('productContainer');
            if (view === 'list') {
                container.className = 'products-list';
            } else {
                container.className = 'products-grid';
            }
        }
    </script>

</body>
</html>