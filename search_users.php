<?php
include 'db.php';

session_start();
$current_user_id = $_SESSION['user_id'];
$query = $_GET['query'] ?? '';

if (!empty($query)) {
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE username LIKE CONCAT('%', ?, '%') AND id != ? LIMIT 10");
    $stmt->bind_param('si', $query, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($user = $result->fetch_assoc()) {
        echo '<div onclick="addUser(' . $user['id'] . ', \'' . htmlspecialchars($user['username'], ENT_QUOTES) . '\')" style="cursor: pointer; padding: 5px; border-bottom: 1px solid #ddd;">';
        echo htmlspecialchars($user['username']);
        echo '</div>';
    }
}
?>
