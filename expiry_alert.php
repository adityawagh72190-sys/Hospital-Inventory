<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include("config/db.php");

$user_id = $_SESSION['user_id'] ?? 0;
$today = date('Y-m-d');

/* Expired + medicines expiring within next 30 days */
$result = mysqli_query(
    $conn,
    "SELECT *
     FROM medicines
     WHERE user_id = '$user_id'
     AND expiry_date IS NOT NULL
     AND expiry_date != ''
     AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY expiry_date ASC"
);

$rows = [];
$total_expired_loss = 0;

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        /* Pack size */
        $pack_size = (int)($row['pack_size'] ?? 1);

        if ($pack_size <= 0) {
            $pack_size = 1;
        }

        /* Actual stock in individual units */
        $stock_units = (int)($row['stock_units'] ?? 0);

        /*
         * Fallback for old medicines
         * quantity = full packs
         */
        if ($stock_units <= 0) {
            $quantity = (int)($row['quantity'] ?? 0);
            $stock_units = $quantity * $pack_size;
        }

        /* Full packs and loose units */
        $full_packs = intdiv($stock_units, $pack_size);
        $loose_units = $stock_units % $pack_size;

        /*
         * PURCHASE PRICE
         *
         * purchase_price = price of complete pack
         */
        $purchase_price_pack = (float)($row['purchase_price'] ?? 0);

        $purchase_price_unit =
            $purchase_price_pack / $pack_size;

        /*
         * SELLING PRICE
         *
         * price = price of complete pack
         *
         * This is only displayed.
         * It is NOT used for loss calculation.
         */
        $selling_price_pack = (float)($row['price'] ?? 0);

        $selling_price_unit =
            $selling_price_pack / $pack_size;

        $expiry_date = $row['expiry_date'];

        /* Check status */

        if ($expiry_date < $today) {

            $status = "Expired";
            $status_class = "expired";

            /*
             * IMPORTANT LOSS FORMULA:
             *
             * Expired Loss =
             * Total Expired Units
             * × Purchase Price Per Unit
             *
             * SELLING PRICE IS NOT USED.
             */

            $expired_loss = round(
                $stock_units * $purchase_price_unit,
                2
            );

            $loss_calculation =
                $stock_units .
                " × ₹" .
                number_format($purchase_price_unit, 2) .
                " = ₹" .
                number_format($expired_loss, 2);

            $total_expired_loss += $expired_loss;

        } else {

            $status = "Expiring Soon";
            $status_class = "soon";

            /*
             * Expiring Soon is NOT counted as loss.
             */

            $expired_loss = 0;

            $loss_calculation = "Not included";
        }

        /* Store calculated values */

        $row['pack_size_display'] = $pack_size;

        $row['stock_units_display'] = $stock_units;

        $row['full_packs_display'] = $full_packs;

        $row['loose_units_display'] = $loose_units;

        $row['purchase_price_pack'] =
            $purchase_price_pack;

        $row['purchase_price_unit'] =
            $purchase_price_unit;

        $row['selling_price_pack'] =
            $selling_price_pack;

        $row['selling_price_unit'] =
            $selling_price_unit;

        $row['status_display'] =
            $status;

        $row['status_class'] =
            $status_class;

        $row['expired_loss'] =
            $expired_loss;

        $row['loss_calculation'] =
            $loss_calculation;

        $rows[] = $row;
    }
}

$total_expired_loss = round($total_expired_loss, 2);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Expiry Alert - MediTrack</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f7fb;
    color: #222;
}

.container {
    width: 95%;
    margin: 30px auto;
}

/* HEADER */

.header {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.header h1 {
    margin: 0 0 8px;
    color: #1f4e79;
}

.header p {
    margin: 0;
    color: #666;
}

/* LOSS CARD */

.loss-card {
    background: white;
    border-left: 6px solid #dc3545;
    padding: 18px 22px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.07);
}

.loss-card h3 {
    margin: 0 0 8px;
    color: #b02a37;
}

.loss-amount {
    font-size: 28px;
    font-weight: bold;
    color: #dc3545;
}

.loss-note {
    margin-top: 8px;
    color: #666;
    font-size: 14px;
}

/* TABLE */

.table-box {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 1500px;
    border-collapse: collapse;
}

th,
td {
    padding: 12px 10px;
    border-bottom: 1px solid #ddd;
    text-align: left;
    white-space: nowrap;
}

th {
    background: #1f4e79;
    color: white;
    font-size: 14px;
}

td {
    font-size: 14px;
}

tr:hover {
    background: #f8fbff;
}

/* STATUS */

.expired {
    color: #dc3545;
    font-weight: bold;
}

.soon {
    color: #d39e00;
    font-weight: bold;
}

/* LOSS */

.loss-value {
    color: #dc3545;
    font-weight: bold;
}

.calculation {
    font-weight: 600;
    color: #444;
}

.no-loss {
    color: #777;
}

/* BACK BUTTON */

.back-btn {
    display: inline-block;
    margin-top: 20px;
    padding: 11px 18px;
    background: #1f4e79;
    color: white;
    text-decoration: none;
    border-radius: 7px;
}

