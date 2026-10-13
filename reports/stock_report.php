<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../config/db.php");

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$result = mysqli_query(
    $conn,
    "SELECT 
        m.*,
        COALESCE(
            (
                SELECT SUM(s.quantity)
                FROM sales s
                WHERE s.medicine_id = m.id
                AND s.user_id = '$user_id'
            ),
            0
        ) AS total_sold_units
     FROM medicines m
     WHERE m.user_id = '$user_id'
     ORDER BY m.id DESC"
);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Stock Report - MediTrack</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f7fb;
    color: #222;
}

.container {
    width: 96%;
    margin: 25px auto;
}

.header {
    background: #ffffff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header h2 {
    margin: 0;
    color: #1f4e79;
}

.header p {
    margin-top: 6px;
    color: #666;
}

.buttons {
    display: flex;
    gap: 10px;
}

.dashboard-btn,
.print-btn {
    color: white;
    text-decoration: none;
    padding: 10px 16px;
    border-radius: 7px;
    font-size: 14px;
    font-weight: bold;
    border: none;
    cursor: pointer;
}

.dashboard-btn {
    background: #1f4e79;
}

.dashboard-btn:hover {
    background: #163a5c;
}

.print-btn {
    background: #28a745;
}

.print-btn:hover {
    background: #218838;
}

.table-container {
    background: #fff;
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1800px;
}

th {
    background: #1f4e79;
    color: white;
    padding: 12px 10px;
    border: 1px solid #ddd;
    white-space: nowrap;
}

td {
    padding: 10px;
    border: 1px solid #ddd;
    white-space: nowrap;
}

tr:nth-child(even) {
    background: #f8f9fa;
}

.status {
    font-weight: bold;
    padding: 6px 10px;
    border-radius: 6px;
    display: inline-block;
}

.available {
    background: #d4edda;
    color: #155724;
}

.low {
    background: #fff3cd;
    color: #856404;
}

.out {
    background: #f8d7da;
    color: #721c24;
}

.expired {
    background: #f5c6cb;
    color: #721c24;
}

.h1-yes {
    color: #b00020;
    font-weight: bold;
}

.h1-no {
    color: #555;
}

.amount {
    text-align: right;
}

.no-data {
    text-align: center;
    padding: 30px;
    color: #777;
    font-size: 16px;
}

/* PRINT */
@media print {

    body {
        background: white;
    }

    .container {
        width: 100%;
        margin: 0;
    }

    .header {
        box-shadow: none;
    }

    .buttons {
        display: none;
    }

    .table-container {
        box-shadow: none;
        padding: 0;
    }

    table {
        min-width: 0;
        font-size: 9px;
    }

    th {
        background: #1f4e79 !important;
        color: white !important;
    }

}

</style>

</head>

<body>

