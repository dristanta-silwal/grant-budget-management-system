<?php
include 'db.php';

$grant_id = $_POST['grant_id'];
$user_id = $_POST['user_id'];
$action = $_POST['action'];

if ($action === 'update') {
    $role = $_POST['role'];
    $checkQuery = "SELECT COUNT(*) FROM grant_users WHERE grant_id = ? AND user_id = ? AND role = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('iis', $grant_id, $user_id, $role);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        header("Location: manage_people.php?grant_id=$grant_id&error=User is already assigned this role.");
        exit();
    }

    $query = "UPDATE grant_users SET role = ? WHERE grant_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sii', $role, $grant_id, $user_id);
    $stmt->execute();
} elseif ($action === 'delete') {
    $checkQuery = "SELECT COUNT(*) FROM grant_users WHERE grant_id = ? AND user_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('ii', $grant_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count === 0) {
        header("Location: manage_people.php?grant_id=$grant_id&error=User does not exist in the grant.");
        exit();
    }

    $query = "DELETE FROM grant_users WHERE grant_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $grant_id, $user_id);
    $stmt->execute();
}

header("Location: manage_people.php?grant_id=$grant_id");
exit();
?>
