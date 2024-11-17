<?php
include 'db.php';
session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify'])) {
        $first_name = $_POST['first_name'];
        $email = $_POST['email'];

        $stmt = $conn->prepare("SELECT id FROM users WHERE first_name = ? AND email = ?");
        $stmt->bind_param('ss', $first_name, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['reset_user_id'] = $user['id'];
            $message = "User verified. You can now enter a new password.";
        } else {
            $message = "No user found with that first name and email.";
        }
        $stmt->close();
    }
    elseif (isset($_POST['reset_password']) && isset($_SESSION['reset_user_id'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hashed_password, $_SESSION['reset_user_id']);
            $stmt->execute();
            $stmt->close();

            unset($_SESSION['reset_user_id']);
            $message = "Password has been updated successfully. <a href='login.php' style='color: #3498db;'>Click here to login</a>";
        } else {
            $message = "Passwords do not match. Please try again.";
        }
    }
}
?>

<h1 style="text-align: center; font-family: Arial, sans-serif;">Forget Password</h1>
<div style="max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);">
    <form action="forget_password.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
        <?php if (empty($_SESSION['reset_user_id'])): ?>
            <label for="first_name" style="font-size: 16px; color: #333;">First Name:</label>
            <input type="text" id="first_name" name="first_name" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">

            <label for="email" style="font-size: 16px; color: #333;">Email:</label>
            <input type="email" id="email" name="email" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">

            <button type="submit" name="verify" style="padding: 10px; background-color: #3498db; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">Verify</button>
        <?php else: ?>
            <label for="new_password" style="font-size: 16px; color: #333;">New Password:</label>
            <input type="password" id="new_password" name="new_password" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;" disabled>

            <label for="confirm_password" style="font-size: 16px; color: #333;">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;" disabled>

            <button type="submit" name="reset_password" style="padding: 10px; background-color: #2ecc71; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;" disabled>Reset Password</button>

            <script>
                document.getElementById('new_password').disabled = false;
                document.getElementById('confirm_password').disabled = false;
                document.querySelector('button[name="reset_password"]').disabled = false;
            </script>
        <?php endif; ?>
    </form>

    <?php if (!empty($message)): ?>
        <p style="color: red; text-align: center;"><?php echo $message; ?></p>
    <?php endif; ?>
</div>
