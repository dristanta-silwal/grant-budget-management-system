<?php
session_start();
include 'db.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$grant_id = $_GET['grant_id'] ?? null;
if (!$grant_id) {
    die("<p style='color: red; font-weight: bold;'>Error: Grant ID is required.</p>");
}

$user_id = $_SESSION['user_id'];
$query = "
    SELECT role 
    FROM grant_users 
    WHERE grant_id = ? AND user_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $grant_id, $user_id);
$stmt->execute();
$user_grant = $stmt->get_result()->fetch_assoc();

if (!$user_grant || $user_grant['role'] !== 'PI') {
    die("<p style='color: red; font-weight: bold;'>Error: You do not have permission to delete this grant.</p>");
}

$grant = $conn->query("SELECT title FROM grants WHERE id = $grant_id")->fetch_assoc();
if (!$grant) {
    die("<p style='color: red; font-weight: bold;'>Error: Grant not found.</p>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $conn->begin_transaction();

    try {
        $delete_items = $conn->prepare("DELETE FROM budget_items WHERE grant_id = ?");
        $delete_items->bind_param("i", $grant_id);
        $delete_items->execute();

        $delete_users = $conn->prepare("DELETE FROM grant_users WHERE grant_id = ?");
        $delete_users->bind_param("i", $grant_id);
        $delete_users->execute();
        $delete_grant = $conn->prepare("DELETE FROM grants WHERE id = ?");
        $delete_grant->bind_param("i", $grant_id);
        $delete_grant->execute();

        $conn->commit();

        echo "<p style='color: green; font-weight: bold;'>Grant deleted successfully.</p>";
        echo "<p><a href='index.php' style='color: #3498db; text-decoration: none; font-weight: bold;'>Return to Grants</a></p>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color: red; font-weight: bold;'>Error deleting grant: " . $e->getMessage() . "</p>";
    }

    exit();
}
?>

<h2 style="color: #333; font-size: 1.5rem; margin-bottom: 1rem;">Delete Grant</h2>
<p style="color: #555; font-size: 1rem; line-height: 1.5;">Are you sure you want to delete the grant titled "<strong><?php echo htmlspecialchars($grant['title']); ?></strong>"? This action cannot be undone and will delete all associated data.</p>

<form method="POST" action="delete_grant.php?grant_id=<?php echo $grant_id; ?>" style="margin-top: 1rem;">
    <input type="hidden" name="confirm_delete" value="yes">
    <button type="submit" style="background-color: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin-right: 10px;">Confirm Delete</button>
    <a href="index.php" style="padding: 10px 20px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">Cancel</a>
</form>

<?php include 'footer.php'; ?>
