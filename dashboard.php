<?php

session_start();

require_once "config/db.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];


/* ==================================================
   TOTAL INCOME
================================================== */

$income_query = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total_income
     FROM transactions
     WHERE user_id = ? AND type = 'income'"
);

$income_query->bind_param("i", $user_id);
$income_query->execute();

$income_result = $income_query->get_result();
$income_data = $income_result->fetch_assoc();

$total_income = $income_data["total_income"];

$income_query->close();


/* ==================================================
   TOTAL EXPENSE
================================================== */

$expense_query = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total_expense
     FROM transactions
     WHERE user_id = ? AND type = 'expense'"
);

$expense_query->bind_param("i", $user_id);
$expense_query->execute();

$expense_result = $expense_query->get_result();
$expense_data = $expense_result->fetch_assoc();

$total_expense = $expense_data["total_expense"];

$expense_query->close();


/* ==================================================
   CURRENT BALANCE
================================================== */

$current_balance = $total_income - $total_expense;


/* ==================================================
   CURRENT MONTH
================================================== */

$current_month = date("m");
$current_year = date("Y");


/* ==================================================
   MONTHLY INCOME
================================================== */

$monthly_income_query = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS monthly_income
     FROM transactions
     WHERE user_id = ?
     AND type = 'income'
     AND MONTH(transaction_date) = ?
     AND YEAR(transaction_date) = ?"
);

$monthly_income_query->bind_param(
    "iii",
    $user_id,
    $current_month,
    $current_year
);

$monthly_income_query->execute();

$monthly_income_result = $monthly_income_query->get_result();
$monthly_income_data = $monthly_income_result->fetch_assoc();

$monthly_income = $monthly_income_data["monthly_income"];

$monthly_income_query->close();


/* ==================================================
   MONTHLY EXPENSE
================================================== */

$monthly_expense_query = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS monthly_expense
     FROM transactions
     WHERE user_id = ?
     AND type = 'expense'
     AND MONTH(transaction_date) = ?
     AND YEAR(transaction_date) = ?"
);

$monthly_expense_query->bind_param(
    "iii",
    $user_id,
    $current_month,
    $current_year
);

$monthly_expense_query->execute();

$monthly_expense_result = $monthly_expense_query->get_result();
$monthly_expense_data = $monthly_expense_result->fetch_assoc();

$monthly_expense = $monthly_expense_data["monthly_expense"];

$monthly_expense_query->close();


/* ==================================================
   SEARCH + FILTER VALUES
================================================== */

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";

$category_filter = isset($_GET["category"])
    ? trim($_GET["category"])
    : "";

$type_filter = isset($_GET["type"])
    ? trim($_GET["type"])
    : "";


/* ==================================================
   TRANSACTION QUERY
================================================== */

$sql = "SELECT id, type, category, amount, description, transaction_date
        FROM transactions
        WHERE user_id = ?";

$params = [$user_id];
$types = "i";


/* SEARCH */

if (!empty($search)) {

    $sql .= " AND (
                category LIKE ?
                OR description LIKE ?
              )";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "ss";
}


/* CATEGORY FILTER */

if (!empty($category_filter)) {

    $sql .= " AND category = ?";

    $params[] = $category_filter;

    $types .= "s";
}


/* TYPE FILTER */

if ($type_filter === "income" || $type_filter === "expense") {

    $sql .= " AND type = ?";

    $params[] = $type_filter;

    $types .= "s";
}


/* SORT */

$sql .= " ORDER BY transaction_date DESC, id DESC";


/* PREPARE */

$transaction_query = $conn->prepare($sql);


/* BIND PARAMETERS */

$transaction_query->bind_param(
    $types,
    ...$params
);

$transaction_query->execute();

$transactions_result = $transaction_query->get_result();


/* ==================================================
   GET CATEGORIES
================================================== */

$category_query = $conn->prepare(
    "SELECT DISTINCT category
     FROM transactions
     WHERE user_id = ?
     ORDER BY category ASC"
);

$category_query->bind_param("i", $user_id);
$category_query->execute();

