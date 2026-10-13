<?php
session_start();

include("config/db.php");

$message = "";

if(isset($_POST['login']))
{
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $username_safe = mysqli_real_escape_string($conn, $username);

    $sql = "SELECT * FROM users
            WHERE username='$username_safe'
            LIMIT 1";

    $result = mysqli_query($conn, $sql);

    if($result && mysqli_num_rows($result) > 0)
    {
        $user = mysqli_fetch_assoc($result);

        $stored_password = $user['password'];

        /*
           New registered users:
           Password is stored using password_hash()

           Existing users:
           If old plain password is stored, this code also supports it.
        */

        $password_verified = false;

        if(password_verify($password, $stored_password))
        {
            $password_verified = true;
        }
        elseif($password === $stored_password)
        {
            // Support for old users with plain-text password
            $password_verified = true;

            // Convert old password into secure hashed password
            $new_hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $user_id = intval($user['id']);

            mysqli_query(
                $conn,
                "UPDATE users
                 SET password='$new_hashed_password'
                 WHERE id=$user_id"
            );
        }

        if($password_verified)
        {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;

            if(isset($user['full_name']))
            {
                $_SESSION['full_name'] = $user['full_name'];
            }

            if(isset($user['shop_name']))
            {
                $_SESSION['shop_name'] = $user['shop_name'];
            }

            header("Location: dashboard.php");
            exit();
        }
        else
        {
            $message = "Invalid username or password.";
        }
    }
    else
    {
        $message = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MediTrack - Login</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;

            background:
                linear-gradient(
                    90deg,
                    rgba(7, 35, 76, 0.88),
                    rgba(7, 35, 76, 0.50),
                    rgba(7, 35, 76, 0.20)
                ),
                url("https://images.unsplash.com/photo-1585435557343-3b092031a831?auto=format&fit=crop&w=1600&q=80");

            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .login-container {
            width: 100%;
            max-width: 1000px;
            min-height: 570px;

            display: grid;
            grid-template-columns: 1.1fr 0.9fr;

            background: rgba(255, 255, 255, 0.97);
            border-radius: 24px;
            overflow: hidden;

            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.30);
        }

        /* LEFT SIDE */

        .left-panel {
            padding: 55px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;

            color: white;

            background:
                linear-gradient(
                    145deg,
                    rgba(15, 61, 117, 0.96),
                    rgba(37, 99, 235, 0.82)
                ),
                url("https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1000&q=80");

            background-size: cover;
            background-position: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;

            font-size: 32px;
            font-weight: 800;
            margin-bottom: 35px;
        }

        .logo-icon {
            width: 55px;
            height: 55px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.30);
            border-radius: 16px;

            font-size: 30px;
        }

        .left-panel h1 {
            font-size: 34px;
            line-height: 1.2;
            margin-bottom: 18px;
        }

        .left-panel p {
            max-width: 390px;
            color: #dbeafe;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 28px;
        }

        .features {
            list-style: none;
        }

        .features li {
            margin: 17px 0;
            font-size: 15px;
            color: #f8fafc;
        }

        .features li span {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            width: 25px;
            height: 25px;
            margin-right: 10px;

            border-radius: 50%;
            background: rgba(255, 255, 255, 0.18);
        }

        /* RIGHT SIDE */

        .right-panel {
            padding: 55px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .right-panel h2 {
            color: #0f172a;
            font-size: 30px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .message {
            padding: 12px 14px;
            margin-bottom: 20px;

            border-radius: 9px;
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;

            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #334155;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 14px 15px;

            border: 1px solid #cbd5e1;
            border-radius: 10px;

            font-size: 14px;
            outline: none;

            transition: 0.25s;
        }

        input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .password-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .password-row label {
            margin-bottom: 0;
        }

        .forgot-link {
            color: #2563eb;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 14px;

            border: none;
            border-radius: 10px;

            background: #2563eb;
            color: white;

            font-size: 15px;
            font-weight: 700;

            cursor: pointer;
            transition: 0.25s;
        }

        .login-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
        }

        .register-text {
            text-align: center;
            margin-top: 25px;

            color: #64748b;
            font-size: 14px;
        }

        .register-text a {
            color: #2563eb;
            font-weight: 700;
            text-decoration: none;
        }

        .register-text a:hover {
            text-decoration: underline;
        }

        .security-text {
            text-align: center;
            margin-top: 30px;

            color: #94a3b8;
            font-size: 12px;
        }

        /* RESPONSIVE */

        @media(max-width: 800px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }

            .left-panel {
                padding: 35px 30px;
            }

            .left-panel h1 {
                font-size: 28px;
            }

            .left-panel p {
                margin-bottom: 15px;
            }

            .features {
                display: none;
            }

            .right-panel {
                padding: 40px 30px;
            }
        }

        @media(max-width: 450px) {
            body {
                padding: 12px;
            }

            .right-panel {
                padding: 30px 22px;
            }

            .left-panel {
                padding: 30px 22px;
            }

            .right-panel h2 {
                font-size: 25px;
            }
        }
    </style>
</head>

<body>

<div class="login-container">

    <!-- LEFT PANEL -->

    <div class="left-panel">

        <div class="logo">
            <div class="logo-icon">💊</div>
            <span>MediTrack</span>
        </div>

        <h1>Smart Pharmacy<br>Management System</h1>

        <p>
            Manage medicines, suppliers, purchases, sales,
            inventory and billing from one secure platform.
        </p>

        <ul class="features">
            <li>
                <span>✓</span>
                Smart Medicine Inventory
            </li>

            <li>
                <span>✓</span>
                Real-time Stock Monitoring
            </li>

            <li>
                <span>✓</span>
                Expiry and Low-stock Alerts
            </li>

            <li>
                <span>✓</span>
                Professional Billing with UPI
            </li>
        </ul>

    </div>

    <!-- RIGHT PANEL -->

    <div class="right-panel">

        <h2>Welcome Back</h2>

        <p class="subtitle">
            Login to manage your pharmacy account.
        </p>

        <?php if($message != "") { ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php } ?>

        <form method="POST" action="">

            <div class="form-group">
                <label>Username</label>

                <input
                    type="text"
                    name="username"
                    placeholder="Enter your username"
                    required
                    autocomplete="username"
                >
            </div>

            <div class="form-group">

                <div class="password-row">
                    <label>Password</label>

                    <a href="forgot_password.php" class="forgot-link">
                        Forgot Password?
                    </a>
                </div>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button
                type="submit"
                name="login"
                class="login-btn"
            >
                Login to Dashboard
            </button>

        </form>

        <div class="register-text">
            New user?
            <a href="register.php">Create an account</a>
        </div>

        <div class="security-text">
            🔒 Your account information is protected.
        </div>

    </div>

</div>

</body>
</html>