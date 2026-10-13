<?php
session_start();

include("config/db.php");

// PHPMailer files
require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';

// Mail configuration
require_once __DIR__ . '/config/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    if ($email === "") {

        $message = "Please enter your registered email.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } else {

        // Check whether email is registered
        $stmt = $conn->prepare("
            SELECT id, full_name, email
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            // Generate 6-digit OTP
            $otp = random_int(100000, 999999);

            // Hash OTP before storing
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);

            // OTP valid for 5 minutes
            $expires_at = date(
                "Y-m-d H:i:s",
                time() + 300
            );

            // Remove old OTP records
            $delete = $conn->prepare("
                DELETE FROM password_resets
                WHERE email = ?
            ");

            $delete->bind_param("s", $email);
            $delete->execute();
            $delete->close();

            // Store new OTP
            $insert = $conn->prepare("
                INSERT INTO password_resets
                (user_id, email, otp_hash, expires_at, verified)
                VALUES (?, ?, ?, ?, 0)
            ");

            $insert->bind_param(
                "isss",
                $user["id"],
                $email,
                $otp_hash,
                $expires_at
            );

            if ($insert->execute()) {

                /*
                 * Send OTP through Gmail using PHPMailer
                 */

                $mail = new PHPMailer(true);

                try {

                    // SMTP settings
                    $mail->isSMTP();
                    $mail->Host = SMTP_HOST;
                    $mail->SMTPAuth = true;
                    $mail->Username = SMTP_USERNAME;
                    $mail->Password = SMTP_APP_PASSWORD;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = SMTP_PORT;

                    // Sender
                    $mail->setFrom(
                        SMTP_FROM_EMAIL,
                        SMTP_FROM_NAME
                    );

                    // Recipient = user's registered email
                    $mail->addAddress(
                        $email,
                        $user["full_name"]
                    );

                    // Email format
                    $mail->isHTML(true);

                    $mail->Subject =
                        "MediTrack - Password Reset OTP";

                    $mail->Body = "
                        <div style='
                            font-family: Arial, sans-serif;
                            padding: 20px;
                        '>

                            <h2 style='color:#1f4e79;'>
                                MediTrack Password Reset
                            </h2>

                            <p>
                                Hello " .
                                htmlspecialchars($user["full_name"]) .
                                ",
                            </p>

                            <p>
                                Your password reset OTP is:
                            </p>

                            <h1 style='
                                color:#1f4e79;
                                letter-spacing:5px;
                            '>
                                $otp
                            </h1>

                            <p>
                                This OTP is valid for
                                <strong>5 minutes</strong>.
                            </p>

                            <p>
                                If you did not request a password reset,
                                please ignore this email.
                            </p>

                            <br>

                            <p>
                                Regards,<br>
                                <strong>
                                    MediTrack Pharmacy Management System
                                </strong>
                            </p>

                        </div>
                    ";

                    // Plain text version
                    $mail->AltBody =
                        "Your MediTrack password reset OTP is: "
                        . $otp
                        . ". This OTP is valid for 5 minutes.";

                    // Send email
                    $mail->send();

                    // Store email in session
                    $_SESSION["reset_email"] = $email;

                    // IMPORTANT:
                    // Testing OTP is no longer required
                    unset($_SESSION["reset_otp_testing"]);

                    // Go to OTP verification page
                    header("Location: verify_otp.php");
                    exit();

                } catch (Exception $e) {

                    // If email sending fails,
                    // remove the OTP record
                    $delete_reset = $conn->prepare("
                        DELETE FROM password_resets
                        WHERE email = ?
                    ");

                    $delete_reset->bind_param("s", $email);
                    $delete_reset->execute();
                    $delete_reset->close();

                    $message =
                        "Unable to send OTP email. Please try again.";

                    $message_type = "error";
                }

            } else {

                $message =
                    "Something went wrong. Please try again.";

                $message_type = "error";
            }

            $insert->close();

        } else {

            $message =
                "No account found with this email address.";

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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Forgot Password - MediTrack</title>

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
            font-size: 15px;
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

    <h2>Forgot Password?</h2>

    <p>
        Enter your registered email address to reset your password.
    </p>

    <?php if ($message !== ""): ?>

        <div class="message <?php echo $message_type; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>

    <form method="POST">

        <label for="email">
            Registered Email
        </label>

        <input
            type="email"
            name="email"
            id="email"
            placeholder="Enter your registered email"
            required
        >

        <button type="submit">
            Send OTP
        </button>

    </form>

    <a href="login.php" class="back">
        ← Back to Login
    </a>

</div>

</body>

</html>