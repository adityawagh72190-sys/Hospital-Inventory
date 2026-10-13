<?php

session_start();

if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true)
{
    header("Location: login.php");
    exit();
}

include("config/db.php");


/* =========================
   LOGGED-IN USER PROFILE
========================= */

$user_id = $_SESSION['user_id'] ?? 0;

$user_query = mysqli_query(
    $conn,
    "SELECT full_name, username, email, mobile, shop_name, upi_id, role
     FROM users
     WHERE id = '$user_id'
     LIMIT 1"
);

$user = mysqli_fetch_assoc($user_query);

$full_name = $user['full_name'] ?? $user['username'] ?? 'User';
$username = $user['username'] ?? '';
$email = $user['email'] ?? '';
$mobile = $user['mobile'] ?? '';
$shop_name = $user['shop_name'] ?? 'My Medical Store';
$upi_id = $user['upi_id'] ?? '';
$role = $user['role'] ?? 'Admin';


/* =========================
   DASHBOARD COUNTS
========================= */

$totalMedicines = mysqli_num_rows(
    mysqli_query(
        $conn,
        "SELECT id
         FROM medicines
         WHERE user_id = '$user_id'"
    )
);

$totalSuppliers = mysqli_num_rows(
    mysqli_query(
        $conn,
        "SELECT id
         FROM suppliers
         WHERE user_id = '$user_id'"
    )
);


/* =========================
   OVERALL SALES + PROFIT
========================= */

$result = mysqli_query(
    $conn,
    "SELECT
        SUM(quantity * selling_price) AS total_sales,
        SUM(quantity * (selling_price - purchase_price)) AS total_profit
     FROM sales
     WHERE user_id = '$user_id'"
);

$row = mysqli_fetch_assoc($result);

$totalSales = $row['total_sales'] ?? 0;
$totalProfit = $row['total_profit'] ?? 0;


/* =========================
   THIS MONTH SALES + PROFIT
========================= */

$result = mysqli_query(
    $conn,
    "SELECT
        SUM(quantity * selling_price) AS monthly_sales,
        SUM(quantity * (selling_price - purchase_price)) AS monthly_profit
     FROM sales
     WHERE user_id = '$user_id'
     AND MONTH(sale_date) = MONTH(CURDATE())
     AND YEAR(sale_date) = YEAR(CURDATE())"
);

$row = mysqli_fetch_assoc($result);

$monthlySales = $row['monthly_sales'] ?? 0;
$monthlyProfit = $row['monthly_profit'] ?? 0;


/* =========================
   TODAY SALES + PROFIT
========================= */

$result = mysqli_query(
    $conn,
    "SELECT
        SUM(quantity * selling_price) AS today_sales,
        SUM(quantity * (selling_price - purchase_price)) AS today_profit
     FROM sales
     WHERE user_id = '$user_id'
     AND sale_date = CURDATE()"
);

$row = mysqli_fetch_assoc($result);

$todaySales = $row['today_sales'] ?? 0;
$todayProfit = $row['today_profit'] ?? 0;


/* =========================
   TOTAL STOCK
========================= */

$result = mysqli_query(
    $conn,
    "SELECT SUM(quantity) AS total_stock
     FROM medicines
     WHERE user_id = '$user_id'"
);

$row = mysqli_fetch_assoc($result);

$totalStock = $row['total_stock'] ?? 0;


/* =========================
   LOW STOCK
========================= */

$lowStockQuery = mysqli_query(
    $conn,
    "SELECT id
     FROM medicines
     WHERE stock_units < 20
     AND user_id = '$user_id'"
);

$lowStock = mysqli_num_rows($lowStockQuery);


/* =========================
   EXPIRY ALERT
========================= */

$expiryAlertQuery = mysqli_query(
    $conn,
    "SELECT id
     FROM medicines
     WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     AND user_id = '$user_id'"
);

