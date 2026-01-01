<?php
session_start();
require 'DBconnect.php';

// 1. SECURITY: Ensure only Admin can access
if (!isset($_SESSION['users_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: signin.php");
    exit();
}

$msg = "";
$error = "";

// 2. HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // A. Collect Common PC_Part Data
    $name = $_POST['name'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $watts = intval($_POST['watts']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $type = $_POST['type'];

    // B. Handle Image Upload
    $image_filename = "default_part.jpg"; // Fallback
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "images/";
        // Create unique name to prevent overwriting
        $image_filename = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_filename;
        
        // Move file
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $error = "Failed to upload image.";
        }
    }

    if (!$error) {
        // C. Insert into Parent Table (PC_Part)
        $sql_parent = "INSERT INTO PC_Part (brand, name, model, watts, price, stock, image, type) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql_parent);
        $stmt->bind_param("sssidiss", $brand, $name, $model, $watts, $price, $stock, $image_filename, $type);
        
        if ($stmt->execute()) {
            $new_part_id = $conn->insert_id; // Get the ID generated for this new part
            
            // D. Insert into Child Table based on Type
            $child_sql = "";
            $child_stmt = null;

            switch ($type) {
                case 'CPU':
                    $socket = $_POST['cpu_socket'];
                    $cores = intval($_POST['cpu_cores']);
                    $child_sql = "INSERT INTO CPU (part_id, socket, cores) VALUES (?, ?, ?)";
                    $child_stmt = $conn->prepare($child_sql);
                    $child_stmt->bind_param("isi", $new_part_id, $socket, $cores);
                    break;

                case 'GPU':
                    $vram = $_POST['gpu_vram'];
                    $chipset = $_POST['gpu_chipset'];
                    $child_sql = "INSERT INTO GPU (part_id, vram, chipset) VALUES (?, ?, ?)";
                    $child_stmt = $conn->prepare($child_sql);
                    $child_stmt->bind_param("iss", $new_part_id, $vram, $chipset);
                    break;

                case 'RAM':
                    $capacity = $_POST['ram_capacity'];
                    $rtype = $_POST['ram_type'];
                    $child_sql = "INSERT INTO RAM (part_id, capacity, type) VALUES (?, ?, ?)";
                    $child_stmt = $conn->prepare($child_sql);
                    $child_stmt->bind_param("iss", $new_part_id, $capacity, $rtype);
                    break;

                case 'Motherboard':
                    $socket = $_POST['mobo_socket'];
                    $form = $_POST['mobo_form'];
                    $child_sql = "INSERT INTO Motherboard (part_id, socket, form_factor) VALUES (?, ?, ?)";
                    $child_stmt = $conn->prepare($child_sql);
                    $child_stmt->bind_param("iss", $new_part_id, $socket, $form);
                    break;

                case 'Storage':
                    $capacity = $_POST['storage_capacity'];
                    $stype = $_POST['storage_type'];
                    $child_sql = "INSERT INTO Storage (part_id, capacity, type) VALUES (?, ?, ?)";
                    $child_stmt = $conn->prepare($child_sql);
                    $child_stmt->bind_param("iss", $new_part_id, $capacity, $stype);
                    break;

                case 'PSU':
                    $wattage = intval($_POST['psu_wattage']);
                    $rating = $_POST['psu_rating'];
                    $child_sql = "INSERT INTO PSU (part_id, wattage, rating) VALUES (?, ?, ?)";
                    $child_stmt = $conn->prepare($child_sql);
                    $child_stmt->bind_param("iis", $new_part_id, $wattage, $rating);
                    break;

                case 'Casing':
                    $case_type = $_POST['case_type'];
                    $child_sql = "INSERT INTO Casing (part_id, case_type) VALUES (?, ?)";
                    $child_stmt = $conn->prepare($child_sql);
                    $child_stmt->bind_param("is", $new_part_id, $case_type);
                    break;

                case 'Cooler':
                    $ctype = $_POST['cooler_type'];
                    $supports = $_POST['cooler_socket'];
                    $child_sql = "INSERT INTO Cooler (part_id, cooler_type, supports_socket) VALUES (?, ?, ?)";
                    $child_stmt = $conn->prepare($child_sql);
                    $child_stmt->bind_param("iss", $new_part_id, $ctype, $supports);
                    break;
            }

            // Execute Child Insert
            if ($child_stmt && $child_stmt->execute()) {
                $msg = "Success! Item added to Inventory.";
            } else {
                $error = "Item created, but failed to add specific details: " . $conn->error;
            }

        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Inventory Item</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        h2 { text-align: center; color: #333; margin-bottom: 25px; border-bottom: 2px solid #28a745; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        
        .row { display: flex; gap: 20px; }
        .col { flex: 1; }

        /* Specific Sections hidden by default */
        .specific-section { display: none; background-color: #f9f9f9; padding: 15px; border: 1px solid #eee; border-radius: 5px; margin-top: 15px; }
        
        .btn-submit { width: 100%; padding: 12px; background-color: #28a745; color: white; border: none; font-size: 1.1em; font-weight: bold; cursor: pointer; border-radius: 4px; margin-top: 20px; }
        .btn-submit:hover { background-color: #218838; }

        .back-link { display: inline-block; margin-bottom: 20px; color: #333; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }

        .alert { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
    </style>
    <script>
        function showSpecificFields() {
            // Hide all specific sections first
            var sections = document.getElementsByClassName('specific-section');
            for(var i = 0; i < sections.length; i++) {
                sections[i].style.display = 'none';
            }

            // Get selected type
            var type = document.getElementById('partType').value;

            // Show relevant section
            if(type === 'CPU') document.getElementById('sec-cpu').style.display = 'block';
            if(type === 'GPU') document.getElementById('sec-gpu').style.display = 'block';
            if(type === 'RAM') document.getElementById('sec-ram').style.display = 'block';
            if(type === 'Motherboard') document.getElementById('sec-mobo').style.display = 'block';
            if(type === 'Storage') document.getElementById('sec-storage').style.display = 'block';
            if(type === 'PSU') document.getElementById('sec-psu').style.display = 'block';
            if(type === 'Casing') document.getElementById('sec-casing').style.display = 'block';
            if(type === 'Cooler') document.getElementById('sec-cooler').style.display = 'block';
        }
    </script>
</head>
<body>

<div class="container">
    <a href="admin_dashboard.php" class="back-link">&larr; Back to Dashboard</a>

    <?php if($msg) echo "<div class='alert alert-success'>$msg</div>"; ?>
    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <h2>Add New Item</h2>

    <form method="POST" enctype="multipart/form-data">
        
        <div class="row">
            <div class="col">
                <div class="form-group">
                    <label>Part Type</label>
                    <select name="type" id="partType" onchange="showSpecificFields()" required>
                        <option value="" disabled selected>Select Type...</option>
                        <option value="CPU">CPU</option>
                        <option value="GPU">GPU</option>
                        <option value="RAM">RAM</option>
                        <option value="Motherboard">Motherboard</option>
                        <option value="Storage">Storage</option>
                        <option value="PSU">Power Supply (PSU)</option>
                        <option value="Casing">Casing</option>
                        <option value="Cooler">Cooler</option>
                    </select>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label>Brand</label>
                    <input type="text" name="brand" placeholder="e.g. Intel, Corsair" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Product Name</label>
            <input type="text" name="name" placeholder="Full Product Title" required>
        </div>

        <div class="row">
            <div class="col">
                <div class="form-group">
                    <label>Model Number</label>
                    <input type="text" name="model" placeholder="e.g. i9-13900K">
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label>Watts (0 if N/A)</label>
                    <input type="number" name="watts" value="0">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="stock" value="1" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Product Image</label>
            <input type="file" name="image" required>
        </div>

        <div id="sec-cpu" class="specific-section">
            <h4>CPU Details</h4>
            <div class="row">
                <div class="col"><label>Socket</label><input type="text" name="cpu_socket" placeholder="e.g. LGA1700, AM5"></div>
                <div class="col"><label>Cores</label><input type="number" name="cpu_cores" placeholder="e.g. 16"></div>
            </div>
        </div>

        <div id="sec-gpu" class="specific-section">
            <h4>GPU Details</h4>
            <div class="row">
                <div class="col"><label>VRAM</label><input type="text" name="gpu_vram" placeholder="e.g. 12GB GDDR6"></div>
                <div class="col"><label>Chipset</label><input type="text" name="gpu_chipset" placeholder="e.g. NVIDIA, AMD"></div>
            </div>
        </div>

        <div id="sec-ram" class="specific-section">
            <h4>RAM Details</h4>
            <div class="row">
                <div class="col"><label>Capacity</label><input type="text" name="ram_capacity" placeholder="e.g. 16GB (2x8)"></div>
                <div class="col"><label>Type</label><input type="text" name="ram_type" placeholder="e.g. DDR4, DDR5"></div>
            </div>
        </div>

        <div id="sec-mobo" class="specific-section">
            <h4>Motherboard Details</h4>
            <div class="row">
                <div class="col"><label>Socket</label><input type="text" name="mobo_socket" placeholder="e.g. AM5"></div>
                <div class="col"><label>Form Factor</label><input type="text" name="mobo_form" placeholder="e.g. ATX, Micro-ATX"></div>
            </div>
        </div>

        <div id="sec-storage" class="specific-section">
            <h4>Storage Details</h4>
            <div class="row">
                <div class="col"><label>Capacity</label><input type="text" name="storage_capacity" placeholder="e.g. 1TB"></div>
                <div class="col"><label>Type</label><input type="text" name="storage_type" placeholder="e.g. NVMe SSD, HDD"></div>
            </div>
        </div>

        <div id="sec-psu" class="specific-section">
            <h4>PSU Details</h4>
            <div class="row">
                <div class="col"><label>Wattage (W)</label><input type="number" name="psu_wattage" placeholder="e.g. 750"></div>
                <div class="col"><label>Rating</label><input type="text" name="psu_rating" placeholder="e.g. 80+ Gold"></div>
            </div>
        </div>

        <div id="sec-casing" class="specific-section">
            <h4>Casing Details</h4>
            <label>Case Type</label>
            <input type="text" name="case_type" placeholder="e.g. Mid Tower, Full Tower">
        </div>

        <div id="sec-cooler" class="specific-section">
            <h4>Cooler Details</h4>
            <div class="row">
                <div class="col"><label>Cooler Type</label><input type="text" name="cooler_type" placeholder="e.g. Air, AIO Liquid"></div>
                <div class="col"><label>Supported Sockets</label><input type="text" name="cooler_socket" placeholder="e.g. LGA1700, AM4"></div>
            </div>
        </div>

        <button type="submit" class="btn-submit">Add Item to Inventory</button>
    </form>
</div>

</body>
</html>