<?php
include __DIR__ . '/../header.php';
require __DIR__ . '/../src/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    die("Access denied. You do not have permission to access this page.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Single update (role + year + fringe_rate)
    $role        = filter_input(INPUT_POST, 'role', FILTER_DEFAULT);
    $role        = is_string($role) ? trim($role) : '';
    $year        = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    $fringe_rate = filter_input(INPUT_POST, 'fringe_rate', FILTER_VALIDATE_FLOAT);

    // Bulk updates via fringe_rate[id] => value
    $bulk = $_POST['fringe_rate'] ?? null;

    try {
        if ($role !== '' && $year && $fringe_rate !== false && $fringe_rate !== null) {
            $stmt = $pdo->prepare('UPDATE fringe_rates SET fringe_rate = :rate WHERE role = :role AND year = :year');
            $stmt->execute([':rate' => (float)$fringe_rate, ':role' => $role, ':year' => (int)$year]);

            $_SESSION['message'] = "Fringe rate updated successfully for " . htmlspecialchars($role, ENT_QUOTES) . " in year " . (int)$year . ".";
            $_SESSION['message_type'] = 'success';
            header('Location: update_fringes.php');
            exit();
        }

        if (is_array($bulk)) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE fringe_rates SET fringe_rate = :rate WHERE id = :id');
            foreach ($bulk as $id => $val) {
                $id  = (int)$id;
                if ($id <= 0) { continue; }
                // Accept empty string as skip; otherwise coerce to float
                if ($val === '' || $val === null) { continue; }
                $rate = filter_var($val, FILTER_VALIDATE_FLOAT);
                if ($rate === false) { continue; }
                $stmt->execute([':rate' => (float)$rate, ':id' => $id]);
            }
            $pdo->commit();

            $_SESSION['message'] = 'Fringe rates updated successfully.';
            $_SESSION['message_type'] = 'success';
            header('Location: update_fringes.php');
            exit();
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $_SESSION['message'] = 'Error updating fringe rates: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES);
        $_SESSION['message_type'] = 'error';
        header('Location: update_fringes.php');
        exit();
    }
}

$fringe_rates = $pdo->query("SELECT id, role, year, fringe_rate FROM fringe_rates ORDER BY role, year")->fetchAll(PDO::FETCH_ASSOC);

$roles = ['PI', 'Co-PI', 'Faculty', 'GRAs/UGrads', 'Temp Help', 'UI professional staff & Post Docs'];
$years = [1, 2, 3, 4, 5, 6];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Update Fringe Rates</title>
</head>

<h2 style="color: #333; text-align: center;">Update Fringe Rates</h2>

<form method="POST" action="update_fringes.php"
    style="max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; margin-bottom: 20px;">
    <label for="role" style="display: block; margin-bottom: 8px; font-weight: bold;">Role:</label>
    <select name="role" id="role" required
        style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;">
        <option value="">Select Role</option>
        <?php foreach ($roles as $role): ?>
            <option value="<?php echo htmlspecialchars($role); ?>"><?php echo htmlspecialchars($role); ?></option>
        <?php endforeach; ?>
    </select>

    <label for="year" style="display: block; margin-bottom: 8px; font-weight: bold;">Year:</label>
    <select name="year" id="year" required
        style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;">
        <option value="">Select Year</option>
        <?php foreach ($years as $year): ?>
            <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
        <?php endforeach; ?>
    </select>

    <label for="fringe_rate" style="display: block; margin-bottom: 8px; font-weight: bold;">Fringe Rate:</label>
    <input type="number" step="0.01" name="fringe_rate" id="fringe_rate" required placeholder="Enter new fringe rate"
        style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;">

    <div style="text-align: center;">
        <button type="submit"
            style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">Update
            Rate</button>
    </div>
</form>

<br>

<form method="POST" action="update_fringes.php" style="max-width: 600px; margin: 0 auto;">
    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <tr>
            <th style="border: 1px solid #ddd; padding: 8px; background-color: #f4f4f4;">Role</th>
            <th style="border: 1px solid #ddd; padding: 8px; background-color: #f4f4f4;">Year</th>
            <th style="border: 1px solid #ddd; padding: 8px; background-color: #f4f4f4;">Fringe Rate</th>
        </tr>

        <?php foreach ($fringe_rates as $row): ?>
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($row['role']); ?></td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;"><?php echo htmlspecialchars((string)$row['year']); ?></td>
                <td style="border: 1px solid #ddd; padding: 8px;">
                    <input type="number" step="0.01" name="fringe_rate[<?php echo (int)$row['id']; ?>]"
                        value="<?php echo htmlspecialchars((string)$row['fringe_rate']); ?>"
                        style="width: 100%; padding: 5px; box-sizing: border-box;">
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div style="text-align: center; margin-top: 20px;">
        <button type="submit"
            style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">Update
            Rates</button>
    </div>
</form>

<hr>
<?php
include __DIR__ . '/../footer.php';
?>
