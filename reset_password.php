<?php
session_start();

include("config/db.php");

$message = "";
$message_type = "";

// User must verify OTP first
if (
    !isset($_SESSION["otp_verified"]) ||
    $_SESSION["otp_verified"] !== true ||
    !isset($_SESSION["reset_user_id"])
) {
    header("Location: forgot_password.php");
    exit();
}

$user_id = $_SESSION["reset_user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Check empty fields
    if ($new_password === "" || $confirm_password === "") {

        $message = "Please fill in both password fields.";
        $message_type = "error";

    // Check password length
    } elseif (strlen($new_password) < 6) {

        $message = "Password must be at least 6 characters.";
        $message_type = "error";

    // Check passwords match
    } elseif ($new_password !== $confirm_password) {

        $message = "Passwords do not match.";
        $message_type = "error";

    } else {

        // Hash new password
        $hashed_password = password_hash(
            $new_password,
            PASSWORD_DEFAULT
        );

        // Update password
        $stmt = $conn->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "si",
            $hashed_password,
            $user_id
        );

        if ($stmt->execute()) {

            // Delete used reset records
            $delete = $conn->prepare("
                DELETE FROM password_resets
                WHERE user_id = ?
            ");

            $delete->bind_param("i", $user_id);
            $delete->execute();
            $delete->close();

            // Clear reset session
            unset($_SESSION["reset_email"]);
            unset($_SESSION["reset_user_id"]);
            unset($_SESSION["otp_verified"]);
            unset($_SESSION["reset_otp_testing"]);

            // Redirect to login
            header("Location: login.php?reset=success");
            exit();

        } else {

            $message = "Unable to reset password. Please try again.";
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

    <title>Reset Password - MediTrack</title>

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
            margin-top: 18px;
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
            margin-top: 22px;
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

    </style>

</head>

<body>

<div class="container">

    <h2>Reset Password</h2>

    <p>Create your new password.</p>

    <?php if ($message !== ""): ?>

        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <label for="new_password">
            New Password
        </label>

        <input
            type="password"
            name="new_password"
            id="new_password"
            placeholder="Enter new password"
            minlength="6"
            required
        >

        <label for="confirm_password">
            Confirm Password
        </label>

        <input
            type="password"
            name="confirm_password"
            id="confirm_password"
            placeholder="Confirm new password"
            minlength="6"
            required
        >

        <button type="submit">
            Reset Password
        </button>

    </form>

</div>

</body>

</html>