$category_result = $category_query->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Expense Tracker</title>

    <link rel="stylesheet" href="css/style.css">

</head>


<body class="dashboard-body">


<!-- ==================================================
     NAVBAR
================================================== -->

<nav class="navbar">

    <div class="nav-brand">
        💰 Expense Tracker
    </div>

    <div class="nav-right">

        <span class="welcome-text">
            Welcome, <?php echo htmlspecialchars($user_name); ?>
        </span>

        <a href="analytics.php" class="nav-link-btn">
            📊 Analytics
        </a>

        <a href="export_csv.php" class="export-btn" title="Download all transactions as CSV">
            📥 Export CSV
        </a>

        <a href="logout.php" class="logout-btn">
            Logout
        </a>

    </div>

</nav>


<!-- ==================================================
     MAIN CONTAINER
================================================== -->

<div class="dashboard-container">


    <!-- HEADER -->

    <div class="dashboard-header">

        <div>

            <h1>Dashboard</h1>

            <p>
                Here's an overview of your finances.
            </p>

        </div>

    </div>


    <!-- ==================================================
         SUMMARY CARDS
    ================================================== -->

    <div class="summary-grid">


        <div class="summary-card income-card">

            <div class="card-icon">
                💰
            </div>

            <div>

                <p>Total Income</p>

                <h2>
                    ₹<?php echo number_format($total_income, 2); ?>
                </h2>

            </div>

        </div>


        <div class="summary-card expense-card">

            <div class="card-icon">
                💸
            </div>

            <div>

                <p>Total Expense</p>

                <h2>
                    ₹<?php echo number_format($total_expense, 2); ?>
                </h2>

            </div>

        </div>


        <div class="summary-card balance-card">

            <div class="card-icon">
                🏦
            </div>

            <div>

                <p>Current Balance</p>

                <h2>
                    ₹<?php echo number_format($current_balance, 2); ?>
                </h2>

            </div>

        </div>


    </div>


    <!-- ==================================================
         MONTHLY SUMMARY
    ================================================== -->

    <div class="section-title">

        <h2>
            <?php echo date("F Y"); ?> Summary
        </h2>

    </div>


    <div class="monthly-grid">


        <div class="monthly-card">

            <div>

                <span class="monthly-label">
                    Monthly Income
                </span>

                <h3>
                    ₹<?php echo number_format($monthly_income, 2); ?>
                </h3>

            </div>

            <span class="monthly-icon">
                📈
            </span>

        </div>


        <div class="monthly-card">

            <div>

                <span class="monthly-label">
                    Monthly Expense
                </span>

                <h3>
                    ₹<?php echo number_format($monthly_expense, 2); ?>
                </h3>

            </div>

            <span class="monthly-icon">
                📉
            </span>

        </div>


    </div>


    <!-- ==================================================
         ACTION BUTTONS
    ================================================== -->

    <div class="action-section">

        <a href="add_transaction.php?type=income"
           class="action-btn income-action">

            <span>＋</span>

            Add Income

        </a>


        <a href="add_transaction.php?type=expense"
           class="action-btn expense-action">

            <span>−</span>

            Add Expense

        </a>

    </div>


    <!-- ==================================================
         TRANSACTIONS SECTION
    ================================================== -->

    <div class="transactions-section">


        <div class="transactions-header">

            <div>

                <h2>
                    Transactions
                </h2>

                <p>
                    Search, filter and manage your transactions.
                </p>

            </div>

        </div>


        <!-- ==================================================
             SEARCH + FILTER
        ================================================== -->

        <form method="GET"
              action="dashboard.php"
              class="filter-form">


            <!-- SEARCH -->

            <div class="search-box">

                <input
                    type="text"
                    name="search"
                    placeholder="🔎 Search category or description..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >

            </div>


            <!-- CATEGORY -->

            <div class="filter-box">

                <select name="category">

                    <option value="">
                        All Categories
                    </option>

                    <?php while ($cat = $category_result->fetch_assoc()): ?>

                        <option
                            value="<?php echo htmlspecialchars($cat["category"]); ?>"
                            <?php
                            if ($category_filter === $cat["category"]) {
                                echo "selected";
                            }
                            ?>
                        >

                            <?php
                            echo htmlspecialchars($cat["category"]);
                            ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <!-- TYPE -->

            <div class="filter-box">

                <select name="type">

                    <option value="">
                        All Types
                    </option>

                    <option
                        value="income"
                        <?php
                        if ($type_filter === "income") {
                            echo "selected";
                        }
                        ?>
                    >
                        Income
                    </option>

                    <option
                        value="expense"
                        <?php
                        if ($type_filter === "expense") {
                            echo "selected";
                        }
                        ?>
                    >
                        Expense
                    </option>

                </select>

            </div>


            <!-- SEARCH BUTTON -->

            <button
                type="submit"
                class="filter-btn"
            >
                Search
            </button>


            <!-- RESET -->

            <a
                href="dashboard.php"
                class="reset-btn"
            >
                Reset
            </a>


        </form>


        <!-- RESULT COUNT -->

        <div class="result-count">

            <?php

            echo $transactions_result->num_rows;

            ?>

            transaction(s) found

        </div>


        <!-- ==================================================
             TRANSACTION TABLE
        ================================================== -->

        <?php if ($transactions_result->num_rows > 0): ?>

            <div class="table-wrapper">

                <table class="transaction-table">

                    <thead>

                        <tr>

                            <th>Date</th>

                            <th>Category</th>

                            <th>Description</th>

                            <th>Type</th>

                            <th>Amount</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while ($transaction = $transactions_result->fetch_assoc()): ?>


                        <tr>


                            <!-- DATE -->

                            <td>

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $transaction["transaction_date"]
                                    )
                                );

                                ?>

                            </td>


                            <!-- CATEGORY -->

                            <td>

                                <span class="category-badge">

                                    <?php

                                    echo htmlspecialchars(
                                        $transaction["category"]
                                    );

                                    ?>

                                </span>

                            </td>


                            <!-- DESCRIPTION -->

                            <td>

                                <?php

                                if (!empty($transaction["description"])) {

                                    echo htmlspecialchars(
                                        $transaction["description"]
                                    );

                                } else {

                                    echo "—";

                                }

                                ?>

                            </td>


                            <!-- TYPE -->

                            <td>

                                <?php

                                if (
                                    $transaction["type"] === "income"
                                ):

                                ?>

                                    <span class="type-income">
                                        Income
                                    </span>

                                <?php else: ?>

                                    <span class="type-expense">
                                        Expense
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- AMOUNT -->

                            <td>

                                <?php

                                if (
                                    $transaction["type"] === "income"
                                ):

                                ?>

                                    <span class="amount-income">

                                        +
                                        ₹<?php

                                        echo number_format(
                                            $transaction["amount"],
                                            2
                                        );

                                        ?>

                                    </span>

                                <?php else: ?>

                                    <span class="amount-expense">

                                        -
                                        ₹<?php

                                        echo number_format(
                                            $transaction["amount"],
                                            2
                                        );

                                        ?>

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ACTIONS -->

                            <td>

                                <div class="table-actions">


                                    <!-- EDIT -->

                                    <a
                                        href="edit_transaction.php?id=<?php echo $transaction["id"]; ?>"
                                        class="edit-btn"
                                    >
                                        ✏️ Edit
                                    </a>


                                    <!-- DELETE -->

                                    <form
                                        method="POST"
                                        action="delete_transaction.php"
                                        class="delete-form"
                                        
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php echo $transaction["id"]; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="delete-btn"
                                        >
                                            🗑️ Delete
                                        </button>

                                    </form>


                                </div>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty-state">

                <div class="empty-icon">
                    🔎
                </div>

                <h3>
                    No transactions found
                </h3>

                <p>
                    Try changing your search or filter.
                </p>

            </div>


        <?php endif; ?>


    </div>


</div>


<script src="js/script.js"></script>


</body>

</html>


<?php

$transaction_query->close();
$category_query->close();

$conn->close();

?>