<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

include 'connectdb.php';
require_once 'system_helpers.php';
mysqli_select_db($dataconn, 'cookie');
spg_ensure_schema($dataconn);

// Week Navigation Logic
$week_offset = isset($_GET['week']) ? (int)$_GET['week'] : 0;
$monday_this_week = strtotime("monday this week");
$target_monday = strtotime("$week_offset weeks", $monday_this_week);
$user_id_column = spg_user_id_column($dataconn);

// Fetch Live Stats
$total_facilities = mysqli_fetch_assoc(mysqli_query($dataconn, "SELECT COUNT(*) as total FROM facilities"))['total'] ?? 0;
$total_users = mysqli_fetch_assoc(mysqli_query($dataconn, "SELECT COUNT(*) as total FROM users"))['total'] ?? 0;
$total_bookings = mysqli_fetch_assoc(mysqli_query($dataconn, "SELECT COUNT(*) as total FROM booking"))['total'] ?? 0;

$booking_query = "SELECT b.booking_id, b.date, b.time, b.purpose, b.emergency_phone, b.remarks,
                         f.name AS facility_name, f.location, f.category, " . spg_facility_description_select('f', 'fd') . ", f.peripherals,
                         f.person_in_charge, f.pic_phone,
                         u.full_name, u.matric_id, u.email, u.phone_number
                  FROM booking b
                  INNER JOIN facilities f ON b.facility_id = f.facility_id
                  " . spg_facility_description_join('f', 'fd') . "
                  LEFT JOIN users u ON b.user_id = u.$user_id_column
                  ORDER BY b.date DESC, b.time DESC";
