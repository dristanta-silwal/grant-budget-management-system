<?php
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../src/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$message_type = 'info';
if (isset($_SESSION['message'])) {
    $message = (string)$_SESSION['message'];
    $message_type = (string)($_SESSION['message_type'] ?? 'info');
    unset($_SESSION['message'], $_SESSION['message_type']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $_SESSION['message'] = 'Invalid form token. Please try again.';
        $_SESSION['message_type'] = 'danger';
        header('Location: register.php');
        exit;
    }

    $username     = filter_input(INPUT_POST, 'username', FILTER_DEFAULT);
    $password     = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);
    $confirm      = filter_input(INPUT_POST, 'confirm_password', FILTER_DEFAULT);
    $first_name   = filter_input(INPUT_POST, 'first_name', FILTER_DEFAULT);
    $last_name    = filter_input(INPUT_POST, 'last_name', FILTER_DEFAULT);
    $email        = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $organization = filter_input(INPUT_POST, 'organization', FILTER_DEFAULT);

    $username     = is_string($username) ? trim($username) : '';
    $first_name   = is_string($first_name) ? trim($first_name) : '';
    $last_name    = is_string($last_name) ? trim($last_name) : '';
    $organization = is_string($organization) ? trim($organization) : '';
    $password     = is_string($password) ? $password : '';
    $confirm      = is_string($confirm) ? $confirm : '';
    $email        = is_string($email) ? trim($email) : '';

    $email = strtolower($email);
    $usernameRuleOk = (bool)preg_match('/^[A-Z][a-z]*$/', $username);

    if ($username === '' || $first_name === '' || $last_name === '' || $email === '' || $organization === '' || $password === '' || $confirm === '') {
        $_SESSION['message'] = 'All fields are required and email must be valid.';
        $_SESSION['message_type'] = 'danger';
    } elseif (!$usernameRuleOk) {
        $_SESSION['message'] = 'Username must be a single word with the first letter capitalized (e.g., Dristanta).';
        $_SESSION['message_type'] = 'danger';
    } elseif ($password !== $confirm) {
        $_SESSION['message'] = 'Passwords do not match.';
        $_SESSION['message_type'] = 'danger';
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[!@#$%^&*]/', $password)) {
        $_SESSION['message'] = 'Password must be at least 8 characters and include upper/lowercase, a number, and a special character.';
        $_SESSION['message_type'] = 'danger';
    } else {
        try {
            $dupe = $pdo->prepare('SELECT 1 FROM users WHERE lower(username) = lower(:u) OR lower(email) = lower(:e) LIMIT 1');
            $dupe->execute([':u' => $username, ':e' => $email]);
            if ($dupe->fetch()) {
                $_SESSION['message'] = 'Username or email already exists.';
                $_SESSION['message_type'] = 'danger';
                header('Location: register.php');
                exit;
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password, first_name, last_name, email, organization) VALUES (:u, :p, :fn, :ln, :em, :org)');
            $stmt->execute([
                ':u'   => $username,
                ':p'   => $hashed,
                ':fn'  => $first_name,
                ':ln'  => $last_name,
                ':em'  => $email,
                ':org' => $organization,
            ]);

            $_SESSION['message'] = 'Registration successful. You can log in now.';
            $_SESSION['message_type'] = 'success';
        } catch (Throwable $e) {
            $_SESSION['message'] = 'Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES);
            $_SESSION['message_type'] = 'danger';
        }
    }

    header('Location: register.php');
    exit;
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

<h1 style="text-align: center; font-family: Arial, sans-serif;">Register</h1>
<div style="max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);">

    <?php if ($message): ?>
        <?php
            $color = [
                'success' => '#2ecc71',
                'danger'  => '#e74c3c',
                'warning' => '#f39c12',
                'info'    => '#3498db',
            ][$message_type] ?? '#3498db';
        ?>
        <div style="text-align: center; margin-bottom: 20px; padding:10px; border:1px solid #e1e5ea; border-radius:6px; background:#fff;">
            <span style="color: <?= $color ?>; font-weight:700; margin-right:8px; text-transform:capitalize;"><?= htmlspecialchars($message_type, ENT_QUOTES) ?>:</span>
            <span style="color:#2c3e50;"><?= htmlspecialchars($message, ENT_QUOTES) ?></span>
        </div>
    <?php endif; ?>

    <form id="registerForm" action="register.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">

        <div style="display: flex; gap: 10px;">
            <div style="flex: 1;">
                <label for="first_name" style="font-size: 16px; color: #333;">First Name:</label>
                <input type="text" id="first_name" name="first_name" required autocomplete="given-name" style="padding: 8px; border: 1px solid #ccc; border-radius: 5px; width: 100%;">
            </div>
            <div style="flex: 1;">
                <label for="last_name" style="font-size: 16px; color: #333;">Last Name:</label>
                <input type="text" id="last_name" name="last_name" required autocomplete="family-name" style="padding: 8px; border: 1px solid #ccc; border-radius: 5px; width: 100%;">
            </div>
        </div>

        <label for="email" style="font-size: 16px; color: #333;">Email:</label>
        <input type="email" id="email" name="email" required autocomplete="email" style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">

        <label for="organization" style="font-size: 16px; color: #333;">Organization:</label>
        <select id="organization" name="organization" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
            <option value="University of Idaho">University of Idaho</option>
            <option value="Idaho State University">Idaho State University</option>
            <option value="Boise State University">Boise State University</option>
        </select>

        <label for="username" style="font-size: 16px; color: #333;">Username:</label>
        <input type="text" id="username" name="username" required pattern="^[A-Z][a-z]*$" autocomplete="username" style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
        <p id="username_message" style="font-size: 14px; color: black; display: block; margin: -10px 0 0 2px;">Username must be your first name. eg:- Dristanta</p>

        <label for="password" style="font-size: 16px; color: #333;">Password:</label>
        <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">

        <ul id="passwordRequirements" style="font-size: 14px; color: #555; margin-top: -10px; padding-left: 20px;">
            <li>Password must be at least 8 characters long</li>
            <li>Include at least one uppercase letter</li>
            <li>Include at least one lowercase letter</li>
            <li>Include at least one number</li>
            <li>Include at least one special character (!@#$%^&*)</li>
        </ul>

        <label for="confirm_password" style="font-size: 16px; color: #333;">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password" style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
        <p id="password_message" style="font-size: 14px; color: red; display: none;">Passwords do not match.</p>

        <input type="submit" id="register_button" value="Register" style="padding: 10px; background-color: #2ecc71; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;" disabled>
    </form>

    <div style="text-align: center; margin-top: 20px;">
        <p style="color: #333; font-size: 14px;">Already have an account?</p>
        <a href="login.php" style="display: inline-block; padding: 10px 20px; background-color: #3498db; color: white; border-radius: 5px; text-decoration: none; font-size: 16px;">Login</a>
    </div>
</div>

<script>
    const username = document.getElementById('username');
    const usernameMessage = document.getElementById('username_message');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const passwordMessage = document.getElementById('password_message');
    const passwordRequirements = document.getElementById('passwordRequirements');
    const submitButton = document.getElementById('register_button');

    function validateUsername() {
        const usernameValue = username.value.trim();
        const isValid = /^[A-Z][a-z]*$/.test(usernameValue);
        usernameMessage.style.color = isValid ? '#2ecc71' : 'red';
        return isValid;
    }

    function validatePassword() {
        const value = password.value;

        const hasLength = value.length >= 8;
        const hasUpperCase = /[A-Z]/.test(value);
        const hasLowerCase = /[a-z]/.test(value);
        const hasNumber = /\d/.test(value);
        const hasSpecialChar = /[!@#$%^&*]/.test(value);
        const passwordsMatch = password.value === confirmPassword.value;

        passwordRequirements.children[0].style.color = hasLength ? 'green' : 'red';
        passwordRequirements.children[1].style.color = hasUpperCase ? 'green' : 'red';
        passwordRequirements.children[2].style.color = hasLowerCase ? 'green' : 'red';
        passwordRequirements.children[3].style.color = hasNumber ? 'green' : 'red';
        passwordRequirements.children[4].style.color = hasSpecialChar ? 'green' : 'red';

        passwordMessage.style.display = passwordsMatch ? 'none' : 'block';

        return hasLength && hasUpperCase && hasLowerCase && hasNumber && hasSpecialChar && passwordsMatch;
    }

    function validateForm() {
        const isUsernameValid = validateUsername();
        const isPasswordValid = validatePassword();
        submitButton.disabled = !(isUsernameValid && isPasswordValid);
    }

    [username, password, confirmPassword].forEach(input => {
        input.addEventListener('input', validateForm);
    });
</script>