<?php
include __DIR__ . '/../header.php';
require __DIR__ . '/../src/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$grant_id = filter_input(INPUT_GET, 'grant_id', FILTER_VALIDATE_INT);
if (!$grant_id) {
    die("Invalid grant ID.");
}

if (isset($_GET['update'])) {
    $item_id = filter_input(INPUT_GET, 'update', FILTER_VALIDATE_INT);
    $year_column = filter_input(INPUT_POST, 'year_column', FILTER_DEFAULT);
    $year_column = is_string($year_column) ? trim($year_column) : null;
    $year_value = filter_input(INPUT_POST, 'year_value', FILTER_VALIDATE_FLOAT);
    if ($year_value !== false) { $year_value = (float)$year_value; }

    if ($item_id && $year_column && $year_value !== false) {
        // Ensure the year_column matches the expected format
        if (preg_match('/^year_\d+$/', $year_column)) {
            $stmt = $pdo->prepare("UPDATE budget_items SET $year_column = :val WHERE id = :id AND grant_id = :gid");
            $stmt->execute([':val' => $year_value, ':id' => $item_id, ':gid' => $grant_id]);
            header("Location: add_budget.php?grant_id=$grant_id&updated=1");
            exit();
        }
    }
}


if (isset($_GET['delete'])) {
    $item_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($item_id) {
        $stmt = $pdo->prepare("DELETE FROM budget_items WHERE id = :id AND grant_id = :gid");
        $stmt->execute([':id' => $item_id, ':gid' => $grant_id]);
        header("Location: add_budget.php?grant_id=$grant_id");
        exit();
    }
}

$stmt = $pdo->prepare("SELECT * FROM grants WHERE id = :id");
$stmt->execute([':id' => $grant_id]);
$grant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grant) {
    die("Grant not found.");
}

$title = $grant['title'];
$years = min($grant['duration_in_years'], 6);
$total_budget = $grant['total_amount'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

    $stmt = $pdo->prepare("SELECT category_name FROM budget_categories WHERE id = :id");
    $stmt->execute([':id' => $category_id]);
    $category_name = $stmt->fetchColumn();

    if (!$category_name) {
        die("Invalid category.");
    }

    if ($category_name === "Fringe" && isset($_POST['fringe_option'])) {
        $description = $_POST['fringe_option'];
    } elseif ($category_name === "Personnel Compensation" && isset($_POST['personnel_option'])) {
        $description = $_POST['personnel_option'];
    } elseif ($category_name === "Other Personnel" && isset($_POST['other_personnel_option'])) {
        $description = $_POST['other_personnel_option'];
    } else {
        $description = isset($_POST['description']) ? $_POST['description'] : '';
    }

    $yearly_amounts = [];
    $total_amount = 0;

    for ($year = 1; $year <= $years; $year++) {
        $yearly_amounts["year_$year"] = isset($_POST["year$year"]) ? floatval($_POST["year$year"]) : 0;
    }
    for ($y = 1; $y <= 6; $y++) {
        if (!isset($yearly_amounts["year_$y"])) { $yearly_amounts["year_$y"] = 0.0; }
    }
    $total_amount = array_sum($yearly_amounts);

    $hourly_rate = isset($_POST['hourly_rate']) ? (float)$_POST['hourly_rate'] : 0.0;

    $sql = "INSERT INTO budget_items
            (grant_id, category_id, description, hourly_rate, year_1, year_2, year_3, year_4, year_5, year_6, amount)
            VALUES (:grant_id, :category_id, :description, :hourly_rate, :y1, :y2, :y3, :y4, :y5, :y6, :amount)";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        ':grant_id' => $grant_id,
        ':category_id' => $category_id,
        ':description' => $description,
        ':hourly_rate' => $hourly_rate,
        ':y1' => $yearly_amounts['year_1'],
        ':y2' => $yearly_amounts['year_2'],
        ':y3' => $yearly_amounts['year_3'],
        ':y4' => $yearly_amounts['year_4'],
        ':y5' => $yearly_amounts['year_5'],
        ':y6' => $yearly_amounts['year_6'],
        ':amount' => $total_amount
    ]);
    if ($ok) {
        header("Location: add_budget.php?grant_id=$grant_id");
        exit();
    } else {
        $errInfo = $stmt->errorInfo();
        echo "Error: " . ($errInfo[2] ?? 'unknown');
    }
}

$categories = $pdo->query("SELECT * FROM budget_categories");
$itemsStmt = $pdo->prepare("SELECT * FROM budget_items WHERE grant_id = :gid");
$itemsStmt->execute([':gid' => $grant_id]);
$items = $itemsStmt;
?>

<h1 style="font-family: Arial, sans-serif; text-align: center; color: #333; font-size: 1.8em; margin-top: 20px;">Add Budget for <?php echo htmlspecialchars($title); ?></h1>
<p style="font-family: Arial, sans-serif; text-align: center; color: #333; margin-top: 20px;">You can either delete all the rows and start fresh or update each cell individually, though that can be a tedious process.</p>

