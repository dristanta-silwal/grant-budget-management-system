<?php
include 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

$unread_count = 0;
$user_name = '';

if ($user_id) {
    if ($conn) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND `read` = FALSE");
        if ($stmt) {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stmt->bind_result($unread_count);
            $stmt->fetch();
            $stmt->close();
        } else {
            die("Error preparing statement for notifications count: " . $conn->error);
        }

        $stmt = $conn->prepare("SELECT first_name FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stmt->bind_result($user_name);
            $stmt->fetch();
            $stmt->close();
        } else {
            die("Error preparing statement for user name: " . $conn->error);
        }
    } else {
        die("Database connection error.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Grant Budget Management System</title>
    <link rel="stylesheet" href="style.css">
</head>

<body
    style="font-family: Arial, sans-serif; background-color: #f9f9fc; width: 100%; min-height: 100vh; padding: 1rem; color: #333; max-width: 100%;">
    <header
        style="background-color: #ffffff; color: #333; padding: 1rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); width: 95%; text-align: center;">
        <h1 style="font-size: 1.6rem; color: #4a90e2; margin-bottom: 1rem;">Grant Budget Management System</h1>
        <nav
            style="display: flex; justify-content: space-around; align-items: center; flex-wrap: wrap; padding: 0.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="index.php"
                    style="text-decoration: none; color: #4a90e2; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; background-color: #e6f0fa; transition: background-color 0.3s ease;">Home</a>
                <a href="create_grant.php"
                    style="text-decoration: none; color: #4a90e2; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; background-color: #e6f0fa; transition: background-color 0.3s ease;">Create New Grant</a>
                <a href="logout.php"
                    style="text-decoration: none; color: #4a90e2; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; background-color: #e6f0fa; transition: background-color 0.3s ease;">Logout</a>
                <?php if ($user_id == 1): ?>
                    <a href="update_salaries.php"
                        style="text-decoration: none; color: #4a90e2; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; background-color: #e6f0fa; transition: background-color 0.3s ease;">Update
                        Salaries</a>
                    <a href="update_fringes.php"
                        style="text-decoration: none; color: #4a90e2; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; background-color: #e6f0fa; transition: background-color 0.3s ease;">UpdateFringes</a>
                <?php endif; ?>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span>Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                <a href="notifications.php" style="position: relative; text-decoration: none; color: black;">
                    🔔
                    <?php if ($unread_count > 0): ?>
                        <span
                            style="background-color: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; position: absolute; top: -5px; right: -5px;">
                            <?php echo $unread_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
    </header>