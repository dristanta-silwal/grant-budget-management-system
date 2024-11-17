<?php
session_start();
include 'db.php';

$message = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $organization = $_POST['organization'];

    if ($password !== $confirm_password) {
        $_SESSION['message'] = "<p style='color: red;'>Error: Passwords do not match.</p>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, password, first_name, last_name, email, organization) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $username, $hashed_password, $first_name, $last_name, $email, $organization);

        if ($stmt->execute()) {
            $_SESSION['message'] = "<p style='color: green;'>Registration successful. You can <a href='login.php'>login here</a>.</p>";
        } else {
            $_SESSION['message'] = "<p style='color: red;'>Error: " . $stmt->error . "</p>";
        }
    }

    header("Location: register.php");
    exit;
}
?>

<h1 style="text-align: center; font-family: Arial, sans-serif;">Register</h1>
<div style="max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);">

    <?php if ($message): ?>
        <div style="text-align: center; margin-bottom: 20px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form id="registerForm" action="register.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
        
        <div style="display: flex; gap: 10px;">
            <div style="flex: 1;">
                <label for="first_name" style="font-size: 16px; color: #333;">First Name:</label>
                <input type="text" id="first_name" name="first_name" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px; width: 100%;">
            </div>
            <div style="flex: 1;">
                <label for="last_name" style="font-size: 16px; color: #333;">Last Name:</label>
                <input type="text" id="last_name" name="last_name" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px; width: 100%;">
            </div>
        </div>

        <label for="email" style="font-size: 16px; color: #333;">Email:</label>
        <input type="email" id="email" name="email" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">

        <label for="organization" style="font-size: 16px; color: #333;">Organization:</label>
        <select id="organization" name="organization" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
            <option value="University of Idaho">University of Idaho</option>
            <option value="Idaho State University">Idaho State University</option>
            <option value="Boise State University">Boise State University</option>
        </select>

        <label for="username" style="font-size: 16px; color: #333;">Username:</label>
        <input type="text" id="username" name="username" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">

        <label for="password" style="font-size: 16px; color: #333;">Password:</label>
        <input type="password" id="password" name="password" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">

        <label for="confirm_password" style="font-size: 16px; color: #333;">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
        <p id="password_message" style="font-size: 14px; color: red; display: none;">Passwords do not match.</p>

        <input type="submit" id="register_button" value="Register" style="padding: 10px; background-color: #2ecc71; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;" disabled>
    </form>

    <div style="text-align: center; margin-top: 20px;">
        <p style="color: #333; font-size: 14px;">Already have an account?</p>
        <a href="login.php" style="display: inline-block; padding: 10px 20px; background-color: #3498db; color: white; border-radius: 5px; text-decoration: none; font-size: 16px;">Login</a>
    </div>
</div>

<script>
    const form = document.getElementById('registerForm');
    const submitButton = document.getElementById('register_button');
    const fields = Array.from(form.querySelectorAll('input[required], select[required]'));
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const passwordMessage = document.getElementById('password_message');

    function validateForm() {
        const allFieldsFilled = fields.every(field => field.value.trim() !== '');
        const passwordsMatch = password.value === confirmPassword.value;

        passwordMessage.style.display = passwordsMatch ? 'none' : 'block';
        submitButton.disabled = !(allFieldsFilled && passwordsMatch);
    }

    fields.forEach(field => field.addEventListener('input', validateForm));
    confirmPassword.addEventListener('input', validateForm);
</script>
