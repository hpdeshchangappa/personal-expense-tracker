<?php

session_start();

require_once "config/db.php";


// Check login

if (!isset($_SESSION["user_id"])) {

    header("Location: index.php");
    exit();

}


$user_id = $_SESSION["user_id"];


// Get transaction ID

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: dashboard.php");
    exit();

}


$transaction_id = intval($_GET["id"]);


// Get transaction

$stmt = $conn->prepare(
    "SELECT id, type, category, amount, description, transaction_date
     FROM transactions
     WHERE id = ? AND user_id = ?"
);

$stmt->bind_param(
    "ii",
    $transaction_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();


// Make sure transaction belongs to current user

if ($result->num_rows !== 1) {

    $stmt->close();

    header("Location: dashboard.php");
    exit();

}


$transaction = $result->fetch_assoc();

$stmt->close();


$message = "";
$message_type = "";


// ==================================================
// UPDATE TRANSACTION
// ==================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $type = $_POST["type"];
    $category = trim($_POST["category"]);
    $amount = trim($_POST["amount"]);
    $description = trim($_POST["description"]);
    $transaction_date = $_POST["transaction_date"];


    // Validation

    if (
        empty($category) ||
        empty($amount) ||
        empty($transaction_date)
    ) {

        $message = "Please fill in all required fields.";
        $message_type = "error";

    } elseif (!is_numeric($amount) || $amount <= 0) {

        $message = "Please enter a valid amount.";
        $message_type = "error";

    } elseif (
        $type !== "income" &&
        $type !== "expense"
    ) {

        $message = "Invalid transaction type.";
        $message_type = "error";

    } else {


        // Update transaction

        $update = $conn->prepare(
            "UPDATE transactions
             SET type = ?,
                 category = ?,
                 amount = ?,
                 description = ?,
                 transaction_date = ?
             WHERE id = ? AND user_id = ?"
        );


        $update->bind_param(
            "ssdssii",
            $type,
            $category,
            $amount,
            $description,
            $transaction_date,
            $transaction_id,
            $user_id
        );


        if ($update->execute()) {

            $update->close();

            header("Location: dashboard.php");
            exit();

        } else {

            $message = "Unable to update transaction.";
            $message_type = "error";

            $update->close();

        }

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Transaction | Expense Tracker</title>

    <link rel="stylesheet" href="css/style.css">

</head>


<body class="dashboard-body">


<!-- NAVBAR -->

<nav class="navbar">

    <div class="nav-brand">
        💰 Expense Tracker
    </div>

    <div class="nav-right">

        <a
            href="dashboard.php"
            class="back-btn"
        >
            ← Dashboard
        </a>

        <a
            href="logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </div>

</nav>


<!-- FORM -->

<div class="form-page-container">

    <div class="transaction-form-card">


        <!-- HEADER -->

        <div class="form-header">

            <div class="large-form-icon">
                ✏️
            </div>

            <h1>
                Edit Transaction
            </h1>

            <p>
                Update your transaction details.
            </p>

        </div>


        <!-- MESSAGE -->

        <?php if (!empty($message)): ?>

            <div class="message <?php echo $message_type; ?>">

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <form method="POST">


            <!-- TYPE -->

            <div class="form-group">

                <label for="type">
                    Transaction Type *
                </label>

                <select
                    id="type"
                    name="type"
                    required
                >

                    <option
                        value="income"
                        <?php
                        if ($transaction["type"] === "income") {
                            echo "selected";
                        }
                        ?>
                    >
                        Income
                    </option>

                    <option
                        value="expense"
                        <?php
                        if ($transaction["type"] === "expense") {
                            echo "selected";
                        }
                        ?>
                    >
                        Expense
                    </option>

                </select>

            </div>


            <!-- CATEGORY -->

            <div class="form-group">

                <label for="category">
                    Category *
                </label>

                <input
                    type="text"
                    id="category"
                    name="category"
                    value="<?php echo htmlspecialchars($transaction["category"]); ?>"
                    placeholder="Enter category"
                    required
                >

            </div>


            <!-- AMOUNT -->

            <div class="form-group">

                <label for="amount">
                    Amount (₹) *
                </label>

                <input
                    type="number"
                    id="amount"
                    name="amount"
                    step="0.01"
                    min="0.01"
                    value="<?php echo htmlspecialchars($transaction["amount"]); ?>"
                    required
                >

            </div>


            <!-- DATE -->

            <div class="form-group">

                <label for="transaction_date">
                    Date *
                </label>

                <input
                    type="date"
                    id="transaction_date"
                    name="transaction_date"
                    value="<?php echo htmlspecialchars($transaction["transaction_date"]); ?>"
                    required
                >

            </div>


            <!-- DESCRIPTION -->

            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    placeholder="Add a description"
                ><?php echo htmlspecialchars($transaction["description"]); ?></textarea>

            </div>


            <!-- UPDATE -->

            <button
                type="submit"
                class="btn-primary transaction-submit"
            >
                💾 Update Transaction
            </button>


        </form>


        <a
            href="dashboard.php"
            class="cancel-link"
        >
            Cancel
        </a>


    </div>

</div>


</body>

</html>