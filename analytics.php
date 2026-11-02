<?php

session_start();

require_once "config/db.php";

// 1. Check login authentication
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];

// 2. Validate that user_id exists in `users` table to satisfy foreign key constraints
$check_user = $conn->prepare("SELECT id, name FROM users WHERE id = ?");
$check_user->bind_param("i", $user_id);
$check_user->execute();
$user_res = $check_user->get_result();

if ($user_row = $user_res->fetch_assoc()) {
    $user_id = (int)$user_row["id"];
    $user_name = $user_row["name"];
    $_SESSION["user_id"] = $user_id;
    $_SESSION["user_name"] = $user_name;
} else {
    // If user_id in session is stale or was deleted, fall back to an existing user in `users`
    $any_user = $conn->query("SELECT id, name FROM users ORDER BY id ASC LIMIT 1");
    if ($any_user && ($any_row = $any_user->fetch_assoc())) {
        $user_id = (int)$any_row["id"];
        $user_name = $any_row["name"];
        $_SESSION["user_id"] = $user_id;
        $_SESSION["user_name"] = $user_name;
    } else {
        // If users table is completely empty, create an initial user record
        $default_hash = password_hash("password123", PASSWORD_DEFAULT);
        $create_u = $conn->prepare("INSERT INTO users (name, email, password) VALUES ('Student Analyst', 'analyst@pes.edu', ?)");
        $create_u->bind_param("s", $default_hash);
        $create_u->execute();
        $user_id = (int)$conn->insert_id;
        $user_name = "Student Analyst";
        $_SESSION["user_id"] = $user_id;
        $_SESSION["user_name"] = $user_name;
        $create_u->close();
    }
}
$check_user->close();

