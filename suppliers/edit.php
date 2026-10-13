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

/* Get Supplier ID */
if(!isset($_GET['id']) || !is_numeric($_GET['id']))
{
    header("Location: view.php");
    exit();
}

$id = intval($_GET['id']);

/* Fetch Only Logged-in User's Supplier */
$result = mysqli_query(
    $conn,
    "SELECT * FROM suppliers
     WHERE id = '$id' AND user_id = '$user_id'"
);

if(mysqli_num_rows($result) == 0)
{
    echo "<script>
            alert('Supplier not found or access denied!');
            window.location='view.php';
          </script>";
    exit();
}

$row = mysqli_fetch_assoc($result);

/* Update Supplier */
if(isset($_POST['update']))
{
    $supplier_name = mysqli_real_escape_string($conn, $_POST['supplier_name']);
    $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    $sql = "UPDATE suppliers SET
            supplier_name='$supplier_name',
            contact_person='$contact_person',
            phone='$phone',
            email='$email',
            address='$address'
            WHERE id='$id' AND user_id='$user_id'";

    if(mysqli_query($conn, $sql))
    {
        echo "<script>
                alert('Supplier Updated Successfully');
                window.location='view.php';
              </script>";
        exit();
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

    <title>Edit Supplier | MediTrack</title>

    <style>

        *{
            box-sizing:border-box;
        }

        body{
            margin:0;
            padding:0;
            font-family:Arial,sans-serif;
            min-height:100vh;

            background:
            linear-gradient(
                rgba(240,248,250,0.90),
                rgba(240,248,250,0.90)
            ),
            url('https://images.unsplash.com/photo-1585435557343-3b092031a831?auto=format&fit=crop&w=1600&q=80')
            no-repeat center center fixed;

            background-size:cover;
        }

        /* Topbar */
        .topbar{
            background:#0f766e;
            color:white;
            padding:18px 35px;

            display:flex;
            justify-content:space-between;
            align-items:center;

            box-shadow:0 4px 12px rgba(0,0,0,0.15);
        }

        .brand{
            font-size:22px;
            font-weight:bold;
        }

        .dashboard-btn{
            color:white;
            text-decoration:none;
            background:#115e59;
            padding:10px 17px;
            border-radius:8px;
            font-weight:bold;
        }

        .dashboard-btn:hover{
            background:#134e4a;
        }

        /* Main container */
        .container{
            width:100%;
            max-width:650px;
            margin:40px auto;
            padding:0 20px;
        }

        /* Form card */
        .form-box{
            background:rgba(255,255,255,0.97);
            padding:35px 40px;
            border-radius:16px;

            box-shadow:
            0 10px 30px rgba(0,0,0,0.15);

            border-top:5px solid #0f766e;
        }

        .form-title{
            text-align:center;
            margin-bottom:28px;
        }

        .form-title h2{
            margin:0;
            color:#134e4a;
            font-size:27px;
        }

        .form-title p{
            color:#64748b;
            font-size:14px;
            margin-top:8px;
        }

        /* Form group */
        .form-group{
            margin-bottom:19px;
        }

        label{
            display:block;
            margin-bottom:7px;
            font-weight:bold;
            color:#334155;
            font-size:14px;
        }

        input,
        textarea{
            width:100%;
            padding:12px 14px;

            border:1px solid #cbd5e1;
            border-radius:8px;

            font-size:15px;
            font-family:Arial,sans-serif;

            background:#f8fafc;
            outline:none;

            transition:0.2s;
        }

        input:focus,
        textarea:focus{
            border-color:#0f766e;
            background:white;

            box-shadow:
            0 0 0 3px rgba(15,118,110,0.12);
        }

        textarea{
            min-height:105px;
            resize:vertical;
        }

        /* Two fields in one row */
        .row{
            display:flex;
            gap:18px;
        }

        .row .form-group{
            flex:1;
        }

        /* Update button */
        .update-btn{
            width:100%;
            background:#0f766e;
            color:white;

            border:none;
            padding:13px;

            margin-top:8px;

            border-radius:8px;
            font-size:16px;
            font-weight:bold;

            cursor:pointer;
        }

        .update-btn:hover{
            background:#115e59;
        }

        /* Back button */
        .back{
            text-align:center;
            margin-top:18px;
        }

        .back a{
            display:inline-block;

            background:#f0fdfa;
            color:#0f766e;

            text-decoration:none;
            font-weight:bold;

            padding:10px 18px;
            border-radius:8px;

            border:1px solid #99f6e4;
        }

        .back a:hover{
            background:#ccfbf1;
        }

        /* Mobile */
        @media(max-width:600px){

            .topbar{
                padding:16px 20px;
            }

            .brand{
                font-size:18px;
            }

            .container{
                margin:25px auto;
            }

            .form-box{
                padding:25px 20px;
            }

            .row{
                flex-direction:column;
                gap:0;
            }

        }

    </style>

</head>

<body>

<!-- Topbar -->
<div class="topbar">

    <div class="brand">
        💊 MediTrack Supplier Management
    </div>

    <a href="../dashboard.php" class="dashboard-btn">
        🏠 Dashboard
    </a>

</div>


<!-- Main Container -->
<div class="container">

    <div class="form-box">

        <div class="form-title">

            <h2>✏️ Edit Supplier</h2>

            <p>Update supplier information and contact details</p>

        </div>


        <form method="POST">

            <!-- Supplier Name -->
            <div class="form-group">

                <label>Supplier Name</label>

                <input
                    type="text"
                    name="supplier_name"
                    value="<?php echo htmlspecialchars($row['supplier_name']); ?>"
                    required
                >

            </div>


            <!-- Contact Person -->
            <div class="form-group">

                <label>Contact Person</label>

                <input
                    type="text"
                    name="contact_person"
                    value="<?php echo htmlspecialchars($row['contact_person']); ?>"
                    required
                >

            </div>


            <!-- Phone + Email -->
            <div class="row">

                <div class="form-group">

                    <label>Phone</label>

                    <input
                        type="text"
                        name="phone"
                        value="<?php echo htmlspecialchars($row['phone']); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Email</label>

                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($row['email']); ?>"
                        required
                    >

                </div>

            </div>


            <!-- Address -->
            <div class="form-group">

                <label>Address</label>

                <textarea
                    name="address"
                    required
                ><?php echo htmlspecialchars($row['address']); ?></textarea>

            </div>


            <!-- Update -->
            <button
                type="submit"
                name="update"
                class="update-btn"
            >
                ✅ Update Supplier
            </button>

        </form>


        <!-- Back -->
        <div class="back">

            <a href="view.php">
                ⬅ Back to Suppliers List
            </a>

        </div>

    </div>

</div>

</body>
</html>