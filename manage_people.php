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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_ids'])) {
    $selected_user_ids = $_POST['user_ids'];
    $selected_roles = $_POST['roles'];

    $conn->begin_transaction();

    try {
        $stmtGrantUser = $conn->prepare("INSERT INTO grant_users (grant_id, user_id, role, status) VALUES (?, ?, ?, ?)");
        if (!$stmtGrantUser) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        foreach ($selected_user_ids as $index => $selected_user_id) {
            $role = $selected_roles[$index];
            $status = ($role === 'PI' || $role === 'CO-PI') ? 'pending' : 'accepted';

            $checkQuery = "SELECT COUNT(*) FROM grant_users WHERE grant_id = ? AND user_id = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param('ii', $grant_id, $selected_user_id);
            $checkStmt->execute();
            $checkStmt->bind_result($count);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($count > 0) {
                continue;
            }

            $stmtGrantUser->bind_param('iiss', $grant_id, $selected_user_id, $role, $status);
            $stmtGrantUser->execute();

            if ($status === 'pending') {
                $message = "You have been invited to join the grant: $title as a $role.";
                $stmtNotification = $conn->prepare("INSERT INTO notifications (user_id, message, grant_id) VALUES (?, ?, ?)");
                if (!$stmtNotification) {
                    throw new Exception("Error preparing notification statement: " . $conn->error);
                }
                $stmtNotification->bind_param('isi', $selected_user_id, $message, $grant_id);
                $stmtNotification->execute();
            }
        }

        $conn->commit();
        $_SESSION['success_message'] = "Users added successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }

    header("Location: manage_people.php?grant_id=$grant_id");
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

<!-- Current Members Table -->
<table style="width: 50%; margin: 20px auto; border-collapse: collapse; font-family: Arial, sans-serif;">
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
                <form action="update_role.php" method="POST" style="display: inline;">
                    <input type="hidden" name="grant_id" value="<?php echo $grant_id; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                    <select name="role" style="padding: 5px;">
                        <option value="CO-PI" <?php echo ($member['role'] == 'CO-PI') ? 'selected' : ''; ?>>CO-PI</option>
                        <option value="viewer" <?php echo ($member['role'] == 'viewer') ? 'selected' : ''; ?>>Viewer</option>
                    </select>
                    <button type="submit" name="action" value="update" style="padding: 5px; background-color: #4CAF50; color: white; border: none; cursor: pointer;">Update</button>
                    <button type="submit" name="action" value="delete" style="padding: 5px; background-color: red; color: white; border: none; cursor: pointer;">Delete</button>
                </form>
        </tr>
    <?php endwhile; ?>
</table>

<h3 style="text-align: center; font-family: Arial, sans-serif; color: #333; margin-top: 40px;">Add Users to Grant</h3>
<form id="addUserForm" method="POST" style="max-width: 600px; margin: 20px auto; font-family: Arial, sans-serif;">
    <input type="hidden" name="grant_id" value="<?php echo $grant_id; ?>">

    <label for="user_search" style="display: block; font-size: 16px; color: #333; margin-bottom: 8px;">Search and Add Users:</label>
    <input type="text" id="user_search" placeholder="Type to search users..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 15px;">

    <div id="user_search_results" style="border: 1px solid #ddd; max-height: 200px; overflow-y: auto; background: #f9f9f9; display: none; padding: 10px;">
        <!-- Search results will appear here -->
    </div>

    <div id="selected_users" style="margin-top: 15px;">
        <h4 style="font-family: Arial, sans-serif; color: #333;">Selected Users and Assign Roles:</h4>
        <!-- Selected users will appear here -->
    </div>

    <button type="submit" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin-top: 15px;">Add Users</button>
</form>

<script>
    const userSearch = document.getElementById('user_search');
    const searchResults = document.getElementById('user_search_results');
    const selectedUsersContainer = document.getElementById('selected_users');

    userSearch.addEventListener('input', () => {
        const query = userSearch.value.trim();
        if (query.length > 1) {
            fetch(`search_users.php?query=${query}`)
                .then(response => response.text())
                .then(data => {
                    searchResults.innerHTML = data;
                    searchResults.style.display = 'block';
                });
        } else {
            searchResults.style.display = 'none';
        }
    });

    function addUser(userId, username) {
        if (document.getElementById(`user_${userId}`)) return;

        const userContainer = document.createElement('div');
        userContainer.id = `user_${userId}`;
        userContainer.style.marginBottom = '10px';
        userContainer.innerHTML = `
            <input type="hidden" name="user_ids[]" value="${userId}">
            ${username}
            <select name="roles[]" style="margin-left: 10px; font-size: 0.9em;">
                <option value="CO-PI">CO-PI</option>
                <option value="viewer">Viewer</option>
            </select>
            <button type="button" onclick="removeUser(${userId})" style="margin-left: 10px; padding: 5px; background-color: #f44336; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove</button>
        `;
        selectedUsersContainer.appendChild(userContainer);

        searchResults.style.display = 'none';
        userSearch.value = '';
    }

    function removeUser(userId) {
        const userElement = document.getElementById(`user_${userId}`);
        if (userElement) userElement.remove();
    }
</script>

<?php include 'footer.php'; ?>