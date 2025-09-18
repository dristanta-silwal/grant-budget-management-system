<?php
include __DIR__ . '/../header.php';
require __DIR__ . '/../src/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['grant_id']) || empty($_GET['grant_id'])) {
    echo "<p>Error: Grant ID not provided. Please go back and select a valid grant to manage.</p>";
    exit();
}

$grant_id = filter_input(INPUT_GET, 'grant_id', FILTER_VALIDATE_INT);
if (!$grant_id) {
    echo "<p>Error: Invalid grant ID.</p>";
    exit();
}
$user_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT title FROM grants WHERE id = :gid');
$stmt->execute([':gid' => $grant_id]);
$grant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grant) {
    echo "<p>Error: Grant not found. The grant ID provided does not exist.</p>";
    exit();
}

$title = $grant['title'];

$stmt = $pdo->prepare("SELECT 1 FROM grant_users WHERE grant_id = :gid AND user_id = :uid AND role IN ('PI','CO-PI')");
$stmt->execute([':gid' => $grant_id, ':uid' => $user_id]);
if (!$stmt->fetch(PDO::FETCH_NUM)) {
    echo "<p>You don't have permission to manage this grant.</p>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_ids'])) {
    $selected_user_ids = isset($_POST['user_ids']) && is_array($_POST['user_ids']) ? $_POST['user_ids'] : [];
    $selected_roles    = isset($_POST['roles'])    && is_array($_POST['roles'])    ? $_POST['roles']    : [];

    if (count($selected_user_ids) !== count($selected_roles)) {
        $_SESSION['error_message'] = 'Mismatched users/roles.';
        header("Location: manage_people.php?grant_id=$grant_id");
        exit();
    }

    try {
        $pdo->beginTransaction();

        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM grant_users WHERE grant_id = :gid AND user_id = :uid');
        $insertGU  = $pdo->prepare('INSERT INTO grant_users (grant_id, user_id, role, status) VALUES (:gid, :uid, :role, :status)');
        $insertNot = $pdo->prepare('INSERT INTO notifications (user_id, message, grant_id) VALUES (:uid, :message, :gid)');

        foreach ($selected_user_ids as $index => $selected_user_id) {
            $uid  = (int)$selected_user_id;
            $role = isset($selected_roles[$index]) ? trim((string)$selected_roles[$index]) : 'viewer';
            $status = (in_array($role, ['PI','CO-PI'], true)) ? 'pending' : 'accepted';

            $checkStmt->execute([':gid' => $grant_id, ':uid' => $uid]);
            $exists = (int)$checkStmt->fetchColumn();
            if ($exists > 0) {
                continue; // already a member
            }

            $insertGU->execute([':gid' => $grant_id, ':uid' => $uid, ':role' => $role, ':status' => $status]);

            if ($status === 'pending') {
                $message = "You have been invited to join the grant: {$title} as a {$role}.";
                $insertNot->execute([':uid' => $uid, ':message' => $message, ':gid' => $grant_id]);
            }
        }

        $pdo->commit();
        $_SESSION['success_message'] = 'Users added successfully!';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    }

    header("Location: manage_people.php?grant_id=$grant_id");
    exit();
}

$stmt = $pdo->prepare("
        SELECT u.id, u.username, gu.role, gu.status
        FROM users u
        JOIN grant_users gu ON u.id = gu.user_id
        WHERE gu.grant_id = :gid AND gu.status IN ('accepted','pending')
    ");
$stmt->execute([':gid' => $grant_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <?php foreach ($members as $member): ?>
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
    <?php endforeach; ?>
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

<?php include __DIR__ . '/../footer.php'; ?>