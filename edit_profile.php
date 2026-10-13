<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include("config/db.php");

$user_id = intval($_SESSION['user_id']);

$message = "";
$error = "";

/* =========================
   UPDATE PROFILE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name           = trim($_POST['full_name'] ?? "");
    $username            = trim($_POST['username'] ?? "");
    $email               = trim($_POST['email'] ?? "");
    $mobile              = trim($_POST['mobile'] ?? "");
    $shop_name           = trim($_POST['shop_name'] ?? "");
    $shop_address        = trim($_POST['shop_address'] ?? "");
    $medical_license_no  = trim($_POST['medical_license_no'] ?? "");
    $gst_no              = trim($_POST['gst_no'] ?? "");
    $upi_id              = trim($_POST['upi_id'] ?? "");


    /* =========================
       VALIDATION
    ========================= */

    if (
        $full_name === "" ||
        $username === "" ||
        $mobile === "" ||
        $shop_name === "" ||
        $shop_address === "" ||
        $medical_license_no === "" ||
        $upi_id === ""
    ) {

        $error = "Please fill all required fields.";

    } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {

        $error = "Mobile number must contain exactly 10 digits.";

    } else {

        /* =========================
           CHECK DUPLICATE USERNAME
           OR EMAIL
        ========================= */

        $check_sql = "SELECT id 
                      FROM users 
                      WHERE (username = ? OR email = ?)
                      AND id != ?
                      LIMIT 1";

        $check_stmt = mysqli_prepare($conn, $check_sql);

        if ($check_stmt) {

            mysqli_stmt_bind_param(
                $check_stmt,
                "ssi",
                $username,
                $email,
                $user_id
            );

            mysqli_stmt_execute($check_stmt);

            $check_result = mysqli_stmt_get_result($check_stmt);

            if ($check_result && mysqli_num_rows($check_result) > 0) {

                $error = "Username or Email already exists.";

            }

            mysqli_stmt_close($check_stmt);

        } else {

            $error = "Database error. Please try again.";

        }


        /* =========================
           UPDATE DATABASE
        ========================= */

        if ($error === "") {

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET full_name = ?,
                     username = ?,
                     email = ?,
                     mobile = ?,
                     shop_name = ?,
                     shop_address = ?,
                     medical_license_no = ?,
                     gst_no = ?,
                     upi_id = ?
                 WHERE id = ?"
            );

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssssssssi",
                    $full_name,
                    $username,
                    $email,
                    $mobile,
                    $shop_name,
                    $shop_address,
                    $medical_license_no,
                    $gst_no,
                    $upi_id,
                    $user_id
                );

                if (mysqli_stmt_execute($stmt)) {

                    $message = "Profile updated successfully!";

                } else {

                    $error = "Unable to update profile.";

                }

                mysqli_stmt_close($stmt);

            } else {

                $error = "Database error. Please try again.";

            }
        }
    }
}


/* =========================
   GET CURRENT PROFILE
========================= */

$sql = "SELECT
            full_name,
            username,
            email,
            mobile,
            shop_name,
            shop_address,
            medical_license_no,
            gst_no,
            upi_id,
            role
        FROM users
        WHERE id = $user_id
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {

    echo "User profile not found.";
    exit();

}

$user = mysqli_fetch_assoc($result);


/* =========================
   PROFILE VALUES
========================= */

$full_name          = $user['full_name'] ?? "";
$username           = $user['username'] ?? "";
$email              = $user['email'] ?? "";
$mobile             = $user['mobile'] ?? "";
$shop_name          = $user['shop_name'] ?? "";
$shop_address       = $user['shop_address'] ?? "";
$medical_license_no = $user['medical_license_no'] ?? "";
$gst_no             = $user['gst_no'] ?? "";
$upi_id             = $user['upi_id'] ?? "";
$role               = $user['role'] ?? "Administrator";

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>MediTrack - Edit Profile</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

body {
    min-height: 100vh;
    background: #f1f5f9;
    color: #0f172a;
}

/* =========================
   TOP BAR
========================= */

.topbar {
    background: #0f3d75;
    color: white;
    padding: 18px 35px;

    display: flex;
    align-items: center;
    justify-content: space-between;
}

.topbar h1 {
    font-size: 24px;
}

.topbar a {
    color: white;
    text-decoration: none;
    padding: 10px 16px;

    border: 1px solid rgba(255,255,255,0.4);
    border-radius: 8px;

    font-size: 14px;
}

.topbar a:hover {
    background: #2563eb;
}


/* =========================
   CONTAINER
========================= */

.container {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
}


/* =========================
   CARD
========================= */

.card {
    background: white;
    border-radius: 20px;
    padding: 35px;

    box-shadow: 0 12px 35px rgba(15, 23, 42, 0.08);
}

.title {
    font-size: 25px;
    color: #0f3d75;
    margin-bottom: 8px;
}

.subtitle {
    color: #64748b;
    margin-bottom: 30px;
    font-size: 14px;
}


/* =========================
   FORM
========================= */

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full {
    grid-column: 1 / -1;
}