<div class="container">

    <div class="header">

        <div class="header-top">

            <div>
                <h2>Medicine Stock Report</h2>

                <p>
                    Current medicine stock, sales and pricing details
                </p>
            </div>

            <div class="buttons">

                <a href="../dashboard.php" class="dashboard-btn">
                    Back to Dashboard
                </a>

                <button
                    type="button"
                    class="print-btn"
                    onclick="window.print()">
                    Print
                </button>

            </div>

        </div>

    </div>

    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>Medicine Name</th>
                    <th>Type</th>
                    <th>H1</th>
                    <th>Pack Type</th>
                    <th>Pack Size</th>
                    <th>Batch No</th>
                    <th>Expiry Date</th>

                    <th>Stock Units</th>
                    <th>Full Packs</th>
                    <th>Loose Units</th>

                    <th>Total Sold Units</th>

                    <th>Purchase Price / Pack</th>
                    <th>Purchase / Unit</th>

                    <th>Selling Price / Pack</th>
                    <th>Sale / Unit</th>

                    <th>Profit / Unit</th>

                    <th>Profit on Sold Units</th>

                    <th>Stock Value</th>
                    <th>Status</th>

                </tr>

            </thead>

            <tbody>

            <?php

            if (mysqli_num_rows($result) == 0) {

                echo '
                <tr>
                    <td colspan="19" class="no-data">
                        No medicine stock available.
                    </td>
                </tr>';

            }

            while ($row = mysqli_fetch_assoc($result)) {

                $medicine_name = htmlspecialchars(
                    $row['medicine_name'] ?? ''
                );

                $medicine_type = htmlspecialchars(
                    $row['medicine_type'] ?? 'General'
                );

                $is_h1 = (int)(
                    $row['is_h1'] ?? 0
                );

                $pack_type = htmlspecialchars(
                    $row['pack_type'] ?? 'Tablet'
                );

                $pack_size = (int)(
                    $row['pack_size'] ?? 1
                );

                if ($pack_size <= 0) {
                    $pack_size = 1;
                }

                $pack_size_display =
                    "1 × " . $pack_size;

                $stock_units = (int)(
                    $row['stock_units'] ?? 0
                );

                if (
                    $stock_units <= 0 &&
                    isset($row['quantity']) &&
                    (int)$row['quantity'] > 0
                ) {

                    $stock_units =
                        (int)$row['quantity']
                        * $pack_size;
                }

                $full_packs = intdiv(
                    $stock_units,
                    $pack_size
                );

                $loose_units =
                    $stock_units % $pack_size;

                $total_sold_units = (int)(
                    $row['total_sold_units'] ?? 0
                );

                $purchase_price = (float)(
                    $row['purchase_price'] ?? 0
                );

                $selling_price = (float)(
                    $row['price'] ?? 0
                );

                $purchase_per_unit =
                    $purchase_price / $pack_size;

                $sale_per_unit =
                    $selling_price / $pack_size;

                $profit_per_unit =
                    $sale_per_unit
                    - $purchase_per_unit;

                $profit_on_sold_units =
                    $total_sold_units
                    * $profit_per_unit;

                $stock_value =
                    ($stock_units / $pack_size)
                    * $purchase_price;

                $expiry_date =
                    $row['expiry_date'] ?? '';

                if (
                    !empty($expiry_date) &&
                    $expiry_date < $today
                ) {

                    $status = "Expired";
                    $status_class = "expired";

                } elseif ($stock_units <= 0) {

                    $status = "Out of Stock";
                    $status_class = "out";

                } elseif ($stock_units < 10) {

                    $status = "Low Stock";
                    $status_class = "low";

                } else {

                    $status = "Available";
                    $status_class = "available";
                }

            ?>

                <tr>

                    <td><?= $medicine_name ?></td>

                    <td><?= $medicine_type ?></td>

                    <td>

                        <?php if ($is_h1): ?>

                            <span class="h1-yes">Yes</span>

                        <?php else: ?>

                            <span class="h1-no">No</span>

                        <?php endif; ?>

                    </td>

                    <td><?= $pack_type ?></td>

                    <td><?= $pack_size_display ?></td>

                    <td>
                        <?= htmlspecialchars(
                            $row['batch_no'] ?? ''
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $expiry_date
                        ) ?>
                    </td>

                    <td class="amount">
                        <?= number_format($stock_units) ?>
                    </td>

                    <td class="amount">
                        <?= number_format($full_packs) ?>
                    </td>

                    <td class="amount">
                        <?= number_format($loose_units) ?>
                    </td>

                    <td class="amount">
                        <?= number_format($total_sold_units) ?>
                    </td>

                    <td class="amount">
                        ₹<?= number_format(
                            $purchase_price,
                            2
                        ) ?>
                    </td>

                    <td class="amount">
                        ₹<?= number_format(
                            $purchase_per_unit,
                            2
                        ) ?>
                    </td>

                    <td class="amount">
                        ₹<?= number_format(
                            $selling_price,
                            2
                        ) ?>
                    </td>

                    <td class="amount">
                        ₹<?= number_format(
                            $sale_per_unit,
                            2
                        ) ?>
                    </td>

                    <td class="amount">
                        ₹<?= number_format(
                            $profit_per_unit,
                            2
                        ) ?>
                    </td>

                    <td class="amount">
                        ₹<?= number_format(
                            $profit_on_sold_units,
                            2
                        ) ?>
                    </td>

                    <td class="amount">
                        ₹<?= number_format(
                            $stock_value,
                            2
                        ) ?>
                    </td>

                    <td>

                        <span class="status <?= $status_class ?>">
                            <?= $status ?>
                        </span>

                    </td>

                </tr>

            <?php
            }
            ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>