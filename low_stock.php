<?php

session_start();

if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true)
{
    header("Location: login.php");
    exit();
}

include("config/db.php");

$user_id = $_SESSION['user_id'] ?? 0;


/* =========================
   LOW STOCK MEDICINES
========================= */

$sql = "SELECT *
        FROM medicines
        WHERE stock_units < 20
        AND user_id = '$user_id'
        ORDER BY stock_units ASC";

$result = mysqli_query($conn, $sql);

$lowStockCount = mysqli_num_rows($result);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Low Stock Medicines - MediTrack</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        .header {
            background: #1e3a8a;
            color: white;
            padding: 22px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            margin: 0;
            font-size: 25px;
        }

        .header p {
            margin: 7px 0 0;
            color: #dbeafe;
            font-size: 14px;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .header-buttons a {
            text-decoration: none;
            color: white;
            background: #2563eb;
            padding: 11px 17px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.3s;
        }

        .header-buttons a:hover {
            background: #1d4ed8;
        }

        .container {
            width: 95%;
            max-width: 1250px;
            margin: 30px auto;
        }

        .page-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 22px;
        }

        .page-title h2 {
            margin: 0;
            font-size: 24px;
            color: #1e293b;
        }

        .page-title p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .count-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
        }

        .table-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(15, 23, 42, 0.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1250px;
        }

        thead {
            background: #1e3a8a;
            color: white;
        }

        th {
            padding: 16px 14px;
            text-align: left;
            font-size: 14px;
            white-space: nowrap;
        }

        td {
            padding: 15px 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            color: #334155;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .low-quantity {
            display: inline-block;
            background: #fee2e2;
            color: #b91c1c;
            padding: 6px 11px;
            border-radius: 7px;
            font-weight: 700;
        }

        .expiry-date {
            color: #475569;
        }

        .empty-box {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(15, 23, 42, 0.08);
            text-align: center;
            padding: 60px 20px;
        }

        .empty-box .icon {
            font-size: 55px;
            margin-bottom: 15px;
        }

        .empty-box h3 {
            margin: 0;
            color: #16a34a;
            font-size: 22px;
        }

        .empty-box p {
            color: #64748b;
            margin-top: 10px;
            font-size: 14px;
        }

        .footer {
            text-align: center;
            color: #64748b;
            font-size: 13px;
            margin: 35px 0 20px;
        }

        @media(max-width: 600px) {

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 21px;
            }

            .container {
                width: 92%;
                margin-top: 22px;
            }

            .page-title h2 {
                font-size: 21px;
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

        <h1>⚠️ Low Stock Medicines</h1>

        <p>
            MediTrack Pharmacy Management System
        </p>

    </div>

    <div class="header-buttons">

        <a href="dashboard.php">
            ← Dashboard
        </a>

        <a href="medicines/view.php">
            💊 All Medicines
        </a>

    </div>

</div>


<!-- =========================
     MAIN CONTAINER
========================= -->

<div class="container">

    <div class="page-title">

        <div>

            <h2>
                Low Stock Report
            </h2>

            <p>
                Medicines having stock below 20 pieces
            </p>

        </div>

        <div class="count-badge">

            Low Stock:
            <?php echo $lowStockCount; ?>

        </div>

    </div>


    <?php if($lowStockCount > 0) { ?>

        <div class="table-card">

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

                        <th>Price</th>

                    </tr>

                </thead>

                <tbody>

                    <?php while($row = mysqli_fetch_assoc($result)) { ?>

                        <?php

                        /*
                         * Pack size protection
                         */
                        $pack_size = (int)($row['pack_size'] ?? 1);

                        if($pack_size <= 0)
                        {
                            $pack_size = 1;
                        }


                        /*
                         * Actual available stock
                         */
                        $stock_units = (int)($row['stock_units'] ?? 0);


                        /*
                         * Calculate full packs and loose units
                         */
                        $full_packs = intdiv(
                            $stock_units,
                            $pack_size
                        );

                        $loose_units = $stock_units % $pack_size;

                        ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($row['id']); ?>
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
                                <?php
                                echo htmlspecialchars(
                                    $row['category']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['batch_no']
                                );
                                ?>
                            </td>

                            <td class="expiry-date">
                                <?php
                                echo htmlspecialchars(
                                    $row['expiry_date']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['pack_type'] ?? 'Tablet'
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo $pack_size; ?>
                            </td>

                            <td>
                                <?php echo $full_packs; ?>
                            </td>

                            <td>
                                <?php echo $loose_units; ?>
                            </td>

                            <td>

                                <span class="low-quantity">

                                    <?php echo $stock_units; ?>

                                    units

                                </span>

                            </td>

                            <td>

                                ₹<?php
                                echo number_format(
                                    (float)$row['price'],
                                    2
                                );
                                ?>

                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </div>

    <?php } else { ?>

        <div class="empty-box">

            <div class="icon">
                ✅
            </div>

            <h3>
                No Low Stock Medicines Found
            </h3>

            <p>
                All your medicines currently have stock of 20 pieces or more.
            </p>

        </div>

    <?php } ?>


    <div class="footer">

        © 2025 MediTrack Pharmacy Management System

    </div>

</div>


</body>

</html>