label {
    font-size: 13px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 7px;
}

input,
textarea {
    width: 100%;
    padding: 13px 14px;

    border: 1px solid #cbd5e1;
    border-radius: 9px;

    font-size: 14px;
    outline: none;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

input:focus,
textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.10);
}

input[readonly] {
    background: #f1f5f9;
    color: #64748b;
    cursor: not-allowed;
}


/* =========================
   MESSAGE
========================= */

.message {
    padding: 12px 15px;
    border-radius: 9px;
    margin-bottom: 20px;

    font-size: 14px;
    font-weight: 600;
}

.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}


/* =========================
   BUTTONS
========================= */

.buttons {
    display: flex;
    gap: 12px;
    margin-top: 30px;
}

.btn {
    padding: 12px 22px;
    border-radius: 9px;

    border: none;
    text-decoration: none;

    font-size: 14px;
    font-weight: 700;

    cursor: pointer;
}

.save-btn {
    background: #2563eb;
    color: white;
}

.save-btn:hover {
    background: #1d4ed8;
}

.cancel-btn {
    background: #e2e8f0;
    color: #334155;
}

.cancel-btn:hover {
    background: #cbd5e1;
}


/* =========================
   MOBILE
========================= */

@media(max-width: 600px) {

    .topbar {
        padding: 16px 20px;
    }

    .topbar h1 {
        font-size: 20px;
    }

    .card {
        padding: 25px 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: auto;
    }

    .buttons {
        flex-direction: column;
    }

    .btn {
        text-align: center;
    }

}

</style>

</head>


<body>


<!-- =========================
     TOP BAR
========================= -->

<div class="topbar">

    <h1>💊 MediTrack</h1>

    <a href="profile.php">
        ← Back to Profile
    </a>

</div>


<!-- =========================
     MAIN CONTAINER
========================= -->

<div class="container">

<div class="card">

    <h2 class="title">✏️ Edit Profile</h2>

    <p class="subtitle">
        Update your personal and medical shop information.
    </p>


    <!-- SUCCESS MESSAGE -->

    <?php if ($message !== "") { ?>

        <div class="message success">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php } ?>


    <!-- ERROR MESSAGE -->

    <?php if ($error !== "") { ?>

        <div class="message error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php } ?>


    <form method="POST" action="">


        <div class="form-grid">


            <!-- FULL NAME -->

            <div class="form-group">

                <label>Full Name *</label>

                <input
                    type="text"
                    name="full_name"
                    value="<?php echo htmlspecialchars($full_name); ?>"
                    required
                >

            </div>


            <!-- USERNAME -->

            <div class="form-group">

                <label>Username *</label>

                <input
                    type="text"
                    name="username"
                    value="<?php echo htmlspecialchars($username); ?>"
                    required
                >

            </div>


            <!-- EMAIL -->

            <div class="form-group">

                <label>Email Address</label>

                <input
                    type="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email); ?>"
                >

            </div>


            <!-- MOBILE -->

            <div class="form-group">

                <label>Mobile Number *</label>

                <input
                    type="text"
                    name="mobile"
                    value="<?php echo htmlspecialchars($mobile); ?>"
                    maxlength="10"
                    pattern="[0-9]{10}"
                    required
                >

            </div>


            <!-- SHOP NAME -->

            <div class="form-group">

                <label>Medical Shop Name *</label>

                <input
                    type="text"
                    name="shop_name"
                    value="<?php echo htmlspecialchars($shop_name); ?>"
                    required
                >

            </div>


            <!-- MEDICAL LICENSE -->

            <div class="form-group">

                <label>Medical Licence No. *</label>

                <input
                    type="text"
                    name="medical_license_no"
                    value="<?php echo htmlspecialchars($medical_license_no); ?>"
                    required
                >

            </div>


            <!-- GST -->

            <div class="form-group">

                <label>GST No.</label>

                <input
                    type="text"
                    name="gst_no"
                    value="<?php echo htmlspecialchars($gst_no); ?>"
                    maxlength="15"
                >

            </div>


            <!-- UPI -->

            <div class="form-group">

                <label>UPI ID *</label>

                <input
                    type="text"
                    name="upi_id"
                    value="<?php echo htmlspecialchars($upi_id); ?>"
                    placeholder="example@upi"
                    required
                >

            </div>


            <!-- SHOP ADDRESS -->

            <div class="form-group full">

                <label>Shop Address *</label>

                <textarea
                    name="shop_address"
                    required
                ><?php echo htmlspecialchars($shop_address); ?></textarea>

            </div>


            <!-- ROLE -->

            <div class="form-group full">

                <label>Account Role</label>

                <input
                    type="text"
                    value="<?php echo htmlspecialchars($role); ?>"
                    readonly
                >

            </div>


        </div>


        <!-- BUTTONS -->

        <div class="buttons">

            <button type="submit" class="btn save-btn">
                💾 Save Changes
            </button>

            <a href="profile.php" class="btn cancel-btn">
                Cancel
            </a>

        </div>


    </form>

</div>

</div>


</body>

</html>