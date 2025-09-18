<?php
require __DIR__ . '/../src/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grant_id = filter_input(INPUT_POST, 'grant_id', FILTER_VALIDATE_INT);
    $user_id  = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $role     = filter_input(INPUT_POST, 'role', FILTER_DEFAULT);
    $role     = is_string($role) ? trim($role) : '';
    $status = 'pending';

    if (!$grant_id || !$user_id || $role === '') {
        echo "<p>Invalid input.</p>";
        exit();
    }

    $checkQuery = $pdo->prepare("SELECT status FROM grant_users WHERE grant_id = :gid AND user_id = :uid");
    $checkQuery->execute([':gid' => $grant_id, ':uid' => $user_id]);
    $existingMember = $checkQuery->fetch(PDO::FETCH_ASSOC);

    if ($existingMember) {
        if ($existingMember['status'] === 'rejected') {
            $updateStmt = $pdo->prepare("UPDATE grant_users SET status = :status, role = :role WHERE grant_id = :gid AND user_id = :uid");
            $updateStmt->execute([':status' => $status, ':role' => $role, ':gid' => $grant_id, ':uid' => $user_id]);

            $message = "You have been re-invited to join the grant: {$grant_id} as a {$role}.";
            $stmtNotification = $pdo->prepare("INSERT INTO notifications (user_id, message, grant_id) VALUES (:uid, :message, :gid)");
            $stmtNotification->execute([':uid' => $user_id, ':message' => $message, ':gid' => $grant_id]);

            header("Location: manage_people.php?grant_id=$grant_id&reinvited=1");
            exit();
        } else {
            echo "<p>User is already a member of this grant.</p>";
            exit();
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO grant_users (grant_id, user_id, role, status) VALUES (:gid, :uid, :role, :status)");
        $stmt->execute([':gid' => $grant_id, ':uid' => $user_id, ':role' => $role, ':status' => $status]);

        $message = "You have been invited to join the grant: {$grant_id} as a {$role}.";
        $stmtNotification = $pdo->prepare("INSERT INTO notifications (user_id, message, grant_id) VALUES (:uid, :message, :gid)");
        $stmtNotification->execute([':uid' => $user_id, ':message' => $message, ':gid' => $grant_id]);

        header("Location: manage_people.php?grant_id=$grant_id&added=1");
        exit();
    }
} else {
    echo "<p>Invalid request method.</p>";
    exit();
}
?>
