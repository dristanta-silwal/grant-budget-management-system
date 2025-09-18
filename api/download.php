<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

ob_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$grant_id = filter_input(INPUT_GET, 'grant_id', FILTER_VALIDATE_INT);
if (!$grant_id) {
    die("Error: Grant ID is required.");
}

$stmt = $pdo->prepare("SELECT title, agency, start_date, duration_in_years FROM grants WHERE id = :id");
$stmt->execute([':id' => $grant_id]);
$grant = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$grant) {
    die("Error: Grant not found.");
}

$stmt = $pdo->prepare("
        SELECT u.username AS name, gu.role
        FROM grant_users gu
        JOIN users u ON gu.user_id = u.id
        WHERE gu.grant_id = :gid AND gu.status = 'accepted' AND gu.role IN ('PI', 'CO-PI')
     ");
$stmt->execute([':gid' => $grant_id]);
$pis = [];
$co_pis = [];
while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($user['role'] === 'PI') {
        $pis[] = $user['name'];
    } elseif ($user['role'] === 'CO-PI') {
        $co_pis[] = $user['name'];
    }
}

$duration = min($grant['duration_in_years'], 6);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Budget');

$headerStyle = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAD3']]
];

$generalInfoStyle = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
];

$categoryStyle = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f2f2f2']]
];
$cellStyle = [];

$sheet->setCellValue('A1', 'Grant Title')->setCellValue('B1', $grant['title']);
$sheet->mergeCells("B1:D1");
$sheet->setCellValue('A2', 'Agency')->setCellValue('B2', $grant['agency']);
$sheet->setCellValue('A3', 'Start Date')->setCellValue('B3', $grant['start_date']);
$sheet->setCellValue('A4', 'Duration (years)')->setCellValue('B4', $duration);
$sheet->setCellValue('A5', 'Principal Investigator(s)')->setCellValue('B5', implode(", ", $pis));
$sheet->mergeCells("B5:C5");
$sheet->setCellValue('D5', 'Co-Principal Investigator(s)')->setCellValue('F5', implode(", ", $co_pis));
$sheet->mergeCells("D5:E5");
$sheet->mergeCells("F5:G5");
$sheet->getStyle('A1:A6')->applyFromArray($generalInfoStyle);
$sheet->getStyle('B1:B6')->applyFromArray($cellStyle);

// Header row setup
$sheet->setCellValue('A7', 'Description');
$sheet->mergeCells("A7:A8");
$sheet->setCellValue('B7', 'Hourly Rate');
$sheet->mergeCells("B7:B8");
for ($year = 1; $year <= $duration; $year++) {
    $sheet->mergeCells(chr(66 + $year) . "7:" . chr(66 + $year) . "8");
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($year + 2) . 7, "Y$year");
}
$sheet->setCellValue(Coordinate::stringFromColumnIndex($duration + 3) . 7, 'Total');
$sheet->mergeCells("G7:G8");
$sheet->getStyle("A7:" . chr(66 + $duration + 1) . "7")->applyFromArray($headerStyle);
$sheet->getStyle("A8:" . chr(66 + $duration + 1) . "8")->applyFromArray($headerStyle);

$row = 9;
$salary_rows = [];
$categoriesStmt = $pdo->query("SELECT * FROM budget_categories WHERE category_name IN ('Personnel Compensation', 'Other Personnel') ORDER BY id");

while ($category = $categoriesStmt->fetch(PDO::FETCH_ASSOC)) {
    $sheet->setCellValue("A$row", $category['category_name']);
    $sheet->mergeCells("A$row:" . chr(66 + $duration + 1) . "$row");
    $sheet->getStyle("A$row:" . chr(66 + $duration + 1) . "$row")->applyFromArray($categoryStyle);
    $row++;

    $itemsStmt = $pdo->prepare("SELECT description, year_1, year_2, year_3, year_4, year_5, year_6 FROM budget_items WHERE grant_id = :gid AND category_id = :cid");
    $itemsStmt->execute([':gid' => $grant_id, ':cid' => $category['id']]);

    $rateStmt = $pdo->prepare("SELECT hourly_rate FROM salaries WHERE role = :role AND year = :year");

    while ($item = $itemsStmt->fetch(PDO::FETCH_ASSOC)) {
        $description = $item['description'];
        $yearly_amounts = array_slice([
            (float)$item['year_1'], (float)$item['year_2'], (float)$item['year_3'],
            (float)$item['year_4'], (float)$item['year_5'], (float)$item['year_6']
        ], 0, $duration);

        if (in_array($category['category_name'], ['Personnel Compensation', 'Other Personnel'], true)) {
            $salary_rows[$description] = $row;
            for ($year = 1; $year <= $duration; $year++) {
                $rateStmt->execute([':role' => $description, ':year' => $year]);
                $hourly_rate_value = (float)($rateStmt->fetchColumn() ?: 0);
                if ($year === 1) {
                    $sheet->setCellValue("B{$row}", $hourly_rate_value);
                }
                $amount_for_year = $yearly_amounts[$year - 1] * $hourly_rate_value;
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($year + 2) . $row, $amount_for_year);
            }
        } else {
            for ($year = 1; $year <= $duration; $year++) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($year + 2) . $row, $yearly_amounts[$year - 1]);
            }
        }

        $total_formula = "=SUM(" . chr(67) . "{$row}:" . chr(66 + $duration) . "{$row})";
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($duration + 3) . $row, $total_formula);

        $sheet->setCellValue("A{$row}", $description);
        $sheet->getStyle("A{$row}:" . chr(66 + $duration + 1) . "{$row}")->applyFromArray($cellStyle);
        $row++;
    }
    $row++;
}

