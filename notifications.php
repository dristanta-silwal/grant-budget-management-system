<?php
include 'header.php';
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$action = $_GET['action'] ?? 'view';
$notification_id = $_POST['notification_id'] ?? null;
$status = $_POST['status'] ?? null;

if ($action === 'mark_read' && $notification_id) {
    $grant_id = $_POST['grant_id'] ?? null;
    
    if ($grant_id && ($status === 'accepted' || $status === 'rejected')) {
        $stmt = $conn->prepare("UPDATE grant_users SET status = ? WHERE grant_id = ? AND user_id = ?");
        if (!$stmt) {
            die("Error preparing statement for updating grant_users status: " . $conn->error);
        }
        $stmt->bind_param('sii', $status, $grant_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare("UPDATE notifications SET `read` = TRUE WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        die("Error preparing statement for updating notifications: " . $conn->error);
    }
    $stmt->bind_param('ii', $notification_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: notifications.php");
    exit();
} elseif ($action === 'delete' && $notification_id) {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        die("Error preparing statement for deleting notification: " . $conn->error);
    }
    $stmt->bind_param('ii', $notification_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications.php");
    exit();
}

$stmt = $conn->prepare("SELECT id, message, created_at, `read`, grant_id FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
if (!$stmt) {
    die("Error preparing statement for selecting notifications: " . $conn->error);
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$notifications = $stmt->get_result();
?>

<h2 style="color: #333; font-size: 1.5rem; margin-bottom: 1rem; text-align: center;">Notifications</h2>

<?php if ($notifications->num_rows > 0): ?>
    <ul style="list-style-type: none; padding: 0; max-width: 600px; margin: 0 auto;">
        <?php while ($notification = $notifications->fetch_assoc()): ?>
            <li style="border-bottom: 1px solid #ddd; padding: 15px; background-color: #f9f9fc; border-radius: 8px; margin-bottom: 10px;">
                <p style="margin-bottom: 5px; color: #555;"><?php echo htmlspecialchars($notification['message']); ?></p>
                <small style="color: #999;">Received: <?php echo $notification['created_at']; ?></small><br>

                <?php if (!$notification['read'] && $notification['grant_id']): ?>
                    <form action="notifications.php?action=mark_read" method="POST" style="display: inline;">
                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                        <input type="hidden" name="grant_id" value="<?php echo $notification['grant_id']; ?>">
                        <input type="hidden" name="status" value="accepted">
                        <button type="submit" style="background-color: #2ecc71; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer; margin-right: 5px;">Accept</button>
                    </form>
                    <form action="notifications.php?action=mark_read" method="POST" style="display: inline;">
                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                        <input type="hidden" name="grant_id" value="<?php echo $notification['grant_id']; ?>">
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" style="background-color: #ddb771; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer;">Reject</button>
                    </form>
                <?php elseif (!$notification['read']): ?>
                    <form action="notifications.php?action=mark_read" method="POST" style="display: inline;">
                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                        <button type="submit" style="background-color: #3498db; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer;">Mark as Read</button>
                    </form>
                <?php endif; ?>
                
                <form action="notifications.php?action=delete" method="POST" style="display: inline;">
                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                    <button type="submit" style="background-color: #e74c3c; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer;">Delete</button>
                </form>
            </li>
        <?php endwhile; ?>
    </ul>
<?php else: ?>
    <p style="text-align: center; color: #555;">You have no notifications.</p>
<?php endif; ?>

<?php
include 'footer.php';
?>
