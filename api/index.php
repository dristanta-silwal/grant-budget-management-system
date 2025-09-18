<?php
require __DIR__ . '/../src/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$rootHeader = dirname(__DIR__) . '/header.php';
$localHeader = __DIR__ . '/header.php';
if (file_exists($rootHeader)) {
    include $rootHeader;
} elseif (file_exists($localHeader)) {
    include $localHeader;
} else {
    trigger_error('header.php not found', E_USER_WARNING);
}

try {
    $stmt = $pdo->prepare(
        "SELECT g.id AS grant_id,
                g.title,
                g.agency,
                g.start_date,
                g.end_date,
                g.total_amount,
                gu.role,
                gu.status
         FROM grants g
         JOIN grant_users gu ON g.id = gu.grant_id
         WHERE gu.user_id = :uid AND gu.status IN ('accepted','creator')
         ORDER BY g.start_date DESC NULLS LAST, g.id DESC"
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

<?php if (!empty($grants)): ?>
    <ul style="list-style:none; padding-left:0;">
        <?php foreach ($grants as $row):
            $gid   = (int)($row['grant_id'] ?? 0);
            $title = htmlspecialchars($row['title'] ?? 'Untitled', ENT_QUOTES);
            $agency = htmlspecialchars($row['agency'] ?? '—', ENT_QUOTES);
            $total = number_format((float)($row['total_amount'] ?? 0), 2, '.', ',');
            $role  = (string)($row['role'] ?? 'viewer');

            $canManageBudget = in_array($role, ['PI','creator'], true);
            $canManagePeople = in_array($role, ['PI','CO-PI','creator'], true);
            $canDownload     = in_array($role, ['PI','CO-PI','creator'], true);
            $canDelete       = in_array($role, ['PI','creator'], true);
        ?>
            <li style="max-width: 93%; background-color: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <div>
                    <div style="font-weight:700; font-size:18px; color:#2c3e50; line-height:1.2;"><?= $title ?></div>
                    <div style="color:#7f8c8d; font-size:14px; margin-top:6px;">Agency: <?= $agency ?></div>
                    <div style="color:#7f8c8d; font-size:14px; margin-top:6px;">Total Amount: $<?= $total ?></div>
                    <div style="color:#7f8c8d; font-size:14px; margin-top:6px;">Role: <?= htmlspecialchars($role, ENT_QUOTES) ?></div>
                </div>
                <div style="display:flex; gap:10px;">
                    <?php if ($canManageBudget): ?>
                        <a href="add_budget.php?grant_id=<?= $gid ?>" style="text-decoration:none; padding:8px 12px; background-color:#3498db; color:#fff; border-radius:5px; font-size:14px;">Manage Budget</a>
                    <?php endif; ?>

                    <?php if ($canManagePeople): ?>
                        <a href="manage_people.php?grant_id=<?= $gid ?>" style="text-decoration:none; padding:8px 12px; background-color:#f39c12; color:#fff; border-radius:5px; font-size:14px;">Manage People</a>
                    <?php endif; ?>

                    <?php if ($canDownload): ?>
                        <a href="download.php?grant_id=<?= $gid ?>" style="text-decoration:none; padding:8px 12px; background-color:#2ecc71; color:#fff; border-radius:5px; font-size:14px;">Download as Excel</a>
                    <?php endif; ?>

                    <?php if ($canDelete): ?>
                        <a href="delete_grant.php?grant_id=<?= $gid ?>" onclick="return confirm('Are you sure you want to remove this grant?');" style="text-decoration:none; padding:8px 12px; background-color:#e74c3c; color:#fff; border-radius:5px; font-size:14px;">Remove</a>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <div style="background:#f8f9fa; border:1px solid #e1e5ea; border-radius:8px; padding:16px; max-width:93%;">
        <p style="margin:0 0 10px 0; color:#555;">You aren’t a member of any grants yet.</p>
        <a href="create_grant.php" style="display:inline-block; padding:8px 12px; background:#3498db; color:#fff; border-radius:6px; text-decoration:none;">Create your first grant</a>
    </div>
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
