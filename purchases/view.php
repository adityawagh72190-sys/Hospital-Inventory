<?php

session_start();

if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true)
{
    header("Location: ../login.php");
    exit();
}

include("../config/db.php");

$user_id = $_SESSION['user_id'] ?? 0;


/* =========================
   CURRENT USER PURCHASES
========================= */

$query = mysqli_query(
    $conn,
    "SELECT
        purchases.id,
        medicines.medicine_name,
        medicines.pack_type,
        medicines.pack_size,
        medicines.stock_units,
        suppliers.supplier_name,
        purchases.quantity,
        purchases.purchase_price,
        purchases.purchase_date
     FROM purchases

     INNER JOIN medicines
        ON purchases.medicine_id = medicines.id

     INNER JOIN suppliers
        ON purchases.supplier_id = suppliers.id

     WHERE purchases.user_id = '$user_id'

     ORDER BY purchases.id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Purchase Records - MediTrack</title>

    <style>

        *{
            box-sizing:border-box;
        }

        body{
            margin:0;
            font-family:Arial, sans-serif;

            background:
            linear-gradient(
                rgba(230,245,255,0.90),
                rgba(245,252,255,0.96)
            ),
            url("https://images.unsplash.com/photo-1585435557343-3b092031a831?auto=format&fit=crop&w=1600&q=80");

            background-size:cover;
            background-position:center;
            background-attachment:fixed;

            min-height:100vh;
            padding:35px;
        }

        .container{
            max-width:1250px;
            margin:auto;

            background:rgba(255,255,255,0.97);

            padding:30px;

            border-radius:22px;

            box-shadow:0 12px 40px rgba(0,0,0,0.15);
        }

        .header{
            display:flex;
            justify-content:space-between;
            align-items:center;

            gap:15px;
            margin-bottom:25px;
        }

        .header h1{
            margin:0;
            color:#155a92;
            font-size:30px;
        }

        .header p{
            margin:8px 0 0;
            color:#6b7280;
        }

        .add-btn{
            background:#18a957;
            color:white;

            padding:12px 20px;

            border-radius:10px;

            text-decoration:none;

            font-weight:bold;

            transition:0.2s;
        }

        .add-btn:hover{
            background:#128c48;
        }

        .table-wrapper{
            overflow-x:auto;
        }

        table{
            width:100%;
            border-collapse:collapse;

            margin-top:15px;

            min-width:1050px;
        }

        th{
            background:#155a92;
            color:white;

            padding:15px;

            text-align:left;

            font-size:14px;

            white-space:nowrap;
        }

        td{
            padding:14px 15px;

            border-bottom:1px solid #e5e7eb;

            color:#374151;

            font-size:14px;

            white-space:nowrap;
        }

        tr:hover td{
            background:#f0f8ff;
        }

        .price{
            color:#16803c;
            font-weight:bold;
        }

        .pack-badge{
            display:inline-block;

            background:#eef6ff;
            color:#155a92;

            padding:5px 9px;

            border-radius:7px;

            font-size:12px;

            font-weight:bold;
        }

        .stock-badge{
            display:inline-block;

            background:#ecfdf5;
            color:#15803d;

            padding:5px 9px;

            border-radius:7px;

            font-size:12px;

            font-weight:bold;
        }

        .empty{
            text-align:center;

            padding:35px;

            color:#6b7280;

            font-size:16px;
        }

        .back-btn{
            display:inline-block;

            margin-top:25px;

            padding:11px 20px;

            background:#eef6ff;

            color:#155a92;

            text-decoration:none;

            border-radius:10px;

            font-weight:bold;
        }

        .back-btn:hover{
            background:#dceeff;
        }

        .info-box{
            margin-top:18px;

            padding:14px 16px;

            background:#f0f8ff;

            border-left:4px solid #155a92;

            border-radius:8px;

            color:#374151;

            font-size:13px;

            line-height:1.6;
        }

        @media(max-width:768px){

            body{
                padding:15px;
            }

            .container{
                padding:20px;
            }

            .header{
                flex-direction:column;
                align-items:flex-start;
            }

            .header h1{
                font-size:25px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <div class="header">

        <div>

            <h1>🛒 Purchase Records</h1>

            <p>
                View your medicine purchase history
            </p>

        </div>

        <a href="add.php" class="add-btn">
            + Add Purchase
        </a>

    </div>


    <div class="table-wrapper">

        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Medicine</th>

                    <th>Supplier</th>

                    <th>Pack Type</th>

                    <th>Pack Size</th>

                    <th>Purchase Quantity</th>

                    <th>Purchase Price / Pack</th>

                    <th>Total Amount</th>

                    <th>Current Stock</th>

                    <th>Purchase Date</th>

                </tr>

            </thead>


            <tbody>

                <?php

                if(mysqli_num_rows($query) > 0)
                {

                    while($row = mysqli_fetch_assoc($query))
                    {

                        /*
                         * Purchase quantity is stored in PACKS.
                         *
                         * Example:
                         * 5 packs × 15 tablets = 75 units
                         */

                        $purchase_quantity =
                            (int)$row['quantity'];

                        $pack_size =
                            (int)$row['pack_size'];

                        if($pack_size <= 0)
                        {
                            $pack_size = 1;
                        }

                        $added_units =
                            $purchase_quantity * $pack_size;


                        /*
                         * Purchase price is the price
                         * of ONE FULL PACK.
                         */

                        $purchase_price =
                            (float)$row['purchase_price'];


                        $total_amount =
                            $purchase_quantity * $purchase_price;


                        $current_stock =
                            (int)($row['stock_units'] ?? 0);

                ?>

                <tr>

                    <!-- ID -->

                    <td>
                        <?php
                        echo (int)$row['id'];
                        ?>
                    </td>


                    <!-- MEDICINE -->

                    <td>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $row['medicine_name']
                            );
                            ?>
                        </strong>

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

                        <br>

                        <small style="color:#6b7280;">

                            =
                            <?php
                            echo number_format($added_units);
                            ?>
                            units

                        </small>

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

                    <td class="price">

                        ₹<?php
                        echo number_format(
                            $total_amount,
                            2
                        );
                        ?>

                    </td>


                    <!-- CURRENT STOCK -->

                    <td>

                        <span class="stock-badge">

                            <?php
                            echo number_format(
                                $current_stock
                            );
                            ?>

                            Units

                        </span>

                        <br>

                        <small style="color:#6b7280;">

                            <?php

                            $current_packs =
                                $current_stock / $pack_size;

                            echo number_format(
                                $current_packs,
                                2
                            );

                            ?>

                            pack(s)

                        </small>

                    </td>


                    <!-- PURCHASE DATE -->

                    <td>

                        <?php

                        if(!empty($row['purchase_date']))
                        {

                            echo date(
                                "d-m-Y",
                                strtotime(
                                    $row['purchase_date']
                                )
                            );

                        }
                        else
                        {

                            echo "-";

                        }

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
                        colspan="10"
                        class="empty"
                    >

                        No purchase records found.

                    </td>

                </tr>

                <?php

                }

                ?>

            </tbody>

        </table>

    </div>


    <!-- INFORMATION -->

    <div class="info-box">

        <strong>Purchase Stock Information:</strong>

        Purchase Quantity is entered in complete packs.

        For example,
        <strong>5 packs × 15 units = 75 units</strong>.

        The Purchase Price shown above is the price of
        <strong>one complete pack</strong>.

        The actual stock is maintained in individual units
        so that loose tablets can also be sold through billing.

    </div>


    <a href="../dashboard.php" class="back-btn">

        ← Back to Dashboard

    </a>

</div>

</body>

</html>