<?php
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "
    SELECT g.*, gu.role 
    FROM grants g
    JOIN grant_users gu ON g.id = gu.grant_id
    WHERE gu.user_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$grants = $stmt->get_result();

if (!$grants) {
    die("Error fetching grants: " . $conn->error);
}
?>

<h2>Your Grants</h2>
<ul>
    <?php while ($row = $grants->fetch_assoc()): ?>
        <li style="background-color: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 10px; 
    display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);">

            <div style="font-weight: bold; font-size: 18px; color: #2c3e50;"><?php echo $row['title']; ?></div>
            
            <div style="display: flex; gap: 10px;">
                <span style="color: #7f8c8d; font-size: 16px; margin-top: 8px;"><?php echo '(' . $row['agency'] . ')'; ?></span>

                <?php if ($row['role'] === 'PI'): ?>
                    <a href="add_budget.php?grant_id=<?php echo $row['id']; ?>" style="text-decoration: none; padding: 8px 12px; 
            background-color: #3498db; color: white; border-radius: 5px; font-size: 14px;">Manage Budget</a>
                <?php endif; ?>

                <?php if ($row['role'] === 'PI' || $row['role'] === 'CO-PI'): ?>
                    <a href="download.php?grant_id=<?php echo $row['id']; ?>" style="text-decoration: none; padding: 8px 12px; 
            background-color: #2ecc71; color: white; border-radius: 5px; font-size: 14px;">Download as Excel</a>
                <?php endif; ?>

                <?php if ($row['role'] === 'PI'): ?>
                    <a href="delete_grant.php?grant_id=<?php echo $row['id']; ?>"
                        onclick="return confirm('Are you sure you want to remove this grant?');" style="text-decoration: none; padding: 8px 12px; 
               background-color: #e74c3c; color: white; border-radius: 5px; font-size: 14px;">Remove</a>
                <?php endif; ?>
            </div>
        </li>
    <?php endwhile; ?>
</ul>

<?php
include 'footer.php';
?>
