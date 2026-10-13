<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

include 'connectdb.php';
require_once 'system_helpers.php';
mysqli_select_db($dataconn, 'cookie');
spg_ensure_schema($dataconn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - GMI Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-800">

    <nav class="bg-blue-700 text-white px-8 py-4 flex justify-between items-center shadow-lg">
        <div class="flex items-center space-x-3">
            <?= spg_brand_logo($dataconn, 'h-9 w-9') ?>
            <span class="font-bold text-xl">User Management</span>
        </div>
        <a href="admin.php" class="text-sm bg-blue-800 px-4 py-2 rounded-lg hover:bg-blue-900 transition">Dashboard</a>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-10">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Registered Users</h2>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 font-bold">
                User account removed successfully.
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b text-slate-400 text-xs font-bold uppercase">
                    <tr>
                        <th class="px-6 py-4">Full Name</th>
                        <th class="px-6 py-4">Matric / Email</th>
                        <th class="px-6 py-4">Role</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm">
                    <?php
                    $user_res = mysqli_query($dataconn, "SELECT * FROM users ORDER BY role ASC");
                    
                    while ($user = mysqli_fetch_assoc($user_res)) {
                        $role_color = ($user['role'] === 'admin') ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700';
                        
                        echo "<tr class='hover:bg-slate-50 transition'>";
                        echo "<td class='px-6 py-4 font-bold'>" . htmlspecialchars($user['full_name']) . "</td>";
                        echo "<td class='px-6 py-4'>" . htmlspecialchars($user['matric_id']) . "<br><span class='text-xs text-gray-400'>" . htmlspecialchars($user['email']) . "</span></td>";
                        echo "<td class='px-6 py-4'><span class='px-3 py-1 rounded-full text-[10px] font-black uppercase $role_color'>" . $user['role'] . "</span></td>";
                        echo "<td class='px-6 py-4 text-center'>";
                        
                        // Prevent deleting current logged in admin
                        if ($user['user_id'] != $_SESSION['user_id']) {
                            echo "<a href='delete_user.php?user_id=" . $user['user_id'] . "' 
                                     onclick=\"return confirm('Are you sure you want to delete this user?')\" 
                                     class='text-red-500 hover:bg-red-50 px-4 py-2 rounded-lg transition font-bold'>
                                     Delete
                                  </a>";
                        } else {
                            echo "<span class='text-gray-300 italic'>Current User</span>";
                        }

                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
