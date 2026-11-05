<?php
require_once __DIR__ . "/auth.php";

// Determine transaction type

$type = isset($_GET["type"]) ? $_GET["type"] : "expense";

if ($type !== "income" && $type !== "expense") {

    $type = "expense";

}


$message = "";
$message_type = "";


// --------------------------------------------------
// FORM SUBMISSION
// --------------------------------------------------

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

    } elseif ($type !== "income" && $type !== "expense") {

        $message = "Invalid transaction type.";
        $message_type = "error";

    } else {

        // Insert transaction

        $stmt = $conn->prepare(
            "INSERT INTO transactions
            (user_id, type, category, amount, description, transaction_date)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "issdss",
            $user_id,
            $type,
            $category,
            $amount,
            $description,
            $transaction_date
        );


        if ($stmt->execute()) {

            header("Location: dashboard.php");
            exit();

        } else {

            $message = "Unable to save transaction. Please try again.";
            $message_type = "error";

        }

        $stmt->close();

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Add <?php echo ucfirst($type); ?> | Expense Tracker
    </title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body class="dashboard-body">


<!-- NAVBAR -->

<nav class="navbar">

    <div class="nav-brand">
        💰 Expense Tracker
    </div>

    <div class="nav-right">

        <a href="dashboard.php" class="back-btn">
            ← Dashboard
        </a>

        <a href="analytics.php" class="nav-link-btn">
            📊 Analytics
        </a>

        <a href="logout.php" class="logout-btn">
            Logout
        </a>

    </div>

</nav>


<!-- FORM CONTAINER -->

<div class="form-page-container">

    <div class="transaction-form-card">


        <!-- TITLE -->

        <div class="form-header">

            <?php if ($type === "income"): ?>

                <div class="large-form-icon income-form-icon">
                    💰
                </div>

                <h1>Add Income</h1>

                <p>
                    Record money you've received.
                </p>

            <?php else: ?>

                <div class="large-form-icon expense-form-icon">
                    💸
                </div>

                <h1>Add Expense</h1>

                <p>
                    Record money you've spent.
                </p>

            <?php endif; ?>

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

        <form method="POST" action="">


            <!-- Hidden Type -->

            <input
                type="hidden"
                name="type"
                value="<?php echo htmlspecialchars($type); ?>"
            >


            <!-- CATEGORY -->

            <div class="form-group">

                <label for="category">
                    Category *
                </label>

                <select
                    id="category"
                    name="category"
                    required
                >

                    <?php if ($type === "income"): ?>

                        <option value="">
                            Select income category
                        </option>

                        <option value="Salary">
                            Salary
                        </option>

                        <option value="Freelance">
                            Freelance
                        </option>

                        <option value="Business">
                            Business
                        </option>

                        <option value="Investment">
                            Investment
                        </option>

                        <option value="Gift">
                            Gift
                        </option>

                        <option value="Other Income">
                            Other
                        </option>

                    <?php else: ?>

                        <option value="">
                            Select expense category
                        </option>

                        <option value="Food">
                            Food
                        </option>

                        <option value="Transport">
                            Transport
                        </option>

                        <option value="Shopping">
                            Shopping
                        </option>

                        <option value="Bills">
                            Bills
                        </option>

                        <option value="Entertainment">
                            Entertainment
                        </option>

                        <option value="Health">
                            Health
                        </option>

                        <option value="Education">
                            Education
                        </option>

                        <option value="Other Expense">
                            Other
                        </option>

                    <?php endif; ?>

                </select>

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
                    placeholder="Enter amount"
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
                    value="<?php echo date("Y-m-d"); ?>"
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
                    placeholder="Add a note about this transaction (optional)"
                ></textarea>

            </div>


            <!-- BUTTON -->

            <?php if ($type === "income"): ?>

                <button
                    type="submit"
                    class="btn-primary transaction-submit income-submit"
                >
                    💰 Save Income
                </button>

            <?php else: ?>

                <button
                    type="submit"
                    class="btn-primary transaction-submit expense-submit"
                >
                    💸 Save Expense
                </button>

            <?php endif; ?>


        </form>


        <!-- CANCEL -->

        <a
            href="dashboard.php"
            class="cancel-link"
        >
            Cancel
        </a>


    </div>

</div>


<script src="js/script.js"></script>

</body>

</html>