<?php
require __DIR__ . '/../src/db.php';
session_start();

$message = '';

// Safe input readers
$first_name = filter_input(INPUT_POST, 'first_name', FILTER_DEFAULT);
$first_name = is_string($first_name) ? trim($first_name) : '';

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$email = is_string($email) ? trim($email) : '';

$new_password = filter_input(INPUT_POST, 'new_password', FILTER_DEFAULT);
$new_password = is_string($new_password) ? $new_password : '';

$confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_DEFAULT);
$confirm_password = is_string($confirm_password) ? $confirm_password : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify'])) {
        if ($first_name === '' || $email === '') {
            $message = 'Please enter a valid first name and email.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE first_name = :first AND email = :email');
            $stmt->execute([':first' => $first_name, ':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION['reset_user_id'] = (int)$user['id'];
                $message = 'User verified. You can now enter a new password.';
            } else {
                $message = 'No user found with that first name and email.';
            }
        }
    }
    elseif (isset($_POST['reset_password']) && isset($_SESSION['reset_user_id'])) {
        if ($new_password === '' || $confirm_password === '') {
            $message = 'Please enter and confirm your new password.';
        } elseif ($new_password !== $confirm_password) {
            $message = 'Passwords do not match. Please try again.';
        } elseif (strlen($new_password) < 8) {
            $message = 'Password must be at least 8 characters.';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE users SET password = :pwd WHERE id = :id');
            $stmt->execute([':pwd' => $hashed_password, ':id' => (int)$_SESSION['reset_user_id']]);

            unset($_SESSION['reset_user_id']);
            $message = "Password has been updated successfully. <a href='login.php' style='color: #3498db;'>Click here to login</a>";
        }
    }
}
?>

<header style="background-color: #ffffff; color: #333; margin: 1rem; padding: 1rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); width: 95%; text-align: center;">
    <h1 style="font-size: 1.6rem; color: #4a90e2; margin-bottom: 1rem;">Grant Budget Management System</h1>
    <nav style="display: flex; justify-content: space-around; align-items: center; flex-wrap: wrap; padding: 0.5rem;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="https://dristanta-silwal.github.io/grant-budget-management-system/" style="text-decoration: none; color: #4a90e2; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; background-color: #e6f0fa; transition: background-color 0.3s ease;" target="_blank">Docs</a>
        </div>
    </nav>
</header>

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
    <div style="text-align: center; margin-top: 20px;">
        <p style="color: #333; font-size: 14px;">Remembered Password?</p>
        <a href="login.php" style="display: inline-block; padding: 10px 20px; background-color:#2ecc71; color: white; border-radius: 5px; text-decoration: none; font-size: 16px;">Go to Login</a>
    </div>
</div>
