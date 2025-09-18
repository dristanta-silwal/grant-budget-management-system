<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($pdo)) {
    require __DIR__ . '/../src/db.php';
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$unread_count = 0;
$user_name = '';

try {
    if ($user_id) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND "read" = FALSE');
        $stmt->execute([':uid' => $user_id]);
        $unread_count = (int)($stmt->fetchColumn() ?: 0);

        $stmt = $pdo->prepare('SELECT first_name FROM users WHERE id = :id');
        $stmt->execute([':id' => $user_id]);
        $user_name = (string)($stmt->fetchColumn() ?: '');
    }
} catch (Throwable $e) {
    $unread_count = 0;
}

$is_admin = (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) || ($user_id === 1);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Grant Budget Management System</title>
    <link rel="stylesheet" href="style.css">
</head>

<body
    style="font-family: Arial, sans-serif; background-color: #f9f9fc; width: 100%; min-height: 100vh; padding: 1rem; color: #333; max-width: 100%;">
    <header
        style="background-color: #ffffff; color: #333; padding: 1rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); width: 95%; text-align: center;">
        <h1 style="font-size: 1.6rem; color: #4a90e2; margin-bottom: 1rem;">Grant Budget Management System</h1>
        <nav
            style="display: flex; justify-content: space-around; align-items: center; flex-wrap: wrap; padding: 0.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="index.php"
                    style="text-decoration: none; color: #4a90e2; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; background-color: #e6f0fa; transition: background-color 0.3s ease;">Home</a>
                <a href="create_grant.php"
                    style="text-decoration: none; color: #4a90e2; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; background-color: #e6f0fa; transition: background-color 0.3s ease;">Create New Grant</a>
                <a href="logout.php"
                    style="text-decoration: none; color: #4a90e2; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; background-color: #e6f0fa; transition: background-color 0.3s ease;">Logout</a>
                <?php if ($is_admin): ?>
                    <a href="update_salaries.php"
                        style="text-decoration: none; color: #4a90e2; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; background-color: #e6f0fa; transition: background-color 0.3s ease;">Update
                        Salaries</a>
                    <a href="update_fringes.php"
                        style="text-decoration: none; color: #4a90e2; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; background-color: #e6f0fa; transition: background-color 0.3s ease;">Update Fringes</a>
                <?php endif; ?>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span>Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                <a href="notifications.php" style="position: relative; text-decoration: none; color: black;">
                    🔔
                    <?php if ($unread_count > 0): ?>
                        <span
                            style="background-color: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; position: absolute; top: -5px; right: -5px;">
                            <?php echo $unread_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
    </header>
<?php if (!empty($_SESSION['message'])): ?>
    <?php
        $__type = $_SESSION['message_type'] ?? 'info';
        $__color = [
            'success' => '#2ecc71',
            'danger'  => '#e74c3c',
            'warning' => '#f39c12',
            'info'    => '#3498db',
        ][$__type] ?? '#3498db';
        $__msg = htmlspecialchars((string)$_SESSION['message'], ENT_QUOTES);
        unset($_SESSION['message'], $_SESSION['message_type']);
    ?>
    <div style="width:95%; margin: 12px auto 0; padding: 10px 14px; border-radius: 6px; background: #fff; border: 1px solid #e1e5ea;">
        <span style="display:inline-block; color: <?= $__color ?>; font-weight:700; margin-right:8px; text-transform:capitalize;"><?= htmlspecialchars($__type, ENT_QUOTES) ?>:</span>
        <span style="color:#2c3e50;"><?= $__msg ?></span>
    </div>
<?php endif; ?>