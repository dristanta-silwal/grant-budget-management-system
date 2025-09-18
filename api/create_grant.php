<?php
include __DIR__ . '/../header.php';
require __DIR__ . '/../src/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic input handling
    $title        = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
    $agency       = isset($_POST['agency']) ? trim((string)$_POST['agency']) : '';
    $start_date   = isset($_POST['start_date']) ? trim((string)$_POST['start_date']) : '';
    $duration     = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
    $total_amount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0.0;

    if ($title === '' || $agency === '' || $start_date === '' || $duration <= 0) {
        echo "<p style='color:red;'>Invalid form input.</p>";
    } else {
        // Compute end_date
        $start = new DateTime($start_date);
        $start->modify("+{$duration} years");
        $end_date = $start->format('Y-m-d');

        // Selected users/roles (optional)
        $selected_user_ids = isset($_POST['user_ids']) && is_array($_POST['user_ids']) ? $_POST['user_ids'] : [];
        $selected_roles    = isset($_POST['roles'])    && is_array($_POST['roles'])    ? $_POST['roles']    : [];

        try {
            $pdo->beginTransaction();

            // Insert grant and RETURNING id (Postgres)
            $stmt = $pdo->prepare(
                "INSERT INTO grants (title, agency, start_date, end_date, duration_in_years, total_amount, user_id)
                 VALUES (:title, :agency, :start_date, :end_date, :duration, :total_amount, :user_id)
                 RETURNING id"
            );
            $stmt->execute([
                ':title'        => $title,
                ':agency'       => $agency,
                ':start_date'   => $start_date,
                ':end_date'     => $end_date,
                ':duration'     => $duration,
                ':total_amount' => $total_amount,
                ':user_id'      => $user_id,
            ]);
            $grant_id = (int)$stmt->fetchColumn();

            // Ensure creator is added as PI (accepted)
            $stmtGrantUser = $pdo->prepare(
                "INSERT INTO grant_users (grant_id, user_id, role, status)
                 VALUES (:gid, :uid, :role, 'accepted')"
            );
            $stmtGrantUser->execute([
                ':gid'  => $grant_id,
                ':uid'  => $user_id,
                ':role' => 'PI'
            ]);

            // Invite/add selected users
            for ($idx = 0; $idx < count($selected_user_ids); $idx++) {
                $selected_user_id = (int)$selected_user_ids[$idx];
                $role             = isset($selected_roles[$idx]) ? trim((string)$selected_roles[$idx]) : 'viewer';
                $status           = (in_array($role, ['PI', 'CO-PI'], true)) ? 'pending' : 'accepted';

                $stmtAdd = $pdo->prepare(
                    "INSERT INTO grant_users (grant_id, user_id, role, status)
                     VALUES (:gid, :uid, :role, :status)"
                );
                $stmtAdd->execute([
                    ':gid'    => $grant_id,
                    ':uid'    => $selected_user_id,
                    ':role'   => $role,
                    ':status' => $status,
                ]);

                if ($status === 'pending') {
                    $message = "You have been invited to join the grant: {$title} as a {$role}.";
                    $stmtNotification = $pdo->prepare(
                        "INSERT INTO notifications (user_id, message, grant_id)
                         VALUES (:uid, :message, :gid)"
                    );
                    $stmtNotification->execute([
                        ':uid'     => $selected_user_id,
                        ':message' => $message,
                        ':gid'     => $grant_id,
                    ]);
                }
            }

            // Insert default budget items
            $defaultHourlyRate   = 0.0;
            $defaultYears        = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0, 5 => 0.0, 6 => 0.0];
            $defaultTotalAmount  = 0.0;

            $stmtBudget = $pdo->prepare(
                "INSERT INTO budget_items
                 (grant_id, category_id, description, hourly_rate, year_1, year_2, year_3, year_4, year_5, year_6, amount)
                 VALUES (:gid, :cat, :desc, :rate, :y1, :y2, :y3, :y4, :y5, :y6, :amt)"
            );

            for ($i = 1; $i <= 8; $i++) {
                if ($i === 3) { // skip category 3
                    continue;
                }

                $itemCount = 1;
                if     ($i === 1) { $itemCount = 2; }
                elseif ($i === 2) { $itemCount = 3; }

                for ($j = 1; $j <= $itemCount; $j++) {
                    switch ($i) {
                        case 1:
                            $defaultDescription = ($j === 1) ? 'PI' : 'Co-PI';
                            break;
                        case 2:
                            $defaultDescription = ($j === 1) ? 'UI professional staff & Post Docs'
                                : (($j === 2) ? 'GRAs/UGrads' : 'Temp Help');
                            break;
                        case 4: $defaultDescription = 'Large Servers'; break;
                        case 5: $defaultDescription = 'Domestic Travel'; break;
                        case 6: $defaultDescription = 'Materials and Supplies'; break;
                        case 7: $defaultDescription = 'Grant of Idaho State University'; break;
                        case 8: $defaultDescription = 'Back Out GRA T&F'; break;
                        default: $defaultDescription = "Initial Budget Item {$i}"; break;
                    }

                    $stmtBudget->execute([
                        ':gid'  => $grant_id,
                        ':cat'  => $i,
                        ':desc' => $defaultDescription,
                        ':rate' => $defaultHourlyRate,
                        ':y1'   => $defaultYears[1],
                        ':y2'   => $defaultYears[2],
                        ':y3'   => $defaultYears[3],
                        ':y4'   => $defaultYears[4],
                        ':y5'   => $defaultYears[5],
                        ':y6'   => $defaultYears[6],
                        ':amt'  => $defaultTotalAmount,
                    ]);
                }
            }

            $pdo->commit();
            header("Location: index.php");
            exit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES) . "</p>";
        }
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
    document.getElementById('user_search').addEventListener('input', function() {
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
include __DIR__ . '/../footer.php';
?>