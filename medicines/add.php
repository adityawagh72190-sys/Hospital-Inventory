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
   ADD MEDICINE
========================= */

if(isset($_POST['save']))
{
    $medicine_name = mysqli_real_escape_string(
        $conn,
        trim($_POST['medicine_name'])
    );

    $category = mysqli_real_escape_string(
        $conn,
        trim($_POST['category'])
    );

    $medicine_type = mysqli_real_escape_string(
        $conn,
        $_POST['medicine_type']
    );

    /*
       H1:
       General = Always 0
       Schedule/Veterinary = Based on checkbox
    */
    if($medicine_type === "General")
    {
        $is_h1 = 0;
    }
    else
    {
        $is_h1 = isset($_POST['is_h1']) ? 1 : 0;
    }

    $pack_type = mysqli_real_escape_string(
        $conn,
        $_POST['pack_type']
    );

    $pack_size = (int) $_POST['pack_size'];

    $batch_no = mysqli_real_escape_string(
        $conn,
        trim($_POST['batch_no'])
    );

    $expiry_date = mysqli_real_escape_string(
        $conn,
        $_POST['expiry_date']
    );

    /* Quantity = Number of Packs */
    $quantity = (int) $_POST['quantity'];

    /* Full Pack Purchase Price */
    $purchase_price = (float) $_POST['purchase_price'];

    /* Full Pack Selling Price */
    $price = (float) $_POST['price'];

    $supplier_id = (int) $_POST['supplier_id'];


    /* =========================
       BASIC VALIDATION
    ========================= */

    if($medicine_name === "")
    {
        echo "<script>
                alert('Please enter medicine name.');
                history.back();
              </script>";
        exit();
    }

    if($category === "")
    {
        echo "<script>
                alert('Please enter category.');
                history.back();
              </script>";
        exit();
    }

    if($purchase_price < 0)
    {
        echo "<script>
                alert('Purchase Price cannot be negative.');
                history.back();
              </script>";
        exit();
    }

    if($price < 0)
    {
        echo "<script>
                alert('Selling Price cannot be negative.');
                history.back();
              </script>";
        exit();
    }

    if($quantity < 0)
    {
        echo "<script>
                alert('Quantity cannot be negative.');
                history.back();
              </script>";
        exit();
    }

    if($pack_size < 1)
    {
        echo "<script>
                alert('Pack Size must be at least 1.');
                history.back();
              </script>";
        exit();
    }


    /* =========================
       MEDICINE TYPE VALIDATION
    ========================= */

    if(
        $medicine_type !== "General" &&
        $medicine_type !== "Schedule" &&
        $medicine_type !== "Veterinary"
    )
    {
        echo "<script>
                alert('Please select a valid Medicine Type.');
                history.back();
              </script>";
        exit();
    }


    /* =========================
       H1 VALIDATION
    ========================= */

    if(
        $is_h1 == 1 &&
        $medicine_type !== "Schedule" &&
        $medicine_type !== "Veterinary"
    )
    {
        echo "<script>
                alert('H1 medicine is allowed only for Schedule or Veterinary medicines.');
                history.back();
              </script>";
        exit();
    }


    /* =========================
       ACTUAL STOCK UNITS
       
       Example:
       5 packs × 15 tablets
       = 75 units
    ========================= */

    $stock_units = $quantity * $pack_size;


    /* =========================
       AUTOMATIC BARCODE
    ========================= */

    do
    {
        $barcode = date("ymdHis") . rand(100,999);

        $check = mysqli_query(
            $conn,
            "SELECT id
             FROM medicines
             WHERE barcode='$barcode'"
        );

    } while(mysqli_num_rows($check) > 0);


    /* =========================
       INSERT MEDICINE
    ========================= */

    $sql = "INSERT INTO medicines
    (
        user_id,
        medicine_name,
        barcode,
        category,
        medicine_type,
        is_h1,
        pack_type,
        pack_size,
        batch_no,
        expiry_date,
        quantity,
        stock_units,
        purchase_price,
        price,
        supplier_id
    )
    VALUES
    (
        '$user_id',
        '$medicine_name',
        '$barcode',
        '$category',
        '$medicine_type',
        '$is_h1',
        '$pack_type',
        '$pack_size',
        '$batch_no',
        '$expiry_date',
        '$quantity',
        '$stock_units',
        '$purchase_price',
        '$price',
        '$supplier_id'
    )";


    if(mysqli_query($conn, $sql))
    {
        echo "<script>
                alert(
                    'Medicine Added Successfully!\\n' +
                    'Barcode: $barcode\\n' +
                    'Total Stock Units: $stock_units'
                );
                window.location='view.php';
              </script>";
    }
    else
    {
        echo "<script>
                alert(
                    'Error while adding medicine!\\n" .
                    mysqli_error($conn) .
                    "'
                );
              </script>";
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

<title>Add Medicine</title>

<style>

body{
    font-family:Arial,sans-serif;
    margin:0;
    padding:30px;
    min-height:100vh;

    background:
    linear-gradient(
        rgba(255,255,255,0.60),
        rgba(255,255,255,0.60)
    ),
    url('https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=1600&q=80')
    no-repeat center center fixed;

    background-size:cover;
}


/* TOP BAR */

.topbar{
    background:rgba(30,58,138,0.90);
    color:white;
    padding:15px 25px;
    border-radius:14px;
    margin-bottom:30px;

    display:flex;
    justify-content:space-between;
    align-items:center;

    box-shadow:0 4px 12px rgba(0,0,0,0.15);
}

.topbar a{
    color:white;
    text-decoration:none;
    background:#2563eb;
    padding:8px 15px;
    border-radius:8px;
    font-weight:bold;
}

.topbar a:hover{
    background:#1d4ed8;
}


/* FORM */

.form-box{
    width:450px;
    max-width:100%;
    margin:40px auto;

    background:white;
    padding:28px;
    border-radius:18px;

    box-shadow:0 10px 30px rgba(0,0,0,0.18);

    box-sizing:border-box;
}

h2{
    text-align:center;
    color:#1e3a8a;
    margin-bottom:25px;
}


/* LABEL */

label{
    font-weight:bold;
    display:block;
    margin-top:14px;
    margin-bottom:6px;
    color:#334155;
}


/* INPUT + SELECT */

input,
select{
    width:100%;
    padding:12px;

    border:1px solid #cbd5e1;
    border-radius:10px;

    box-sizing:border-box;
    font-size:15px;

    background:#f8fafc;
}

input:focus,
select:focus{
    border-color:#2563eb;
    outline:none;
    background:white;

    box-shadow:
    0 0 0 3px rgba(37,99,235,0.15);
}


/* PRICE BOX */

.price-note{
    margin-top:7px;
    padding:9px;

    background:#f0fdf4;
    color:#166534;

    border-radius:8px;

    font-size:12px;
    line-height:1.5;
}


/* MEDICINE TYPE NOTE */

.type-note{
    margin-top:8px;
    padding:9px;

    background:#eff6ff;
    color:#1e40af;

    border-radius:8px;

    font-size:12px;
    line-height:1.5;
}


/* H1 BOX */

.h1-box{
    margin-top:18px;
    padding:14px;

    background:#fff7ed;
    border:1px solid #fed7aa;

    border-radius:10px;
}

.h1-row{
    display:flex;
    align-items:center;
    gap:10px;
}

.h1-row input{
    width:auto;
    margin:0;
    transform:scale(1.2);
}

.h1-row label{
    margin:0;
    color:#9a3412;
    cursor:pointer;
}

.h1-note{
    margin-top:8px;

    font-size:12px;
    color:#9a3412;

    line-height:1.5;
}


/* PACK INFO */

.pack-example{
    margin-top:7px;
    padding:9px;

    background:#eff6ff;
    color:#1e40af;

    border-radius:8px;

    font-size:13px;
    text-align:center;
}


/* STOCK INFO */

.stock-example{
    margin-top:7px;
    padding:10px;

    background:#f0fdf4;
    color:#166534;

    border-radius:8px;

    font-size:13px;
    text-align:center;
}


/* BARCODE INFO */

.barcode-info{
    background:#eff6ff;
    color:#1e40af;

    padding:10px;
    border-radius:8px;

    font-size:13px;

    margin-top:18px;

    text-align:center;
}


/* BUTTON */

button{
    width:100%;

    background:#2563eb;
    color:white;

    border:none;

    padding:13px;

    margin-top:22px;

    border-radius:10px;

    font-size:16px;
    font-weight:bold;

    cursor:pointer;

    transition:0.2s;
}

button:hover{
    background:#1d4ed8;
}


/* BACK */

.back{
    text-align:center;
    margin-top:18px;
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

    transition:0.2s;
}

.back a:hover{
    background:#dbeafe;
    color:#1d4ed8;
}


/* MOBILE */

@media(max-width:520px)
{
    body{
        padding:15px;
    }

    .topbar{
        padding:15px;
        gap:15px;

        flex-direction:column;
        align-items:stretch;

        text-align:center;
    }

    .form-box{
        width:100%;
        margin:25px auto;
        padding:22px;
    }
}

</style>

</head>


<body>


<!-- TOP BAR -->

<div class="topbar">

    <div style="font-size:22px;font-weight:bold;">
        🏥 MediTrack Pharmacy System
    </div>

    <a href="../dashboard.php">
        🏠 Dashboard
    </a>

</div>


<!-- FORM -->

<div class="form-box">

    <h2>
        💊 Add Medicine
    </h2>


    <form method="POST">


        <!-- MEDICINE NAME -->

        <label>
            Medicine Name
        </label>

        <input
            type="text"
            name="medicine_name"
            placeholder="Enter medicine name"
            required
        >


        <!-- CATEGORY -->

        <label>
            Category
        </label>

        <input
            type="text"
            name="category"
            placeholder="e.g. Antibiotic, Painkiller, Cosmetic"
            required
        >


        <!-- MEDICINE TYPE -->

        <label>
            Medicine Type
        </label>

        <select
            name="medicine_type"
            id="medicine_type"
            required
        >

            <option value="General">
                General
            </option>

            <option value="Schedule">
                Schedule
            </option>

            <option value="Veterinary">
                Veterinary
            </option>

        </select>


        <div class="type-note">

            <strong>General:</strong>
            Powder, Lip Balm, Cream, Cosmetic, OTC etc.

            <br>

            <strong>Schedule:</strong>
            Human medicines.

            <br>

            <strong>Veterinary:</strong>
            Animal medicines and products.

        </div>


        <!-- H1 -->

        <div
            class="h1-box"
            id="h1Box"
            style="display:none;"
        >

            <div class="h1-row">

                <input
                    type="checkbox"
                    id="is_h1"
                    name="is_h1"
                    value="1"
                >

                <label for="is_h1">
                    H1 Medicine
                </label>

            </div>


            <div class="h1-note">

                ⚠ H1 can be selected only for
                Schedule or Veterinary medicines.

                <br>

                Select H1 only when the client-provided
                medicine information identifies it as H1.

            </div>

        </div>


        <!-- PACK TYPE -->

        <label>
            Pack Type
        </label>

        <select
            name="pack_type"
            required
        >

            <option value="Tablet">
                Tablet
            </option>

            <option value="Capsule">
                Capsule
            </option>

            <option value="Syrup">
                Syrup
            </option>

            <option value="Injection">
                Injection
            </option>

            <option value="Bottle">
                Bottle
            </option>

            <option value="Tube">
                Tube
            </option>

            <option value="Powder">
                Powder
            </option>

            <option value="Cream">
                Cream
            </option>

            <option value="Other">
                Other
            </option>

        </select>


        <!-- PACK SIZE -->

        <label>
            Pack Size
        </label>

        <input
            type="number"
            name="pack_size"
            id="pack_size"
            min="1"
            value="1"
            required
        >


        <div class="pack-example">

            💊 Example:
            Pack Size 15 → 1 pack contains
            <strong>15 units</strong>

        </div>


        <!-- BATCH -->

        <label>
            Batch No
        </label>

        <input
            type="text"
            name="batch_no"
            required
        >


        <!-- EXPIRY -->

        <label>
            Expiry Date
        </label>

        <input
            type="date"
            name="expiry_date"
            required
        >


        <!-- QUANTITY -->

        <label>
            Quantity / Stock (Packs)
        </label>

        <input
            type="number"
            name="quantity"
            id="quantity"
            min="1"
            required
        >

        <div class="stock-example" id="stockExample">
            📦 Enter quantity and pack size to calculate total stock units.
        </div>


        <!-- PURCHASE PRICE -->

        <label>
            Purchase Price - Full Pack (₹)
        </label>

        <input
            type="number"
            step="0.01"
            name="purchase_price"
            min="0"
            placeholder="Enter full pack purchase price"
            required
        >

        <div class="price-note">

            💰 Enter the price paid by the pharmacy
            for the <strong>complete pack/strip</strong>.

            <br>

            Example: 15 tablets = ₹150
            → 1 tablet = ₹10

        </div>


        <!-- SELLING PRICE -->

        <label>
            Selling Price - Full Pack (₹)
        </label>

        <input
            type="number"
            step="0.01"
            name="price"
            min="0"
            placeholder="Enter full pack selling price"
            required
        >

        <div class="price-note">

            🏷️ Enter the price charged for the
            <strong>complete pack/strip</strong>.

            <br>

            Example: 15 tablets = ₹180
            → 1 tablet = ₹12

        </div>


        <!-- SUPPLIER -->

        <label>
            Supplier ID
        </label>

        <input
            type="number"
            name="supplier_id"
            required
        >


        <!-- BARCODE -->

        <div class="barcode-info">

            🔖 Barcode will be generated automatically
            after adding the medicine.

        </div>


        <!-- SUBMIT -->

        <button
            type="submit"
            name="save"
        >

            💊 Add Medicine

        </button>


    </form>


    <!-- BACK -->

    <div class="back">

        <a href="view.php">
            ⬅ Back to Medicines List
        </a>

    </div>

</div>


<script>

/* =====================================
   H1 CONTROL

   General:
   H1 section completely hidden

   Schedule:
   H1 section visible

   Veterinary:
   H1 section visible
===================================== */

const medicineType =
    document.getElementById("medicine_type");

const h1Checkbox =
    document.getElementById("is_h1");

const h1Box =
    document.getElementById("h1Box");


function updateH1Option()
{
    const type = medicineType.value;

    if(type === "Schedule" || type === "Veterinary")
    {
        h1Box.style.display = "block";

        h1Checkbox.disabled = false;
    }
    else
    {
        h1Checkbox.checked = false;

        h1Checkbox.disabled = true;

        h1Box.style.display = "none";
    }
}


/* Medicine Type Change */

medicineType.addEventListener(
    "change",
    updateH1Option
);


/* Run on Page Load */

updateH1Option();


/* =====================================
   STOCK UNIT CALCULATION
===================================== */

const quantityInput =
    document.getElementById("quantity");

const packSizeInput =
    document.getElementById("pack_size");

const stockExample =
    document.getElementById("stockExample");


function updateStockPreview()
{
    const quantity =
        parseInt(quantityInput.value) || 0;

    const packSize =
        parseInt(packSizeInput.value) || 0;


    if(quantity > 0 && packSize > 0)
    {
        const totalUnits =
            quantity * packSize;


        stockExample.innerHTML =
            "📦 Total Stock = " +
            quantity +
            " pack(s) × " +
            packSize +
            " unit(s) = " +
            "<strong>" +
            totalUnits +
            " units</strong>";
    }
    else
    {
        stockExample.innerHTML =
            "📦 Enter quantity and pack size to calculate total stock units.";
    }
}


/* Quantity Change */

quantityInput.addEventListener(
    "input",
    updateStockPreview
);


/* Pack Size Change */

packSizeInput.addEventListener(
    "input",
    updateStockPreview
);


/* Initial Calculation */

updateStockPreview();

</script>


</body>

</html>