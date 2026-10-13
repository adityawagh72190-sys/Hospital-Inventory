<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include("config/db.php");

$user_id = intval($_SESSION['user_id']);

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

$full_name = $user['full_name'] ?? "";
$username = $user['username'] ?? "";
$email = $user['email'] ?? "";
$mobile = $user['mobile'] ?? "";
$shop_name = $user['shop_name'] ?? "";
$shop_address = $user['shop_address'] ?? "";
$medical_license_no = $user['medical_license_no'] ?? "";
$gst_no = $user['gst_no'] ?? "";
$upi_id = $user['upi_id'] ?? "";
$role = $user['role'] ?? "Admin";
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>MediTrack - My Profile</title>

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

.container {
    max-width: 950px;
    margin: 40px auto;
    padding: 0 20px;
}

.profile-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;

    box-shadow: 0 12px 35px rgba(15, 23, 42, 0.08);
}

.profile-header {
    padding: 35px;

    background: linear-gradient(135deg, #2563eb, #0f3d75);

    color: white;

    display: flex;
    align-items: center;
    gap: 20px;
}

.avatar {
    width: 85px;
    height: 85px;

    border-radius: 50%;

    background: white;
    color: #2563eb;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 35px;
    font-weight: 800;
}

.profile-header h2 {
    font-size: 27px;
    margin-bottom: 5px;
}

.profile-header p {
    color: #dbeafe;
    font-size: 14px;
}

.profile-body {
    padding: 35px;
}

.section-title {
    font-size: 18px;
    color: #0f172a;

    margin-bottom: 20px;
    padding-bottom: 12px;

    border-bottom: 1px solid #e2e8f0;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.detail-box {
    padding: 17px;

    border: 1px solid #e2e8f0;
    border-radius: 12px;

    background: #f8fafc;
}

.detail-box.full {
    grid-column: 1 / -1;
}

.detail-box label {
    display: block;

    color: #64748b;

    font-size: 12px;
    font-weight: 600;

    margin-bottom: 7px;

    text-transform: uppercase;
}

.detail-box p {
    color: #0f172a;

    font-size: 15px;
    font-weight: 600;

    word-break: break-word;
    line-height: 1.5;
}

.action-buttons {
    display: flex;
    gap: 12px;

    margin-top: 30px;
}

.btn {
    display: inline-block;

    padding: 12px 20px;

    border-radius: 9px;

    text-decoration: none;

    font-size: 14px;
    font-weight: 700;

    transition: 0.25s;
}

.edit-btn {
    background: #2563eb;
    color: white;
}

.edit-btn:hover {
    background: #1d4ed8;
}

.back-btn {
    background: #e2e8f0;
    color: #334155;
}

.back-btn:hover {
    background: #cbd5e1;
}

@media(max-width: 600px) {

    .topbar {
        padding: 16px 20px;
    }

    .topbar h1 {
        font-size: 20px;
    }

    .profile-header {
        padding: 25px 20px;
    }

    .profile-header h2 {
        font-size: 22px;
    }

    .profile-body {
        padding: 25px 20px;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .detail-box.full {
        grid-column: auto;
    }

    .action-buttons {
        flex-direction: column;
    }

    .btn {
        text-align: center;
    }
}

</style>

</head>

<body>

<div class="topbar">

    <h1>💊 MediTrack</h1>

    <a href="dashboard.php">
        ← Back to Dashboard
    </a>

</div>


<div class="container">

<div class="profile-card">


    <!-- PROFILE HEADER -->

    <div class="profile-header">

        <div class="avatar">

            <?php
            echo strtoupper(
                substr(
                    $full_name ?: $username,
                    0,
                    1
                )
            );
            ?>

        </div>


        <div>

            <h2>
                <?php
                echo htmlspecialchars(
                    $full_name ?: $username
                );
                ?>
            </h2>

            <p>
                <?php
                echo htmlspecialchars(
                    $shop_name ?: "Medical Shop"
                );
                ?>
            </p>

        </div>

    </div>


    <!-- PROFILE BODY -->

    <div class="profile-body">


        <!-- PERSONAL INFORMATION -->

        <h3 class="section-title">
            Personal Information
        </h3>


        <div class="details-grid">


            <div class="detail-box">

                <label>Full Name</label>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $full_name ?: "Not provided"
                    );
                    ?>
                </p>

            </div>


            <div class="detail-box">

                <label>Username</label>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $username ?: "Not provided"
                    );
                    ?>
                </p>

            </div>


            <div class="detail-box">

                <label>Email Address</label>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $email ?: "Not provided"
                    );
                    ?>
                </p>

            </div>


            <div class="detail-box">

                <label>Mobile Number</label>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $mobile ?: "Not provided"
                    );
                    ?>
                </p>

            </div>


        </div>


        <!-- MEDICAL SHOP INFORMATION -->

        <h3 class="section-title" style="margin-top: 35px;">
            Medical Shop Information
        </h3>


        <div class="details-grid">


            <div class="detail-box">

                <label>Medical Shop Name</label>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $shop_name ?: "Not provided"
                    );
                    ?>
                </p>

            </div>


            <div class="detail-box">

                <label>Medical Licence No.</label>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $medical_license_no ?: "Not provided"
                    );
                    ?>
                </p>

            </div>


            <div class="detail-box full">

                <label>Medical Shop Address</label>

                <p>
                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $shop_address ?: "Not provided"
                        )
                    );
                    ?>
                </p>

            </div>


            <div class="detail-box">

                <label>GST No.</label>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $gst_no ?: "Not provided"
                    );
                    ?>
                </p>

            </div>


            <div class="detail-box">

                <label>UPI ID</label>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $upi_id ?: "Not provided"
                    );
                    ?>
                </p>

            </div>


            <div class="detail-box">

                <label>Account Role</label>

                <p>
                    <?php
                    echo htmlspecialchars($role);
                    ?>
                </p>

            </div>


        </div>


        <!-- BUTTONS -->

        <div class="action-buttons">

            <a
                href="edit_profile.php"
                class="btn edit-btn"
            >
                ✏️ Edit Profile
            </a>


            <a
                href="dashboard.php"
                class="btn back-btn"
            >
                Dashboard
            </a>

        </div>


    </div>

</div>

</div>

</body>

</html>