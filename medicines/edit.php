<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true)
{
    header("Location: ../login.php");
    exit();
}

include("../config/db.php");


/* =========================
   LOGGED-IN USER ID
========================= */

$user_id = $_SESSION['user_id'] ?? 0;


/* =========================
   GET MEDICINE ID
========================= */

$id = intval($_GET['id'] ?? 0);

if ($id <= 0)
{
    header("Location: view.php");
    exit();
}


/* =========================
   FETCH ONLY OWNER MEDICINE
========================= */

$result = mysqli_query(
    $conn,
    "SELECT *
     FROM medicines
     WHERE id = '$id'
     AND user_id = '$user_id'
     LIMIT 1"
);

if (!$result || mysqli_num_rows($result) == 0)
{
    header("Location: view.php");
    exit();
}

$row = mysqli_fetch_assoc($result);


/* =========================
   UPDATE MEDICINE
========================= */

if (isset($_POST['update']))
{

    /* =========================
       OLD VALUES
    ========================= */

    $old_quantity =
        (int)($row['quantity'] ?? 0);

    $old_pack_size =
        max(
            1,
            (int)($row['pack_size'] ?? 1)
        );

    $old_stock_units =
        (int)($row['stock_units'] ?? 0);


    /* =========================
       FORM VALUES
    ========================= */

    $medicine_name =
        trim($_POST['medicine_name'] ?? '');

    $barcode =
        trim($_POST['barcode'] ?? '');

    $category =
        trim($_POST['category'] ?? '');

    $medicine_type =
        $_POST['medicine_type'] ?? 'General';

    $is_h1 =
        isset($_POST['is_h1']) ? 1 : 0;

    $pack_type =
        $_POST['pack_type'] ?? 'Tablet';

    $pack_size =
        intval($_POST['pack_size'] ?? 1);

    $batch_no =
        trim($_POST['batch_no'] ?? '');

    $expiry_date =
        $_POST['expiry_date'] ?? '';

    $purchase_price =
        floatval(
            $_POST['purchase_price'] ?? 0
        );

    $price =
        floatval(
            $_POST['price'] ?? 0
        );

    /*
       Stock adjustment is in FULL PACKS.

       Example:
       +2 = add 2 full packs
       -1 = remove 1 full pack
       0  = no stock change
    */

    $stock_adjustment_packs =
        intval(
            $_POST['stock_adjustment_packs'] ?? 0
        );


    /* =========================
       VALIDATION
    ========================= */

    if (
        $medicine_name == '' ||
        $category == '' ||
        $batch_no == ''
    )
    {
        $error =
            "Please fill all required fields.";
    }

    elseif ($pack_size < 1)
    {
        $error =
            "Pack Size must be at least 1.";
    }

    elseif ($purchase_price < 0)
    {
        $error =
            "Purchase Price cannot be negative.";
    }

    elseif ($price < 0)
    {
        $error =
            "Selling Price cannot be negative.";
    }

    elseif (
        !in_array(
            $medicine_type,
            [
                'General',
                'Schedule',
                'Veterinary'
            ]
        )
    )
    {
        $error =
            "Invalid medicine type.";
    }

    /*
       H1 is allowed for:
       Schedule
       Veterinary

       H1 is NOT allowed for General.
    */

    elseif (
        $is_h1 == 1 &&
        $medicine_type == 'General'
    )
    {
        $error =
            "H1 medicine cannot be General. Select Schedule or Veterinary.";
    }

    elseif (
        !empty($expiry_date) &&
        strtotime($expiry_date) === false
    )
    {
        $error =
            "Invalid expiry date.";
    }

    else
    {

        /* =========================
           PACK SIZE CHANGE CHECK
        ========================= */

        /*
           If pack size is changed while
           current stock contains loose units,
           automatic conversion is unsafe.

           Example:
           Old pack = 15
           Stock = 72 tablets

           Changing pack to 10 cannot tell
           whether those 72 tablets came from
           old packs or loose stock.

           Therefore allow pack size change
           only when stock is exactly made of
           complete old packs.
        */

        if (
            $pack_size != $old_pack_size &&
            $old_stock_units !=
            ($old_quantity * $old_pack_size)
        )
        {
            $error =
                "Pack Size cannot be changed while loose stock exists. Current stock contains individual units.";
        }

        else
        {

            /* =========================
               CALCULATE NEW STOCK
            ========================= */

            $new_stock_units =
                $old_stock_units +
                (
                    $stock_adjustment_packs *
                    $pack_size
                );


            /* =========================
               STOCK CANNOT BE NEGATIVE
            ========================= */

            if ($new_stock_units < 0)
            {
                $error =
                    "Stock cannot become negative. Current stock is "
                    . $old_stock_units
                    . " units.";
            }

            else
            {

                /*
                   Quantity (Packs) is now calculated
                   from actual stock.

                   Example:
                   99 units / 20 = 4 complete packs

                   Loose units are kept separately
                   in stock_units.
                */

                $new_quantity =
                    intdiv(
                        $new_stock_units,
                        $pack_size
                    );


                /* =========================
                   ESCAPE VALUES
                ========================= */

                $medicine_name_db =
                    mysqli_real_escape_string(
                        $conn,
                        $medicine_name
                    );

                $barcode_db =
                    mysqli_real_escape_string(
                        $conn,
                        $barcode
                    );

                $category_db =
                    mysqli_real_escape_string(
                        $conn,
                        $category
                    );

                $medicine_type_db =
                    mysqli_real_escape_string(
                        $conn,
                        $medicine_type
                    );

                $pack_type_db =
                    mysqli_real_escape_string(
                        $conn,
                        $pack_type
                    );

                $batch_no_db =
                    mysqli_real_escape_string(
                        $conn,
                        $batch_no
                    );

                $expiry_date_db =
                    mysqli_real_escape_string(
                        $conn,
                        $expiry_date
                    );


                /* =========================
                   UPDATE DATABASE
                ========================= */

                $update = mysqli_query(
                    $conn,
                    "UPDATE medicines SET

                        medicine_name =
                        '$medicine_name_db',

                        barcode =
                        '$barcode_db',

                        category =
                        '$category_db',

                        medicine_type =
                        '$medicine_type_db',

                        is_h1 =
                        '$is_h1',

                        pack_type =
                        '$pack_type_db',

                        pack_size =
                        '$pack_size',

                        batch_no =
                        '$batch_no_db',

                        expiry_date =
                        '$expiry_date_db',

                        quantity =
                        '$new_quantity',

                        stock_units =
                        '$new_stock_units',

                        purchase_price =
                        '$purchase_price',

                        price =
                        '$price'

                     WHERE id = '$id'
                     AND user_id = '$user_id'"
                );


                /* =========================
                   SUCCESS
                ========================= */

                if ($update)
                {
                    header(
                        "Location: view.php"
                    );

                    exit();
                }

                else
                {
                    $error =
                        "Failed to update medicine: "
                        . mysqli_error($conn);
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

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Edit Medicine | MediTrack</title>


<style>

*{
    box-sizing:border-box;
}


body{

    margin:0;

    padding:0;

    font-family:Arial,sans-serif;

    background:
    linear-gradient(
        rgba(240,248,250,0.90),
        rgba(240,248,250,0.90)
    ),
    url("https://images.unsplash.com/photo-1585435557343-3b092031a831?auto=format&fit=crop&w=1600&q=80");

    background-size:cover;

    background-position:center;

    background-attachment:fixed;

    min-height:100vh;
}


/* =========================
   TOPBAR
========================= */

.topbar{

    background:#0f766e;

    color:white;

    padding:18px 40px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    box-shadow:
    0 3px 10px
    rgba(0,0,0,0.15);
}


.topbar h2{

    margin:0;

    font-size:24px;
}


.topbar span{

    font-size:14px;

    opacity:0.9;
}


/* =========================
   CONTAINER
========================= */

.container{

    width:100%;

    max-width:750px;

    margin:45px auto;

    padding:0 20px;
}


/* =========================
   FORM CARD
========================= */

.form-card{

    background:
    rgba(255,255,255,0.97);

    border-radius:15px;

    padding:35px 40px;

    box-shadow:
    0 8px 30px
    rgba(0,0,0,0.15);

    border-top:
    5px solid #0f766e;
}


/* =========================
   TITLE
========================= */

.form-title{

    text-align:center;

    margin-bottom:30px;
}


.form-title h2{

    margin:0;

    color:#134e4a;

    font-size:27px;
}


.form-title p{

    margin-top:8px;

    color:#64748b;

    font-size:14px;
}


/* =========================
   ERROR
========================= */

.error{

    background:#fee2e2;

    color:#b91c1c;

    padding:12px 14px;

    border-radius:8px;

    margin-bottom:20px;

    font-size:14px;

    font-weight:bold;
}


/* =========================
   FORM GROUP
========================= */

.form-group{

    margin-bottom:20px;
}


.form-group label{

    display:block;

    margin-bottom:7px;

    font-weight:bold;

    color:#334155;

    font-size:14px;
}


.form-group input,
.form-group select{

    width:100%;

    padding:12px 14px;

    border:
    1px solid #cbd5e1;

    border-radius:8px;

    font-size:15px;

    outline:none;

    background:#f8fafc;

    transition:0.2s;
}


.form-group input:focus,
.form-group select:focus{

    border-color:#0f766e;

    background:white;

    box-shadow:
    0 0 0 3px
    rgba(15,118,110,0.12);
}


/* =========================
   TWO COLUMN
========================= */

.row{

    display:flex;

    gap:18px;
}


.row .form-group{

    flex:1;
}


/* =========================
   H1 BOX
========================= */

.h1-box{

    background:#fff7ed;

    border:
    1px solid #fed7aa;

    padding:13px 15px;

    border-radius:8px;

    margin-bottom:20px;
}


.h1-label{

    display:flex !important;

    align-items:center;

    gap:10px;

    margin:0 !important;

    color:#9a3412 !important;

    cursor:pointer;
}


.h1-label input{

    width:auto !important;

    transform:scale(1.2);

    accent-color:#dc2626;
}


.h1-note{

    margin-top:7px;

    margin-left:27px;

    font-size:12px;

    color:#92400e;
}


/* =========================
   STOCK BOX
========================= */

.stock-box{

    background:#eff6ff;

    border:
    1px solid #bfdbfe;

    color:#1e40af;

    padding:15px;

    border-radius:10px;

    margin-bottom:20px;
}


.stock-title{

    font-weight:bold;

    font-size:15px;

    margin-bottom:8px;
}


.stock-number{

    font-size:24px;

    font-weight:bold;

    color:#2563eb;
}


.stock-description{

    font-size:12px;

    margin-top:5px;

    color:#475569;
}


/* =========================
   STOCK ADJUSTMENT
========================= */

.adjust-box{

    background:#f0fdf4;

    border:
    1px solid #bbf7d0;

    padding:15px;

    border-radius:10px;

    margin-bottom:20px;
}


.adjust-title{

    font-weight:bold;

    color:#166534;

    margin-bottom:6px;
}


.adjust-note{

    color:#475569;

    font-size:12px;

    line-height:1.5;

    margin-bottom:12px;
}


.adjust-positive{

    color:#15803d;

    font-weight:bold;
}


/* =========================
   PACK INFO
========================= */

.pack-info{

    background:#f8fafc;

    border:
    1px solid #e2e8f0;

    color:#475569;

    padding:11px 13px;

    border-radius:8px;

    font-size:13px;

    margin-top:-8px;

    margin-bottom:20px;
}


/* =========================
   BUTTONS
========================= */

.buttons{

    display:flex;

    gap:12px;

    margin-top:28px;
}


.btn{

    flex:1;

    padding:13px;

    border:none;

    border-radius:8px;

    font-size:15px;

    font-weight:bold;

    cursor:pointer;

    text-align:center;

    text-decoration:none;
}


.update-btn{

    background:#0f766e;

    color:white;
}


.update-btn:hover{

    background:#115e59;
}


.cancel-btn{

    background:#e2e8f0;

    color:#334155;
}


.cancel-btn:hover{

    background:#cbd5e1;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width:600px){

    .topbar{

        padding:16px 20px;

        flex-direction:column;

        align-items:flex-start;

        gap:5px;
    }


    .topbar h2{

        font-size:20px;
    }


    .container{

        margin:25px auto;
    }


    .form-card{

        padding:25px 20px;
    }


    .row{

        flex-direction:column;

        gap:0;
    }


    .buttons{

        flex-direction:column;
    }

}

</style>

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<div class="topbar">

    <h2>
        💊 MediTrack
    </h2>

    <span>
        Pharmacy Management System
    </span>

</div>



<!-- =========================
     MAIN
========================= -->

<div class="container">


<div class="form-card">


    <!-- TITLE -->

    <div class="form-title">

        <h2>
            ✏️ Edit Medicine
        </h2>

        <p>
            Update medicine information and stock details
        </p>

    </div>



    <!-- ERROR -->

    <?php if(isset($error)) { ?>

        <div class="error">

            ⚠️
            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php } ?>



    <form method="POST">


        <!-- =========================
             MEDICINE NAME
        ========================== -->

        <div class="form-group">

            <label>
                Medicine Name
            </label>

            <input
                type="text"
                name="medicine_name"

                value="<?php

                echo htmlspecialchars(
                    $_POST['medicine_name']
                    ?? $row['medicine_name']
                );

                ?>"

                required
            >

        </div>



        <!-- =========================
             BARCODE
        ========================== -->

        <div class="form-group">

            <label>
                Barcode
            </label>

            <input
                type="text"
                name="barcode"

                value="<?php

                echo htmlspecialchars(
                    $_POST['barcode']
                    ?? ($row['barcode'] ?? '')
                );

                ?>"

                placeholder="Enter barcode"
            >

        </div>



        <!-- =========================
             CATEGORY
        ========================== -->

        <div class="form-group">

            <label>
                Category
            </label>

            <input
                type="text"
                name="category"

                value="<?php

                echo htmlspecialchars(
                    $_POST['category']
                    ?? ($row['category'] ?? '')
                );

                ?>"

                required
            >

        </div>



        <!-- =========================
             MEDICINE TYPE
        ========================== -->

        <div class="form-group">

            <label>
                Medicine Type
            </label>


            <?php

            $current_type =
                $_POST['medicine_type']
                ??
                (
                    $row['medicine_type']
                    ??
                    'General'
                );

            ?>


            <select
                name="medicine_type"
                id="medicineType"
                required
            >

                <option
                    value="General"

                    <?php

                    if(
                        $current_type
                        ==
                        'General'
                    )
                    {
                        echo 'selected';
                    }

                    ?>

                >
                    General
                </option>


                <option
                    value="Schedule"

                    <?php

                    if(
                        $current_type
                        ==
                        'Schedule'
                    )
                    {
                        echo 'selected';
                    }

                    ?>

                >
                    Schedule
                </option>


                <option
                    value="Veterinary"

                    <?php

                    if(
                        $current_type
                        ==
                        'Veterinary'
                    )
                    {
                        echo 'selected';
                    }

                    ?>

                >
                    Veterinary
                </option>

            </select>

        </div>



        <!-- =========================
             H1
        ========================== -->

        <div class="h1-box">

            <label class="h1-label">

                <input
                    type="checkbox"
                    name="is_h1"
                    value="1"

                    id="h1Checkbox"

                    <?php

                    $current_h1 =
                        $_POST['is_h1']
                        ??
                        (
                            $row['is_h1']
                            ??
                            0
                        );

                    if(
                        $current_h1 == 1
                    )
                    {
                        echo 'checked';
                    }

                    ?>

                >

                ⚠️ H1 Medicine

            </label>


            <div class="h1-note">

                H1 can be used for applicable
                Schedule or Veterinary medicines.

            </div>

        </div>



        <!-- =========================
             PACK TYPE + SIZE
        ========================== -->

        <div class="row">


            <div class="form-group">

                <label>
                    Pack Type
                </label>


                <?php

                $current_pack_type =
                    $_POST['pack_type']
                    ??
                    (
                        $row['pack_type']
                        ??
                        'Tablet'
                    );

                ?>


                <select
                    name="pack_type"
                    required
                >

                    <option
                        value="Tablet"
                        <?php
                        if(
                            $current_pack_type
                            ==
                            'Tablet'
                        )
                        {
                            echo 'selected';
                        }
                        ?>
                    >
                        Tablet
                    </option>


                    <option
                        value="Capsule"
                        <?php
                        if(
                            $current_pack_type
                            ==
                            'Capsule'
                        )
                        {
                            echo 'selected';
                        }
                        ?>
                    >
                        Capsule
                    </option>


                    <option
                        value="Syrup"
                        <?php
                        if(
                            $current_pack_type
                            ==
                            'Syrup'
                        )
                        {
                            echo 'selected';
                        }
                        ?>
                    >
                        Syrup
                    </option>


                    <option
                        value="Injection"
                        <?php
                        if(
                            $current_pack_type
                            ==
                            'Injection'
                        )
                        {
                            echo 'selected';
                        }
                        ?>
                    >
                        Injection
                    </option>


                    <option
                        value="Bottle"
                        <?php
                        if(
                            $current_pack_type
                            ==
                            'Bottle'
                        )
                        {
                            echo 'selected';
                        }
                        ?>
                    >
                        Bottle
                    </option>


                    <option
                        value="Tube"
                        <?php
                        if(
                            $current_pack_type
                            ==
                            'Tube'
                        )
                        {
                            echo 'selected';
                        }
                        ?>
                    >
                        Tube
                    </option>


                    <option
                        value="Other"
                        <?php
                        if(
                            $current_pack_type
                            ==
                            'Other'
                        )
                        {
                            echo 'selected';
                        }
                        ?>
                    >
                        Other
                    </option>

                </select>

            </div>



            <div class="form-group">

                <label>
                    Pack Size
                </label>

                <input
                    type="number"
                    name="pack_size"

                    id="packSize"

                    value="<?php

                    echo htmlspecialchars(
                        $_POST['pack_size']
                        ??
                        (
                            $row['pack_size']
                            ??
                            1
                        )
                    );

                    ?>"

                    min="1"

                    required
                >

            </div>

        </div>



        <div class="pack-info">

            💊
            <strong>Example:</strong>

            Pack Size 15 means
            one pack contains 15 units.

            Selling price and purchase price
            are maintained per full pack.

        </div>



        <!-- =========================
             BATCH + EXPIRY
        ========================== -->

        <div class="row">


            <div class="form-group">

                <label>
                    Batch No
                </label>

                <input
                    type="text"
                    name="batch_no"

                    value="<?php

                    echo htmlspecialchars(
                        $_POST['batch_no']
                        ??
                        ($row['batch_no'] ?? '')
                    );

                    ?>"

                    required
                >

            </div>



            <div class="form-group">

                <label>
                    Expiry Date
                </label>

                <input
                    type="date"
                    name="expiry_date"

                    value="<?php

                    echo htmlspecialchars(
                        $_POST['expiry_date']
                        ??
                        ($row['expiry_date'] ?? '')
                    );

                    ?>"

                    required
                >

            </div>

        </div>



        <!-- =========================
             CURRENT STOCK
        ========================== -->

        <?php

        $display_stock_units =
            (int)
            ($row['stock_units'] ?? 0);

        $display_pack_size =
            max(
                1,
                (int)
                ($row['pack_size'] ?? 1)
            );

        $display_packs =
            intdiv(
                $display_stock_units,
                $display_pack_size
            );

        $display_loose =
            $display_stock_units
            %
            $display_pack_size;

        ?>


        <div class="stock-box">

            <div class="stock-title">

                📦 Current Stock

            </div>


            <div class="stock-number">

                <?php
                echo $display_stock_units;
                ?>

                units

            </div>


            <div class="stock-description">

                <?php

                echo $display_packs;

                ?>

                full packs

                <?php

                if(
                    $display_loose > 0
                )
                {

                    echo
                    " + "
                    .
                    $display_loose
                    .
                    " loose units";

                }

                ?>

            </div>

        </div>



        <!-- =========================
             STOCK ADJUSTMENT
        ========================== -->

        <div class="adjust-box">

            <div class="adjust-title">

                📦 Adjust Current Stock

            </div>


            <div class="adjust-note">

                Enter the number of
                <strong>full packs</strong>
                you want to add or remove.

                <br><br>

                Example:
                <span class="adjust-positive">
                    +2
                </span>
                = Add 2 full packs

                <br>

                <span style="color:#dc2626;font-weight:bold;">
                    -1
                </span>
                = Remove 1 full pack

                <br>

                <strong>
                    0
                </strong>
                = No stock change

            </div>


            <div class="form-group">

                <label>
                    Stock Adjustment (Packs)
                </label>

                <input
                    type="number"
                    name="stock_adjustment_packs"

                    value="<?php

                    echo htmlspecialchars(
                        $_POST[
                            'stock_adjustment_packs'
                        ]
                        ??
                        0
                    );

                    ?>"

                    step="1"

                    placeholder="Example: +2 or -1"
                >

            </div>

        </div>



        <!-- =========================
             PURCHASE + SELLING PRICE
        ========================== -->

        <div class="row">


            <div class="form-group">

                <label>
                    Purchase Price / Pack (₹)
                </label>

                <input
                    type="number"
                    name="purchase_price"

                    value="<?php

                    echo htmlspecialchars(
                        $_POST['purchase_price']
                        ??
                        (
                            $row['purchase_price']
                            ??
                            0
                        )
                    );

                    ?>"

                    min="0"

                    step="0.01"

                    required
                >

            </div>



            <div class="form-group">

                <label>
                    Selling Price / Pack (₹)
                </label>

                <input
                    type="number"
                    name="price"

                    value="<?php

                    echo htmlspecialchars(
                        $_POST['price']
                        ??
                        (
                            $row['price']
                            ??
                            0
                        )
                    );

                    ?>"

                    min="0"

                    step="0.01"

                    required
                >

            </div>

        </div>



        <!-- =========================
             PRICE INFO
        ========================== -->

        <div class="pack-info">

            💰
            <strong>Price rule:</strong>

            Enter the price of the
            complete pack.

            <br><br>

            Example:

            Pack Size = 15

            <br>

            Selling Price = ₹150 per pack

            <br>

            System billing price =
            ₹150 ÷ 15 = ₹10 per unit.

        </div>



        <!-- =========================
             BUTTONS
        ========================== -->

        <div class="buttons">


            <a
                href="view.php"
                class="btn cancel-btn"
            >
                Cancel
            </a>


            <button
                type="submit"
                name="update"
                class="btn update-btn"
            >
                Update Medicine
            </button>


        </div>


    </form>


</div>

</div>



<script>

/* =========================
   H1 CHECK
========================= */

const medicineType =
    document.getElementById(
        "medicineType"
    );

const h1Checkbox =
    document.getElementById(
        "h1Checkbox"
    );


function checkH1Type()
{

    /*
       H1 is allowed for:
       Schedule
       Veterinary

       Not General.
    */

    if(
        medicineType.value ===
        "General"
    )
    {

        h1Checkbox.checked =
            false;

    }

}


medicineType.addEventListener(
    "change",
    checkH1Type
);


</script>


</body>

</html>