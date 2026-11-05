<?php
require_once __DIR__ . "/auth.php";

// Optional filter parameter
$period = isset($_GET["period"]) ? $_GET["period"] : "all";

$sql = "SELECT id, transaction_date, type, category, amount, description 
        FROM transactions 
        WHERE user_id = ?";
$params = [$user_id];
$types = "i";

if ($period === "this_month") {
    $sql .= " AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())";
} elseif ($period === "last_30_days") {
    $sql .= " AND transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
} elseif ($period === "this_year") {
    $sql .= " AND YEAR(transaction_date) = YEAR(CURRENT_DATE())";
}

$sql .= " ORDER BY transaction_date DESC, id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Set CSV download headers
$filename = "transactions_export_" . date("Ymd_His") . ".csv";
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Open output stream
$output = fopen("php://output", "w");

// Output CSV column headers (standard Data Analytics format)
fputcsv($output, ["Transaction ID", "Date", "Type", "Category", "Amount (INR)", "Description"]);

// Stream rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row["id"],
        $row["transaction_date"],
        ucfirst($row["type"]),
        $row["category"],
        number_format((float)$row["amount"], 2, ".", ""),
        $row["description"]
    ]);
}

fclose($output);
$stmt->close();
$conn->close();
exit();
