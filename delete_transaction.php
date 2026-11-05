<?php
require_once __DIR__ . "/auth.php";

// Only allow POST requests

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: dashboard.php");
    exit();

}


// Validate transaction ID

if (!isset($_POST["id"]) || !is_numeric($_POST["id"])) {

    header("Location: dashboard.php");
    exit();

}


$transaction_id = intval($_POST["id"]);


// Delete only if transaction belongs to logged-in user

$stmt = $conn->prepare(
    "DELETE FROM transactions
     WHERE id = ? AND user_id = ?"
);

$stmt->bind_param(
    "ii",
    $transaction_id,
    $user_id
);


$stmt->execute();

$stmt->close();

$conn->close();


// Return to dashboard

header("Location: dashboard.php");
exit();

?>