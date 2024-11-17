<?php
include 'header.php';
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    die("Access denied. You do not have permission to access this page.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $year = $_POST['year'] ?? '';
    $fringe_rate = $_POST['fringe_rate'] ?? '';

    if ($role && $year && $fringe_rate) {
        $stmt = $conn->prepare("UPDATE fringe_rates SET fringe_rate = ? WHERE role = ? AND year = ?");
        if (!$stmt) {
            $_SESSION['message'] = "Error preparing statement: " . $conn->error;
            $_SESSION['message_type'] = "error";
        } else {
            $stmt->bind_param('dsi', $fringe_rate, $role, $year);
            $stmt->execute();
            $stmt->close();

            $_SESSION['message'] = "Fringe rate updated successfully for $role in year $year.";
            $_SESSION['message_type'] = "success";
        }
        header("Location: update_fringe_rates.php");
        exit();
    }
}

$fringe_rates = $conn->query("SELECT id, role, year, fringe_rate FROM fringe_rates ORDER BY role, year");

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

<form method="POST" action="update_fringe_rates.php"
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

<form method="POST" action="update_fringe_rates.php" style="max-width: 600px; margin: 0 auto;">
    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <tr>
            <th style="border: 1px solid #ddd; padding: 8px; background-color: #f4f4f4;">Role</th>
            <th style="border: 1px solid #ddd; padding: 8px; background-color: #f4f4f4;">Year</th>
            <th style="border: 1px solid #ddd; padding: 8px; background-color: #f4f4f4;">Fringe Rate</th>
        </tr>

        <?php while ($row = $fringe_rates->fetch_assoc()): ?>
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($row['role']); ?></td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                    <?php echo htmlspecialchars($row['year']); ?></td>
                <td style="border: 1px solid #ddd; padding: 8px;">
                    <input type="number" step="0.01" name="fringe_rate[<?php echo $row['id']; ?>]"
                        value="<?php echo htmlspecialchars($row['fringe_rate']); ?>"
                        style="width: 100%; padding: 5px; box-sizing: border-box;">
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <div style="text-align: center; margin-top: 20px;">
        <button type="submit"
            style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">Update
            Rates</button>
    </div>
</form>

<hr>
<?php
include 'footer.php';
?>
