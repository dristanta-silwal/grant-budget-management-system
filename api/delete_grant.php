<?php
require __DIR__ . '/../src/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirect_with_flash(string $url, string $type, string $message): void {
    $_SESSION['message_type'] = $type;
    $_SESSION['message'] = $message;
    header('Location: ' . $url);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    redirect_with_flash('login.php', 'warning', 'Please log in to continue.');
}

$grant_id = filter_input(INPUT_GET, 'grant_id', FILTER_VALIDATE_INT);
if (!$grant_id) {
    redirect_with_flash('index.php', 'danger', 'Error: Grant ID is required.');
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM grant_users WHERE grant_id = :gid AND user_id = :uid');
$stmt->execute([':gid' => $grant_id, ':uid' => $user_id]);
$user_grant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_grant || !in_array($user_grant['role'], ['PI','creator'], true)) {
    redirect_with_flash('index.php', 'danger', 'You do not have permission to delete this grant.');
}

$stmt = $pdo->prepare('SELECT title FROM grants WHERE id = :gid');
$stmt->execute([':gid' => $grant_id]);
$grant = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$grant) {
    redirect_with_flash('index.php', 'danger', 'Grant not found.');
}

$rootHeader = dirname(__DIR__) . '/header.php';
$localHeader = __DIR__ . '/header.php';
if (file_exists($rootHeader)) {
    include $rootHeader;
} elseif (file_exists($localHeader)) {
    include $localHeader;
} else {
    trigger_error('header.php not found', E_USER_WARNING);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        $pdo->beginTransaction();

        $delItems = $pdo->prepare("DELETE FROM budget_items WHERE grant_id = :gid");
        $delItems->execute([':gid' => $grant_id]);

        $delUsers = $pdo->prepare("DELETE FROM grant_users WHERE grant_id = :gid");
        $delUsers->execute([':gid' => $grant_id]);

        $delGrant = $pdo->prepare("DELETE FROM grants WHERE id = :gid");
        $delGrant->execute([':gid' => $grant_id]);

        $pdo->commit();
        redirect_with_flash('index.php', 'success', 'Grant deleted successfully.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        redirect_with_flash('index.php', 'danger', 'Error deleting grant: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES));
    }

    exit();
}
?>

<h2 style="color: #333; font-size: 1.5rem; margin-bottom: 1rem;">Delete Grant</h2>
<p style="color: #555; font-size: 1rem; line-height: 1.5;">Are you sure you want to delete the grant titled "<strong><?php echo htmlspecialchars($grant['title']); ?></strong>"? This action cannot be undone and will delete all associated data.</p>

<form method="POST" action="delete_grant.php?grant_id=<?php echo $grant_id; ?>" style="margin-top: 1rem;">
    <input type="hidden" name="confirm_delete" value="yes">
    <button type="submit" style="background-color: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin-right: 10px;">Confirm Delete</button>
    <a href="index.php" style="padding: 10px 20px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">Cancel</a>
</form>

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
