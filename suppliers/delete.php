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

/* Check Supplier ID */
if(!isset($_GET['id']) || !is_numeric($_GET['id']))
{
    header("Location: view.php");
    exit();
}

$id = intval($_GET['id']);

/* Delete Only Logged-in User's Supplier */
$sql = "DELETE FROM suppliers
        WHERE id = '$id' AND user_id = '$user_id'";

if(mysqli_query($conn, $sql))
{
    echo "<script>
            alert('Supplier Deleted Successfully');
            window.location='view.php';
          </script>";
}
else
{
    echo "Error: " . mysqli_error($conn);
}
?>