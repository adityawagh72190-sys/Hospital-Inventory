<?php
include("config/db.php");

$message = "";
$message_type = "";

if(isset($_POST['register']))
{
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $shop_name = trim($_POST['shop_name']);
    $shop_address = trim($_POST['shop_address']);
    $medical_license_no = trim($_POST['medical_license_no']);
    $gst_no = trim($_POST['gst_no']);
    $upi_id = trim($_POST['upi_id']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($password !== $confirm_password)
    {
        $message = "Passwords do not match.";
        $message_type = "error";
    }
    elseif(strlen($password) < 6)
    {
        $message = "Password must contain at least 6 characters.";
        $message_type = "error";
    }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    }
    elseif(!preg_match("/^[0-9]{10}$/", $mobile))
    {
        $message = "Please enter a valid 10-digit mobile number.";
        $message_type = "error";
    }
    elseif($shop_address == "")
    {
        $message = "Please enter medical shop address.";
        $message_type = "error";
    }
    elseif($medical_license_no == "")
    {
        $message = "Please enter medical licence number.";
        $message_type = "error";
    }
    else
    {
        $username_safe = mysqli_real_escape_string($conn, $username);
        $email_safe = mysqli_real_escape_string($conn, $email);
        $mobile_safe = mysqli_real_escape_string($conn, $mobile);
        $shop_name_safe = mysqli_real_escape_string($conn, $shop_name);
        $shop_address_safe = mysqli_real_escape_string($conn, $shop_address);
        $medical_license_no_safe = mysqli_real_escape_string($conn, $medical_license_no);
        $gst_no_safe = mysqli_real_escape_string($conn, $gst_no);
        $upi_id_safe = mysqli_real_escape_string($conn, $upi_id);
        $full_name_safe = mysqli_real_escape_string($conn, $full_name);

        $check = mysqli_query(
            $conn,
            "SELECT id FROM users
             WHERE username='$username_safe'
             OR email='$email_safe'
             LIMIT 1"
        );

        if(mysqli_num_rows($check) > 0)
        {
            $message = "Username or email already exists.";
            $message_type = "error";
        }
        else
        {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users
                    (
                        full_name,
                        username,
                        password,
                        email,
                        mobile,
                        shop_name,
                        shop_address,
                        medical_license_no,
                        gst_no,
                        upi_id,
                        role
                    )
                    VALUES
                    (
                        '$full_name_safe',
                        '$username_safe',
                        '$hashed_password',
                        '$email_safe',
                        '$mobile_safe',
                        '$shop_name_safe',
                        '$shop_address_safe',
                        '$medical_license_no_safe',
                        '$gst_no_safe',
                        '$upi_id_safe',
                        'Admin'
                    )";

            if(mysqli_query($conn, $sql))
            {
                $message = "Registration successful. You can now login.";
                $message_type = "success";
            }
            else
            {
                $message = "Registration failed. Please try again.";
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediTrack - Register</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 15px;

            background:
                linear-gradient(
                    135deg,
                    rgba(7, 35, 76, 0.92),
                    rgba(37, 99, 235, 0.78)
                ),
                url("https://images.unsplash.com/photo-1585435557343-3b092031a831?auto=format&fit=crop&w=1600&q=80");

            background-size: cover;
            background-position: center;
        }

        .register-container {
            width: 100%;
            max-width: 850px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.28);
            display: grid;
            grid-template-columns: 0.9fr 1.4fr;
        }

        .left-panel {
            padding: 45px 35px;
            background: linear-gradient(145deg, #0f3d75, #2563eb);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo {
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .left-panel h2 {
            font-size: 25px;
            margin-bottom: 15px;
        }

        .left-panel p {
            font-size: 14px;
            line-height: 1.7;
            color: #dbeafe;
            margin-bottom: 25px;
        }

        .features {
            list-style: none;
        }

        .features li {
            margin: 14px 0;
            font-size: 14px;
            color: #eff6ff;
        }

        .right-panel {
            padding: 35px 40px;
        }

        .right-panel h1 {
            color: #0f172a;
            font-size: 28px;
            margin-bottom: 7px;
        }

        .subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 17px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            color: #334155;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            outline: none;
            font-size: 14px;
            transition: 0.25s;
            resize: vertical;
        }

        textarea {
            min-height: 75px;
        }

        input:focus,
        textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .optional {
            color: #64748b;
            font-size: 11px;
            font-weight: normal;
        }

        .register-btn {
            width: 100%;
            border: none;
            border-radius: 9px;
            padding: 13px;
            background: #2563eb;
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.25s;
        }

        .register-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .message {
            padding: 11px 13px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 13px;
        }

        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .bottom-text {
            text-align: center;
            margin-top: 20px;
            color: #64748b;
            font-size: 14px;
        }

        .bottom-text a {
            color: #2563eb;
            font-weight: 700;
            text-decoration: none;
        }

        .bottom-text a:hover {
            text-decoration: underline;
        }

        @media(max-width: 700px) {
            .register-container {
                grid-template-columns: 1fr;
            }

            .left-panel {
                padding: 30px;
            }

            .right-panel {
                padding: 30px 22px;
            }
        }

        @media(max-width: 480px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>

<div class="register-container">

    <div class="left-panel">
        <div class="logo">💊 MediTrack</div>

        <h2>Join MediTrack</h2>

        <p>
            Create your pharmacy account and manage medicines,
            suppliers, billing and inventory in one professional system.
        </p>

        <ul class="features">
            <li>✓ Smart Medicine Inventory</li>
            <li>✓ Dynamic UPI Billing</li>
            <li>✓ Stock and Expiry Alerts</li>
            <li>✓ Secure Owner Account</li>
        </ul>
    </div>

    <div class="right-panel">

        <h1>Create Account</h1>

        <p class="subtitle">
            Register your medical shop to get started.
        </p>

        <?php if($message != "") { ?>

            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php } ?>

        <form method="POST">

            <div class="form-grid">

                <!-- Full Name -->
                <div class="form-group">
                    <label>Full Name</label>

                    <input
                        type="text"
                        name="full_name"
                        placeholder="Enter full name"
                        required
                    >
                </div>

                <!-- Username -->
                <div class="form-group">
                    <label>Username</label>

                    <input
                        type="text"
                        name="username"
                        placeholder="Choose username"
                        required
                    >
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label>Email Address</label>

                    <input
                        type="email"
                        name="email"
                        placeholder="example@gmail.com"
                        required
                    >
                </div>

                <!-- Mobile -->
                <div class="form-group">
                    <label>Mobile Number</label>

                    <input
                        type="text"
                        name="mobile"
                        placeholder="10-digit mobile number"
                        maxlength="10"
                        required
                    >
                </div>

                <!-- Shop Name -->
                <div class="form-group full">
                    <label>Medical Shop Name</label>

                    <input
                        type="text"
                        name="shop_name"
                        placeholder="Enter medical shop name"
                        required
                    >
                </div>

                <!-- Shop Address -->
                <div class="form-group full">
                    <label>Medical Shop Address</label>

                    <textarea
                        name="shop_address"
                        placeholder="Enter complete medical shop address"
                        required
                    ></textarea>
                </div>

                <!-- Medical Licence -->
                <div class="form-group">
                    <label>Medical Licence No.</label>

                    <input
                        type="text"
                        name="medical_license_no"
                        placeholder="Enter licence number"
                        required
                    >
                </div>

                <!-- GST -->
                <div class="form-group">
                    <label>
                        GST No.
                        <span class="optional">(Optional)</span>
                    </label>

                    <input
                        type="text"
                        name="gst_no"
                        placeholder="Enter GST number if available"
                    >
                </div>

                <!-- UPI -->
                <div class="form-group full">
                    <label>UPI ID</label>

                    <input
                        type="text"
                        name="upi_id"
                        placeholder="example@oksbi"
                        required
                    >
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label>Password</label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Minimum 6 characters"
                        required
                    >
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label>Confirm Password</label>

                    <input
                        type="password"
                        name="confirm_password"
                        placeholder="Re-enter password"
                        required
                    >
                </div>

                <!-- Submit -->
                <div class="form-group full">

                    <button
                        type="submit"
                        name="register"
                        class="register-btn"
                    >
                        Create Account
                    </button>

                </div>

            </div>

        </form>

        <div class="bottom-text">
            Already have an account?
            <a href="login.php">Login here</a>
        </div>

    </div>

</div>

</body>
</html>