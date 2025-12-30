<?php
session_start();

// 1. CHECK LOGIN (User must be logged in to post)
if (!isset($_SESSION['users_id'])) {
    header("Location: signin.php"); 
    exit();
}

// 2. DATABASE CONNECTION
$conn = new mysqli('localhost', 'root', '', 'pc_website');
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$message = "";
$error = "";

// 3. HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $user_id = $_SESSION['users_id'];
    $title = $conn->real_escape_string($_POST['title']);
    
    // --- FIX STARTS HERE ---
    // This line was MISSING. We must "catch" the category from the form.
    $category = $conn->real_escape_string($_POST['category']); 
    // --- FIX ENDS HERE ---

    $price = (float)$_POST['price'];
    $type = $conn->real_escape_string($_POST['type']);
    $desc = $conn->real_escape_string($_POST['description']);
    
    // --- IMAGE UPLOAD LOGIC ---
    $image_filename = "default.jpg"; // Fallback if needed

    // Check if file was uploaded without errors
    if (isset($_FILES['part_image']) && $_FILES['part_image']['error'] == 0) {
        $target_dir = "images/";
        
        // Create unique filename: UserID_Timestamp_OriginalName (prevents overwriting)
        $file_name = $user_id . "_" . time() . "_" . basename($_FILES["part_image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate file type
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
            $error = "Sorry, only JPG, JPEG, & PNG files are allowed.";
        } else {
            // Move file to folder
            if (move_uploaded_file($_FILES["part_image"]["tmp_name"], $target_file)) {
                $image_filename = $file_name; // Save this name to DB
            } else {
                $error = "Sorry, there was an error uploading your file. Check folder permissions.";
            }
        }
    } else {
        $error = "Please upload an image for your listing.";
    }

    // --- INSERT INTO DATABASE ---
    if (empty($error)) {
        // We use NOW() for created_at so sorting works immediately
        // Note: $category is now defined, so this will work correctly
        $sql = "INSERT INTO Listing (users_id, title, category, description, price, type, status, image, created_at) 
        VALUES ('$user_id', '$title', '$category', '$desc', '$price', '$type', 'Active', '$image_filename', NOW())";

        if ($conn->query($sql) === TRUE) {
            header("Location: listing.php"); // Redirect to listings on success
            exit();
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
    <title>Create Listing - PC Shop</title>
    <style>
        /* --- GLOBAL STYLES (Matches listing.php) --- */
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
        
        /* Search Bar */
        .search-container { flex-grow: 1; margin: 0 40px; text-align: center; }
        .search-container form { display: flex; justify-content: center; }
        .search-container input { 
            width: 60%; padding: 8px; border: none; border-radius: 4px 0 0 4px; outline: none;
        }
        .search-container button { 
            padding: 8px 15px; border: none; background-color: #555; color: white; 
            border-radius: 0 4px 4px 0; cursor: pointer; 
        }
        .search-container button:hover { background-color: #777; }

        /* Nav Links */
        .nav-links { display: flex; align-items: center; gap: 20px; }

        /* --- FORM CONTAINER --- */
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h2 { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-top: 0; }

        .form-group { margin-bottom: 20px; }
        
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        
        input[type="text"], 
        input[type="number"], 
        textarea, 
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box; 
        }
        
        textarea { height: 120px; resize: vertical; }

        /* Radio Buttons */
        .radio-group { display: flex; gap: 20px; }
        .radio-group label { font-weight: normal; display: flex; align-items: center; cursor: pointer; }
        .radio-group input { margin-right: 8px; transform: scale(1.2); }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        .submit-btn:hover { background-color: #218838; }

        /* Error Message */
        .error-msg { 
            color: #721c24; background-color: #f8d7da; border-color: #f5c6cb;
            padding: 10px; border-radius: 4px; margin-bottom: 15px; 
            text-align: center;
        }

        /* Footer */
        .footer {
            border-top: 2px solid #333;
            margin-top: 50px;
            padding: 20px;
            text-align: center;
            background-color: #fff;
            color: #555;
        }
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

    <div class="container">
        <h2>Create a New Listing</h2>

        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="create_listing.php" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required placeholder="e.g. GTX 1080 Used">
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="" disabled selected>Select Part Type</option>
                    <option value="CPU">CPU</option>
                    <option value="GPU">GPU</option>
                    <option value="RAM">RAM</option>
                    <option value="Motherboard">Motherboard</option>
                    <option value="Storage">Storage (SSD/HDD)</option>
                    <option value="PSU">Power Supply</option>
                    <option value="Casing">Casing</option>
                    <option value="Cooler">Cooler</option>
                    <option value="Monitor">Monitor</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Type</label>
                <div class="radio-group">
                    <label><input type="radio" name="type" value="Sell" checked> Sell</label>
                    <label><input type="radio" name="type" value="Exchange"> Exchange</label>
                </div>
            </div>

            <div class="form-group">
                <label for="price">Price ($)</label>
                <input type="number" id="price" name="price" step="0.01" required placeholder="0.00">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required placeholder="Describe condition, usage, specs..."></textarea>
            </div>

            <div class="form-group">
                <label for="part_image">Upload Image</label>
                <input type="file" id="part_image" name="part_image" accept="image/*" required style="border: none; padding-left: 0;">
            </div>

            <button type="submit" class="submit-btn">Post Listing</button>

        </form>
    </div>

    <div class="footer">
        <p>Contact us: support@pcwebsite.com | Phone: +123 456 789</p>
        <p>&copy; 2025 PC Shop Project</p>
    </div>

</body>
</html>