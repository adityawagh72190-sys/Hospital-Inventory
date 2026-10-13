<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "hospital inventory";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}
?>