.back-btn:hover {
    background: #163a5b;
}

/* EMPTY */

.empty {
    text-align: center;
    padding: 30px;
    color: #777;
}

</style>

</head>

<body>

<div class="container">

    <!-- HEADER -->

    <div class="header">

        <h1>Expiry Alert</h1>

        <p>
            Expired and medicines expiring within the next 30 days
        </p>

    </div>


    <!-- TOTAL EXPIRED LOSS -->

    <div class="loss-card">

        <h3>Total Expired Medicine Loss</h3>

        <div class="loss-amount">

            ₹<?php
            echo number_format(
                $total_expired_loss,
                2
            );
            ?>

        </div>

        <div class="loss-note">

            Loss is calculated using
            <strong>Purchase Price</strong>,
            not Selling Price.

            Expiring Soon medicines are
            <strong>not included</strong>
            in this loss.

        </div>

    </div>


    <!-- TABLE -->

    <div class="table-box">

        <?php if (count($rows) > 0): ?>

        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Medicine Name</th>

                    <th>Category</th>

                    <th>Batch No.</th>

                    <th>Expiry Date</th>

                    <th>Pack Type</th>

                    <th>Pack Size</th>

                    <th>Full Packs</th>

                    <th>Loose Units</th>

                    <th>Total Units</th>

                    <th>Purchase Price / Pack</th>

                    <th>Purchase Price / Unit</th>

                    <th>Selling Price / Pack</th>

                    <th>Selling Price / Unit</th>

                    <th>Loss Calculation</th>

                    <th>Expired Loss</th>

                    <th>Status</th>

                </tr>

            </thead>


            <tbody>

            <?php foreach ($rows as $row): ?>

                <tr>

                    <!-- ID -->

                    <td>
                        <?php
                        echo (int)$row['id'];
                        ?>
                    </td>


                    <!-- MEDICINE NAME -->

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $row['medicine_name']
                        );
                        ?>

                    </td>


                    <!-- CATEGORY -->

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $row['category'] ?? ''
                        );
                        ?>

                    </td>


                    <!-- BATCH -->

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $row['batch_no'] ?? ''
                        );
                        ?>

                    </td>


                    <!-- EXPIRY -->

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $row['expiry_date']
                        );
                        ?>

                    </td>


                    <!-- PACK TYPE -->

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $row['pack_type'] ?? ''
                        );
                        ?>

                    </td>


                    <!-- PACK SIZE -->

                    <td>

                        <?php
                        echo (int)
                            $row['pack_size_display'];
                        ?>

                    </td>


                    <!-- FULL PACKS -->

                    <td>

                        <?php
                        echo (int)
                            $row['full_packs_display'];
                        ?>

                    </td>


                    <!-- LOOSE UNITS -->

                    <td>

                        <?php
                        echo (int)
                            $row['loose_units_display'];
                        ?>

                    </td>


                    <!-- TOTAL UNITS -->

                    <td>

                        <strong>

                            <?php
                            echo (int)
                                $row['stock_units_display'];
                            ?>

                        </strong>

                    </td>


                    <!-- PURCHASE PRICE / PACK -->

                    <td>

                        ₹<?php
                        echo number_format(
                            $row['purchase_price_pack'],
                            2
                        );
                        ?>

                    </td>


                    <!-- PURCHASE PRICE / UNIT -->

                    <td>

                        ₹<?php
                        echo number_format(
                            $row['purchase_price_unit'],
                            2
                        );
                        ?>

                    </td>


                    <!-- SELLING PRICE / PACK -->

                    <td>

                        ₹<?php
                        echo number_format(
                            $row['selling_price_pack'],
                            2
                        );
                        ?>

                    </td>


                    <!-- SELLING PRICE / UNIT -->

                    <td>

                        ₹<?php
                        echo number_format(
                            $row['selling_price_unit'],
                            2
                        );
                        ?>

                    </td>


                    <!-- LOSS CALCULATION -->

                    <td class="calculation">

                        <?php

                        if (
                            $row['status_display']
                            === 'Expired'
                        ) {

                            echo htmlspecialchars(
                                $row['loss_calculation']
                            );

                        } else {

                            echo '<span class="no-loss">
                                  Not included
                                  </span>';
                        }

                        ?>

                    </td>


                    <!-- EXPIRED LOSS -->

                    <td class="loss-value">

                        <?php

                        if (
                            $row['status_display']
                            === 'Expired'
                        ) {

                            echo '₹' .
                                number_format(
                                    $row['expired_loss'],
                                    2
                                );

                        } else {

                            echo '<span class="no-loss">
                                  ₹0.00
                                  </span>';
                        }

                        ?>

                    </td>


                    <!-- STATUS -->

                    <td
                        class="<?php
                        echo $row['status_class'];
                        ?>"
                    >

                        <?php
                        echo $row['status_display'];
                        ?>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

        <?php else: ?>

            <div class="empty">

                No expired or expiring medicines found.

            </div>

        <?php endif; ?>

    </div>


    <!-- BACK TO DASHBOARD -->

    <a
        href="dashboard.php"
        class="back-btn"
    >
        ← Back to Dashboard
    </a>

</div>

</body>

</html>