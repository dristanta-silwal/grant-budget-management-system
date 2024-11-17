<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grant_id = $_POST['grant_id'];
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    $status = 'pending';
    $checkQuery = $conn->prepare("SELECT * FROM grant_users WHERE grant_id = ? AND user_id = ?");
    if (!$checkQuery) {
        die("Error preparing statement for checking member: " . $conn->error);
    }
    $checkQuery->bind_param('ii', $grant_id, $user_id);
    $checkQuery->execute();
    $result = $checkQuery->get_result();

    if ($result->num_rows > 0) {
        $existingMember = $result->fetch_assoc();
        if ($existingMember['status'] === 'rejected') {
            $updateStmt = $conn->prepare("UPDATE grant_users SET status = ?, role = ? WHERE grant_id = ? AND user_id = ?");
            if (!$updateStmt) {
                die("Error preparing statement for updating member: " . $conn->error);
            }
            $updateStmt->bind_param('ssii', $status, $role, $grant_id, $user_id);
            $updateStmt->execute();

            $message = "You have been re-invited to join the grant: {$grant_id} as a $role.";
            $stmtNotification = $conn->prepare("INSERT INTO notifications (user_id, message, grant_id) VALUES (?, ?, ?)");
            if (!$stmtNotification) {
                die("Error preparing statement for adding notification: " . $conn->error);
            }
            $stmtNotification->bind_param('isi', $user_id, $message, $grant_id);
            $stmtNotification->execute();

            header("Location: manage_people.php?grant_id=$grant_id&reinvited=1");
            exit();
        } else {
            echo "<p>User is already a member of this grant.</p>";
            exit();
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO grant_users (grant_id, user_id, role, status) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            die("Error preparing statement for inserting member: " . $conn->error);
        }
        $stmt->bind_param('iiss', $grant_id, $user_id, $role, $status);
        $stmt->execute();

        $message = "You have been invited to join the grant: {$grant_id} as a $role.";
        $stmtNotification = $conn->prepare("INSERT INTO notifications (user_id, message, grant_id) VALUES (?, ?, ?)");
        if (!$stmtNotification) {
            die("Error preparing statement for adding notification: " . $conn->error);
        }
        $stmtNotification->bind_param('isi', $user_id, $message, $grant_id);
        $stmtNotification->execute();

        header("Location: manage_people.php?grant_id=$grant_id&added=1");
        exit();
    }
} else {
    echo "<p>Invalid request method.</p>";
    exit();
}
?>
