<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'connectdb.php';
require_once 'system_helpers.php';

mysqli_select_db($dataconn, 'cookie');
spg_ensure_schema($dataconn);

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

$error_message = "";

if (($_SERVER["REQUEST_METHOD"] ?? '') == "POST" && isset($_POST['login'])) {
    $username = mysqli_real_escape_string($dataconn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE (matric_id = '$username' OR email = '$username') AND role = 'admin' LIMIT 1";
    $result = mysqli_query($dataconn, $query);

    if ($result && mysqli_num_rows($result) == 1) {
        $user_data = mysqli_fetch_assoc($result);

        if (password_verify($password, $user_data['password'])) {
            $_SESSION['user_id']  = $user_data[spg_user_id_column($dataconn)];
            $_SESSION['username'] = $user_data['full_name'];
            $_SESSION['role']     = $user_data['role'];

            header("Location: admin.php");
            exit();
        } else {
            $error_message = "Invalid Password. Please try again.";
        }
    } else {
        $error_message = "Admin account not found. Users must use the User Login page.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GMI Facility Booking - Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans antialiased min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-8">
        <div class="text-blue-600 text-6xl mb-4 flex justify-center">
            <?= spg_brand_logo($dataconn, 'h-16 w-16') ?>
        </div>
        <h1 class="text-3xl font-bold text-gray-900">GMI Facility Booking</h1>
        <p class="text-sm text-gray-500 mt-1">German-Malaysian Institute</p>
    </div>

    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 w-full max-w-md">
        <h2 class="text-2xl font-bold text-slate-900 mb-6 text-center">Admin Login</h2>

        <?php if (!empty($error_message)): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl font-medium text-center">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="admin_login.php" method="POST" class="space-y-5">
            <div>
                <label for="username" class="block text-sm font-semibold text-slate-700 mb-2">Email / Staff ID</label>
                <input type="text" id="username" name="username" placeholder="Enter ID or Email" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                <input type="password" id="password" name="password" placeholder="Password" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>

            <button type="submit" name="login"
                    class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-3 px-4 rounded-xl text-sm transition flex items-center justify-center space-x-2 shadow-lg shadow-slate-100">
                <span>Sign In as Admin</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
        </form>

        <div class="text-center mt-8">
            <p class="text-sm text-gray-500">
                Student or user account?
                <a href="login.php" class="font-bold text-blue-600 hover:text-blue-700">Go to User Login</a>
            </p>
        </div>
    </div>

    <div class="mt-8 bg-white border border-gray-100 rounded-full px-4 py-1.5 flex items-center space-x-2 text-[10px] text-gray-400 font-bold uppercase tracking-widest">
        <i class="fa-solid fa-check-circle text-green-400"></i>
        <span>Administrator Session Control</span>
    </div>
</body>
</html>
