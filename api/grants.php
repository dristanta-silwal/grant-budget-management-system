<?php
require __DIR__ . '/../src/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$rootHeader  = dirname(__DIR__) . '/header.php';
$localHeader = __DIR__ . '/header.php';
if (file_exists($rootHeader)) {
    include $rootHeader;
} elseif (file_exists($localHeader)) {
    include $localHeader;
} else {
    trigger_error('header.php not found', E_USER_WARNING);
}

$user_id = (int)$_SESSION['user_id'];

try {
    $stmt = $pdo->prepare(
        "SELECT g.id, g.title, g.agency, g.updated_at, gu.role, gu.status
         FROM grants g
         JOIN grant_users gu ON g.id = gu.grant_id
         WHERE gu.user_id = :uid AND gu.status IN ('accepted','creator')
         ORDER BY COALESCE(g.updated_at, g.created_at) DESC, g.id DESC"
    );
    $stmt->execute([':uid' => $user_id]);
    $grants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    http_response_code(500);
    echo '<div style="color:#e74c3c;">Error fetching grants: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</div>';
    $grants = [];
}
?>

<h2>Your Grants</h2>

<?php if (empty($grants)): ?>
    <div style="background:#f8f9fa;border:1px solid #e1e5ea;border-radius:8px;padding:16px;">
        <p style="margin:0 0 10px 0;color:#555;">You aren&#39;t a member of any grants yet.</p>
        <a href="create_grant.php" style="display:inline-block;padding:8px 12px;background:#3498db;color:#fff;border-radius:6px;text-decoration:none;">Create your first grant</a>
    </div>
<?php else: ?>
    <ul style="list-style:none;padding-left:0;">
        <?php foreach ($grants as $row):
            $gid    = (int)$row['id'];
            $title  = htmlspecialchars($row['title'] ?? 'Untitled', ENT_QUOTES);
            $agency = htmlspecialchars($row['agency'] ?? '—', ENT_QUOTES);
            $role   = (string)($row['role'] ?? 'viewer');

            $canManageBudget = in_array($role, ['PI','creator'], true);
            $canDownload     = in_array($role, ['PI','CO-PI','creator'], true);
            $canDelete       = in_array($role, ['PI','creator'], true);
        ?>
        <li style="background-color:#fff;border:1px solid #ddd;border-radius:8px;padding:15px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 5px rgba(0,0,0,0.08);">
            <div>
                <div style="font-weight:700;font-size:18px;color:#2c3e50;line-height:1.2;"><?= $title ?></div>
                <div style="color:#7f8c8d;font-size:14px;margin-top:4px;">(<?= $agency ?>) • Role: <strong><?= htmlspecialchars($role, ENT_QUOTES) ?></strong></div>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <?php if ($canManageBudget): ?>
                    <a href="add_budget.php?grant_id=<?= $gid ?>" style="text-decoration:none;padding:8px 12px;background-color:#3498db;color:#fff;border-radius:6px;font-size:14px;">Manage Budget</a>
                <?php endif; ?>

                <?php if ($canDownload): ?>
                    <a href="download.php?grant_id=<?= $gid ?>" style="text-decoration:none;padding:8px 12px;background-color:#2ecc71;color:#fff;border-radius:6px;font-size:14px;">Download as Excel</a>
                <?php endif; ?>

                <?php if ($canDelete): ?>
                    <a href="delete_grant.php?grant_id=<?= $gid ?>" onclick="return confirm('Are you sure you want to remove this grant?');" style="text-decoration:none;padding:8px 12px;background-color:#e74c3c;color:#fff;border-radius:6px;font-size:14px;">Remove</a>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php
$rootFooter  = dirname(__DIR__) . '/footer.php';
$localFooter = __DIR__ . '/footer.php';
if (file_exists($rootFooter)) {
    include $rootFooter;
} elseif (file_exists($localFooter)) {
    include $localFooter;
} else {
    trigger_error('footer.php not found', E_USER_WARNING);
}
?>
