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
   FETCH CURRENT USER MEDICINES
========================= */

$medicine = mysqli_query(
    $conn,
    "SELECT
        id,
        medicine_name,
        pack_type,
        pack_size,
        quantity,
        stock_units,
        purchase_price
     FROM medicines
     WHERE user_id = '$user_id'
     ORDER BY medicine_name ASC"
);


/* =========================
   FETCH CURRENT USER SUPPLIERS
========================= */

$supplier = mysqli_query(
    $conn,
    "SELECT
        id,
        supplier_name
     FROM suppliers
     WHERE user_id = '$user_id'
     ORDER BY supplier_name ASC"
);


/* =========================
   SAVE PURCHASE
========================= */

if(isset($_POST['save']))
{
    $medicine_id    = intval($_POST['medicine_id'] ?? 0);
    $supplier_id    = intval($_POST['supplier_id'] ?? 0);
    $batch_no       = trim($_POST['batch_no'] ?? '');
    $quantity       = intval($_POST['quantity'] ?? 0);
    $purchase_price = floatval($_POST['purchase_price'] ?? 0);
    $purchase_date  = $_POST['purchase_date'] ?? '';


    /* =========================
       BASIC VALIDATION
    ========================= */

    if($medicine_id <= 0)
    {
        $error = "Please select a medicine.";
    }
    elseif($supplier_id <= 0)
    {
        $error = "Please select a supplier.";
    }
    elseif($quantity <= 0)
    {
        $error = "Purchase quantity must be greater than 0.";
    }
    elseif($purchase_price < 0)
    {
        $error = "Purchase price cannot be negative.";
    }
    elseif($purchase_date == '')
    {
        $error = "Please select purchase date.";
    }
    else
    {

        /* =========================
           VERIFY MEDICINE BELONGS TO USER
        ========================= */

        $medicine_check = mysqli_query(
            $conn,
            "SELECT
                id,
                medicine_name,
                pack_size,
                quantity,
                stock_units,
                purchase_price
             FROM medicines
             WHERE id = '$medicine_id'
             AND user_id = '$user_id'
             LIMIT 1"
        );


        if(!$medicine_check || mysqli_num_rows($medicine_check) == 0)
        {
            $error = "Invalid medicine selected.";
        }
        else
        {

            $medicine_row = mysqli_fetch_assoc($medicine_check);


            /* =========================
               VERIFY SUPPLIER BELONGS TO USER
            ========================= */

            $supplier_check = mysqli_query(
                $conn,
                "SELECT id
                 FROM suppliers
                 WHERE id = '$supplier_id'
                 AND user_id = '$user_id'
                 LIMIT 1"
            );


            if(!$supplier_check || mysqli_num_rows($supplier_check) == 0)
            {
                $error = "Invalid supplier selected.";
            }
            else
            {

                /* =========================
                   GET PACK SIZE
                ========================= */

                $pack_size = intval(
                    $medicine_row['pack_size'] ?? 1
                );

                if($pack_size < 1)
                {
                    $pack_size = 1;
                }


                /* =========================
                   CALCULATE ACTUAL UNITS

                   Example:
                   5 packs × 15 tablets
                   = 75 tablets
                ========================= */

                $additional_units = $quantity * $pack_size;


                /* =========================
                   START DATABASE TRANSACTION
                ========================= */

                mysqli_begin_transaction($conn);


                try
                {

                    /* =========================
                       INSERT PURCHASE RECORD

                       Purchase price is stored
                       separately for this purchase.
                    ========================= */

                    $batch_no_sql = mysqli_real_escape_string(
                        $conn,
                        $batch_no
                    );

                    $insert_purchase = mysqli_query(
                        $conn,
                        "INSERT INTO purchases
                        (
                            medicine_id,
                            supplier_id,
                            batch_no,
                            quantity,
                            pack_size,
                            remaining_units,
                            purchase_price,
                            purchase_date,
                            user_id
                        )
                        VALUES
                        (
                            '$medicine_id',
                            '$supplier_id',
                            '$batch_no_sql',
                            '$quantity',
                            '$pack_size',
                            '$additional_units',
                            '$purchase_price',
                            '$purchase_date',
                            '$user_id'
                        )"
                    );


                    if(!$insert_purchase)
                    {
                        throw new Exception(
                            "Failed to save purchase."
                        );
                    }


                    /* =========================
                       UPDATE MEDICINE STOCK

                       quantity = packs
                       stock_units = actual units

                       IMPORTANT:
                       purchase_price is NOT updated here.

                       This prevents a new purchase price
                       from changing the old medicine cost.
                    ========================= */

                    $update_medicine = mysqli_query(
                        $conn,
                        "UPDATE medicines
                         SET
                            quantity = quantity + $quantity,

                            stock_units = stock_units + $additional_units

                         WHERE id = '$medicine_id'
                         AND user_id = '$user_id'"
                    );


                    if(!$update_medicine)
                    {
                        throw new Exception(
                            "Failed to update medicine stock."
                        );
                    }


                    /* =========================
                       COMMIT
                    ========================= */

                    mysqli_commit($conn);


                    echo "<script>
                            alert(
                                'Purchase Added Successfully!\\n\\n' +
                                'Stock updated: $additional_units units'
                            );
                            window.location='add.php';
                          </script>";

                    exit();

                }
                catch(Exception $e)
                {

                    /* =========================
                       ROLLBACK
                    ========================= */

                    mysqli_rollback($conn);

                    $error = $e->getMessage();
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Add Purchase - MediTrack</title>

<style>

*{
    box-sizing:border-box;
}

body{
    margin:0;

    font-family:Arial, sans-serif;

    background:
    linear-gradient(
        rgba(230,245,255,0.88),
        rgba(245,252,255,0.94)
    ),
    url("https://images.unsplash.com/photo-1585435557343-3b092031a831?auto=format&fit=crop&w=1600&q=80");

    background-size:cover;

    background-position:center;

    background-attachment:fixed;

    min-height:100vh;

    display:flex;

    justify-content:center;

    align-items:center;
}

.container{
    width:650px;

    background:rgba(255,255,255,0.96);

    padding:35px 45px;

    border-radius:25px;

    box-shadow:
        0 12px 40px rgba(0,0,0,0.18);

    backdrop-filter:blur(5px);
}

.title{
    text-align:center;

    margin-bottom:28px;
}

.title h1{
    margin:0;

    color:#155a92;

    font-size:32px;
}

.title p{
    margin-top:8px;

    color:#6b7280;

    font-size:15px;
}


/* =========================
   ERROR
========================= */

.error{
    background:#fee2e2;

    color:#b91c1c;

    padding:13px 15px;

    border-radius:10px;

    margin-bottom:20px;

    font-size:14px;

    font-weight:bold;

    border-left:4px solid #dc2626;
}


/* =========================
   SUCCESS INFO
========================= */

.info-box{
    background:#eff6ff;

    border:1px solid #bfdbfe;

    color:#1e40af;

    padding:13px 15px;

    border-radius:10px;

    margin-bottom:20px;

    font-size:13px;

    line-height:1.5;
}


/* =========================
   LABELS
========================= */

label{
    display:block;

    margin-top:17px;

    margin-bottom:7px;

    color:#263746;

    font-size:16px;

    font-weight:bold;
}


/* =========================
   INPUTS
========================= */

select,
input{
    width:100%;

    padding:14px 15px;

    border:1px solid #cbd5e1;

    border-radius:11px;

    font-size:16px;

    background:#ffffff;

    outline:none;

    transition:0.2s;
}

select:focus,
input:focus{

    border-color:#2196f3;

    box-shadow:
        0 0 6px rgba(33,150,243,0.25);
}


/* =========================
   MEDICINE INFO
========================= */

.medicine-info{

    display:none;

    background:#f0fdf4;

    border:1px solid #bbf7d0;

    color:#166534;

    padding:13px 15px;

    border-radius:10px;

    margin-top:12px;

    font-size:13px;

    line-height:1.6;
}

.medicine-info strong{
    color:#14532d;
}


/* =========================
   CALCULATION BOX
========================= */

.calculation-box{

    display:none;

    background:#fff7ed;

    border:1px solid #fed7aa;

    color:#9a3412;

    padding:13px 15px;

    border-radius:10px;

    margin-top:15px;

    font-size:13px;

    line-height:1.7;
}


/* =========================
   SUBMIT BUTTON
========================= */

.submit-btn{
    width:100%;

    margin-top:28px;

    padding:15px;

    border:none;

    border-radius:12px;

    background:#18a957;

    color:white;

    font-size:18px;

    font-weight:bold;

    cursor:pointer;

    transition:0.2s;
}

.submit-btn:hover{

    background:#128c48;

    transform:translateY(-1px);
}


/* =========================
   BACK BUTTON
========================= */

.back-btn{

    display:block;

    width:max-content;

    margin:20px auto 0;

    padding:11px 20px;

    background:#eef6ff;

    color:#155a92;

    text-decoration:none;

    border-radius:10px;

    font-weight:bold;

    transition:0.2s;
}

.back-btn:hover{
    background:#dceeff;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width:700px){

    body{
        padding:20px;
    }

    .container{

        width:100%;

        padding:28px 22px;

        border-radius:20px;
    }

    .title h1{
        font-size:27px;
    }

}

</style>

</head>


<body>


<div class="container">


    <!-- =========================
         TITLE
    ========================= -->

    <div class="title">

        <h1>
            🛒 Add Purchase
        </h1>

        <p>
            Record a new medicine purchase
        </p>

    </div>


    <!-- =========================
         ERROR MESSAGE
    ========================= -->

    <?php if(isset($error)) { ?>

        <div class="error">

            ⚠️
            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php } ?>


    <!-- =========================
         INFORMATION
    ========================= -->

    <div class="info-box">

        💊
        <strong>Stock System:</strong>

        Enter purchase quantity in
        <strong>packs</strong>.

        Stock units are automatically calculated
        using the medicine's pack size.

        <br>

        Example:
        <strong>5 packs × 15 tablets = 75 units</strong>.

    </div>


    <form method="POST">


        <!-- =========================
             MEDICINE
        ========================= -->

        <label>
            Medicine
        </label>

        <select
            name="medicine_id"
            id="medicine_id"
            required
            onchange="showMedicineInfo()"
        >

            <option value="">
                Select Medicine
            </option>


            <?php while($m=mysqli_fetch_assoc($medicine)){ ?>

                <?php

                $pack_size_option =
                    intval($m['pack_size'] ?? 1);

                if($pack_size_option < 1)
                {
                    $pack_size_option = 1;
                }

                ?>


                <option
                    value="<?php echo $m['id']; ?>"

                    data-pack-size="<?php
                        echo $pack_size_option;
                    ?>"

                    data-pack-type="<?php
                        echo htmlspecialchars(
                            $m['pack_type'] ?? 'Unit'
                        );
                    ?>"

                    data-stock="<?php
                        echo intval(
                            $m['stock_units'] ?? 0
                        );
                    ?>"

                    data-purchase-price="<?php
                        echo htmlspecialchars(
                            $m['purchase_price'] ?? 0
                        );
                    ?>"
                >

                    <?php
                    echo htmlspecialchars(
                        $m['medicine_name']
                    );
                    ?>

                </option>

            <?php } ?>

        </select>


        <!-- =========================
             MEDICINE INFORMATION
        ========================= -->

        <div
            class="medicine-info"
            id="medicineInfo"
        >

            <strong>Medicine Information</strong>

            <br>

            Pack:
            <span id="infoPack">
                -
            </span>

            <br>

            Current Stock:
            <span id="infoStock">
                -
            </span>

        </div>


        <!-- =========================
             SUPPLIER
        ========================= -->

        <label>
            Supplier
        </label>

        <select
            name="supplier_id"
            required
        >

            <option value="">
                Select Supplier
            </option>


            <?php while($s=mysqli_fetch_assoc($supplier)){ ?>

                <option
                    value="<?php echo $s['id']; ?>"
                >

                    <?php
                    echo htmlspecialchars(
                        $s['supplier_name']
                    );
                    ?>

                </option>

            <?php } ?>

        </select>


        <!-- =========================
             BATCH NUMBER
        ========================= -->

        <label>
            Batch No.
        </label>

        <input
            type="text"
            name="batch_no"
            placeholder="Enter batch number"
        >


        <!-- =========================
             QUANTITY
        ========================= -->

        <label>
            Quantity / Packs
        </label>

        <input
            type="number"
            name="quantity"
            id="quantity"
            min="1"
            placeholder="Enter number of packs"
            required
            oninput="calculateUnits()"
        >


        <!-- =========================
             PURCHASE PRICE
        ========================= -->

        <label>
            Purchase Price / Pack (₹)
        </label>

        <input
            type="number"
            name="purchase_price"
            id="purchase_price"
            step="0.01"
            min="0"
            placeholder="Enter purchase price per pack"
            required
        >


        <!-- =========================
             CALCULATION
        ========================= -->

        <div
            class="calculation-box"
            id="calculationBox"
        >

            📦
            <strong>Stock Calculation</strong>

            <br>

            Purchase:
            <span id="calcPacks">
                0
            </span>
            packs

            ×

            <span id="calcPackSize">
                1
            </span>
            units

            =

            <strong>

                <span id="calcUnits">
                    0
                </span>

                units

            </strong>

        </div>


        <!-- =========================
             PURCHASE DATE
        ========================= -->

        <label>
            Purchase Date
        </label>

        <input
            type="date"
            name="purchase_date"
            value="<?php echo date('Y-m-d'); ?>"
            required
        >


        <!-- =========================
             SAVE
        ========================= -->

        <button
            type="submit"
            name="save"
            class="submit-btn"
        >

            🛒 Add Purchase

        </button>


    </form>


    <!-- =========================
         BACK
    ========================= -->

    <a
        href="../dashboard.php"
        class="back-btn"
    >

        ← Back to Dashboard

    </a>


</div>


<script>

/* =========================
   SHOW MEDICINE INFORMATION
========================= */

function showMedicineInfo()
{
    const select =
        document.getElementById("medicine_id");

    const option =
        select.options[select.selectedIndex];

    const info =
        document.getElementById("medicineInfo");

    if(!option || !option.value)
    {
        info.style.display = "none";

        return;
    }


    const packSize =
        parseInt(
            option.dataset.packSize || 1
        );

    const packType =
        option.dataset.packType || "Unit";

    const stock =
        parseInt(
            option.dataset.stock || 0
        );


    document.getElementById("infoPack").innerText =
        "1 × " + packSize + " " + packType;

    document.getElementById("infoStock").innerText =
        stock + " units";


    info.style.display = "block";


    calculateUnits();
}


/* =========================
   CALCULATE STOCK UNITS
========================= */

function calculateUnits()
{
    const select =
        document.getElementById("medicine_id");

    const option =
        select.options[select.selectedIndex];

    if(!option || !option.value)
    {
        document.getElementById(
            "calculationBox"
        ).style.display = "none";

        return;
    }


    const packSize =
        parseInt(
            option.dataset.packSize || 1
        );

    const quantity =
        parseInt(
            document.getElementById("quantity").value || 0
        );


    const units =
        quantity * packSize;


    document.getElementById("calcPacks").innerText =
        quantity;

    document.getElementById("calcPackSize").innerText =
        packSize;

    document.getElementById("calcUnits").innerText =
        units;


    document.getElementById(
        "calculationBox"
    ).style.display =
        quantity > 0 ? "block" : "none";
}

</script>


</body>

</html>