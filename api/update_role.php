<?php
require __DIR__ . '/../src/db.php';

$grant_id = filter_input(INPUT_POST, 'grant_id', FILTER_VALIDATE_INT);
$user_id  = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$action   = filter_input(INPUT_POST, 'action', FILTER_DEFAULT);
$action   = is_string($action) ? trim($action) : '';

if ($action === 'update') {
    $role = filter_input(INPUT_POST, 'role', FILTER_DEFAULT);
    $role = is_string($role) ? trim($role) : '';

    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM grant_users WHERE grant_id = :gid AND user_id = :uid AND role = :role');
        $stmt->execute([':gid' => $grant_id, ':uid' => $user_id, ':role' => $role]);
        $count = (int)$stmt->fetchColumn();

        if ($count > 0) {
            header("Location: manage_people.php?grant_id=$grant_id&error=User is already assigned this role.");
            exit();
        }

        $stmt = $pdo->prepare('UPDATE grant_users SET role = :role WHERE grant_id = :gid AND user_id = :uid');
        $stmt->execute([':role' => $role, ':gid' => $grant_id, ':uid' => $user_id]);
    } catch (Throwable $e) {
        header("Location: manage_people.php?grant_id=$grant_id&error=" . urlencode($e->getMessage()));
        exit();
    }
} elseif ($action === 'delete') {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM grant_users WHERE grant_id = :gid AND user_id = :uid');
        $stmt->execute([':gid' => $grant_id, ':uid' => $user_id]);
        $count = (int)$stmt->fetchColumn();

        if ($count === 0) {
            header("Location: manage_people.php?grant_id=$grant_id&error=User does not exist in the grant.");
            exit();
        }

        $stmt = $pdo->prepare('DELETE FROM grant_users WHERE grant_id = :gid AND user_id = :uid');
        $stmt->execute([':gid' => $grant_id, ':uid' => $user_id]);
    } catch (Throwable $e) {
        header("Location: manage_people.php?grant_id=$grant_id&error=" . urlencode($e->getMessage()));
        exit();
    }
}

header("Location: manage_people.php?grant_id=$grant_id");
exit();
?>
