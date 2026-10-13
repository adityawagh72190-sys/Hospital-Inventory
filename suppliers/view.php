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

/* Fetch Only Logged-in User's Suppliers */
$result = mysqli_query(
    $conn,
    "SELECT * FROM suppliers
     WHERE user_id = '$user_id'
     ORDER BY id DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Suppliers List</title>

    <style>
        body{
            font-family:Arial,sans-serif;
            margin:0;
            padding:20px;

            background:
            linear-gradient(rgba(255,255,255,0.35), rgba(255,255,255,0.35)),
            url('https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&w=1600&q=80')
            no-repeat center center fixed;

            background-size:cover;
        }

        .container{
            width:100%;
            max-width:1400px;
            margin:auto;
        }

        .topbar{
            background:#1e3a8a;
            color:white;
            padding:15px 25px;
            border-radius:16px;
            margin-bottom:25px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            box-shadow:0 4px 12px rgba(0,0,0,0.15);
        }

        .topbar a{
            background:#2563eb;
            color:white;
            text-decoration:none;
            padding:10px 18px;
            border-radius:10px;
            font-weight:bold;
        }

        .topbar a:hover{
            background:#1d4ed8;
        }

        .table-box{
            width:100%;
            overflow-x:auto;
            background:rgba(255,255,255,0.96);
            padding:24px;
            border-radius:18px;
            box-shadow:0 8px 24px rgba(0,0,0,0.15);
            box-sizing:border-box;
        }

        table{
            width:100%;
            min-width:1100px;
            border-collapse:collapse;
            table-layout:auto;
        }

        th{
            background:#2563eb;
            color:white;
            padding:14px;
            text-align:left;
        }

        td{
            padding:12px;
            border-bottom:1px solid #e5e7eb;
            vertical-align:top;
        }

        tr:hover{
            background:#f8fafc;
        }

        .address{
            min-width:280px;
            word-break:break-word;
        }

        .actions{
            min-width:180px;
            white-space:nowrap;
        }

        .btn{
            display:inline-block;
            padding:8px 12px;
            border-radius:8px;
            text-decoration:none;
            color:white;
            font-size:14px;
            font-weight:bold;
            margin-right:6px;
            margin-bottom:4px;
        }

        .edit{
            background:#16a34a;
        }

        .delete{
            background:#dc2626;
        }

        .edit:hover{
            background:#15803d;
        }

        .delete:hover{
            background:#b91c1c;
        }

        .no-data{
            text-align:center;
            padding:25px;
            color:#64748b;
            font-weight:bold;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="topbar">
        <div style="font-size:24px;font-weight:bold;">
            🏢 Suppliers List
        </div>

        <a href="add.php">➕ Add Supplier</a>
    </div>

    <div class="table-box">

        <table>

            <tr>
                <th style="width:60px;">ID</th>
                <th style="width:180px;">Supplier Name</th>
                <th style="width:180px;">Contact Person</th>
                <th style="width:130px;">Phone</th>
                <th style="width:220px;">Email</th>
                <th class="address">Address</th>
                <th class="actions">Actions</th>
            </tr>

            <?php
            if(mysqli_num_rows($result) > 0)
            {
                while($row = mysqli_fetch_assoc($result))
                {
            ?>

            <tr>
                <td>
                    <?php echo $row['id']; ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($row['supplier_name']); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($row['contact_person']); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($row['phone']); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($row['email']); ?>
                </td>

                <td class="address">
                    <?php echo htmlspecialchars($row['address']); ?>
                </td>

                <td class="actions">

                    <a class="btn edit"
                       href="edit.php?id=<?php echo $row['id']; ?>">
                       ✏ Edit
                    </a>

                    <a class="btn delete"
                       href="delete.php?id=<?php echo $row['id']; ?>"
                       onclick="return confirm('Delete this supplier?')">
                       🗑 Delete
                    </a>

                </td>
            </tr>

            <?php
                }
            }
            else
            {
            ?>

            <tr>
                <td colspan="7" class="no-data">
                    No suppliers found. Please add a supplier.
                </td>
            </tr>

            <?php
            }
            ?>

        </table>

    </div>

</div>

</body>
</html>