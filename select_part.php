<?php
session_start();
require 'DBconnect.php';

// 1. SECURITY & INPUT CHECKS
if (!isset($_SESSION['users_id'])) {
    header("Location: signin.php");
    exit();
}

if (!isset($_GET['category']) || !isset($_GET['build_id'])) {
    echo "Error: Missing category or build ID.";
    exit();
}

$category = $_GET['category']; // e.g., 'CPU', 'GPU'
$build_id = $_GET['build_id'];

// 2. HANDLE "ADD TO BUILD" LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['part_id'])) {
    $part_id = $_POST['part_id'];
    
    // A. "Swap" Logic: First, remove any existing part of this specific TYPE from the build.
    // This ensures you don't end up with 2 CPUs in one build.
    // We need to find if there is already a part with this type in the build.
    
    // First, find all items currently in this build
    $check_sql = "SELECT bi.build_item_id FROM Build_Items bi 
                  JOIN PC_Part p ON bi.part_id = p.part_id 
                  WHERE bi.build_id = ? AND p.type = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("is", $build_id, $category);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    // If a part of this type exists (e.g., an old CPU), delete it.
    if ($row = $result_check->fetch_assoc()) {
        $del_stmt = $conn->prepare("DELETE FROM Build_Items WHERE build_item_id = ?");
        $del_stmt->bind_param("i", $row['build_item_id']);
        $del_stmt->execute();
    }

    // B. Insert the NEW part
    $stmt_insert = $conn->prepare("INSERT INTO Build_Items (build_id, part_id, quantity) VALUES (?, ?, 1)");
    $stmt_insert->bind_param("ii", $build_id, $part_id);
    
    if ($stmt_insert->execute()) {
        // C. Redirect back to the Main Builder Page
        header("Location: pc_builder.php");
        exit();
    } else {
        echo "Error adding part: " . $conn->error;
    }
}

// 3. FETCH PARTS FOR DISPLAY (Filtered by Category)
// We only show parts that match the requested category (e.g. 'CPU')
$sql = "SELECT * FROM PC_Part WHERE type = ? AND stock > 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $category);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select <?php echo htmlspecialchars($category); ?></title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f4; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        .header-nav { margin-bottom: 20px; }
        .header-nav a { text-decoration: none; font-weight: bold; color: #333; }
        
        h2 { border-bottom: 2px solid #007bff; padding-bottom: 10px; display: inline-block; }

        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        
        .product-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; display: flex; flex-direction: column; justify-content: space-between; }
        .product-card img { max-width: 100%; height: 150px; object-fit: contain; margin-bottom: 10px; }
        
        .p-name { font-weight: bold; margin-bottom: 5px; min-height: 40px; }
        .p-price { color: #28a745; font-size: 1.2em; font-weight: bold; margin-bottom: 10px; }
        .p-specs { font-size: 0.85em; color: #666; margin-bottom: 15px; }

        .btn-add { background: #007bff; color: white; border: none; padding: 10px; width: 100%; cursor: pointer; border-radius: 5px; font-weight: bold; }
        .btn-add:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-nav">
        <a href="pc_builder.php">← Back to Builder</a>
    </div>

    <h2>Select <?php echo htmlspecialchars($category); ?></h2>

    <?php if ($result->num_rows == 0): ?>
        <p>No products found in this category.</p>
    <?php else: ?>
        <div class="product-grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="product-card">
                    
                    <img src="images/<?php echo $row['image'] ? $row['image'] : 'placeholder.jpg'; ?>" alt="Part Image">
                    
                    <div class="p-name"><?php echo htmlspecialchars($row['name']); ?></div>
                    
                    <div class="p-specs">
                        Brand: <?php echo $row['brand']; ?><br>
                        Power: <?php echo $row['watts']; ?>W
                    </div>
                    
                    <div class="p-price">$<?php echo number_format($row['price'], 2); ?></div>
                    
                    <form method="POST">
                        <input type="hidden" name="part_id" value="<?php echo $row['part_id']; ?>">
                        <button type="submit" class="btn-add">Add to Build</button>
                    </form>
                    
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>