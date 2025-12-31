<?php
session_start();

// 1. CHECK LOGIN
if (!isset($_SESSION['users_id'])) {
    header("Location: signin.php"); 
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'pc_website');
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$message = "";
$error = "";
$user_id = $_SESSION['users_id'];

// 2. FETCH EXISTING DATA (If User Just Arrived via Link)
if (isset($_GET['id'])) {
    $listing_id = intval($_GET['id']);
    
    // Security: Only select if it belongs to the logged-in user!
    $sql = "SELECT * FROM Listing WHERE id = '$listing_id' AND users_id = '$user_id'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
    } else {
        // Listing doesn't exist or doesn't belong to this user
        header("Location: account.php"); 
        exit();
    }
} 
// 3. HANDLE UPDATES (If User Clicked "Update Listing")
else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $listing_id = intval($_POST['listing_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $category = $conn->real_escape_string($_POST['category']); 
    $price = (float)$_POST['price'];
    $type = $conn->real_escape_string($_POST['type']);
    $desc = $conn->real_escape_string($_POST['description']);
    
    // Get current image in case we don't upload a new one
    $old_image = $_POST['current_image'];
    $image_filename = $old_image;

    // --- NEW IMAGE UPLOAD LOGIC ---
    if (isset($_FILES['part_image']) && $_FILES['part_image']['error'] == 0) {
        $target_dir = "images/";
        $file_name = $user_id . "_" . time() . "_" . basename($_FILES["part_image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
            $error = "Sorry, only JPG, JPEG, & PNG files are allowed.";
        } else {
            if (move_uploaded_file($_FILES["part_image"]["tmp_name"], $target_file)) {
                $image_filename = $file_name; // Update variable to new name
            } else {
                $error = "Error uploading file.";
            }
        }
    }

    // --- UPDATE DATABASE ---
    if (empty($error)) {
        // Note: WE use WHERE users_id = $user_id again for extra safety
        $sql = "UPDATE Listing SET 
                title='$title', 
                category='$category',
                description='$desc', 
                price='$price', 
                type='$type', 
                image='$image_filename' 
                WHERE id='$listing_id' AND users_id='$user_id'";

        if ($conn->query($sql) === TRUE) {
            header("Location: account.php"); // Go back to account dashboard
            exit();
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
    
    // Keep data in $row so the form doesn't go blank on error
    $row = $_POST; 
    $row['id'] = $listing_id;
    $row['image'] = $old_image; // Keep old image for display
} else {
    // If accessed without ID or POST
    header("Location: account.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Listing - PC Shop</title>
    <style>
        /* Reusing exact styles from Create Listing for consistency */
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; color: #333; }
        .navbar { background-color: #333; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; font-weight: bold; font-size: 1.1em; }
        .container { max-width: 600px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input[type="text"], input[type="number"], textarea, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; box-sizing: border-box; }
        textarea { height: 120px; resize: vertical; }
        .radio-group { display: flex; gap: 20px; }
        .radio-group label { font-weight: normal; display: flex; align-items: center; cursor: pointer; }
        .submit-btn { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; font-size: 18px; cursor: pointer; }
        .submit-btn:hover { background-color: #0056b3; }
        .error-msg { color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }
        .current-img-preview { display: block; margin-top: 5px; max-height: 100px; border: 1px solid #ddd; padding: 3px; }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="index.php">Home</a>
        <a href="account.php">Back to My Account</a>
    </div>

    <div class="container">
        <h2>Edit Your Listing</h2>

        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_listing.php" enctype="multipart/form-data">
            
            <input type="hidden" name="listing_id" value="<?php echo $row['id']; ?>">
            <input type="hidden" name="current_image" value="<?php echo $row['image']; ?>">

            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($row['title']); ?>">
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="" disabled>Select Part Type</option>
                    <?php 
                    $cats = ["CPU", "GPU", "RAM", "Motherboard", "Storage", "PSU", "Casing", "Cooler", "Monitor", "Other"];
                    foreach($cats as $c) {
                        $selected = ($row['category'] == $c) ? "selected" : "";
                        echo "<option value='$c' $selected>$c</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Type</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="type" value="Sell" <?php echo ($row['type'] == 'Sell') ? 'checked' : ''; ?>> Sell
                    </label>
                    <label>
                        <input type="radio" name="type" value="Exchange" <?php echo ($row['type'] == 'Exchange') ? 'checked' : ''; ?>> Exchange
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="price">Price (Tk)</label>
                <input type="number" id="price" name="price" step="0.01" required value="<?php echo htmlspecialchars($row['price']); ?>">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($row['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="part_image">Change Image (Optional)</label>
                <?php if(!empty($row['image'])): ?>
                    <img src="images/<?php echo $row['image']; ?>" class="current-img-preview" alt="Current Image">
                    <small style="color: #666;">Leave empty to keep current image</small>
                <?php endif; ?>
                <input type="file" id="part_image" name="part_image" accept="image/*" style="margin-top: 5px;">
            </div>

            <button type="submit" class="submit-btn">Update Listing</button>

        </form>
    </div>

</body>
</html>