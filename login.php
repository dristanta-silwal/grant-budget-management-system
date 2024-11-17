<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit();
        } else {
            echo "<p style='color: red;'>Incorrect password.</p>";
        }
    } else {
        echo "<p style='color: red;'>No user found with that username.</p>";
    }
}
?>

<h1 style="text-align: center; font-family: Arial, sans-serif;">Login</h1>
<div style="max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);">
    <form action="login.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
        <label for="username" style="font-size: 16px; color: #333;">Username:</label>
        <input type="text" id="username" name="username" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">

        <label for="password" style="font-size: 16px; color: #333;">Password:</label>
        <input type="password" id="password" name="password" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">

        <input type="submit" value="Login" style="padding: 10px; background-color: #3498db; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
    </form>

    <div style="text-align: center; margin-top: 20px;">
        <a href="forget_password.php" style="display: block; font-size: 14px; color: #3498db; text-decoration: none; margin-bottom: 10px;">
            Forget Password?
        </a>
        
        <p style="color: #333; font-size: 14px;">Don't have an account?</p>
        <a href="register.php" style="display: inline-block; padding: 10px 20px; background-color: #2ecc71; color: white; border-radius: 5px; text-decoration: none; font-size: 16px;">Register</a>
    </div>
</div>