$expiryAlert = mysqli_num_rows($expiryAlertQuery);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MediTrack Dashboard</title>

    <link rel="stylesheet" href="css/dashboard.css">

    <style>

        /* =========================
           LEFT SIDEBAR
        ========================= */

        .sidebar > a {
            display: flex;
            align-items: center;
            gap: 12px;

            background: transparent !important;
            color: #cbd5e1 !important;

            border: none !important;
            box-shadow: none !important;

            transition: 0.3s ease;
        }

        .sidebar > a span {
            color: inherit !important;
        }


        .sidebar > a.active {
            background: transparent !important;
            color: #cbd5e1 !important;

            border: none !important;
            box-shadow: none !important;
        }


        .sidebar > a:hover {
            background: #2563eb !important;
            color: white !important;

            border: none !important;
            box-shadow: none !important;
        }


        .sidebar > a.active:hover {
            background: #2563eb !important;
            color: white !important;
        }


        /* =========================
           BILLING
        ========================= */

        .sidebar > a.billing-link {
            background: transparent !important;
            color: #cbd5e1 !important;

            border: none !important;
            box-shadow: none !important;
        }


        .sidebar > a.billing-link:hover {
            background: #2563eb !important;
            color: white !important;

            border: none !important;
            box-shadow: none !important;
        }


        /* =========================
           LOGOUT
        ========================= */

        .menu-bottom a {
            display: flex;
            align-items: center;
            gap: 12px;

            background: transparent !important;
            color: #cbd5e1 !important;

            border: none !important;
            box-shadow: none !important;

            transition: 0.3s ease;
        }


        .menu-bottom a:hover {
            background: #2563eb !important;
            color: white !important;

            border: none !important;
            box-shadow: none !important;
        }


        /* =========================
           QUICK ACTION BUTTONS
        ========================= */

        .quick-card {
            background: #ffffff !important;

            border: 2px solid #2563eb !important;

            box-shadow: none !important;

            transition: 0.3s ease;
        }


        .quick-card.billing-card {
            background: #ffffff !important;

            border: 2px solid #2563eb !important;

            box-shadow: none !important;
        }


        .quick-card:hover {
            border: 2px solid #2563eb !important;

            box-shadow:
                0 8px 20px rgba(37, 99, 235, 0.12) !important;

            transform: translateY(-3px);
        }


        /* =========================
           PROFILE MENU
        ========================= */

        .profile-menu {
            position: relative;
            margin-left: 20px;
        }


        .profile-btn {
            display: flex;
            align-items: center;
            gap: 10px;

            background: #ffffff;
            border: 1px solid #e2e8f0;

            color: #1e293b;

            padding: 9px 13px;
            border-radius: 12px;

            cursor: pointer;
            font-size: 14px;
            font-weight: 600;

            transition: 0.3s ease;
        }


        .profile-btn:hover {
            background: #f8fafc;
            border-color: #2563eb;
        }


        .profile-avatar {
            width: 35px;
            height: 35px;

            border-radius: 50%;

            background: #2563eb;
            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 16px;
        }


        .profile-btn small {
            display: block;

            color: #64748b;
            font-size: 11px;
            font-weight: 400;

            margin-top: 2px;

            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }


        .profile-dropdown {
            display: none;

            position: absolute;
            right: 0;
            top: 58px;

            width: 290px;

            background: #ffffff;

            border: 1px solid #e2e8f0;
            border-radius: 16px;

            padding: 20px;

            box-shadow:
                0 12px 35px rgba(15, 23, 42, 0.15);

            z-index: 9999;
        }


        .profile-dropdown.show {
            display: block;
        }


        .profile-header {
            display: flex;
            align-items: center;
            gap: 12px;

            padding-bottom: 15px;
            margin-bottom: 15px;

            border-bottom: 1px solid #e2e8f0;
        }


        .profile-header .profile-avatar {
            width: 45px;
            height: 45px;

            font-size: 20px;
        }


        .profile-header h3 {
            margin: 0;

            color: #1e293b;
            font-size: 16px;
        }


        .profile-header p {
            margin: 4px 0 0;

            color: #64748b;
            font-size: 12px;
        }


        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }


        .profile-info p {
            margin: 0;

            color: #475569;
            font-size: 13px;
            line-height: 1.5;

            word-break: break-word;
        }


        .profile-info strong {
            color: #1e293b;
        }


        .profile-dropdown .profile-link {
            display: block;

            text-align: center;

            margin-top: 18px;
            padding: 11px;

            background: #2563eb;
            color: white;

            text-decoration: none;

            border-radius: 9px;

            font-size: 13px;
            font-weight: 600;

            transition: 0.3s ease;
        }


        .profile-dropdown .profile-link:hover {
            background: #1d4ed8;
        }


        /* =========================
           SHOP NAME
        ========================= */

        .small-title {
            display: inline-block;
            max-width: 100%;

            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }


        /* =========================
           SALES PROFIT
        ========================= */

        .card-profit {
            color: #16a34a;

            font-size: 14px;

            font-weight: bold;

            margin: 8px 0 12px;
        }


        /* =========================
           MOBILE PROFILE
        ========================= */

        @media(max-width: 768px) {

            .top-header {
                align-items: flex-start;
                gap: 15px;
            }

            .profile-menu {
                margin-left: 0;
            }

            .profile-btn {
                padding: 8px;
            }

            .profile-btn > span:nth-child(2) {
                display: none;
            }

            .profile-dropdown {
                right: 0;
                width: 270px;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

    <div class="logo">

        <div class="logo-icon">
            💊
        </div>

        <div>
            <strong>MediTrack</strong>
            <small>Pharmacy System</small>
        </div>

    </div>


    <div class="menu-title">
        MAIN MENU
    </div>


    <a href="dashboard.php" class="active">

        <span>🏠</span>

        Dashboard

    </a>


    <a href="medicines/view.php">

        <span>💊</span>

        Medicines

    </a>


    <a href="suppliers/view.php">

        <span>🏢</span>

        Suppliers

    </a>


    <a href="purchases/add.php">

        <span>📦</span>

        Purchase

    </a>


    <a href="sales/add.php">

        <span>💰</span>

        Sales

    </a>


    <a href="billing/bill.php" class="billing-link">

        <span>🧾</span>

        Billing

    </a>


    <a href="reports/index.php">

        <span>📊</span>

        Reports

    </a>


    <div class="menu-bottom">

        <a
            href="logout.php"
            onclick="return confirm('Are you sure you want to logout?')"
        >

            <span>🚪</span>

            Logout

        </a>

    </div>

</div>



<!-- =========================
     MAIN
========================= -->

<div class="main">


    <!-- =========================
         TOP HEADER
    ========================= -->

    <div class="top-header">

        <div>

            <h1>
                Dashboard
            </h1>

            <p>
                Welcome back,
                <?php echo htmlspecialchars($full_name); ?>!
                Here's your pharmacy overview.
            </p>

        </div>


        <div style="display: flex; align-items: center; gap: 18px;">

            <div class="live-time">

                <div>
                    📅 <span id="date"></span>
                </div>

                <div>
                    🕒 <span id="time"></span>
                </div>

            </div>


            <!-- PROFILE MENU -->

            <div class="profile-menu">

                <button
                    type="button"
                    class="profile-btn"
                    onclick="toggleProfile()"
                >

                    <span class="profile-avatar">
                        👤
                    </span>

                    <span>

                        <!-- SHOP / MEDICAL NAME FIRST -->

                        <?php echo htmlspecialchars($shop_name); ?>

                        <!-- OWNER NAME SECOND -->

                        <small>
                            <?php echo htmlspecialchars($full_name); ?>
                        </small>

                    </span>

                    <span>⌄</span>

                </button>


                <div
                    class="profile-dropdown"
                    id="profileDropdown"
                >

                    <div class="profile-header">

                        <span class="profile-avatar">
                            👤
                        </span>

                        <div>

                            <h3>
                                <?php echo htmlspecialchars($full_name); ?>
                            </h3>

                            <p>
                                <?php echo htmlspecialchars($role); ?>
                            </p>

                        </div>

                    </div>


                    <div class="profile-info">

                        <p>
                            🏥 <strong>Medical Shop:</strong><br>
                            <?php echo htmlspecialchars($shop_name); ?>
                        </p>

                        <p>
                            👤 <strong>Username:</strong><br>
                            <?php echo htmlspecialchars($username); ?>
                        </p>

                        <p>
                            📧 <strong>Email:</strong><br>
                            <?php echo htmlspecialchars($email); ?>
                        </p>

                        <p>
                            📱 <strong>Mobile:</strong><br>
                            <?php echo htmlspecialchars($mobile); ?>
                        </p>

                        <p>
                            💳 <strong>UPI ID:</strong><br>
                            <?php echo htmlspecialchars($upi_id); ?>
                        </p>

                    </div>


                    <a
                        href="profile.php"
                        class="profile-link"
                    >
                        View Full Profile →
                    </a>

                </div>

            </div>

        </div>

    </div>



    <!-- =========================
         WELCOME BANNER
    ========================= -->

    <div class="welcome-banner">

        <div class="welcome-text">

            <span class="small-title">

                <?php echo htmlspecialchars($shop_name); ?>

            </span>

            <h2>
                Manage your pharmacy with ease 💙
            </h2>

            <p>
                Track medicines, inventory, purchases,
                sales and billing from one place.
            </p>

            <a
                href="billing/bill.php"
                class="start-billing"
            >
                🧾 Create New Bill →
            </a>

        </div>


        <div class="welcome-icon">

            💊

        </div>

    </div>



    <!-- =========================
         QUICK ACTIONS
    ========================= -->

    <div class="section-heading">

        <div>

            <h2>
                Quick Actions
            </h2>

            <p>
                Frequently used operations
            </p>

        </div>

    </div>


    <div class="quick-actions">


        <a
            href="billing/bill.php"
            class="quick-card billing-card"
        >

            <div class="quick-icon">
                🧾
            </div>

            <div>

                <strong>
                    Create Bill
                </strong>

                <span>
                    Generate customer bill
                </span>

            </div>

            <b>→</b>

        </a>



        <a
            href="medicines/add.php"
            class="quick-card"
        >

            <div class="quick-icon">
                💊
            </div>

            <div>

                <strong>
                    Add Medicine
                </strong>

                <span>
                    Add new medicine
                </span>

            </div>

            <b>→</b>

        </a>



        <a
            href="purchases/add.php"
            class="quick-card"
        >

            <div class="quick-icon">
                📦
            </div>

            <div>

                <strong>
                    Add Purchase
                </strong>

                <span>
                    Update inventory
                </span>

            </div>

            <b>→</b>

        </a>



        <a
            href="suppliers/add.php"
            class="quick-card"
        >

            <div class="quick-icon">
                🏢
            </div>

            <div>

                <strong>
                    Add Supplier
                </strong>

                <span>
                    Manage suppliers
                </span>

            </div>

            <b>→</b>

        </a>

    </div>



    <!-- =========================
         OVERVIEW
    ========================= -->

    <div class="section-heading">

        <div>

            <h2>
                Pharmacy Overview
            </h2>

            <p>
                Current inventory and sales summary
            </p>

        </div>

    </div>


    <div class="cards">


        <!-- MEDICINES -->

        <div class="stat-card medicine-card">

            <div class="stat-top">

                <div class="stat-icon">
                    💊
                </div>

                <span class="stat-label">
                    INVENTORY
                </span>

            </div>

            <h3>
                <?php echo $totalMedicines; ?>
            </h3>

            <p>
                Total Medicines
            </p>

            <a href="medicines/view.php">
                View Medicines →
            </a>

        </div>



        <!-- STOCK -->

        <div class="stat-card stock-card">

            <div class="stat-top">

                <div class="stat-icon">
                    📦
                </div>

                <span class="stat-label">
                    STOCK
                </span>

            </div>

            <h3>
                <?php echo $totalStock; ?>
            </h3>

            <p>
                Total Stock Pieces
            </p>

            <a href="medicines/view.php">
                View Stock →
            </a>

        </div>



        <!-- SUPPLIERS -->

        <div class="stat-card supplier-card">

            <div class="stat-top">

                <div class="stat-icon">
                    🏢
                </div>

                <span class="stat-label">
                    SUPPLIERS
                </span>

            </div>

            <h3>
                <?php echo $totalSuppliers; ?>
            </h3>

            <p>
                Registered Suppliers
            </p>

            <a href="suppliers/view.php">
                View Suppliers →
            </a>

        </div>



        <!-- TODAY SALES -->

        <div class="stat-card today-card">

            <div class="stat-top">

                <div class="stat-icon">
                    ☀️
                </div>

                <span class="stat-label">
                    TODAY
                </span>

            </div>

            <h3>
                ₹<?php echo number_format($todaySales, 2); ?>
            </h3>

            <p>
                Today's Sales
            </p>

            <div class="card-profit">
                Profit: ₹<?php echo number_format($todayProfit, 2); ?>
            </div>

            <a href="reports/sales_report.php?date=today">
                View Sales →
            </a>

        </div>



        <!-- MONTH SALES -->

        <div class="stat-card month-card">

            <div class="stat-top">

                <div class="stat-icon">
                    📅
                </div>

                <span class="stat-label">
                    THIS MONTH
                </span>

            </div>

            <h3>
                ₹<?php echo number_format($monthlySales, 2); ?>
            </h3>

            <p>
                Current Month Sales
            </p>

            <div class="card-profit">
                Profit: ₹<?php echo number_format($monthlyProfit, 2); ?>
            </div>

            <a href="reports/sales_report.php?date=month">
                Sales Report →
            </a>

        </div>



        <!-- OVERALL SALES -->

        <div class="stat-card total-sales-card">

            <div class="stat-top">

                <div class="stat-icon">
                    💰
                </div>

                <span class="stat-label">
                    OVERALL
                </span>

            </div>

            <h3>
                ₹<?php echo number_format($totalSales, 2); ?>
            </h3>

            <p>
                Total Sales Amount
            </p>

            <div class="card-profit">
                Profit: ₹<?php echo number_format($totalProfit, 2); ?>
            </div>

            <a href="reports/sales_report.php">
                View Report →
            </a>

        </div>

    </div>



    <!-- =========================
         ALERT SECTION
    ========================= -->

    <div class="section-heading alert-heading">

        <div>

            <h2>
                Attention Required
            </h2>

            <p>
                Check these items regularly
            </p>

        </div>

    </div>


    <div class="alerts">


        <!-- LOW STOCK -->

        <div class="alert-card low-alert">

            <div class="alert-icon">
                ⚠️
            </div>

            <div class="alert-content">

                <h3>
                    Low Stock
                </h3>

                <p>
                    <?php echo $lowStock; ?>
                    medicines have stock below 20 pieces.
                </p>

            </div>

            <a href="low_stock.php">
                Check →
            </a>

        </div>



        <!-- EXPIRY -->

        <div class="alert-card expiry-alert">

            <div class="alert-icon">
                ⏰
            </div>

            <div class="alert-content">

                <h3>
                    Expiry Alert
                </h3>

                <p>
                    <?php echo $expiryAlert; ?>
                    medicines need expiry attention.
                </p>

            </div>

            <a href="expiry_alert.php">
                Check →
            </a>

        </div>

    </div>



    <!-- =========================
         FOOTER
    ========================= -->

    <footer>

        © 2025 MediTrack Pharmacy Management System
        <span>•</span>
        Developed by Shraddha Khade

    </footer>


</div>



<!-- =========================
     DATE & TIME
========================= -->

<script>

function updateDateTime()
{
    const now = new Date();

    document.getElementById("date").innerHTML =
        now.toLocaleDateString("en-GB");

    document.getElementById("time").innerHTML =
        now.toLocaleTimeString("en-GB");
}

updateDateTime();

setInterval(
    updateDateTime,
    1000
);


/* =========================
   PROFILE DROPDOWN
========================= */

function toggleProfile()
{
    document
        .getElementById("profileDropdown")
        .classList.toggle("show");
}


document.addEventListener("click", function(event)
{
    const profileMenu =
        document.querySelector(".profile-menu");

    const dropdown =
        document.getElementById("profileDropdown");

    if(profileMenu && !profileMenu.contains(event.target))
    {
        dropdown.classList.remove("show");
    }
});

</script>


</body>

</html>