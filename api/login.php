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

$MAX_ATTEMPTS = 5;
$WINDOW_SEC   = 10 * 60;
$now = time();
if (!isset($_SESSION['login_window_start'])) {
    $_SESSION['login_window_start'] = $now;
    $_SESSION['login_attempts'] = 0;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($now - (int)$_SESSION['login_window_start']) > $WINDOW_SEC) {
        $_SESSION['login_window_start'] = $now;
        $_SESSION['login_attempts'] = 0;
    }

    if ((int)$_SESSION['login_attempts'] >= $MAX_ATTEMPTS) {
        $message = 'Too many login attempts. Please wait a few minutes and try again.';
    } else {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            $message = 'Invalid form token. Please refresh and try again.';
        } else {
            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');

            if ($username === '' || $password === '') {
                $message = 'Please enter both username and password.';
            } else {
                $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE lower(username) = lower(:uname) LIMIT 1');
                $stmt->execute([':uname' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                $genericError = 'Invalid username or password.';

                if (!$user) {
                    $_SESSION['login_attempts']++;
                    $message = $genericError;
                } else {
                    if (!password_verify($password, (string)$user['password'])) {
                        $_SESSION['login_attempts']++;
                        $message = $genericError;
                    } else {
                        if (password_needs_rehash((string)$user['password'], PASSWORD_DEFAULT)) {
                            $newHash = password_hash($password, PASSWORD_DEFAULT);
                            $upd = $pdo->prepare('UPDATE users SET password = :p WHERE id = :id');
                            $upd->execute([':p' => $newHash, ':id' => (int)$user['id']]);
                        }

                        $_SESSION['login_window_start'] = $now;
                        $_SESSION['login_attempts'] = 0;

                        session_regenerate_id(true);
                        $_SESSION['user_id']  = (int)$user['id'];
                        $_SESSION['username'] = (string)$user['username'];
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        header('Location: index.php');
                        exit();
                    }
                }
            }
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

<h1 style="text-align: center; font-family: Arial, sans-serif;">Login</h1>
<div style="max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);">
    <?php if (!empty($message)): ?>
        <p style="color: #e67e22; text-align: center; margin-top: 0;">
            <?php echo htmlspecialchars($message, ENT_QUOTES); ?>
        </p>
    <?php endif; ?>

    <form action="login.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">

        <label for="username" style="font-size: 16px; color: #333;">Username:</label>
        <input type="text" id="username" name="username" required autocomplete="username" style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">

        <label for="password" style="font-size: 16px; color: #333;">Password:</label>
        <input type="password" id="password" name="password" required autocomplete="current-password" style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">

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