$booking_result = mysqli_query($dataconn, $booking_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GMI Admin - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-900">

    <nav class="bg-slate-900 text-white px-8 py-4 flex justify-between items-center shadow-lg border-b border-slate-700">
        <div class="flex items-center space-x-3">
            <?= spg_brand_logo($dataconn, 'h-9 w-9') ?>
            <span class="text-xl font-bold tracking-tight">GMI Admin System</span>
        </div>

        <div class="flex items-center space-x-6">
            <div class="flex items-center space-x-3 bg-slate-800 px-4 py-2 rounded-xl border border-slate-700">
                <div class="h-8 w-8 bg-blue-500 rounded-full flex items-center justify-center text-xs font-bold">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <div class="text-left">
                    <p class="text-[10px] font-black text-blue-400 uppercase leading-none">Logged in as</p>
                    <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                </div>
            </div>

            <a href="admin_login.php?action=logout" class="bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white px-4 py-2 rounded-lg text-xs font-bold transition flex items-center">
                <i class="fa-solid fa-right-from-bracket mr-2"></i> LOGOUT
            </a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-10">
        <div class="mb-10">
            <h1 class="text-3xl font-bold text-gray-900">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p class="text-slate-500 mt-1">You are currently managing the system with <span class="font-bold text-blue-600">Administrator Privileges</span>.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition">
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase">Total Facilities</p>
                    <h3 class="text-3xl font-bold text-slate-900"><?php echo $total_facilities; ?></h3>
                </div>
                <div class="h-12 w-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-building text-xl"></i>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition">
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase">Registered Users</p>
                    <h3 class="text-3xl font-bold text-slate-900"><?php echo $total_users; ?></h3>
                </div>
                <div class="h-12 w-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-users text-xl"></i>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition">
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase">All Bookings</p>
                    <h3 class="text-3xl font-bold text-slate-900"><?php echo $total_bookings; ?></h3>
                </div>
                <div class="h-12 w-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-calendar-check text-xl"></i>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
            <a href="mng_facility.php" class="group bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:border-blue-500 transition-all flex items-center space-x-4">
                <div class="p-4 bg-blue-600 text-white rounded-2xl group-hover:rotate-6 transition shadow-lg shadow-blue-200">
                    <i class="fa-solid fa-sliders text-2xl"></i>
                </div>
                <div>
                    <h4 class="text-xl font-bold text-slate-900">Manage Facilities</h4>
                    <p class="text-slate-500 text-sm mt-1">Configure lab names, buildings, descriptions, and PIC details.</p>
                </div>
            </a>

            <a href="mng_user.php" class="group bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:border-purple-500 transition-all flex items-center space-x-4">
                <div class="p-4 bg-purple-600 text-white rounded-2xl group-hover:rotate-6 transition shadow-lg shadow-purple-200">
                    <i class="fa-solid fa-users-gear text-2xl"></i>
                </div>
                <div>
                    <h4 class="text-xl font-bold text-slate-900">Manage Users</h4>
                    <p class="text-slate-500 text-sm mt-1">Review student accounts and remove users if necessary.</p>
                </div>
            </a>

            <a href="mng_booking.php" class="group bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:border-sky-500 transition-all flex items-center space-x-4">
                <div class="p-4 bg-sky-600 text-white rounded-2xl group-hover:rotate-6 transition shadow-lg shadow-sky-200">
                    <i class="fa-solid fa-calendar-days text-2xl"></i>
                </div>
                <div>
                    <h4 class="text-xl font-bold text-slate-900">Manage Bookings</h4>
                    <p class="text-slate-500 text-sm mt-1">Review, filter, edit, or cancel booked facilities.</p>
                </div>
            </a>

            <a href="report.php" class="group bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:border-green-500 transition-all flex items-center space-x-4">
                <div class="p-4 bg-green-600 text-white rounded-2xl group-hover:rotate-6 transition shadow-lg shadow-green-200">
                    <i class="fa-solid fa-chart-column text-2xl"></i>
                </div>
                <div>
                    <h4 class="text-xl font-bold text-slate-900">Booking Reports</h4>
                    <p class="text-slate-500 text-sm mt-1">Generate complete monthly or all-month booking reports.</p>
                </div>
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-10">
            <div class="px-8 py-6 bg-slate-50 border-b flex justify-between items-center">
                <h3 class="font-bold text-lg text-slate-800 flex items-center">
                    <i class="fa-solid fa-clock-rotate-left mr-3 text-slate-400"></i>Active Schedule
                </h3>
                <div class="flex items-center space-x-2">
                    <a href="?week=<?php echo $week_offset - 1; ?>" class="p-2 hover:bg-gray-200 rounded-full transition text-slate-600">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>

                    <span class="text-sm font-bold text-slate-700 bg-white border px-4 py-2 rounded-lg">
                        <?php
                            if($week_offset == 0) echo "This Week";
                            else if($week_offset == 1) echo "Next Week";
                            else if($week_offset == -1) echo "Last Week";
                            else echo "Week $week_offset";
                        ?>
                    </span>

                    <a href="?week=<?php echo $week_offset + 1; ?>" class="p-2 hover:bg-gray-200 rounded-full transition text-slate-600">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>

                    <?php if($week_offset != 0): ?>
                        <a href="admin.php" class="text-xs text-blue-600 hover:underline ml-2 font-bold uppercase">Today</a>
                    <?php endif; ?>
                </div>
            </div>
            <table class="w-full text-left">
                <thead class="bg-white text-slate-400 text-[10px] font-black uppercase tracking-widest border-b">
                    <tr>
                        <th class="px-8 py-4 w-1/4">Day & Date</th>
                        <th class="px-8 py-4">Scheduled Bookings</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php
                    for ($i = 0; $i < 7; $i++) {
                        $date_str = date('Y-m-d', strtotime("+$i days", $target_monday));
                        $display_date = date('D, M d', strtotime($date_str));
                        $is_today = ($date_str == date('Y-m-d')) ? 'bg-blue-50/40 border-l-4 border-l-blue-500' : '';

                        echo "<tr class='$is_today'>";
                        echo "<td class='px-8 py-6 font-bold text-slate-900 bg-slate-50/30'>$display_date</td>";
                        echo "<td class='px-8 py-6'>";

                        $q = "SELECT b.*, f.name, f.location FROM booking b
                            JOIN facilities f ON b.facility_id = f.facility_id
                            WHERE b.date = '$date_str'
                            ORDER BY b.time ASC";

                        $res = mysqli_query($dataconn, $q);
                        if($res && mysqli_num_rows($res) > 0) {
                            while($row = mysqli_fetch_assoc($res)) {
                                echo "<div class='inline-block bg-blue-50 border border-blue-100 text-blue-700 px-3 py-1 rounded-lg text-xs font-bold mr-2 mb-2'>" . htmlspecialchars($row['name']) . " - " . date('g:i A', strtotime($row['time'])) . "</div>";
                            }
                        } else {
                            echo "<span class='text-slate-300 italic text-xs'>No bookings</span>";
                        }

                        echo "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-8 py-6 bg-slate-50 border-b">
                <h3 class="font-bold text-lg text-slate-800 flex items-center">
                    <i class="fa-solid fa-list-check mr-3 text-slate-400"></i>Complete Booking Information
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-white text-slate-400 text-[10px] font-black uppercase tracking-widest border-b">
                        <tr>
                            <th class="px-6 py-4">Booking</th>
                            <th class="px-6 py-4">Facility & Place</th>
                            <th class="px-6 py-4">Booked By</th>
                            <th class="px-6 py-4">PIC Emergency Phone</th>
                            <th class="px-6 py-4">Purpose</th>
                            <th class="px-6 py-4">PIC</th>
                            <th class="px-6 py-4">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if ($booking_result && mysqli_num_rows($booking_result) > 0): ?>
                            <?php while ($booking = mysqli_fetch_assoc($booking_result)): ?>
                                <tr class="hover:bg-slate-50 align-top">
                                    <td class="px-6 py-5 font-bold text-slate-900">
                                        #<?= $booking['booking_id'] ?><br>
                                        <span class="text-xs font-normal text-gray-500"><?= date('D, M j, Y', strtotime($booking['date'])) ?> at <?= date('g:i A', strtotime($booking['time'])) ?></span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <p class="font-bold text-slate-900"><?= htmlspecialchars($booking['facility_name']) ?></p>
                                        <p class="text-gray-500"><?= htmlspecialchars($booking['location']) ?> - <?= htmlspecialchars($booking['category']) ?></p>
                                    </td>
                                    <td class="px-6 py-5">
                                        <p class="font-bold text-slate-900"><?= htmlspecialchars($booking['full_name'] ?: 'Unknown user') ?></p>
                                        <p class="text-gray-500"><?= htmlspecialchars($booking['matric_id'] ?: '-') ?></p>
                                        <p class="text-gray-500"><?= htmlspecialchars($booking['email'] ?: '-') ?></p>
                                    </td>
                                    <td class="px-6 py-5 text-gray-600"><?= htmlspecialchars($booking['emergency_phone'] ?: ($booking['pic_phone'] ?: '-')) ?></td>
                                    <td class="px-6 py-5 text-gray-600"><?= htmlspecialchars($booking['purpose'] ?: '-') ?></td>
                                    <td class="px-6 py-5">
                                        <p class="font-bold text-slate-900"><?= htmlspecialchars($booking['person_in_charge'] ?: 'Not assigned') ?></p>
                                        <p class="text-gray-500"><?= htmlspecialchars($booking['pic_phone'] ?: '-') ?></p>
                                    </td>
                                    <td class="px-6 py-5 text-gray-600 min-w-[260px]">
                                        <p><span class="font-bold text-slate-700">Description:</span> <?= htmlspecialchars($booking['facility_description'] ?: '-') ?></p>
                                        <?php if (!empty($booking['unique_details'])): ?>
                                            <p><span class="font-bold text-slate-700">Unique:</span> <?= htmlspecialchars($booking['unique_details']) ?></p>
                                        <?php endif; ?>
                                        <p><span class="font-bold text-slate-700">Peripherals:</span> <?= htmlspecialchars($booking['peripherals'] ?: '-') ?></p>
                                        <p><span class="font-bold text-slate-700">Remarks:</span> <?= htmlspecialchars($booking['remarks'] ?: '-') ?></p>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400 italic">No bookings found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
