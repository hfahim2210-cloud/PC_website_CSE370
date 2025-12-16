<?php
session_start(); // Start session to remember the user
require 'DBconnect.php'; // Include your database connection

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. Prepare the SQL statement (Prevents SQL Injection)
    $stmt = $conn->prepare("SELECT users_id, name, password, role FROM Users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // 2. Verify Password
        // Note: Ideally, use password_verify($password, $user['password'])
        // But for testing manual DB entries, we will check simple text first
        if ($password === $user['password'] || password_verify($password, $user['password'])) {
            
            // 3. Login Success! Store variables in Session
            $_SESSION['users_id'] = $user['users_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // 4. Redirect based on Role
            if ($user['role'] == 'Admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No account found with that email.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign In - PC Shop</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; padding-top: 50px; background-color: #f4f4f4; }
        .login-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #218838; }
        .error { color: red; font-size: 0.9em; text-align: center; }
    </style>
</head>
<body>

<div class="login-container">
    <h2 style="text-align: center;">Sign In</h2>
    
    <?php if($error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Email Address</label>
        <input type="email" name="email" required placeholder="user@example.com">

        <label>Password</label>
        <input type="password" name="password" required placeholder="Enter password">

        <button type="submit">Login</button>
    </form>
    
    <p style="text-align: center; font-size: 0.9em;">
        Don't have an account? <a href="signup.php">Sign Up</a>
    </p>
</div>

</body>
</html>