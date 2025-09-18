
<?php
require __DIR__ . '/../src/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function back(int $grant_id, string $msg = '', string $type = 'info'): void {
    $qs = 'grant_id=' . urlencode((string)$grant_id);
    if ($msg !== '') {
        $qs .= '&message=' . urlencode($msg) . '&type=' . urlencode($type);
    }
    header('Location: manage_people.php?' . $qs);
    exit();
}

$current_user_id = (int)($_SESSION['user_id'] ?? 0);
if ($current_user_id <= 0) {
    header('Location: login.php');
    exit();
}

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    back((int)($_POST['grant_id'] ?? 0), 'Invalid form token. Please try again.', 'danger');
}

$grant_id = filter_input(INPUT_POST, 'grant_id', FILTER_VALIDATE_INT) ?: 0;
$target_user_id  = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT) ?: 0;
$action   = trim((string)($_POST['action'] ?? ''));

if ($grant_id <= 0 || $target_user_id <= 0 || $action === '') {
    back($grant_id, 'Invalid request.', 'danger');
}

$allowed_roles = ['PI', 'CO-PI', 'creator'];

$meStmt = $pdo->prepare("SELECT role, status FROM grant_users WHERE grant_id = :gid AND user_id = :uid LIMIT 1");
$meStmt->execute([':gid' => $grant_id, ':uid' => $current_user_id]);
$me = $meStmt->fetch(PDO::FETCH_ASSOC);
if (!$me || !in_array($me['role'], ['PI','creator'], true) || !in_array($me['status'], ['accepted','creator'], true)) {
    back($grant_id, 'You do not have permission to manage roles for this grant.', 'danger');
}

$targetStmt = $pdo->prepare("SELECT role, status FROM grant_users WHERE grant_id = :gid AND user_id = :uid LIMIT 1");
$targetStmt->execute([':gid' => $grant_id, ':uid' => $target_user_id]);
$target = $targetStmt->fetch(PDO::FETCH_ASSOC);
if (!$target) {
    back($grant_id, 'Selected user is not part of this grant.', 'danger');
}

$countLeaders = function() use ($pdo, $grant_id): int {
    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM grant_users WHERE grant_id = :gid AND role IN ('PI','creator') AND status IN ('accepted','creator')");
    $cStmt->execute([':gid' => $grant_id]);
    return (int)$cStmt->fetchColumn();
};

try {
    if ($action === 'update') {
        $new_role = trim((string)($_POST['role'] ?? ''));
        if ($new_role === '' || !in_array($new_role, $allowed_roles, true)) {
            back($grant_id, 'Invalid role selected.', 'danger');
        }

        if (strcasecmp($target['role'], $new_role) === 0) {
            back($grant_id, 'User already has this role.', 'info');
        }

        $pdo->beginTransaction();

        $leadersBefore = $countLeaders();
        $isTargetLeader = in_array($target['role'], ['PI','creator'], true);
        $becomesLeader  = in_array($new_role, ['PI','creator'], true);

        if ($isTargetLeader && !$becomesLeader && $leadersBefore <= 1) {
            $pdo->rollBack();
            back($grant_id, 'Cannot change role: this is the last PI/creator on the grant.', 'danger');
        }

        $upd = $pdo->prepare('UPDATE grant_users SET role = :role WHERE grant_id = :gid AND user_id = :uid');
        $upd->execute([':role' => $new_role, ':gid' => $grant_id, ':uid' => $target_user_id]);

        $pdo->commit();
        back($grant_id, 'Role updated successfully.', 'success');
    } elseif ($action === 'delete') {
        $pdo->beginTransaction();

        $leadersBefore = $countLeaders();
        $isTargetLeader = in_array($target['role'], ['PI','creator'], true);
        if ($isTargetLeader && $leadersBefore <= 1) {
            $pdo->rollBack();
            back($grant_id, 'Cannot remove the last PI/creator from the grant.', 'danger');
        }
        $del = $pdo->prepare('DELETE FROM grant_users WHERE grant_id = :gid AND user_id = :uid');
        $del->execute([':gid' => $grant_id, ':uid' => $target_user_id]);

        $pdo->commit();
        back($grant_id, 'User removed from grant.', 'success');
    } else {
        back($grant_id, 'Unsupported action.', 'danger');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    back($grant_id, 'Error: ' . $e->getMessage(), 'danger');
}
