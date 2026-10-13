<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include("../config/db.php");

$user_id = (int)$_SESSION['user_id'];

date_default_timezone_set('Asia/Kolkata');


/* =========================
   FILTER
========================= */

$filter = $_GET['filter'] ?? 'today';

$where_date = "";

if ($filter === 'today') {

    $where_date = "AND s.sale_date = CURDATE()";

} elseif ($filter === 'month') {

    $where_date = "
        AND YEAR(s.sale_date) = YEAR(CURDATE())
        AND MONTH(s.sale_date) = MONTH(CURDATE())
    ";

} else {

    $filter = 'all';

}


/* =========================
   H1 SALES DATA
========================= */

$sql = "
    SELECT
        s.id,
        s.sale_date,
        m.medicine_name,
        s.quantity,
        s.purchase_price,
        s.selling_price,
        s.patient_name,
        s.doctor_name,
        s.patient_address,
        s.prescription_verified
    FROM sales s

    INNER JOIN medicines m
        ON s.medicine_id = m.id

    WHERE s.user_id = '$user_id'
      AND m.is_h1 = 1

    $where_date

    ORDER BY s.sale_date DESC, s.id DESC
";

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>H1 Medicine Sales Report</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f4f7fb;

    color: #222;
}


/* =========================
   HEADER
========================= */

.header {

    background: #0b5ed7;

    color: white;

    padding: 20px 30px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;
}

.header h1 {

    margin: 0;

    font-size: 26px;
}

.header p {

    margin: 5px 0 0;

    opacity: 0.9;

    font-size: 14px;
}


/* =========================
   BUTTONS
========================= */

.buttons {

    display: flex;

    gap: 10px;

    flex-wrap: wrap;
}

.btn {

    text-decoration: none;

    border: none;

    padding: 10px 16px;

    border-radius: 6px;

    cursor: pointer;

    font-size: 14px;

    font-weight: bold;

    display: inline-block;
}

.btn-dashboard {

    background: white;

    color: #0b5ed7;
}

.btn-print {

    background: #198754;

    color: white;
}


/* =========================
   CONTAINER
========================= */

.container {

    padding: 25px 30px;

    max-width: 1600px;

    margin: auto;
}


/* =========================
   FILTER
========================= */

.filters {

    display: flex;

    gap: 10px;

    margin-bottom: 25px;

    flex-wrap: wrap;
}

.filter-btn {

    text-decoration: none;

    padding: 10px 18px;

    border-radius: 6px;

    background: white;

    color: #333;

    border: 1px solid #ddd;

    font-weight: bold;
}

.filter-btn.active {

    background: #0b5ed7;

    color: white;

    border-color: #0b5ed7;
}


/* =========================
   TABLE
========================= */

.table-box {

    background: white;

    border-radius: 10px;

    padding: 15px;

    box-shadow:
        0 2px 8px rgba(0,0,0,0.08);

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1250px;
}

th {

    background: #0b5ed7;

    color: white;

    padding: 12px 10px;

    text-align: left;

    font-size: 13px;

    white-space: nowrap;
}

td {

    padding: 11px 10px;

    border-bottom: 1px solid #eee;

    font-size: 13px;

    vertical-align: top;
}

tr:hover {

    background: #f7faff;
}


/* =========================
   BADGES
========================= */

.verified {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 15px;

    background: #d1e7dd;

    color: #0f5132;

    font-weight: bold;

    font-size: 12px;
}

.not-verified {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 15px;

    background: #f8d7da;

    color: #842029;

    font-weight: bold;

    font-size: 12px;
}


/* =========================
   NO DATA
========================= */

.no-data {

    text-align: center;

    padding: 30px;

    color: #777;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 900px) {

    .header {

        flex-direction: column;

        align-items: flex-start;
    }
}


/* =========================
   PRINT
========================= */

