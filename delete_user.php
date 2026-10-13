<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("connectdb.php");

// 1. Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

mysqli_select_db($dataconn, 'cookie');

if (isset($_GET['user_id'])) {
    // Sanitize the input
    $user_id = mysqli_real_escape_string($dataconn, $_GET['user_id']);

    // 2. Prevent self-deletion
    if ($user_id == $_SESSION['user_id']) {
        echo "<script>alert('You cannot delete yourself!'); window.location='mng_user.php';</script>";
        exit();
    }

    // 3. THE FIX: Use $user_id (the variable you defined) and 'users' (your table name)
    $query = "DELETE FROM users WHERE user_id = '$user_id'";
    $result = mysqli_query($dataconn, $query);

    if ($result) {
        header("Location: mng_user.php?msg=deleted");
        exit();
    } else {
        echo "Error: " . mysqli_error($dataconn);
    }
} else {
    header("Location: mng_user.php");
}
?>
