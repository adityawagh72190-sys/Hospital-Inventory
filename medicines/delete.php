<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include("../config/db.php");

$user_id = $_SESSION['user_id'] ?? 0;

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die("Invalid medicine ID.");
}

/* Check whether medicine belongs to logged-in user */
$check = $conn->prepare("
    SELECT id
    FROM medicines
    WHERE id = ? AND user_id = ?
");

$check->bind_param("ii", $id, $user_id);
$check->execute();

$result = $check->get_result();

if ($result->num_rows === 0) {
    $check->close();
    die("Medicine not found or you do not have permission.");
}

$check->close();

/* Archive medicine instead of deleting */
$stmt = $conn->prepare("
    UPDATE medicines
    SET is_active = 0
    WHERE id = ? AND user_id = ?
");

$stmt->bind_param("ii", $id, $user_id);

if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {
        header("Location: view.php?archived=success");
        exit();
    } else {
        die("Medicine was not archived.");
    }

} else {

    die("Archive failed: " . $stmt->error);
}

$stmt->close();

?>