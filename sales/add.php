<?php

session_start();

include("../config/db.php");


// Check login
if(!isset($_SESSION['user_id']))
{
    header("Location: ../login.php");
    exit();
}


$user_id = $_SESSION['user_id'];


// Show only current user's medicines
$medicine = mysqli_query(
    $conn,
    "SELECT *
     FROM medicines
     WHERE user_id = '$user_id'
     ORDER BY medicine_name ASC"
);


$message = "";
$message_type = "";


if(isset($_POST['save']))
{
    $medicine_name = mysqli_real_escape_string(
        $conn,
        $_POST['medicine_name']
    );

    $quantity = (int)$_POST['quantity'];

    $selling_price = (float)$_POST['selling_price'];

    $sale_date = $_POST['sale_date'];


    // Get medicine of current user only
    $getMed = mysqli_query(
        $conn,
        "SELECT id, quantity
         FROM medicines
         WHERE medicine_name = '$medicine_name'
         AND user_id = '$user_id'"
    );


    $medRow = mysqli_fetch_assoc($getMed);


    if($medRow)
    {
        $medicine_id = $medRow['id'];

        $available_stock = (int)$medRow['quantity'];


        /* =========================
           GET LATEST PURCHASE PRICE
        ========================= */

        $purchaseQuery = mysqli_query(
            $conn,
            "SELECT purchase_price
             FROM purchases
             WHERE medicine_id = '$medicine_id'
             AND user_id = '$user_id'
             AND purchase_date <= '$sale_date'
             ORDER BY purchase_date DESC, id DESC
             LIMIT 1"
        );


        $purchaseRow = mysqli_fetch_assoc($purchaseQuery);


        $purchase_price = 0;


        if($purchaseRow)
        {
            $purchase_price = (float)$purchaseRow['purchase_price'];
        }


        // Check valid quantity
        if($quantity <= 0)
        {
            echo "<script>
                    alert('Please enter a valid quantity!');
                  </script>";
        }


        elseif($quantity > $available_stock)
        {
            echo "<script>
                    alert('Insufficient Stock! Available quantity is $available_stock.');
                  </script>";
        }


        elseif($purchase_price <= 0)
        {
            echo "<script>
                    alert('Purchase price not found for this medicine!');
                  </script>";
        }


        else
        {

            /* =========================
               ADD SALE RECORD
            ========================= */

            $sale = mysqli_query(
                $conn,
                "INSERT INTO sales
                (
                    medicine_id,
                    quantity,
                    purchase_price,
                    selling_price,
                    sale_date,
                    user_id
                )
                VALUES
                (
                    '$medicine_id',
                    '$quantity',
                    '$purchase_price',
                    '$selling_price',
                    '$sale_date',
                    '$user_id'
                )"
            );


            if($sale)
            {

                /* =========================
                   REDUCE STOCK
                ========================= */

                $stock_update = mysqli_query(
                    $conn,
                    "UPDATE medicines
                     SET quantity = quantity - $quantity
                     WHERE id = '$medicine_id'
                     AND user_id = '$user_id'"
                );


                if($stock_update)
                {
                    echo "<script>
                            alert('Sale Added Successfully');
                            window.location='add.php';
                          </script>";
                }


                else
                {
                    echo "<script>
                            alert('Sale added but stock update failed!');
                          </script>";
                }

            }


            else
            {
                echo "<script>
                        alert('Error while adding sale!');
                      </script>";
            }

        }

    }


    else
    {
        echo "<script>
                alert('Medicine not found in your inventory!');
              </script>";
    }

}

?>

<!DOCTYPE html>

<html>

<head>

    <title>Add Sale</title>


    <style>

        body{
            font-family:Arial,sans-serif;

            margin:0;

            padding:30px;


            background:
            linear-gradient(
                rgba(255,255,255,0.50),
                rgba(255,255,255,0.50)
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

            margin-bottom:30px;

            display:flex;

            justify-content:space-between;

            align-items:center;

            box-shadow:
            0 4px 12px rgba(0,0,0,0.15);
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


        .form-box{

            width:470px;

            margin:40px auto;

            background:rgba(255,255,255,0.96);

            padding:30px;

            border-radius:20px;

            box-shadow:
            0 10px 30px rgba(0,0,0,0.18);
        }


        h2{

            text-align:center;

            color:#1e3a8a;

            margin-bottom:25px;
        }


        label{

            font-weight:bold;

            display:block;

            margin-top:15px;

            margin-bottom:6px;

            color:#334155;
        }


        input{

            width:100%;

            padding:12px;

            border:1px solid #cbd5e1;

            border-radius:10px;

            box-sizing:border-box;

            font-size:15px;

            background:#f8fafc;
        }


        input:focus{

            border-color:#2563eb;

            outline:none;

            background:white;

            box-shadow:
            0 0 0 3px rgba(37,99,235,0.15);
        }


        button{

            width:100%;

            background:#16a34a;

            color:white;

            border:none;

            padding:13px;

            margin-top:22px;

            border-radius:10px;

            font-size:16px;

            font-weight:bold;

            cursor:pointer;
        }


        button:hover{

            background:#15803d;
        }


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
        }


        .back a:hover{

            background:#dbeafe;
        }

    </style>

</head>


<body>


<div class="topbar">

    <div style="font-size:22px;font-weight:bold;">

        💰 MediTrack Sales Management

    </div>


    <a href="../dashboard.php">

        🏠 Dashboard

    </a>

</div>



<div class="form-box">


    <h2>
        💰 Add Sale
    </h2>



    <form method="POST">


        <label>
            Medicine
        </label>


        <input
            type="text"
            name="medicine_name"
            list="medicineList"
            placeholder="Type medicine name..."
            autocomplete="off"
            required
        >


        <datalist id="medicineList">


            <?php while($m = mysqli_fetch_assoc($medicine)){ ?>


                <option
                    value="<?php echo htmlspecialchars($m['medicine_name']); ?>"
                >


            <?php } ?>


        </datalist>



        <label>
            Quantity
        </label>


        <input
            type="number"
            name="quantity"
            min="1"
            placeholder="Enter quantity"
            required
        >



        <label>
            Selling Price (₹)
        </label>


        <input
            type="number"
            step="0.01"
            name="selling_price"
            min="0"
            placeholder="Enter selling price"
            required
        >



        <label>
            Sale Date
        </label>


        <input
            type="date"
            name="sale_date"
            value="<?php echo date('Y-m-d'); ?>"
            required
        >



        <button
            type="submit"
            name="save"
        >

            💰 Add Sale

        </button>


    </form>



    <div class="back">

        <a href="../dashboard.php">

            ⬅ Back to Dashboard

        </a>

    </div>


</div>


</body>

</html>