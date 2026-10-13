<?php

session_start();

include("../config/db.php");


// =========================
// CHECK LOGIN
// =========================

if(!isset($_SESSION['user_id']))
{
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// =========================
// REPORT FILTER
// =========================

$filter = $_GET['date'] ?? 'all';


// =========================
// SQL QUERY
// =========================

if($filter == 'today')
{
    $sql = "SELECT
                s.id,
                m.medicine_name,
                m.pack_size,
                s.quantity,
                s.purchase_price,
                s.selling_price,
                s.discount_amount,
                s.payment_method,
                s.sale_date
            FROM sales s
            JOIN medicines m
            ON s.medicine_id = m.id
            WHERE s.user_id = '$user_id'
            AND DATE(s.sale_date) = CURDATE()
            ORDER BY s.id DESC";

    $report_title = "Today's Sales";
    $report_subtitle = "Sales made today";
}

elseif($filter == 'month')
{
    $sql = "SELECT
                s.id,
                m.medicine_name,
                m.pack_size,
                s.quantity,
                s.purchase_price,
                s.selling_price,
                s.discount_amount,
                s.payment_method,
                s.sale_date
            FROM sales s
            JOIN medicines m
            ON s.medicine_id = m.id
            WHERE s.user_id = '$user_id'
            AND MONTH(s.sale_date) = MONTH(CURDATE())
            AND YEAR(s.sale_date) = YEAR(CURDATE())
            ORDER BY s.id DESC";

    $report_title = "This Month's Sales";
    $report_subtitle = "Sales made during the current month";
}

else
{
    $sql = "SELECT
                s.id,
                m.medicine_name,
                m.pack_size,
                s.quantity,
                s.purchase_price,
                s.selling_price,
                s.discount_amount,
                s.payment_method,
                s.sale_date
            FROM sales s
            JOIN medicines m
            ON s.medicine_id = m.id
            WHERE s.user_id = '$user_id'
            ORDER BY s.id DESC";

    $report_title = "Overall Sales";
    $report_subtitle = "Complete sales history";
}


$result = mysqli_query($conn, $sql);


// =========================
// TOTALS
// =========================

$total_amount = 0;
$total_profit = 0;
$total_loss = 0;
$total_quantity = 0;
$total_transactions = 0;

?>


<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sales Report - MediTrack</title>


    <style>

        *{
            box-sizing:border-box;
        }


        body{

            font-family:Arial,sans-serif;

            margin:0;

            padding:30px;

            background:
            linear-gradient(
                rgba(255,255,255,0.72),
                rgba(255,255,255,0.72)
            ),

            url('https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&fit=crop&w=1600&q=80')

            no-repeat center center fixed;

            background-size:cover;
        }


        .topbar{

            background:#1e3a8a;

            color:white;

            padding:15px 25px;

            border-radius:14px;

            margin-bottom:25px;

            display:flex;

            justify-content:space-between;

            align-items:center;

            box-shadow:
            0 4px 12px rgba(0,0,0,0.15);
        }


        .topbar h2{

            margin:0;

            font-size:22px;
        }


        .topbar a{

            color:white;

            text-decoration:none;

            background:#2563eb;

            padding:9px 16px;

            border-radius:8px;

            font-weight:bold;
        }


        .topbar a:hover{

            background:#1d4ed8;
        }


        .container{

            max-width:1200px;

            margin:auto;

            background:rgba(255,255,255,0.97);

            padding:30px;

            border-radius:20px;

            box-shadow:
            0 10px 30px rgba(0,0,0,0.15);
        }


        .heading{

            text-align:center;

            margin-bottom:25px;
        }


        .heading h1{

            margin:0;

            color:#1e3a8a;

            font-size:30px;
        }


        .heading p{

            color:#64748b;

            margin-top:8px;
        }


        /* =========================
           FILTER BUTTONS
        ========================= */

        .filters{

            display:flex;

            justify-content:center;

            gap:10px;

            flex-wrap:wrap;

            margin-bottom:25px;
        }


        .filter-btn{

            text-decoration:none;

            padding:10px 18px;

            border-radius:9px;

            font-weight:bold;

            color:#2563eb;

            background:#eff6ff;

            border:1px solid #bfdbfe;

            transition:0.3s;
        }


        .filter-btn:hover{

            background:#dbeafe;
        }


        .filter-btn.active{

            background:#2563eb;

            color:white;

            border-color:#2563eb;
        }


        .print-btn{

            background:#334155;

            color:white;

            border:none;

            padding:10px 18px;

            border-radius:9px;

            font-weight:bold;

            cursor:pointer;

            margin-left:5px;
        }


        .print-btn:hover{

            background:#1e293b;
        }


        /* =========================
           SUMMARY
        ========================= */

        .summary{

            display:flex;

            justify-content:center;

            gap:20px;

            flex-wrap:wrap;

            margin-top:25px;

            margin-bottom:25px;
        }


        .summary-box{

            min-width:180px;

            padding:15px 22px;

            background:#f8fafc;

            border:1px solid #e2e8f0;

            border-radius:12px;

            text-align:center;
        }


        .summary-box span{

            display:block;

            color:#64748b;

            font-size:13px;

            margin-bottom:5px;
        }


        .summary-box strong{

            color:#1e3a8a;

            font-size:20px;
        }


        /* =========================
           PROFIT CARD
        ========================= */

        .profit-box{

            border-color:#bbf7d0;

            background:#f0fdf4;
        }


        .profit-box strong{

            color:#16a34a;
        }


        /* =========================
           LOSS CARD
        ========================= */

        .loss-box{

            border-color:#fecaca;

            background:#fef2f2;
        }


        .loss-box strong{

            color:#dc2626;
        }


        /* =========================
           TABLE
        ========================= */

        .table-wrapper{

            width:100%;

            overflow-x:auto;
        }


        table{

            width:100%;

            border-collapse:collapse;

            min-width:1500px;
        }


        th{

            background:#1e3a8a;

            color:white;

            padding:13px 10px;

            text-align:center;

            font-size:14px;
        }


        td{

            padding:12px 10px;

            border-bottom:1px solid #e2e8f0;

            text-align:center;

            color:#334155;

            font-size:14px;
        }


        tr:hover td{

            background:#f8fafc;
        }


        .amount{

            color:#1e3a8a;

            font-weight:bold;
        }


        /* =========================
           PROFIT / LOSS
        ========================= */

        .profit{

            font-weight:bold;
        }


        .profit.gain{

            color:#16a34a;
        }


        .profit.loss{

            color:#dc2626;
        }


        /* =========================
           PAYMENT MODE
        ========================= */

        .payment{

            font-weight:bold;

            color:#334155;
        }


        .payment.upi{

            color:#7c3aed;
        }


        .payment.cash{

            color:#16a34a;
        }


        .payment.card{

            color:#2563eb;
        }


        .no-data{

            text-align:center;

            padding:40px;

            color:#64748b;

            font-size:17px;

            background:#f8fafc;
        }


        .back{

            text-align:center;

            margin-top:25px;
        }


        .back a{

            display:inline-block;

            background:#eff6ff;

            color:#2563eb;

            text-decoration:none;

            font-weight:bold;

            padding:10px 18px;

            border-radius:10px;

            border:1px solid #bfdbfe;
        }


        .back a:hover{

            background:#dbeafe;
        }


        /* =========================
           MOBILE
        ========================= */

        @media(max-width:700px){

            body{

                padding:15px;
            }


            .topbar{

                flex-direction:column;

                gap:12px;

                text-align:center;
            }


            .container{

                padding:18px;
            }


            .heading h1{

                font-size:24px;
            }

        }


        /* =========================
           PRINT
        ========================= */

        @media print{

            body{

                background:white;

                padding:0;
            }


            .topbar{

                background:#1e3a8a !important;

                -webkit-print-color-adjust:exact;

                print-color-adjust:exact;
            }


            .filters,
            .back{

                display:none;
            }


            .container{

                box-shadow:none;

                padding:10px;

                max-width:none;
            }


            table{

                min-width:0;

                font-size:11px;
            }


            th,
            td{

                padding:7px 5px;

                font-size:11px;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     TOP BAR
========================= -->

<div class="topbar">

    <h2>
        💰 MediTrack Sales Management
    </h2>


    <a href="../dashboard.php">
        🏠 Dashboard
    </a>

</div>


<!-- =========================
     MAIN CONTAINER
========================= -->

<div class="container">


    <div class="heading">

        <h1>
            📊 <?php echo $report_title; ?>
        </h1>

        <p>
            <?php echo $report_subtitle; ?>
        </p>

    </div>


    <!-- =========================
         FILTER BUTTONS
    ========================= -->

    <div class="filters">

        <a
            href="sales_report.php?date=today"
            class="filter-btn <?php echo ($filter == 'today') ? 'active' : ''; ?>"
        >
            📅 Today
        </a>


        <a
            href="sales_report.php?date=month"
            class="filter-btn <?php echo ($filter == 'month') ? 'active' : ''; ?>"
        >
            📆 This Month
        </a>


        <a
            href="sales_report.php"
            class="filter-btn <?php echo ($filter == 'all') ? 'active' : ''; ?>"
        >
            📋 Overall
        </a>


        <button
            onclick="window.print()"
            class="print-btn"
        >
            🖨 Print
        </button>

    </div>


    <!-- =========================
         SALES TABLE
    ========================= -->

    <div class="table-wrapper">

        <table>

            <tr>

                <th>
                    Sale ID
                </th>

                <th>
                    Date & Time
                </th>

                <th>
                    Medicine
                </th>

                <th>
                    Pack Size
                </th>

                <th>
                    Qty
                </th>

                <th>
                    Purchase / Unit
                </th>

                <th>
                    Selling / Unit
                </th>

                <th>
                    Discount
                </th>

                <th>
                    Sale Amount
                </th>

                <th>
                    Profit / Loss
                </th>

                <th>
                    Payment Mode
                </th>

            </tr>


            <?php

            if(mysqli_num_rows($result) > 0)
            {

                while($row = mysqli_fetch_assoc($result))
                {

                    // =========================
                    // BASIC VALUES
                    // =========================

                    $quantity =
                    (int)$row['quantity'];


                    $pack_size =
                    (int)($row['pack_size'] ?? 1);


                    if($pack_size <= 0)
                    {
                        $pack_size = 1;
                    }


                    $pack_display =
                    "1 × " . $pack_size;


                    $purchase_price =
                    (float)$row['purchase_price'];


                    $selling_price =
                    (float)$row['selling_price'];


                    // =========================
                    // DISCOUNT
                    // =========================

                    $discount_amount =
                    (float)($row['discount_amount'] ?? 0);


                    // =========================
                    // GROSS AMOUNT
                    // =========================

                    $gross_amount =
                    $quantity * $selling_price;


                    // =========================
                    // ACTUAL SALE AFTER DISCOUNT
                    // =========================

                    $sale_amount =
                    $gross_amount - $discount_amount;


                    // =========================
                    // PURCHASE COST
                    // =========================

                    $purchase_amount =
                    $quantity * $purchase_price;


                    // =========================
                    // PROFIT / LOSS
                    // =========================

                    $profit_loss =
                    $sale_amount - $purchase_amount;


                    // =========================
                    // SUMMARY TOTALS
                    // =========================

                    $total_quantity +=
                    $quantity;


                    $total_amount +=
                    $sale_amount;


                    if($profit_loss >= 0)
                    {
                        $total_profit +=
                        $profit_loss;
                    }
                    else
                    {
                        $total_loss +=
                        abs($profit_loss);
                    }


                    $total_transactions++;


                    // =========================
                    // PAYMENT METHOD
                    // =========================

                    $payment_method =
                    trim(
                        $row['payment_method']
                        ?? ''
                    );


                    /*
                     * If billing stored
                     * "Online", show UPI.
                     */

                    if(
                        strtolower($payment_method)
                        == 'online'
                    )
                    {
                        $payment_method =
                        'UPI';
                    }


                    if($payment_method == '')
                    {
                        $payment_method =
                        'Cash';
                    }


                    $payment_display =
                    ucfirst(
                        strtolower(
                            $payment_method
                        )
                    );


                    $payment_class =
                    strtolower(
                        $payment_method
                    );


                    // =========================
                    // PROFIT / LOSS CLASS
                    // =========================

                    $profit_class =
                    ($profit_loss < 0)
                    ? 'loss'
                    : 'gain';

            ?>


            <tr>

                <td>
                    <?php echo $row['id']; ?>
                </td>


                <td>

                    <?php

                    echo date(
                        "d-m-Y h:i A",
                        strtotime(
                            $row['sale_date']
                        )
                    );

                    ?>

                </td>


                <td>

                    <?php

                    echo htmlspecialchars(
                        $row['medicine_name']
                    );

                    ?>

                </td>


                <td>

                    <?php

                    echo htmlspecialchars(
                        $pack_display
                    );

                    ?>

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
                        $discount_amount,
                        2
                    );

                    ?>

                </td>


                <td class="amount">

                    ₹<?php

                    echo number_format(
                        $sale_amount,
                        2
                    );

                    ?>

                </td>


                <td class="profit <?php echo $profit_class; ?>">

                    <?php

                    /*
                     * Negative value automatically
                     * shows the loss with minus sign.
                     */

                    if($profit_loss < 0)
                    {
                        echo "-₹" .
                        number_format(
                            abs($profit_loss),
                            2
                        );
                    }
                    else
                    {
                        echo "₹" .
                        number_format(
                            $profit_loss,
                            2
                        );
                    }

                    ?>

                </td>


                <td class="payment <?php echo $payment_class; ?>">

                    <?php

                    echo htmlspecialchars(
                        $payment_display
                    );

                    ?>

                </td>

            </tr>


            <?php

                }

            }

            else

            {

            ?>

            <tr>

                <td
                    colspan="11"
                    class="no-data"
                >

                    <?php

                    if($filter == 'today')
                    {
                        echo "📅 No sales found today.";
                    }

                    elseif($filter == 'month')
                    {
                        echo "📆 No sales found this month.";
                    }

                    else
                    {
                        echo "📋 No sales records found.";
                    }

                    ?>

                </td>

            </tr>

            <?php

            }

            ?>

        </table>

    </div>


    <!-- =========================
         SUMMARY CARDS
    ========================= -->

    <?php if(mysqli_num_rows($result) > 0){ ?>

    <div class="summary">


        <!-- TOTAL QUANTITY -->

        <div class="summary-box">

            <span>
                Total Quantity
            </span>

            <strong>

                <?php

                echo $total_quantity;

                ?>

            </strong>

        </div>


        <!-- TOTAL SALES -->

        <div class="summary-box">

            <span>
                Total Sales
            </span>

            <strong>

                ₹<?php

                echo number_format(
                    $total_amount,
                    2
                );

                ?>

            </strong>

        </div>


        <!-- TOTAL PROFIT -->

        <div class="summary-box profit-box">

            <span>
                Total Profit
            </span>

            <strong>

                ₹<?php

                echo number_format(
                    $total_profit,
                    2
                );

                ?>

            </strong>

        </div>


        <!-- TOTAL LOSS -->

        <div class="summary-box loss-box">

            <span>
                Total Loss
            </span>

            <strong>

                ₹<?php

                echo number_format(
                    $total_loss,
                    2
                );

                ?>

            </strong>

        </div>


        <!-- TRANSACTIONS -->

        <div class="summary-box">

            <span>
                Transactions
            </span>

            <strong>

                <?php

                echo $total_transactions;

                ?>

            </strong>

        </div>


    </div>

    <?php } ?>


    <!-- =========================
         BACK
    ========================= -->

    <div class="back">

        <a href="../dashboard.php">

            ⬅ Back to Dashboard

        </a>

    </div>


</div>


</body>

</html>