<form action="add_budget.php?grant_id=<?php echo $grant_id; ?>" method="POST" style="width: 100%; max-width: 500px; margin: auto; font-family: Arial, sans-serif; font-size: 1em; padding: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 8px;">
    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Category:</label>
    <select name="category_id" id="category-select" required onchange="handleCategoryChange()" style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px;">
        <option value="">Select Category</option>
        <?php while($cat = $categories->fetch(PDO::FETCH_ASSOC)): ?>
            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
        <?php endwhile; ?>
    </select>

    <div id="description-input" style="display: block; margin-bottom: 15px;">
        <label style="font-weight: bold;">Description:</label>
        <input type="text" name="description" style="width: 96%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
    </div>

    <div id="fringe-options" style="display: none; margin-bottom: 15px;">
        <label style="font-weight: bold;">Fringe Type:</label>
        <select name="fringe_option" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <option value="Faculty">Faculty</option>
            <option value="UI professional staff & Post Docs">UI professional staff & Post Docs</option>
            <option value="GRAs/UGrads">GRAs/UGrads</option>
            <option value="Temp Help">Temp Help</option>
        </select>
    </div>

    <div id="personnel-compensation-options" style="display: none; margin-bottom: 15px;">
        <label style="font-weight: bold;">Personnel Type:</label>
        <select name="personnel_option" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <option value="PI">PI</option>
            <option value="Co-PI">Co-PI</option>
        </select>
    </div>

    <div id="other-personnel-options" style="display: none; margin-bottom: 15px;">
        <label style="font-weight: bold;">Other Personnel Type:</label>
        <select name="other_personnel_option" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <option value="UI professional staff & Post Docs">UI professional staff & Post Docs</option>
            <option value="GRAs/UGrads">GRAs/UGrads</option>
            <option value="Temp Help">Temp Help</option>
        </select>
    </div>

    <?php for ($year = 1; $year <= $years; $year++): ?>
        <label style="font-weight: bold;">Year <?php echo $year; ?> :</label>
        <input type="number" step="0.01" name="year<?php echo $year; ?>" required style="width: 96%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px;">
    <?php endfor; ?>

    <input type="submit" value="Add Budget Item" style="width: 100%; padding: 12px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
</form>

<h2 style="font-family: Arial, sans-serif; text-align: center; color: #333; font-size: 1.5em; margin-top: 30px;">Budget Items</h2>
<table style="width: 90%; max-width: 800px; margin: auto; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 0.9em; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden;">
    <tr style="background-color: #f2f2f2;">
        <th style="border: 1px solid #ddd; padding: 12px; font-weight: bold;">Description</th>
        <?php for ($year = 1; $year <= $years; $year++): ?>
            <th style="border: 1px solid #ddd; padding: 12px; font-weight: bold;">Year <?php echo $year; ?></th>
        <?php endfor; ?>
        <th style="border: 1px solid #ddd; padding: 12px; font-weight: bold;">Action</th>
    </tr>
    <?php while ($item = $items->fetch(PDO::FETCH_ASSOC)): ?>
    <tr>
        <td style="border: 1px solid #ddd; padding: 12px;"><?php echo htmlspecialchars($item['description']); ?></td>
        <?php for ($year = 1; $year <= $years; $year++): ?>
            <td style="border: 1px solid #ddd; padding: 12px;">
                <form action="add_budget.php?grant_id=<?php echo $grant_id; ?>&update=<?php echo $item['id']; ?>" method="POST" style="margin: 0; display: flex;">
                    <input type="hidden" name="year_column" value="year_<?php echo $year; ?>">
                    <input type="number" step="0.01" name="year_value" value="<?php echo htmlspecialchars(number_format((float)$item["year_$year"], 2, '.', ''), ENT_QUOTES); ?>" style="width: 60px; padding: 5px;">
                    <button type="submit" style="background-color: #4CAF50; color: white; border: none; padding: 5px 10px; cursor: pointer;">Update</button>
                </form>
            </td>
        <?php endfor; ?>
        <td style="border: 1px solid #ddd; padding: 12px;"><a href="add_budget.php?grant_id=<?php echo $grant_id; ?>&delete=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure you want to delete this item?');" style="color: #f44336; text-decoration: none; font-weight: bold;">Delete</a></td>
    </tr>
<?php endwhile; ?>

</table>

<script>
    function handleCategoryChange() {
        const categorySelect = document.getElementById('category-select');
        const descriptionInput = document.getElementById('description-input');
        const fringeOptions = document.getElementById('fringe-options');
        const personnelCompensationOptions = document.getElementById('personnel-compensation-options');
        const otherPersonnelOptions = document.getElementById('other-personnel-options');
        const selectedCategoryText = categorySelect.options[categorySelect.selectedIndex].text;
        
        descriptionInput.style.display = 'none';
        fringeOptions.style.display = 'none';
        personnelCompensationOptions.style.display = 'none';
        otherPersonnelOptions.style.display = 'none';

        if (selectedCategoryText === 'Fringe') {
            fringeOptions.style.display = 'block';
        } else if (selectedCategoryText === 'Personnel Compensation') {
            personnelCompensationOptions.style.display = 'block';
        } else if (selectedCategoryText === 'Other Personnel') {
            otherPersonnelOptions.style.display = 'block';
        } else {
            descriptionInput.style.display = 'block';
        }
    }
</script>

<br>
<hr>
<?php
include __DIR__ . '/../footer.php';
?>
