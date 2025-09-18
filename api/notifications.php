<?php
require __DIR__ . '/../src/db.php';

$rootHeader = dirname(__DIR__) . '/header.php';
$localHeader = __DIR__ . '/header.php';
if (file_exists($rootHeader)) {
    include $rootHeader;
} elseif (file_exists($localHeader)) {
    include $localHeader;
} else {
    trigger_error('header.php not found', E_USER_WARNING);
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$action = filter_input(INPUT_GET, 'action', FILTER_DEFAULT) ?: 'view';
$notification_id = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT) ?: null;
$status = filter_input(INPUT_POST, 'status', FILTER_DEFAULT);
$status = is_string($status) ? trim($status) : null;

if ($action === 'mark_read' && $notification_id) {
    $grant_id = filter_input(INPUT_POST, 'grant_id', FILTER_VALIDATE_INT) ?: null;

    try {
        if ($grant_id && ($status === 'accepted' || $status === 'rejected')) {
            $stmt = $pdo->prepare("UPDATE grant_users SET status = :status WHERE grant_id = :gid AND user_id = :uid");
            $stmt->execute([':status' => $status, ':gid' => $grant_id, ':uid' => $user_id]);
        }

        $stmt = $pdo->prepare("UPDATE notifications SET \"read\" = TRUE WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $notification_id, ':uid' => $user_id]);
    } catch (Throwable $e) {
        die("Error updating notification: " . htmlspecialchars($e->getMessage(), ENT_QUOTES));
    }

    header("Location: notifications.php");
    exit();
} elseif ($action === 'delete' && $notification_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $notification_id, ':uid' => $user_id]);
    } catch (Throwable $e) {
        die("Error deleting notification: " . htmlspecialchars($e->getMessage(), ENT_QUOTES));
    }
    header("Location: notifications.php");
    exit();
}

try {
    $stmt = $pdo->prepare('SELECT id, message, created_at, "read", grant_id FROM notifications WHERE user_id = :uid ORDER BY created_at DESC');
    $stmt->execute([':uid' => $user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    die('Error loading notifications: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES));
}
?>

<h2 style="color: #333; font-size: 1.5rem; margin-bottom: 1rem; text-align: center;">Notifications</h2>

<?php if (count($notifications) > 0): ?>
    <ul style="list-style-type: none; padding: 0; max-width: 600px; margin: 0 auto;">
        <?php foreach ($notifications as $notification): ?>
            <li style="border-bottom: 1px solid #ddd; padding: 15px; background-color: #f9f9fc; border-radius: 8px; margin-bottom: 10px;">
                <p style="margin-bottom: 5px; color: #555;"><?php echo htmlspecialchars($notification['message']); ?></p>
                <small style="color: #999;">Received: <?php echo $notification['created_at']; ?></small><br>

                <?php if (!$notification['read'] && $notification['grant_id']): ?>
                    <form action="notifications.php?action=mark_read" method="POST" style="display: inline;">
                        <input type="hidden" name="notification_id" value="<?php echo (int)$notification['id']; ?>">
                        <input type="hidden" name="grant_id" value="<?php echo (int)$notification['grant_id']; ?>">
                        <input type="hidden" name="status" value="accepted">
                        <button type="submit" style="background-color: #2ecc71; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer; margin-right: 5px;">Accept</button>
                    </form>
                    <form action="notifications.php?action=mark_read" method="POST" style="display: inline;">
                        <input type="hidden" name="notification_id" value="<?php echo (int)$notification['id']; ?>">
                        <input type="hidden" name="grant_id" value="<?php echo (int)$notification['grant_id']; ?>">
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" style="background-color: #ddb771; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer;">Reject</button>
                    </form>
                <?php elseif (!$notification['read']): ?>
                    <form action="notifications.php?action=mark_read" method="POST" style="display: inline;">
                        <input type="hidden" name="notification_id" value="<?php echo (int)$notification['id']; ?>">
                        <button type="submit" style="background-color: #3498db; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer;">Mark as Read</button>
                    </form>
                <?php endif; ?>
                
                <form action="notifications.php?action=delete" method="POST" style="display: inline;">
                    <input type="hidden" name="notification_id" value="<?php echo (int)$notification['id']; ?>">
                    <button type="submit" style="background-color: #e74c3c; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer;">Delete</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p style="text-align: center; color: #555;">You have no notifications.</p>
<?php endif; ?>

<?php
$rootFooter = dirname(__DIR__) . '/footer.php';
$localFooter = __DIR__ . '/footer.php';
if (file_exists($rootFooter)) {
    include $rootFooter;
} elseif (file_exists($localFooter)) {
    include $localFooter;
} else {
    trigger_error('footer.php not found', E_USER_WARNING);
}
?>
