<?php
require __DIR__ . '/../src/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$is_admin = (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true)
         || ((int)$_SESSION['user_id'] === 1);
if (!$is_admin) {
    http_response_code(403);
    die('Access denied. You do not have permission to access this page.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function flash_redirect(string $msg, string $type = 'info', string $to = 'update_salaries.php') : void {
    $_SESSION['message'] = $msg;
    $_SESSION['message_type'] = $type;
    header('Location: ' . $to);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        flash_redirect('Invalid form token. Please try again.', 'danger');
    }

    $role        = filter_input(INPUT_POST, 'role', FILTER_DEFAULT);
    $role        = is_string($role) ? trim($role) : '';
    $year        = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    $hourly_rate = filter_input(INPUT_POST, 'hourly_rate', FILTER_VALIDATE_FLOAT);

    $bulk = $_POST['hourly_rate'] ?? null;

    try {
        if ($role !== '' && $year && $hourly_rate !== false && $hourly_rate !== null) {
            if ($hourly_rate < 0 || $hourly_rate > 10000) {
                flash_redirect('Hourly rate must be between 0 and 10,000.', 'warning');
            }
            $stmt = $pdo->prepare('UPDATE salaries SET hourly_rate = :rate WHERE role = :role AND year = :year');
            $stmt->execute([':rate' => (float)$hourly_rate, ':role' => $role, ':year' => (int)$year]);
            flash_redirect('Hourly rate updated successfully for ' . htmlspecialchars($role, ENT_QUOTES) . ' in year ' . (int)$year . '.', 'success');
        }

        if (is_array($bulk)) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE salaries SET hourly_rate = :rate WHERE id = :id');
            foreach ($bulk as $id => $val) {
                $id = (int)$id;
                if ($id <= 0) { continue; }
                if ($val === '' || $val === null) { continue; }
                $rate = filter_var($val, FILTER_VALIDATE_FLOAT);
                if ($rate === false) { continue; }
                if ($rate < 0 || $rate > 10000) { continue; }
                $stmt->execute([':rate' => (float)$rate, ':id' => $id]);
            }
            $pdo->commit();
            flash_redirect('Hourly rates updated successfully.', 'success');
        }

        flash_redirect('No changes submitted.', 'info');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        flash_redirect('Error updating hourly rates: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES), 'danger');
    }
}

$salaries = $pdo->query('SELECT id, role, year, hourly_rate FROM salaries ORDER BY role, year')->fetchAll(PDO::FETCH_ASSOC);

$roles = ['PI', 'Co-PI', 'Faculty', 'GRAs/UGrads', 'Temp Help', 'UI professional staff & Post Docs'];
$years = [1, 2, 3, 4, 5, 6];

$rootHeader  = dirname(__DIR__) . '/header.php';
$localHeader = __DIR__ . '/header.php';
if (file_exists($rootHeader)) {
    include $rootHeader;
} elseif (file_exists($localHeader)) {
    include $localHeader;
} else {
    trigger_error('header.php not found', E_USER_WARNING);
}
?>

<h2 style="color: #333; text-align: center;">Update Hourly Rates</h2>

<form method="POST" action="update_salaries.php"
      style="max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; margin-bottom: 20px;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">

    <label for="role" style="display: block; margin-bottom: 8px; font-weight: bold;">Role:</label>
    <select name="role" id="role" required
            style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;">
        <option value="">Select Role</option>
        <?php foreach ($roles as $r): ?>
            <option value="<?= htmlspecialchars($r, ENT_QUOTES) ?>"><?= htmlspecialchars($r) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="year" style="display: block; margin-bottom: 8px; font-weight: bold;">Year:</label>
    <select name="year" id="year" required
            style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;">
        <option value="">Select Year</option>
        <?php foreach ($years as $y): ?>
            <option value="<?= (int)$y ?>"><?= (int)$y ?></option>
        <?php endforeach; ?>
    </select>

    <label for="hourly_rate" style="display: block; margin-bottom: 8px; font-weight: bold;">Hourly Rate:</label>
    <input type="number" step="0.01" min="0" max="10000" name="hourly_rate" id="hourly_rate" required placeholder="Enter new hourly rate"
           style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;">

    <div style="text-align: center;">
        <button type="submit"
                style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">Update Rate</button>
    </div>
</form>

<br>

<form method="POST" action="update_salaries.php" style="max-width: 700px; margin: 0 auto;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">

    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <tr>
            <th style="border: 1px solid #ddd; padding: 8px; background-color: #f4f4f4;">Role</th>
            <th style="border: 1px solid #ddd; padding: 8px; background-color: #f4f4f4;">Year</th>
            <th style="border: 1px solid #ddd; padding: 8px; background-color: #f4f4f4;">Hourly Rate</th>
        </tr>

        <?php foreach ($salaries as $row): ?>
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($row['role']) ?></td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;"><?= (int)$row['year'] ?></td>
                <td style="border: 1px solid #ddd; padding: 8px;">
                    <input type="number" step="0.01" min="0" max="10000" name="hourly_rate[<?= (int)$row['id'] ?>]"
                           value="<?= htmlspecialchars((string)$row['hourly_rate']) ?>"
                           style="width: 100%; padding: 5px; box-sizing: border-box;">
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div style="text-align: center; margin-top: 20px;">
        <button type="submit"
                style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">Update Rates</button>
    </div>
</form>

<hr>
<?php
$rootFooter  = dirname(__DIR__) . '/footer.php';
$localFooter = __DIR__ . '/footer.php';
if (file_exists($rootFooter)) {
    include $rootFooter;
} elseif (file_exists($localFooter)) {
    include $localFooter;
} else {
    trigger_error('footer.php not found', E_USER_WARNING);
}
?>