// ==================================================
// ONE-CLICK DEMO DATA SEEDER (FOR INSTANT PREVIEW)
// ==================================================
$seed_error = "";
if (isset($_GET["action"]) && $_GET["action"] === "seed_demo") {
    try {
        $demo_txns = [
            ['income', 'Salary', 55000.00, 'Monthly Stipend / Salary', date('Y-m-01')],
            ['expense', 'Bills', 4500.00, 'Electricity & High Speed Wifi', date('Y-m-02')],
            ['expense', 'Food', 1800.00, 'Supermarket Groceries', date('Y-m-04')],
            ['expense', 'Transport', 850.00, 'Metro & Auto pass', date('Y-m-06')],
            ['expense', 'Food', 650.00, 'Cafeteria lunch with team', date('Y-m-08')],
            ['expense', 'Entertainment', 1500.00, 'Movie & Snacks', date('Y-m-10')],
            ['income', 'Freelance', 12000.00, 'Data Dashboard Consulting Project', date('Y-m-12')],
            ['expense', 'Shopping', 6500.00, 'Noise Cancelling Headphones', date('Y-m-14')],
            ['expense', 'Food', 1200.00, 'Weekend Dinner with friends', date('Y-m-16')],
            ['expense', 'Health', 1400.00, 'Pharmacy & Vitamins', date('Y-m-18')],
            ['expense', 'Education', 3500.00, 'Data Analytics Certification Course', date('Y-m-20')],
            ['expense', 'Transport', 950.00, 'Cab rides for college events', date('Y-m-22')],
            ['expense', 'Food', 1100.00, 'Dining out', date('Y-m-24')],
            ['expense', 'Entertainment', 2000.00, 'Live Music Concert ticket', date('Y-m-26')],
            // Prior month data for Time-Series trends
            ['income', 'Salary', 55000.00, 'Previous Month Salary', date('Y-m-01', strtotime('-1 month'))],
            ['expense', 'Bills', 4200.00, 'Previous Month Utility Bills', date('Y-m-03', strtotime('-1 month'))],
            ['expense', 'Food', 5400.00, 'Previous Month Dining & Groceries', date('Y-m-10', strtotime('-1 month'))],
            ['expense', 'Shopping', 4200.00, 'Books and Apparel', date('Y-m-15', strtotime('-1 month'))],
            ['expense', 'Transport', 1600.00, 'Previous Month Metro & Cab Commute', date('Y-m-20', strtotime('-1 month'))],
            ['expense', 'Other Expense', 8500.00, 'Laptop Service & RAM Upgrade', date('Y-m-25', strtotime('-1 month'))]
        ];
        
        $insert_stmt = $conn->prepare("INSERT INTO transactions (user_id, type, category, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
        if ($insert_stmt) {
            foreach ($demo_txns as $t) {
                $insert_stmt->bind_param("issdss", $user_id, $t[0], $t[1], $t[2], $t[3], $t[4]);
                $insert_stmt->execute();
            }
            $insert_stmt->close();
        }
        header("Location: analytics.php?seeded=1");
        exit();
    } catch (Exception $e) {
        $seed_error = $e->getMessage();
    }
}

// ==================================================
// TIME FILTER / PERIOD SELECTION
// ==================================================
$period = isset($_GET["period"]) ? $_GET["period"] : "all";

$date_condition = "";
$period_label = "All Time";

if ($period === "this_month") {
    $date_condition = " AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())";
    $period_label = date("F Y");
} elseif ($period === "last_30_days") {
    $date_condition = " AND transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
    $period_label = "Last 30 Days";
} elseif ($period === "last_6_months") {
    $date_condition = " AND transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)";
    $period_label = "Last 6 Months";
} elseif ($period === "this_year") {
    $date_condition = " AND YEAR(transaction_date) = YEAR(CURRENT_DATE())";
    $period_label = "Year " . date("Y");
}

// ==================================================
// 1. OVERALL KPIS
// ==================================================
$kpi_sql = "SELECT 
    COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
    COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS total_expense,
    COUNT(CASE WHEN type = 'income' THEN 1 END) AS count_income,
    COUNT(CASE WHEN type = 'expense' THEN 1 END) AS count_expense,
    COALESCE(AVG(CASE WHEN type = 'expense' THEN amount ELSE NULL END), 0) AS avg_expense_txn,
    COALESCE(MAX(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS max_single_expense
FROM transactions
WHERE user_id = ?" . $date_condition;

$kpi_stmt = $conn->prepare($kpi_sql);
$kpi_stmt->bind_param("i", $user_id);
$kpi_stmt->execute();
$kpi_data = $kpi_stmt->get_result()->fetch_assoc();
$kpi_stmt->close();

$total_income = (float)$kpi_data["total_income"];
$total_expense = (float)$kpi_data["total_expense"];
$net_savings = $total_income - $total_expense;
$count_expense = (int)$kpi_data["count_expense"];
$count_income = (int)$kpi_data["count_income"];
$total_txns = $count_income + $count_expense;
$avg_expense_txn = (float)$kpi_data["avg_expense_txn"];
$max_single_expense = (float)$kpi_data["max_single_expense"];

// Savings Rate Calculation
if ($total_income > 0) {
    $savings_rate = ($net_savings / $total_income) * 100;
} else {
    $savings_rate = $total_expense > 0 ? -100 : 0;
}

// Active spending days & Burn Rate
$days_sql = "SELECT COUNT(DISTINCT transaction_date) AS active_days 
             FROM transactions 
             WHERE user_id = ? AND type = 'expense'" . $date_condition;
$days_stmt = $conn->prepare($days_sql);
$days_stmt->bind_param("i", $user_id);
$days_stmt->execute();
$days_data = $days_stmt->get_result()->fetch_assoc();
$days_stmt->close();

$active_days = max(1, (int)$days_data["active_days"]);
$daily_burn_rate = $total_expense > 0 ? ($total_expense / $active_days) : 0;

// ==================================================
// 2. CATEGORY BREAKDOWN & METRICS
// ==================================================
$cat_sql = "SELECT 
    category,
    COUNT(*) AS txn_count,
    COALESCE(SUM(amount), 0) AS total_spent,
    COALESCE(AVG(amount), 0) AS avg_spent,
    COALESCE(MAX(amount), 0) AS max_spent
FROM transactions
WHERE user_id = ? AND type = 'expense'" . $date_condition . "
GROUP BY category
ORDER BY total_spent DESC";

$cat_stmt = $conn->prepare($cat_sql);
$cat_stmt->bind_param("i", $user_id);
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();

$categories = [];
$cat_labels = [];
$cat_values = [];
$top_category = "N/A";
$top_category_amount = 0;
$top_category_pct = 0;

while ($row = $cat_result->fetch_assoc()) {
    $row["pct_of_total"] = $total_expense > 0 ? ($row["total_spent"] / $total_expense) * 100 : 0;
    $categories[] = $row;
    $cat_labels[] = $row["category"];
    $cat_values[] = round((float)$row["total_spent"], 2);
}
$cat_stmt->close();

if (!empty($categories)) {
    $top_category = $categories[0]["category"];
    $top_category_amount = (float)$categories[0]["total_spent"];
    $top_category_pct = (float)$categories[0]["pct_of_total"];
}

// ==================================================
// 3. ESSENTIALS VS DISCRETIONARY (50/30/20 Rule)
// ==================================================
$needs_categories = ["Food", "Transport", "Bills", "Health", "Education"];
$needs_total = 0;
$wants_total = 0;

foreach ($categories as $cat) {
    if (in_array($cat["category"], $needs_categories)) {
        $needs_total += (float)$cat["total_spent"];
    } else {
        $wants_total += (float)$cat["total_spent"];
    }
}
$needs_pct = $total_expense > 0 ? round(($needs_total / $total_expense) * 100, 1) : 0;
$wants_pct = $total_expense > 0 ? round(($wants_total / $total_expense) * 100, 1) : 0;

// ==================================================
// 4. MONTHLY TIME SERIES TREND (Income vs Expense)
// ==================================================
$trend_sql = "SELECT 
    DATE_FORMAT(transaction_date, '%Y-%m') AS ym,
    DATE_FORMAT(transaction_date, '%b %Y') AS month_label,
    COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS monthly_income,
    COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS monthly_expense
FROM transactions
WHERE user_id = ? AND transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
GROUP BY ym, month_label
ORDER BY ym ASC";

$trend_stmt = $conn->prepare($trend_sql);
$trend_stmt->bind_param("i", $user_id);
$trend_stmt->execute();
$trend_result = $trend_stmt->get_result();

$trend_labels = [];
$trend_income = [];
$trend_expense = [];

while ($row = $trend_result->fetch_assoc()) {
    $trend_labels[] = $row["month_label"];
    $trend_income[] = round((float)$row["monthly_income"], 2);
    $trend_expense[] = round((float)$row["monthly_expense"], 2);
}
$trend_stmt->close();

// ==================================================
// 5. DAY-OF-WEEK BEHAVIORAL SPENDING
// ==================================================
$dow_sql = "SELECT 
    DAYOFWEEK(transaction_date) AS day_num,
    COALESCE(SUM(amount), 0) AS total_spent
FROM transactions
WHERE user_id = ? AND type = 'expense'" . $date_condition . "
GROUP BY day_num";

$dow_stmt = $conn->prepare($dow_sql);
$dow_stmt->bind_param("i", $user_id);
$dow_stmt->execute();
$dow_result = $dow_stmt->get_result();

$days_map = [
    2 => "Mon",
    3 => "Tue",
    4 => "Wed",
    5 => "Thu",
    6 => "Fri",
    7 => "Sat",
    1 => "Sun"
];
$dow_data = array_fill_keys(array_values($days_map), 0);

while ($row = $dow_result->fetch_assoc()) {
    $day_name = $days_map[(int)$row["day_num"]];
    $dow_data[$day_name] = round((float)$row["total_spent"], 2);
}
$dow_stmt->close();

$dow_labels = array_keys($dow_data);
$dow_values = array_values($dow_data);

$peak_day = "N/A";
$peak_day_amount = 0;
foreach ($dow_data as $day => $amt) {
    if ($amt > $peak_day_amount) {
        $peak_day_amount = $amt;
        $peak_day = $day;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Data Analytics | Expense Tracker</title>
    <!-- Cache-busting CSS link -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- EMBEDDED SELF-CONTAINED STYLES TO BYPASS ALL BROWSER CACHING ISSUES -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body.dashboard-body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f5f7fb;
            color: #1e293b;
            min-height: 100vh;
        }
        .navbar {
            background: #ffffff;
            min-height: 70px;
            padding: 0 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .nav-brand { font-size: 20px; font-weight: 700; color: #1e293b; }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .back-btn, .export-btn, .logout-btn, .seed-btn {
            text-decoration: none;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .back-btn { background: #f1f5f9; color: #475569; }
        .back-btn:hover { background: #e2e8f0; color: #1e293b; }
        .export-btn { background: #10b981; color: white; }
        .export-btn:hover { background: #059669; transform: translateY(-1px); }
        .logout-btn { background: #667eea; color: white; }
        .logout-btn:hover { background: #5568d9; }
        .seed-btn { background: #4f46e5; color: white; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25); }
        .seed-btn:hover { background: #4338ca; transform: translateY(-1px); }

        .dashboard-container {
            width: 92%;
            max-width: 1240px;
            margin: 0 auto;
            padding: 35px 0 60px 0;
        }

        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 25px;
        }
        .analytics-header h1 { font-size: 28px; color: #0f172a; margin-bottom: 6px; }
        .analytics-header p { color: #64748b; font-size: 14px; }

        .period-filter-group {
            display: flex;
            background: #ffffff;
            padding: 5px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
            gap: 4px;
        }
        .period-pill {
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            transition: 0.2s ease;
        }
        .period-pill:hover { color: #0f172a; background: #f8fafc; }
        .period-pill.active { background: #667eea; color: #ffffff; }

        .analytics-insights-card {
            background: #ffffff;
            border-left: 5px solid #667eea;
            border-radius: 12px;
            padding: 22px 24px;
            margin-bottom: 30px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
            border-top: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            gap: 18px;
            align-items: flex-start;
        }
        .insights-icon {
            font-size: 24px;
            background: #eef2ff;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            flex-shrink: 0;
        }
        .insights-content { flex: 1; }
        .insights-content h3 { font-size: 16.5px; color: #0f172a; margin-bottom: 12px; }
        .insights-list {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 12px 24px;
        }
        .insights-list li { font-size: 13.5px; line-height: 1.5; color: #475569; }

        .text-success { color: #16a34a !important; font-weight: 700; }
        .text-warning { color: #d97706 !important; font-weight: 700; }
        .text-danger  { color: #dc2626 !important; font-weight: 700; }

        .analytics-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .kpi-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08); }
        .kpi-icon {
            font-size: 26px;
            width: 52px;
            height: 52px;
            background: #f8fafc;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid #edf2f7;
        }
        .kpi-body { flex: 1; }
        .kpi-title { font-size: 12.5px; color: #64748b; font-weight: 600; display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .kpi-body h2 { font-size: 23px; color: #0f172a; margin-bottom: 3px; font-weight: 700; }
        .kpi-subtitle { font-size: 12px; color: #94a3b8; }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(480px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        .chart-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }
        .chart-header { margin-bottom: 20px; }
        .chart-header h3 { font-size: 17px; color: #0f172a; margin-bottom: 4px; font-weight: 700; }
        .chart-header p { font-size: 13px; color: #64748b; }
        .chart-container { position: relative; height: 300px; width: 100%; }

        .transactions-section {
            background: #ffffff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }
        .transactions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .transactions-header h2 { font-size: 18px; color: #0f172a; margin-bottom: 4px; }
        .transactions-header p { font-size: 13px; color: #64748b; }

        .export-btn-outline {
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            color: #10b981;
            border: 1.5px solid #10b981;
            background: white;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s ease;
        }
        .export-btn-outline:hover { background: #ecfdf5; }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }
        .transaction-table th {
            background: #f8fafc;
            color: #475569;
            padding: 12px 16px;
            font-weight: 600;
            border-bottom: 1px solid #e2e8f0;
        }
        .transaction-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }
        .table-wrapper { overflow-x: auto; }

        .category-badge {
            background: #eef2ff;
            color: #4f46e5;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 600;
            display: inline-block;
        }
        .amount-expense { color: #dc2626; font-weight: 600; }
        .amount-income { color: #16a34a; font-weight: 600; }

        .progress-bar-wrapper { display: flex; align-items: center; gap: 8px; min-width: 130px; }
        .progress-bar { height: 8px; background: #667eea; border-radius: 4px; flex-grow: 1; max-width: 100px; }
        .progress-label { font-size: 12px; color: #475569; font-weight: 600; width: 42px; }

        .badge-needs { background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .badge-wants { background: #fef3c7; color: #b45309; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }

        .empty-seed-banner {
            background: #eff6ff;
            border: 1.5px dashed #3b82f6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        .empty-seed-text h4 { font-size: 15.5px; color: #1e40af; margin-bottom: 4px; }
        .empty-seed-text p { font-size: 13.5px; color: #3b82f6; }

        @media (max-width: 768px) {
            .charts-grid { grid-template-columns: 1fr; }
            .analytics-header { flex-direction: column; align-items: flex-start; }
            .period-filter-group { width: 100%; overflow-x: auto; }
        }
    </style>
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
        <a href="export_csv.php?period=<?php echo urlencode($period); ?>" class="export-btn" title="Download dataset for Excel / Tableau analysis">
            📥 Export CSV
        </a>
        <a href="logout.php" class="logout-btn">
            Logout
        </a>
    </div>
</nav>

<!-- MAIN CONTAINER -->
<div class="dashboard-container">

    <!-- PAGE HEADER -->
    <div class="analytics-header">
        <div>
            <h1>📊 Financial Data Analytics</h1>
            <p>Exploratory data analysis, key financial metrics, category breakdown & behavioral patterns.</p>
        </div>

        <!-- PERIOD FILTER TABS -->
        <div class="period-filter-group">
            <a href="analytics.php?period=all" class="period-pill <?php echo $period === 'all' ? 'active' : ''; ?>">All Time</a>
            <a href="analytics.php?period=this_month" class="period-pill <?php echo $period === 'this_month' ? 'active' : ''; ?>">This Month</a>
            <a href="analytics.php?period=last_30_days" class="period-pill <?php echo $period === 'last_30_days' ? 'active' : ''; ?>">Last 30 Days</a>
            <a href="analytics.php?period=this_year" class="period-pill <?php echo $period === 'this_year' ? 'active' : ''; ?>">This Year</a>
        </div>
    </div>

    <!-- ONE-CLICK DEMO DATA SEED BANNER (IF ZERO TRANSACTIONS) -->
    <?php if ($total_txns === 0): ?>
        <div class="empty-seed-banner">
            <div class="empty-seed-text">
                <h4>🚀 Ready to Test Your Analytics Dashboard?</h4>
                <p>Your database currently has 0 transactions. Click the button to automatically load realistic demo records and activate all charts!</p>
            </div>
            <a href="analytics.php?action=seed_demo" class="seed-btn">
                ⚡ Load Sample Analytics Data
            </a>
        </div>
    <?php elseif (isset($_GET["seeded"])): ?>
        <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            ✅ Sample analytics dataset generated successfully! Explore the interactive charts and metrics below.
        </div>
    <?php endif; ?>

    <?php if (!empty($seed_error)): ?>
        <div style="background: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            ⚠️ Unable to load sample data: <?php echo htmlspecialchars($seed_error); ?>
        </div>
    <?php endif; ?>

    <!-- INSIGHTS BANNER -->
    <div class="analytics-insights-card">
        <div class="insights-icon">💡</div>
        <div class="insights-content">
            <h3>Data-Driven Observations (<?php echo htmlspecialchars($period_label); ?>)</h3>
            <ul class="insights-list">
                <li>
                    <strong>Savings Health:</strong> You saved 
                    <span class="<?php echo $savings_rate >= 20 ? 'text-success' : ($savings_rate >= 0 ? 'text-warning' : 'text-danger'); ?>">
                        <?php echo number_format($savings_rate, 1); ?>%
                    </span> 
                    of income. <?php echo $savings_rate >= 20 ? 'Optimal savings buffer maintained.' : ($savings_rate >= 0 ? 'Adequate, but targeting 20%+ is recommended.' : 'Deficit detected: Expenses exceeded income.'); ?>
                </li>
                <li>
                    <strong>Cost Concentration:</strong> Your highest expenditure is 
                    <strong><?php echo htmlspecialchars($top_category); ?></strong>, consuming 
                    <strong><?php echo number_format($top_category_pct, 1); ?>%</strong> of total expenses (₹<?php echo number_format($top_category_amount, 2); ?>).
                </li>
                <li>
                    <strong>Budget Allocation:</strong> 
                    <strong><?php echo $needs_pct; ?>%</strong> spent on Essentials (Food, Bills, Transport, Health, Education) vs 
                    <strong><?php echo $wants_pct; ?>%</strong> on Discretionary items (50/30/20 standard benchmark: 50% Needs / 30% Wants / 20% Savings).
                </li>
                <li>
                    <strong>Peak Spending Velocity:</strong> Heaviest spending day is 
                    <strong><?php echo $peak_day; ?></strong> (₹<?php echo number_format($peak_day_amount, 2); ?> recorded).
                </li>
            </ul>
        </div>
    </div>

    <!-- ==================================================
         PRIMARY DATA ANALYST KPIS (4 METRIC CARDS)
    ================================================== -->
    <div class="analytics-kpi-grid">

        <!-- KPI 1: Savings Rate -->
        <div class="kpi-card">
            <div class="kpi-icon">🎯</div>
            <div class="kpi-body">
                <span class="kpi-title">Net Savings Rate</span>
                <h2 class="<?php echo $savings_rate >= 20 ? 'text-success' : ($savings_rate >= 0 ? 'text-warning' : 'text-danger'); ?>">
                    <?php echo number_format($savings_rate, 1); ?>%
                </h2>
                <span class="kpi-subtitle">
                    Net Surplus: ₹<?php echo number_format($net_savings, 2); ?>
                </span>
            </div>
        </div>

        <!-- KPI 2: Daily Burn Rate -->
        <div class="kpi-card">
            <div class="kpi-icon">🔥</div>
            <div class="kpi-body">
                <span class="kpi-title">Daily Burn Rate</span>
                <h2>₹<?php echo number_format($daily_burn_rate, 2); ?></h2>
                <span class="kpi-subtitle">
                    Across <?php echo $active_days; ?> active transaction day(s)
                </span>
            </div>
        </div>

        <!-- KPI 3: Average Ticket Size -->
        <div class="kpi-card">
            <div class="kpi-icon">🧾</div>
            <div class="kpi-body">
                <span class="kpi-title">Avg Expense per Txn</span>
                <h2>₹<?php echo number_format($avg_expense_txn, 2); ?></h2>
                <span class="kpi-subtitle">
                    Highest single: ₹<?php echo number_format($max_single_expense, 2); ?>
                </span>
            </div>
        </div>

        <!-- KPI 4: Transaction Volume -->
        <div class="kpi-card">
            <div class="kpi-icon">📈</div>
            <div class="kpi-body">
                <span class="kpi-title">Transaction Volume</span>
                <h2><?php echo $total_txns; ?> txns</h2>
                <span class="kpi-subtitle">
                    <?php echo $count_income; ?> Inflow / <?php echo $count_expense; ?> Outflow
                </span>
            </div>
        </div>

    </div>

    <!-- ==================================================
         CHARTS ROW 1: TIME SERIES & CATEGORY COMPOSITION
    ================================================== -->
    <div class="charts-grid">

        <!-- CHART 1: Monthly Cash Flow Trend (Time Series) -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3>Monthly Cash Flow Dynamics</h3>
                    <p>Time-series analysis of Income vs Expense variance (Last 12 Months)</p>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

        <!-- CHART 2: Category Breakdown (Composition Donut) -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3>Expense Distribution by Category</h3>
                    <p>Categorical expenditure share for <?php echo htmlspecialchars($period_label); ?></p>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="categoryDonutChart"></canvas>
            </div>
        </div>

    </div>

    <!-- ==================================================
         CHARTS ROW 2: TOP EXPENSES & BEHAVIORAL PATTERNS
    ================================================== -->
    <div class="charts-grid">

        <!-- CHART 3: Top 5 Expense Categories Ranking -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3>Top Expense Cost Drivers (Pareto Ranking)</h3>
                    <p>Top category expenditures ranked by total spend</p>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="topCategoriesChart"></canvas>
            </div>
        </div>

        <!-- CHART 4: Day-of-Week Behavioral Spending -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3>Behavioral Spending by Day of Week</h3>
                    <p>Aggregated expense volume from Monday to Sunday</p>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="dowBarChart"></canvas>
            </div>
        </div>

    </div>

    <!-- ==================================================
         CATEGORY AGGREGATION & PARETO TABLE
    ================================================== -->
    <div class="transactions-section">
        <div class="transactions-header">
            <div>
                <h2>Detailed Category Aggregation (SQL Group By Summary)</h2>
                <p>Descriptive metrics per category: transaction counts, volume, % share, and average transaction size.</p>
            </div>
            <a href="export_csv.php?period=<?php echo urlencode($period); ?>" class="export-btn-outline">
                📥 Download Table Data (CSV)
            </a>
        </div>

        <?php if (!empty($categories)): ?>
            <div class="table-wrapper">
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Txn Count</th>
                            <th>Total Spent</th>
                            <th>% of Expenses</th>
                            <th>Average Txn</th>
                            <th>Max Single Txn</th>
                            <th>Category Classification</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <span class="category-badge">
                                        <?php echo htmlspecialchars($cat["category"]); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo $cat["txn_count"]; ?></strong>
                                </td>
                                <td>
                                    <span class="amount-expense">
                                        ₹<?php echo number_format($cat["total_spent"], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress-bar-wrapper">
                                        <div class="progress-bar" style="width: <?php echo min(100, round($cat['pct_of_total'], 1)); ?>%;"></div>
                                        <span class="progress-label"><?php echo number_format($cat["pct_of_total"], 1); ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    ₹<?php echo number_format($cat["avg_spent"], 2); ?>
                                </td>
                                <td>
                                    ₹<?php echo number_format($cat["max_spent"], 2); ?>
                                </td>
                                <td>
                                    <?php if (in_array($cat["category"], $needs_categories)): ?>
                                        <span class="badge-needs">Needs (Essential)</span>
                                    <?php else: ?>
                                        <span class="badge-wants">Wants (Discretionary)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px 20px; color: #64748b;">
                <div style="font-size: 36px; margin-bottom: 10px;">📊</div>
                <h3 style="color: #1e293b; margin-bottom: 6px;">No expense records found for this period</h3>
                <p style="margin-bottom: 18px;">Add some transactions or click the button above to load demo data.</p>
                <a href="add_transaction.php?type=expense" class="seed-btn" style="display: inline-block;">
                    ＋ Add First Expense
                </a>
            </div>
        <?php endif; ?>

    </div>

</div>

<!-- PASS PHP DATA SAFELY TO JAVASCRIPT FOR CHART.JS -->
<script>
    const trendLabels = <?php echo json_encode($trend_labels); ?>;
    const trendIncome = <?php echo json_encode($trend_income); ?>;
    const trendExpense = <?php echo json_encode($trend_expense); ?>;

    const catLabels = <?php echo json_encode($cat_labels); ?>;
    const catValues = <?php echo json_encode($cat_values); ?>;

    const dowLabels = <?php echo json_encode($dow_labels); ?>;
    const dowValues = <?php echo json_encode($dow_values); ?>;

    const modernPalette = [
        '#667eea', '#764ba2', '#38b2ac', '#ed8936', 
        '#e53e3e', '#ecc94b', '#48bb78', '#9f7aea', 
        '#4299e1', '#f56565', '#ed64a6', '#667eea'
    ];

    // ==========================================
    // CHART 1: MONTHLY CASH FLOW TREND
    // ==========================================
    const trendCtx = document.getElementById('monthlyTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: trendLabels.length ? trendLabels : ['Current'],
                datasets: [
                    {
                        label: 'Income (₹)',
                        data: trendIncome.length ? trendIncome : [0],
                        backgroundColor: 'rgba(72, 187, 120, 0.85)',
                        borderColor: '#38a169',
                        borderWidth: 1.5,
                        borderRadius: 6
                    },
                    {
                        label: 'Expense (₹)',
                        data: trendExpense.length ? trendExpense : [0],
                        backgroundColor: 'rgba(245, 101, 101, 0.85)',
                        borderColor: '#e53e3e',
                        borderWidth: 1.5,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 14, font: { weight: 'bold' } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.dataset.label + ': ₹' + Number(ctx.raw).toLocaleString('en-IN', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹' + value.toLocaleString('en-IN'); }
                        },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ==========================================
    // CHART 2: CATEGORY DONUT CHART
    // ==========================================
    const donutCtx = document.getElementById('categoryDonutChart');
    if (donutCtx) {
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: catLabels.length ? catLabels : ['No Expenses Recorded'],
                datasets: [{
                    data: catValues.length ? catValues : [1],
                    backgroundColor: catValues.length ? modernPalette.slice(0, catValues.length) : ['#e2e8f0'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 12, padding: 12, font: { size: 12 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const val = ctx.raw;
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return ' ' + ctx.label + ': ₹' + Number(val).toLocaleString('en-IN', {minimumFractionDigits: 2}) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // ==========================================
    // CHART 3: TOP 5 CATEGORIES HORIZONTAL BAR
    // ==========================================
    const topCtx = document.getElementById('topCategoriesChart');
    if (topCtx) {
        const top5Labels = catLabels.slice(0, 5);
        const top5Values = catValues.slice(0, 5);

        new Chart(topCtx, {
            type: 'bar',
            data: {
                labels: top5Labels.length ? top5Labels : ['No Data'],
                datasets: [{
                    label: 'Amount Spent (₹)',
                    data: top5Values.length ? top5Values : [0],
                    backgroundColor: 'rgba(102, 126, 234, 0.85)',
                    borderColor: '#667eea',
                    borderWidth: 1.5,
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' ₹' + Number(ctx.raw).toLocaleString('en-IN', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹' + value.toLocaleString('en-IN'); }
                        },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    // ==========================================
    // CHART 4: DAY OF WEEK BEHAVIORAL SPENDING
    // ==========================================
    const dowCtx = document.getElementById('dowBarChart');
    if (dowCtx) {
        new Chart(dowCtx, {
            type: 'bar',
            data: {
                labels: dowLabels,
                datasets: [{
                    label: 'Total Spend (₹)',
                    data: dowValues,
                    backgroundColor: 'rgba(118, 75, 162, 0.85)',
                    borderColor: '#764ba2',
                    borderWidth: 1.5,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' ₹' + Number(ctx.raw).toLocaleString('en-IN', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹' + value.toLocaleString('en-IN'); }
                        },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }
</script>

</body>
</html>
