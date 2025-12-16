<?php
session_start();
require 'DBconnect.php'; // Using your specific filename

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $address = $_POST['address'];

    // 1. Basic Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // 2. Check if email already exists
        $check = $conn->prepare("SELECT users_id FROM Users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "This email is already registered.";
        } else {
            // 3. Insert New User
            // Note: For now we store plain text passwords to match your testing.
            // In production, change $password to password_hash($password, PASSWORD_DEFAULT)
            $stmt = $conn->prepare("INSERT INTO Users (name, email, password, role, address) VALUES (?, ?, ?, 'Customer', ?)");
            $stmt->bind_param("ssss", $name, $email, $password, $address);

            if ($stmt->execute()) {
                $success = "Account created successfully! <a href='signin.php'>Login here</a>";
            } else {
                $error = "Error: " . $conn->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up - PC Shop</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; padding-top: 50px; background-color: #f4f4f4; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; }
        input, textarea { width: 100%; padding: 10px; margin: 5px 0 15px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .error { color: red; font-size: 0.9em; text-align: center; }
        .success { color: green; font-size: 0.9em; text-align: center; }
    </style>
</head>
<body>

<div class="container">
    <h2 style="text-align: center;">Create Account</h2>
    
    <?php if($error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if($success): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Full Name</label>
        <input type="text" name="name" required placeholder="John Doe">

        <label>Email Address</label>
        <input type="email" name="email" required placeholder="user@example.com">

        <label>Address</label>
        <textarea name="address" rows="2" placeholder="123 Street, City"></textarea>

        <label>Password</label>
        <input type="password" name="password" required placeholder="Create a password">

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required placeholder="Confirm password">

        <button type="submit">Sign Up</button>
    </form>
    
    <p style="text-align: center; font-size: 0.9em;">
        Already have an account? <a href="signin.php">Sign In</a>
    </p>
</div>

</body>
</html>