$sheet->setCellValue("A$row", "Fringe")->getStyle("A$row")->applyFromArray($categoryStyle);
$sheet->mergeCells("A$row:" . chr(66 + $duration + 1) . "$row");
$row++;

$fringe_roles = [
    'Faculty' => ['PI', 'Co-PI'],
    'UI professional staff & Post Docs' => ['UI professional staff & Post Docs'],
    'GRAs/UGrads' => ['GRAs/UGrads'],
    'Temp Help' => ['Temp Help']
];

$fringeRateStmt = $pdo->prepare("SELECT fringe_rate FROM fringe_rates WHERE role = :role AND year = :year");

foreach ($fringe_roles as $fringe_role => $salary_roles) {
    $sheet->setCellValue("A$row", $fringe_role);

    for ($year = 1; $year <= $duration; $year++) {
        $salary_total_formula = "";
        foreach ($salary_roles as $role) {
            if (isset($salary_rows[$role])) {
                $salary_row = $salary_rows[$role];
                $salary_column = chr(65 + $year + 1);
                $salary_total_formula .= "{$salary_column}{$salary_row}+";
            }
        }
        $salary_total_formula = rtrim($salary_total_formula, "+");

        $fringeRateStmt->execute([':role' => $fringe_role, ':year' => $year]);
        $fringe_rate = (float)($fringeRateStmt->fetchColumn() ?: 0);

        if ($year == 1) {
            $sheet->setCellValue("B{$row}", $fringe_rate . '%');
        }

        $formula = "=SUM(" . chr(65 + $year + 1) . ($row - 5) . ":" . chr(65 + $year + 1) . ($row - 1) . ")";
        $sheet->setCellValue(chr(65 + $year + 1) . $row, $formula);
    }

    $total_formula = "=SUM(" . chr(67) . "{$row}:" . chr(66 + $duration) . "{$row})";
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($duration + 3) . $row, $total_formula);

    $row++;
}

$row++;

$categories2Stmt = $pdo->query("SELECT * FROM budget_categories WHERE category_name NOT IN ('Personnel Compensation', 'Other Personnel') ORDER BY id");
while ($category = $categories2Stmt->fetch(PDO::FETCH_ASSOC)) {
    $sheet->setCellValue("A$row", $category['category_name']);
    $sheet->mergeCells("A$row:" . chr(66 + $duration + 1) . "$row");
    $sheet->getStyle("A$row:" . chr(66 + $duration + 1) . "$row")->applyFromArray($categoryStyle);
    $row++;

    $itemsStmt = $pdo->prepare("SELECT description, year_1, year_2, year_3, year_4, year_5, year_6 FROM budget_items WHERE grant_id = :gid AND category_id = :cid");
    $itemsStmt->execute([':gid' => $grant_id, ':cid' => $category['id']]);

    while ($item = $itemsStmt->fetch(PDO::FETCH_ASSOC)) {
        $description = $item['description'];
        $yearly_amounts = array_slice([
            (float)$item['year_1'], (float)$item['year_2'], (float)$item['year_3'],
            (float)$item['year_4'], (float)$item['year_5'], (float)$item['year_6']
        ], 0, $duration);

        for ($year = 1; $year <= $duration; $year++) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($year + 2) . $row, $yearly_amounts[$year - 1]);
        }

        $sheet->setCellValue("A$row", $description);
        $total_formula = "=SUM(" . chr(67) . "{$row}:" . chr(66 + $duration) . "{$row})";
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($duration + 3) . $row, $total_formula);
        $sheet->getStyle("A$row:" . chr(66 + $duration + 1) . "$row")->applyFromArray($cellStyle);
        $row++;
    }
    $row++;
}


$sheet->setCellValue("A$row", "Modified Total Direct Costs");
for ($year = 1; $year <= $duration + 1; $year++) {
    $column = chr(66 + $year);
    $formula = "=SUM(" . $column . ($row - 5) . ":" . $column . ($row - 1) . ")";
    $sheet->setCellValue($column . $row, $formula);
}
$row++;


$sheet->setCellValue("A$row", "Indirect Costs");
$sheet->setCellValue("B$row", "50.0%");
for ($year = 1; $year <= $duration + 1; $year++) {
    $column = chr(66 + $year);
    $formula = "=($column" . ($row - 1) . " * 0.5)";
    $sheet->setCellValue($column . $row, $formula);
}
$row++;

$sheet->setCellValue("A$row", "Total Project Cost");
for ($year = 1; $year <= $duration + 1; $year++) {
    $column = chr(66 + $year);
    $formula = "=SUM($column" . (9) . ": $column" . ($row - 1) . ")";
    $sheet->setCellValue($column . $row, $formula);
}
$row++;


$sheet->getStyle("A" . ($row - 3) . ":" . chr(66 + $duration + 1) . ($row - 1))->applyFromArray($categoryStyle);


$sheet->getColumnDimension('A')->setWidth(30);
$sheet->getColumnDimension('B')->setWidth(20);
foreach (range(1, 100) as $row) {
    $sheet->getRowDimension($row)->setRowHeight(20);
}
for ($year = 1; $year <= $duration; $year++) {
    $sheet->getColumnDimension(chr(66 + $year))->setWidth(15);
}
$sheet->getStyle($sheet->calculateWorksheetDimension())
    ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"grant_budget_{$grant['title']}.xlsx\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
