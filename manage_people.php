<?php
include 'header.php';
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['grant_id']) || empty($_GET['grant_id'])) {
    echo "<p>Error: Grant ID not provided. Please go back and select a valid grant to manage.</p>";
    exit();
}

$grant_id = (int) $_GET['grant_id'];
$user_id = $_SESSION['user_id'];

$grantQuery = $conn->prepare("SELECT title FROM grants WHERE id = ?");
$grantQuery->bind_param('i', $grant_id);
$grantQuery->execute();
$grant = $grantQuery->get_result()->fetch_assoc();

if (!$grant) {
    echo "<p>Error: Grant not found. The grant ID provided does not exist.</p>";
    exit();
}

$title = $grant['title'];

$permissionQuery = $conn->prepare("SELECT role FROM grant_users WHERE grant_id = ? AND user_id = ? AND role IN ('PI', 'CO-PI')");
$permissionQuery->bind_param('ii', $grant_id, $user_id);
$permissionQuery->execute();
$permissionResult = $permissionQuery->get_result();

if ($permissionResult->num_rows === 0) {
    echo "<p>You don't have permission to manage this grant.</p>";
    exit();
}

$membersQuery = $conn->prepare("
    SELECT u.id, u.username, gu.role, gu.status 
    FROM users u 
    JOIN grant_users gu ON u.id = gu.user_id 
    WHERE gu.grant_id = ? AND gu.status IN ('accepted', 'pending')
");
$membersQuery->bind_param('i', $grant_id);
$membersQuery->execute();
$members = $membersQuery->get_result();
?>

<h2 style="font-family: Arial, sans-serif; color: #333; text-align: center; margin-top: 20px;">
    Manage People for <?php echo htmlspecialchars($title); ?>
</h2>

<table style="display:flex; align-items: center; justify-content: center; width: 100%; border-collapse: collapse; margin-top: 20px; font-family: Arial, sans-serif;">
    <tr style="background-color: #f2f2f2;">
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Username</th>
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Role</th>
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Status</th>
        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Action</th>
    </tr>
    <?php while ($member = $members->fetch_assoc()): ?>
        <tr>
            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($member['username']); ?></td>
            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($member['role']); ?></td>
            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($member['status']); ?></td>
            <td style="border: 1px solid #ddd; padding: 8px;">
                <form action="update_role.php" method="POST">
                    <input type="hidden" name="grant_id" value="<?php echo $grant_id; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                    <select name="role">
                        <option value="CO-PI" <?php echo ($member['role'] == 'CO-PI') ? 'selected' : ''; ?>>CO-PI</option>
                        <option value="viewer" <?php echo ($member['role'] == 'viewer') ? 'selected' : ''; ?>>Viewer</option>
                    </select>
                    <button type="submit">Update</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

<br>
<?php
include 'footer.php';
?>