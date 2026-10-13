<?php
session_start();
include("../config/db.php");

/* Check Login */
if(!isset($_SESSION['user_id']))
{
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if(isset($_POST['save']))
{
    $supplier_name = mysqli_real_escape_string($conn, $_POST['supplier_name']);
    $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    $sql = "INSERT INTO suppliers
    (user_id, supplier_name, contact_person, phone, email, address)
    VALUES
    ('$user_id','$supplier_name','$contact_person','$phone','$email','$address')";

    if(mysqli_query($conn, $sql))
    {
        echo "<script>
                alert('Supplier Added Successfully');
                window.location='view.php';
              </script>";
    }
    else
    {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Supplier</title>

    <style>
        body{
            font-family:Arial,sans-serif;
            margin:0;
            padding:30px;

            background:
            linear-gradient(rgba(255,255,255,0.55), rgba(255,255,255,0.55)),
            url('https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=1600&q=80')
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

        .form-box{
            width:450px;
            margin:40px auto;
            background:rgba(255,255,255,0.97);
            padding:30px;
            border-radius:20px;
            box-shadow:0 10px 30px rgba(0,0,0,0.18);
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

        input, textarea{
            width:100%;
            padding:12px;
            border:1px solid #cbd5e1;
            border-radius:10px;
            box-sizing:border-box;
            font-size:15px;
            background:#f8fafc;
        }

        textarea{
            height:100px;
            resize:none;
        }

        input:focus, textarea:focus{
            border-color:#2563eb;
            outline:none;
            background:white;
            box-shadow:0 0 0 3px rgba(37,99,235,0.15);
        }

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
        }

        button:hover{
            background:#1d4ed8;
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
        🏢 MediTrack Supplier Management
    </div>

    <a href="../dashboard.php">🏠 Dashboard</a>
</div>

<div class="form-box">

    <h2>🏢 Add Supplier</h2>

    <form method="POST">

        <label>Supplier Name</label>
        <input type="text" name="supplier_name" required>

        <label>Contact Person</label>
        <input type="text" name="contact_person" required>

        <label>Phone</label>
        <input type="text" name="phone" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Address</label>
        <textarea name="address" required></textarea>

        <button type="submit" name="save">
            🏢 Add Supplier
        </button>

    </form>

    <div class="back">
        <a href="view.php">⬅ Back to Suppliers List</a>
    </div>

</div>

</body>
</html>