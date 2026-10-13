<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../config/db.php");

$user_id = $_SESSION['user_id'];

$today = date('Y-m-d');


/* =========================
   GET ONLY EXPIRY MEDICINES

   SHOW:
   1. EXPIRED
   2. EXPIRING WITHIN 30 DAYS

   DO NOT SHOW:
   - Available
   - Low Stock
   - Out of Stock
   - More than 30 days remaining
   ========================= */

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

?>

<!DOCTYPE html>
<html>

<head>

    <title>Expiry Report - MediTrack</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 20px;
            margin: 0;
        }

        h2 {
            text-align: center;
            color: #dc3545;
            margin-bottom: 20px;
        }


        /* =========================
           TOP SECTION
           ========================= */

        .top-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }


        .back-btn,
        .print-btn {
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }


        .back-btn {
            background: #64748b;
            color: white;
        }

        .back-btn:hover {
            background: #475569;
        }


        .print-btn {
            background: #dc3545;
            color: white;
        }

        .print-btn:hover {
            background: #b91c1c;
        }


        /* =========================
           TABLE
           ========================= */

        .table-container {
            overflow-x: auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }


        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1650px;
        }


        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            white-space: nowrap;
        }


        th {
            background: #dc3545;
            color: white;
            font-weight: bold;
        }


        tr:nth-child(even) {
            background: #f8fafc;
        }


        tr:hover {
            background: #fff1f2;
        }


        /* =========================
           BADGES
           ========================= */

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }


        /* Medicine Type */

        .general {
            background: #dbeafe;
            color: #1e40af;
        }


        .schedule {
            background: #fef3c7;
            color: #92400e;
        }


        .veterinary {
            background: #dcfce7;
            color: #166534;
        }


        /* H1 */

        .h1 {
            background: #fee2e2;
            color: #b91c1c;
        }


        .no-h1 {
            background: #f1f5f9;
            color: #64748b;
        }


        /* =========================
           EXPIRY STATUS
           ========================= */

        .expired {
            background: #fee2e2;
            color: #b91c1c;
        }


        .expiring {
            background: #fef3c7;
            color: #92400e;
        }


        /* =========================
           STOCK DISPLAY
           ========================= */

        .stock-display {
            font-weight: bold;
            color: #0f172a;
        }


        .loose {
            color: #d97706;
        }


        /* =========================
           PROFIT DISPLAY
           ========================= */

        .profit {
            font-weight: bold;
            color: #15803d;
        }


        /* =========================
           NO RECORD MESSAGE
           ========================= */

        .message {
            text-align: center;
            background: white;
            color: #15803d;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            font-weight: bold;
        }


        /* =========================
           MOBILE
           ========================= */

        @media(max-width: 700px) {

            body {
                padding: 10px;
            }


            .top-section {
                flex-direction: column;
                gap: 15px;
            }


            table {
                min-width: 1650px;
            }

        }


        /* =========================
           PRINT
           ========================= */

        @media print {

            body {
                background: white;
                padding: 0;
            }


            .back-btn,
            .print-btn {
                display: none;
            }


            .table-container {
                box-shadow: none;
            }


            h2 {
                color: black;
            }


            th {
                background: #ddd !important;
                color: black !important;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     REPORT TITLE
     ========================= -->

<h2>⏰ Expiry Report</h2>


<!-- =========================
     TOP BUTTONS
     ========================= -->

<div class="top-section">

    <a href="../dashboard.php" class="back-btn">
        ← Back to Dashboard
    </a>


    <button onclick="window.print()" class="print-btn">
        🖨️ Print Report
    </button>

</div>


<?php if ($result && mysqli_num_rows($result) > 0) { ?>


<div class="table-container">

<table>

    <tr>

        <th>Medicine Name</th>

        <th>Type</th>

        <th>H1</th>

        <th>Pack Type</th>

        <th>Pack Size</th>

        <th>Batch No</th>

        <th>Expiry Date</th>

        <th>Stock Units</th>

        <th>Stock</th>

        <th>Purchase Price</th>

        <th>Purchase / Unit</th>

        <th>Selling Price</th>

        <th>Sale / Unit</th>

        <th>Profit / Unit</th>

        <th>Stock Value</th>

        <th>Status</th>

    </tr>


<?php while ($row = mysqli_fetch_assoc($result)) { ?>


<?php

/* =========================
   PACK SIZE
   ========================= */

$pack_size = (int)($row['pack_size'] ?? 1);

if ($pack_size <= 0) {
    $pack_size = 1;
}


/* =========================
   STOCK UNITS
   ========================= */

$stock_units = (int)($row['stock_units'] ?? 0);


/* =========================
   OLD RECORD FALLBACK
   ========================= */

if (
    $stock_units <= 0 &&
    isset($row['quantity']) &&
    (int)$row['quantity'] > 0
) {

    $stock_units =
        (int)$row['quantity'] * $pack_size;

}


/* =========================
   FULL PACKS + LOOSE UNITS
   ========================= */

$full_packs = intdiv(
    $stock_units,
    $pack_size
);

$loose_units = $stock_units % $pack_size;


/* =========================
   USER FRIENDLY STOCK
   ========================= */

if ($loose_units > 0) {

    $stock_display =
        $full_packs .
        " Full Packs + " .
        $loose_units .
        " Loose";

} else {

    $stock_display =
        $full_packs .
        " Full Packs";

}


/* =========================
   PURCHASE PRICE
   ========================= */

$purchase_price =
    (float)($row['purchase_price'] ?? 0);


/* =========================
   SELLING PRICE
   ========================= */

$selling_price =
    (float)($row['price'] ?? 0);


/* =========================
   PURCHASE PRICE PER UNIT
   ========================= */

$purchase_per_unit =
    $purchase_price / $pack_size;


/* =========================
   SELLING PRICE PER UNIT
   ========================= */

$sale_per_unit =
    $selling_price / $pack_size;


/* =========================
   PROFIT PER UNIT
   ========================= */

$profit_per_unit =
    $sale_per_unit - $purchase_per_unit;


/* =========================
   STOCK VALUE
   ========================= */

$stock_value =
    ($stock_units / $pack_size)
    * $purchase_price;


/* =========================
   MEDICINE TYPE
   ========================= */

$medicine_type =
    $row['medicine_type'] ?? 'General';


$type_class = 'general';


if ($medicine_type === 'Schedule') {

    $type_class = 'schedule';

}


if ($medicine_type === 'Veterinary') {

    $type_class = 'veterinary';

}


/* =========================
   H1
   ========================= */

$is_h1 =
    (int)($row['is_h1'] ?? 0);


/* =========================
   EXPIRY DATE
   ========================= */

$expiry_date =
    $row['expiry_date'] ?? '';


/* =========================
   EXPIRY STATUS

   ONLY TWO STATUS:
   - Expired
   - Expiring Soon
   ========================= */

$expiry =
    new DateTime($expiry_date);

$today_date =
    new DateTime($today);


/* EXPIRED */

if ($expiry < $today_date) {

    $status = 'Expired';

    $status_class = 'expired';

}


/* EXPIRING WITHIN 30 DAYS */

else {

    $days_left =
        (int)$today_date->diff($expiry)->days;


    if ($days_left <= 30) {

        $status = 'Expiring Soon';

        $status_class = 'expiring';

    }

}

?>


<tr>


    <!-- MEDICINE NAME -->

    <td>

        <?php

        echo htmlspecialchars(
            $row['medicine_name'] ?? '-'
        );

        ?>

    </td>


    <!-- TYPE -->

    <td>

        <span class="badge <?php echo $type_class; ?>">

            <?php

            echo htmlspecialchars(
                $medicine_type
            );

            ?>

        </span>

    </td>


    <!-- H1 -->

    <td>

        <?php if ($is_h1 == 1) { ?>

            <span class="badge h1">
                H1
            </span>

        <?php } else { ?>

            <span class="badge no-h1">
                No
            </span>

        <?php } ?>

    </td>


    <!-- PACK TYPE -->

    <td>

        <?php

        echo htmlspecialchars(
            $row['pack_type'] ?? 'Tablet'
        );

        ?>

    </td>


    <!-- PACK SIZE -->

    <td>

        1 × <?php echo $pack_size; ?>

    </td>


    <!-- BATCH -->

    <td>

        <?php

        echo htmlspecialchars(
            $row['batch_no'] ?? '-'
        );

        ?>

    </td>


    <!-- EXPIRY DATE -->

    <td>

        <?php

        echo htmlspecialchars(
            $expiry_date
        );

        ?>

    </td>


    <!-- STOCK UNITS -->

    <td>

        <?php

        echo $stock_units;

        ?>

    </td>


    <!-- STOCK -->

    <td class="stock-display">

        <?php if ($loose_units > 0) { ?>

            <?php echo $full_packs; ?> Full Packs

            <span class="loose">

                + <?php echo $loose_units; ?> Loose

            </span>

        <?php } else { ?>

            <?php echo $full_packs; ?> Full Packs

        <?php } ?>

    </td>


    <!-- PURCHASE PRICE / PACK -->

    <td>

        ₹<?php

        echo number_format(
            $purchase_price,
            2
        );

        ?>

    </td>


    <!-- PURCHASE PRICE / UNIT -->

    <td>

        ₹<?php

        echo number_format(
            $purchase_per_unit,
            2
        );

        ?>

    </td>


    <!-- SELLING PRICE / PACK -->

    <td>

        ₹<?php

        echo number_format(
            $selling_price,
            2
        );

        ?>

    </td>


    <!-- SALE PRICE / UNIT -->

    <td>

        ₹<?php

        echo number_format(
            $sale_per_unit,
            2
        );

        ?>

    </td>


    <!-- PROFIT / UNIT -->

    <td class="profit">

        ₹<?php

        echo number_format(
            $profit_per_unit,
            2
        );

        ?>

    </td>


    <!-- STOCK VALUE -->

    <td>

        ₹<?php

        echo number_format(
            $stock_value,
            2
        );

        ?>

    </td>


    <!-- STATUS -->

    <td>

        <span class="badge <?php echo $status_class; ?>">

            <?php

            echo $status;

            ?>

        </span>

    </td>


</tr>


<?php } ?>


</table>

</div>


<?php } else { ?>


<div class="message">

    ✅ No medicines are expired or expiring within the next 30 days.

</div>


<?php } ?>


</body>

</html>