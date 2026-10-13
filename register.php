<?php
require "connectdb.php";
require_once "system_helpers.php";

// FIX 1: Point to the 'cookie' database
mysqli_select_db($dataconn, 'cookie');
spg_ensure_schema($dataconn);

$msg = "";

if (isset($_POST['register'])) {
    // Collect and sanitize data from the HTML form names
    $full_name = mysqli_real_escape_string($dataconn, $_POST["username"]);
    $matric_id = mysqli_real_escape_string($dataconn, $_POST["student_id"]);
    $email     = mysqli_real_escape_string($dataconn, $_POST["email"]);
    $role      = "student";
    $phone_number     = mysqli_real_escape_string($dataconn, $_POST["phone_number"]);
    $address     = mysqli_real_escape_string($dataconn, $_POST["address"]);
    $password  = $_POST["password"]; 
    
    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if Matric ID already exists in the 'users' table
    $check_sql = "SELECT * FROM users WHERE matric_id = '$matric_id'";
    $check_result = mysqli_query($dataconn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        $msg = "Error: Student ID already registered!";
    } else {
        // FIX 2: Insert into the table named 'users'
        $sql = "INSERT INTO users (full_name, matric_id, email, password, role, phone_number, address)
                VALUES ('$full_name', '$matric_id', '$email', '$hashed_password', '$role', '$phone_number', '$address')";

        if (mysqli_query($dataconn, $sql)) {
            // Redirect to login page on success
            header("Location: login.php?status=registered");
            exit();
        } else {
            $msg = "Error: " . mysqli_error($dataconn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GMI Booking - Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#f0f4f8] flex flex-col items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-3xl shadow-xl w-full max-w-md border border-gray-100">
        <div class="flex justify-center text-blue-600 mb-4">
            <?= spg_brand_logo($dataconn, 'h-14 w-14') ?>
        </div>
        <h2 class="text-2xl font-bold text-slate-800 mb-6 text-center">Create Account</h2>
        
        <?php if($msg): ?>
            <div class="bg-red-50 text-red-500 p-3 rounded-xl text-xs mb-4 border border-red-100">
                <i class="fa-solid fa-circle-exclamation mr-1"></i> <?= $msg ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase">Full Name</label>
                <input type="text" name="username" placeholder="Ahmad Dani" required 
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase">Email Address</label>
                <input type="email" name="email" placeholder="dani@student.gmi.edu.my" required 
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase">Phone Number</label>
                <input type="text" name="phone_number" placeholder="012-3456789" required 
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>

            <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase">Student ID</label>
                    <input type="text" name="student_id" placeholder="GMI12345" required 
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-blue-500 transition">
                <input type="hidden" name="role" value="student">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase">Address</label>
                <input type="text" name="address" placeholder="123 Main Street" required 
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>


            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase">Password</label>
                <input type="password" name="password" placeholder="••••••••" required 
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>

            <button type="submit" name="register" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100 mt-2">
                Register Now
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">
                Already have an account? 
                <a href="login.php" class="text-blue-600 font-bold hover:underline">Back to Login</a>
            </p>
        </div>
    </div>
</body>
</html>
