<?php
include 'header.php';
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $agency = $_POST['agency'];
    $start_date = $_POST['start_date'];
    $duration = $_POST['duration'];
    $total_amount = $_POST['total_amount'];

    $start = new DateTime($start_date);
    $start->modify("+$duration years");
    $end_date = $start->format('Y-m-d');

    $selected_user_ids = $_POST['user_ids'];
    $selected_roles = $_POST['roles'];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO grants (title, agency, start_date, end_date, duration_in_years, total_amount, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error preparing statement for inserting grant: " . $conn->error);
        }
        $stmt->bind_param('sssidid', $title, $agency, $start_date, $end_date, $duration, $total_amount, $user_id);
        $stmt->execute();
        $grant_id = $stmt->insert_id;

        $stmtGrantUser = $conn->prepare("INSERT INTO grant_users (grant_id, user_id, role, status) VALUES (?, ?, ?, 'accepted')");
        if (!$stmtGrantUser) {
            throw new Exception("Error preparing statement for inserting grant user (creator): " . $conn->error);
        }
        $creator_role = 'PI';
        $stmtGrantUser->bind_param('iis', $grant_id, $user_id, $creator_role);
        $stmtGrantUser->execute();

        foreach ($selected_user_ids as $index => $selected_user_id) {
            $role = $selected_roles[$index];
            $status = ($role == 'PI' || $role == 'CO-PI') ? 'pending' : 'accepted';

            $stmtGrantUser->prepare("INSERT INTO grant_users (grant_id, user_id, role, status) VALUES (?, ?, ?, ?)");
            if (!$stmtGrantUser) {
                throw new Exception("Error preparing statement for inserting grant user (additional users): " . $conn->error);
            }
            $stmtGrantUser->bind_param('iiss', $grant_id, $selected_user_id, $role, $status);
            $stmtGrantUser->execute();

            if ($status == 'pending') {
                $message = "You have been invited to join the grant: $title as a $role.";
                $stmtNotification = $conn->prepare("INSERT INTO notifications (user_id, message, grant_id) VALUES (?, ?, ?)");
                if (!$stmtNotification) {
                    throw new Exception("Error preparing statement for inserting notification: " . $conn->error);
                }
                $stmtNotification->bind_param('isi', $selected_user_id, $message, $grant_id);
                $stmtNotification->execute();
            }
        }

        $conn->commit();
        header("Location: index.php");
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<h1 style="font-family: Arial, sans-serif; text-align: center; color: #333; margin-top: 20px; font-size: 2em;">Create
    New Grant</h1>
<form action="create_grant.php" method="POST"
    style="width: 90%; max-width: 600px; margin: auto; font-family: Arial, sans-serif;">
    <label style="display: block; margin: 10px 0 5px; font-size: 1em;">Title:</label>
    <input type="text" name="title" required
        style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; font-size: 1em;">

    <label style="display: block; margin: 10px 0 5px; font-size: 1em;">Funding Agency:</label>
    <select name="agency" required style="padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; font-size: 1em;">
        <option value="" disabled selected>Select a funding agency</option>
        <option value="NSF">NSF</option>
        <option value="NIH">NIH</option>
    </select>

    <label style="display: block; margin: 10px 0 5px; font-size: 1em;">Start Date:</label>
    <input type="date" name="start_date" id="start_date" required
        style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; font-size: 1em;">

    <label style="display: block; margin: 10px 0 5px; font-size: 1em;">Duration (in years):</label>
    <input type="number" name="duration" id="duration" max="5" required
        style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; font-size: 1em;">

    <label style="display: block; margin: 10px 0 5px; font-size: 1em;">Total Amount:</label>
    <input type="number" step="0.01" name="total_amount" required
        style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; font-size: 1em;">

    <label style="display: block; margin: 10px 0 5px; font-size: 1em;">Search and Add Users:</label>
    <input type="text" id="user_search" placeholder="Type to search users..."
        style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; font-size: 1em;">
    <div id="user_search_results"
        style="min-width: 99% !important; border: 1px solid #ccc; padding: 10px; max-height: 200px; overflow-y: auto; background-color: #f9f9f9; font-size: 1.5em;">
        <!-- Search results will appear here -->
    </div>


    <div id="selected_users" style="margin-top: 20px;">
        <h3 style="font-family: Arial, sans-serif; color: #333; font-size: 1.2em;">Selected Users and Assign Roles:</h3>
    </div>

    <input type="submit" value="Create Grant"
        style="margin-top: 20px; padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer; font-size: 1em;">
</form>

<script>
    document.getElementById('user_search').addEventListener('input', function () {
        const query = this.value;

        if (query.length > 1) {
            fetch(`search_users.php?query=${query}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('user_search_results').innerHTML = data;
                });
        } else {
            document.getElementById('user_search_results').innerHTML = '';
        }
    });

    function addUser(userId, username) {
        if (document.getElementById(`selected_user_${userId}`)) return;

        const userContainer = document.createElement('div');
        userContainer.id = `selected_user_${userId}`;
        userContainer.style.marginBottom = '10px';

        userContainer.innerHTML = `
        <input type="hidden" name="user_ids[]" value="${userId}">
        ${username}
        <select name="roles[]" style="margin-left: 10px; font-size: 0.9em;">
            <option value="CO-PI">CO-PI</option>
            <option value="PI">PI</option>
            <option value="viewer">Viewer</option>
        </select>
        <button type="button" onclick="removeUser(${userId})" style="margin-left: 10px; padding: 5px; background-color: #f44336; color: white; border: none; cursor: pointer; font-size: 0.9em;">Remove</button>
    `;

        document.getElementById('selected_users').appendChild(userContainer);
    }

    function removeUser(userId) {
        const userElement = document.getElementById(`selected_user_${userId}`);
        if (userElement) {
            userElement.remove();
        }
    }
</script>

<hr>
<?php
include 'footer.php';
?>