@media print {

    body {

        background: white;
    }

    .buttons,
    .filters {

        display: none;
    }

    .header {

        background: white;

        color: black;

        padding: 10px 0;
    }

    .header h1 {

        color: black;
    }

    .container {

        padding: 10px 0;
    }

    .table-box {

        box-shadow: none;

        border: 1px solid #ddd;
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
     HEADER
========================= -->

<div class="header">

    <div>

        <h1>H1 Medicine Sales Report</h1>

        <p>
            Sales report for H1 medicines with patient details
        </p>

    </div>


    <div class="buttons">

        <a
            href="../dashboard.php"
            class="btn btn-dashboard"
        >
            ← Back to Dashboard
        </a>


        <button
            onclick="window.print()"
            class="btn btn-print"
        >
            🖨 Print
        </button>

    </div>

</div>


<div class="container">


<!-- =========================
     FILTERS
========================= -->

<div class="filters">

    <a
        href="?filter=today"
        class="filter-btn
        <?php echo $filter === 'today'
            ? 'active'
            : ''; ?>"
    >
        Today
    </a>


    <a
        href="?filter=month"
        class="filter-btn
        <?php echo $filter === 'month'
            ? 'active'
            : ''; ?>"
    >
        This Month
    </a>


    <a
        href="?filter=all"
        class="filter-btn
        <?php echo $filter === 'all'
            ? 'active'
            : ''; ?>"
    >
        Overall
    </a>

</div>


<!-- =========================
     SALES TABLE
========================= -->

<div class="table-box">

<table>

<thead>

<tr>

    <th>Sale ID</th>

    <th>Date</th>

    <th>Medicine</th>

    <th>Qty</th>

    <th>Purchase / Unit</th>

    <th>Selling / Unit</th>

    <th>Sale Amount</th>

    <th>Profit</th>

    <th>Patient Name</th>

    <th>Doctor Name</th>

    <th>Patient Address</th>

    <th>Prescription</th>

</tr>

</thead>


<tbody>

<?php

if ($result && mysqli_num_rows($result) > 0) {

    while ($row = mysqli_fetch_assoc($result)) {

        $quantity =
            (int)$row['quantity'];

        $purchase_price =
            (float)$row['purchase_price'];

        $selling_price =
            (float)$row['selling_price'];


        $sale_amount =
            $quantity * $selling_price;


        $profit =
            $quantity *
            (
                $selling_price
                -
                $purchase_price
            );

?>

<tr>

    <td>
        <?php echo (int)$row['id']; ?>
    </td>


    <td>
        <?php
        echo date(
            'd-m-Y',
            strtotime($row['sale_date'])
        );
        ?>
    </td>


    <td>
        <strong>
            <?php
            echo htmlspecialchars(
                $row['medicine_name']
            );
            ?>
        </strong>
    </td>


    <td>
        <?php echo $quantity; ?>
    </td>


    <td>
        ₹<?php
        echo number_format(
            $purchase_price,
            2
        );
        ?>
    </td>


    <td>
        ₹<?php
        echo number_format(
            $selling_price,
            2
        );
        ?>
    </td>


    <td>
        ₹<?php
        echo number_format(
            $sale_amount,
            2
        );
        ?>
    </td>


    <td>
        ₹<?php
        echo number_format(
            $profit,
            2
        );
        ?>
    </td>


    <td>
        <?php
        echo htmlspecialchars(
            $row['patient_name']
            ?? ''
        );
        ?>
    </td>


    <td>
        <?php
        echo htmlspecialchars(
            $row['doctor_name']
            ?? ''
        );
        ?>
    </td>


    <td>
        <?php
        echo htmlspecialchars(
            $row['patient_address']
            ?? ''
        );
        ?>
    </td>


    <td>

        <?php

        if (
            (int)$row[
                'prescription_verified'
            ] === 1
        ) {

            echo '<span class="verified">
                    Verified
                  </span>';

        } else {

            echo '<span class="not-verified">
                    Not Verified
                  </span>';
        }

        ?>

    </td>

</tr>

<?php

    }

} else {

?>

<tr>

    <td
        colspan="12"
        class="no-data"
    >
        No H1 medicine sales found.
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