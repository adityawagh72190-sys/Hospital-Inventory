<?php
session_start();

include("config/db.php");

$message = "";
$message_type = "";

if (!isset($_SESSION["reset_email"])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION["reset_email"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $otp = trim($_POST["otp"] ?? "");

    if ($otp === "") {

        $message = "Please enter the OTP.";
        $message_type = "error";

    } elseif (!preg_match('/^[0-9]{6}$/', $otp)) {

        $message = "Please enter a valid 6-digit OTP.";
        $message_type = "error";

    } else {

        // Get latest OTP record
        $stmt = $conn->prepare("
            SELECT id, user_id, otp_hash, expires_at
            FROM password_resets
            WHERE email = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $reset = $result->fetch_assoc();

            // Check OTP expiry
            if (strtotime($reset["expires_at"]) < time()) {

                $message = "OTP has expired. Please request a new OTP.";
                $message_type = "error";

            } elseif (password_verify($otp, $reset["otp_hash"])) {

                // OTP is correct
                $update = $conn->prepare("
                    UPDATE password_resets
                    SET verified = 1
                    WHERE id = ?
                ");

                $update->bind_param("i", $reset["id"]);
                $update->execute();
                $update->close();

                // Store verified reset information in session
                $_SESSION["reset_user_id"] = $reset["user_id"];
                $_SESSION["otp_verified"] = true;

                // Remove testing OTP
                unset($_SESSION["reset_otp_testing"]);

                header("Location: reset_password.php");
                exit();

            } else {

                $message = "Incorrect OTP. Please try again.";
                $message_type = "error";
            }

        } else {

            $message = "OTP not found. Please request a new OTP.";
            $message_type = "error";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Verify OTP - MediTrack</title>

    <style>

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 380px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #1f4e79;
            margin-bottom: 10px;
        }

        p {
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        .email {
            font-weight: bold;
            color: #1f4e79;
        }

        label {
            display: block;
            margin-top: 20px;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 5px;
        }

        button {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            background: #1f4e79;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #163a5c;
        }

        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            font-size: 14px;
        }

        .error {
            background: #ffe5e5;
            color: #b00020;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 18px;
            text-decoration: none;
            color: #1f4e79;
            font-size: 14px;
        }

    </style>

</head>

<body>

<div class="container">

    <h2>Verify OTP</h2>

    <p>
        Enter the 6-digit OTP sent to
        <br>
        <span class="email">
            <?php echo htmlspecialchars($email); ?>
        </span>
    </p>

    <?php if ($message !== ""): ?>

        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <label for="otp">Enter OTP</label>

        <input
            type="text"
            name="otp"
            id="otp"
            maxlength="6"
            pattern="[0-9]{6}"
            inputmode="numeric"
            placeholder="000000"
            required
        >

        <button type="submit">
            Verify OTP
        </button>

    </form>

    <a href="forgot_password.php" class="back">
        ← Request New OTP
    </a>

</div>

</body>

</html>