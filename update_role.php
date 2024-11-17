<?php
include 'db.php';

$grant_id = $_POST['grant_id'];
$user_id = $_POST['user_id'];
$role = $_POST['role'];

$query = "UPDATE grant_users SET role = ? WHERE grant_id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('sii', $role, $grant_id, $user_id);
$stmt->execute();

header("Location: manage_people.php?grant_id=$grant_id");
?>
