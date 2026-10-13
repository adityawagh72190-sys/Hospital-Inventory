<?php

session_start();

include("../config/db.php");

// Check login
if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


/* =========================
   PURCHASE REPORT QUERY
========================= */

$sql = "SELECT 
            p.id,
            m.medicine_name,
            m.pack_type,
            m.pack_size,
            s.supplier_name,
            p.quantity,
            p.purchase_price,
            p.purchase_date
        FROM purchases p

        JOIN medicines m 
            ON p.medicine_id = m.id

        JOIN suppliers s 
            ON p.supplier_id = s.id

        WHERE p.user_id = '$user_id'

        ORDER BY p.id DESC";


$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Purchase Report - MediTrack</title>

    <style>

        *{
            box-sizing:border-box;
        }

        body{
            font-family:Arial, sans-serif;

            background:#f4f6f9;

            padding:20px;

            margin:0;
        }

        h2{
            text-align:center;

            color:#155a92;

            margin-bottom:25px;
        }

        .top-buttons{
            margin-bottom:20px;

            display:flex;

            gap:10px;

            flex-wrap:wrap;
        }

        button,
        a{
            display:inline-block;

            padding:10px 16px;

            border:none;

            border-radius:7px;

            text-decoration:none;

            cursor:pointer;

            color:white;

            font-size:14px;
        }

        button{
            background:#007bff;
        }

        button:hover{
            background:#0069d9;
        }

        .back{
            background:#28a745;
        }

        .back:hover{
            background:#218838;
        }

        .table-wrapper{
            width:100%;

            overflow-x:auto;
        }

        table{
            width:100%;

            min-width:1100px;

            border-collapse:collapse;

            background:white;

            box-shadow:
                0 3px 10px rgba(0,0,0,0.08);
        }

        th,
        td{
            border:1px solid #ddd;

            padding:12px;

            text-align:center;

            font-size:14px;
        }

        th{
            background:#155a92;

            color:white;

            white-space:nowrap;
        }

        td{
            color:#374151;
        }

        tr:nth-child(even){
            background:#f8f9fa;
        }

        tr:hover{
            background:#eaf3ff;
        }

        .medicine-name{
            font-weight:bold;

            color:#155a92;
        }

        .pack-badge{
            display:inline-block;

            background:#eef6ff;

            color:#155a92;

            padding:5px 9px;

            border-radius:6px;

            font-size:12px;

            font-weight:bold;
        }

        .units{
            color:#15803d;

            font-weight:bold;
        }

        .price{
            color:#16803c;

            font-weight:bold;

            white-space:nowrap;
        }

        .total{
            color:#0f766e;

            font-weight:bold;

            white-space:nowrap;
        }

        .no-data{
            text-align:center;

            color:#777;

            padding:25px;
        }

        .info-box{
            margin-top:18px;

            padding:14px 16px;

            background:#eef6ff;

            border-left:4px solid #155a92;

            border-radius:7px;

            color:#374151;

            font-size:13px;

            line-height:1.6;
        }


        /* =========================
           PRINT
        ========================= */

        @media print{

            button,
            .back,
            .info-box{
                display:none;
            }

            body{
                background:white;

                padding:0;
            }

            h2{
                color:#000;
            }

            table{
                box-shadow:none;

                min-width:0;

                width:100%;
            }

            th{
                background:#ddd !important;

                color:#000 !important;
            }

        }


        /* =========================
           MOBILE
        ========================= */

        @media(max-width:768px){

            body{
                padding:12px;
            }

            h2{
                font-size:23px;
            }

            th,
            td{
                padding:10px;

                font-size:13px;
            }

        }

    </style>

</head>


<body>


<h2>
    🛒 Purchase Report
</h2>


<div class="top-buttons">

    <button onclick="window.print()">
        🖨️ Print Report
    </button>

    <a href="../dashboard.php" class="back">
        ⬅ Back to Dashboard
    </a>

</div>


<div class="table-wrapper">

<table>

    <tr>

        <th>ID</th>

        <th>Medicine</th>

        <th>Supplier</th>

        <th>Pack Type</th>

        <th>Pack Size</th>

        <th>Purchase Quantity</th>

        <th>Units Added</th>

        <th>Purchase Price / Pack</th>

        <th>Total Amount</th>

        <th>Purchase Date</th>

    </tr>


    <?php

    if(mysqli_num_rows($result) > 0){

        while($row = mysqli_fetch_assoc($result)){

            /* =========================
               PACK SIZE
            ========================= */

            $pack_size = (int)$row['pack_size'];

            if($pack_size <= 0){
                $pack_size = 1;
            }


            /* =========================
               PURCHASE QUANTITY
               Quantity = Full Packs
            ========================= */

            $purchase_quantity =
                (int)$row['quantity'];


            /* =========================
               UNITS ADDED
            ========================= */

            $units_added =
                $purchase_quantity * $pack_size;


            /* =========================
               PURCHASE PRICE
               Price of ONE FULL PACK
            ========================= */

            $purchase_price =
                (float)$row['purchase_price'];


            /* =========================
               TOTAL PURCHASE AMOUNT
            ========================= */

            $total_amount =
                $purchase_quantity * $purchase_price;

    ?>

    <tr>

        <!-- ID -->

        <td>

            <?php
            echo (int)$row['id'];
            ?>

        </td>


        <!-- MEDICINE -->

        <td class="medicine-name">

            <?php

            echo htmlspecialchars(
                $row['medicine_name']
            );

            ?>

        </td>


        <!-- SUPPLIER -->

        <td>

            <?php

            echo htmlspecialchars(
                $row['supplier_name']
            );

            ?>

        </td>


        <!-- PACK TYPE -->

        <td>

            <span class="pack-badge">

                <?php

                echo htmlspecialchars(
                    strtoupper($row['pack_type'])
                );

                ?>

            </span>

        </td>


        <!-- PACK SIZE -->

        <td>

            <span class="pack-badge">

                1 ×
                <?php
                echo $pack_size;
                ?>

            </span>

        </td>


        <!-- PURCHASE QUANTITY -->

        <td>

            <strong>

                <?php
                echo $purchase_quantity;
                ?>

            </strong>

            Pack(s)

        </td>


        <!-- UNITS ADDED -->

        <td class="units">

            <?php

            echo number_format(
                $units_added
            );

            ?>

            Units

        </td>


        <!-- PURCHASE PRICE -->

        <td class="price">

            ₹<?php

            echo number_format(
                $purchase_price,
                2
            );

            ?>

        </td>


        <!-- TOTAL AMOUNT -->

        <td class="total">

            ₹<?php

            echo number_format(
                $total_amount,
                2
            );

            ?>

        </td>


        <!-- PURCHASE DATE -->

        <td>

            <?php

            if(!empty($row['purchase_date'])){

                echo date(
                    "d-m-Y",
                    strtotime(
                        $row['purchase_date']
                    )
                );

            }
            else{

                echo "-";

            }

            ?>

        </td>

    </tr>


    <?php

        }

    }
    else{

    ?>

    <tr>

        <td
            colspan="10"
            class="no-data"
        >

            No purchase records found.

        </td>

    </tr>

    <?php

    }

    ?>

</table>

</div>


<!-- INFORMATION -->

<div class="info-box">

    <strong>Purchase Information:</strong>

    Purchase Quantity is entered in complete packs.

    For example,

    <strong>5 Packs × 15 Units = 75 Units</strong>.

    Purchase Price represents the price of
    <strong>one complete pack</strong>.

    The total amount is calculated as:

    <strong>
        Purchase Quantity × Purchase Price / Pack
    </strong>.

</div>


</body>

</html>