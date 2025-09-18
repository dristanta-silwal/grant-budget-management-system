<?php
require __DIR__ . '/../src/db.php';

session_start();
$current_user_id = $_SESSION['user_id'];
$query = $_GET['query'] ?? '';

if ($query !== '') {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username ILIKE :q AND id != :uid LIMIT 10");
    $stmt->execute([':q' => "%{$query}%", ':uid' => $current_user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        $uid = (int)$user['id'];
        $uname = htmlspecialchars($user['username'], ENT_QUOTES);
        echo '<div onclick="addUser(' . $uid . ', \'' . $uname . '\')" style="cursor: pointer; padding: 5px; border-bottom: 1px solid #ddd;">';
        echo $uname;
        echo '</div>';
    }
}
?>
