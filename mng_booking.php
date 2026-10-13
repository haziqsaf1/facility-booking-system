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

$user_id_column = spg_user_id_column($dataconn);
$message = "";

if (isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($dataconn, $_GET['delete_id']);
    mysqli_query($dataconn, "DELETE FROM booking WHERE booking_id = '$delete_id'");
    header("Location: mng_booking.php?status=deleted");
    exit();
}

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'deleted') {
        $message = "<div class='mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 font-bold'>Booking deleted successfully.</div>";
    } else if ($_GET['status'] === 'updated') {
        $message = "<div class='mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 font-bold'>Booking updated successfully.</div>";
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$facility_filter = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;
$date_filter = isset($_GET['date']) ? trim($_GET['date']) : '';
$month_filter = isset($_GET['month']) ? $_GET['month'] : 'all';
$year_filter = isset($_GET['year']) ? $_GET['year'] : 'all';

$conditions = array();
if ($search !== '') {
    $search_sql = mysqli_real_escape_string($dataconn, $search);
    $conditions[] = "(f.name LIKE '%$search_sql%' OR f.location LIKE '%$search_sql%' OR u.full_name LIKE '%$search_sql%' OR u.matric_id LIKE '%$search_sql%' OR u.email LIKE '%$search_sql%' OR b.purpose LIKE '%$search_sql%')";
}
if ($facility_filter > 0) {
    $conditions[] = "b.facility_id = '$facility_filter'";
}
if ($date_filter !== '') {
    $date_sql = mysqli_real_escape_string($dataconn, $date_filter);
    $conditions[] = "b.date = '$date_sql'";
}
if ($month_filter !== 'all') {
    $conditions[] = "MONTH(b.date) = '" . (int)$month_filter . "'";
}
if ($year_filter !== 'all') {
    $conditions[] = "YEAR(b.date) = '" . (int)$year_filter . "'";
}

$where_sql = count($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

$facilities = mysqli_query($dataconn, "SELECT facility_id, name, location FROM facilities ORDER BY name ASC");
$years = mysqli_query($dataconn, "SELECT DISTINCT YEAR(date) AS booking_year FROM booking ORDER BY booking_year DESC");

$booking_query = "SELECT b.booking_id, b.user_id, b.facility_id, b.date, b.time, b.purpose, b.emergency_phone, b.remarks,
                         f.name AS facility_name, f.location, f.category, " . spg_facility_description_select('f', 'fd') . ", f.peripherals,
                         f.person_in_charge, f.pic_phone, f.pic_details,
                         u.full_name, u.matric_id, u.email, u.phone_number
                  FROM booking b
                  INNER JOIN facilities f ON b.facility_id = f.facility_id
                  " . spg_facility_description_join('f', 'fd') . "
                  LEFT JOIN users u ON b.user_id = u.$user_id_column
                  $where_sql
                  ORDER BY b.date DESC, b.time DESC";
$bookings = mysqli_query($dataconn, $booking_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Bookings - GMI Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-900">

    <nav class="bg-slate-900 text-white px-8 py-4 flex justify-between items-center shadow-lg border-b border-slate-700">
        <div class="flex items-center space-x-3">
            <?= spg_brand_logo($dataconn, 'h-9 w-9') ?>
            <span class="text-xl font-bold tracking-tight">Booking Management</span>
        </div>
        <div class="flex items-center space-x-4 text-sm">
            <span class="opacity-90"><i class="fa-solid fa-user mr-1.5"></i><?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="admin.php" class="hover:text-blue-300 transition"><i class="fa-solid fa-arrow-left mr-2"></i>Dashboard</a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-10">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Manage Bookings</h1>
                <p class="text-slate-500 mt-1">Search, review, edit, or cancel facility bookings.</p>
            </div>
            <a href="report.php" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-bold transition shadow-lg shadow-blue-100">
                <i class="fa-solid fa-chart-column mr-2"></i>Booking Reports
            </a>
        </div>

        <?= $message ?>

        <form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 grid grid-cols-1 md:grid-cols-6 gap-3">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Search</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Facility, user, email, purpose"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Facility</label>
                <select name="facility_id" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                    <option value="0">All Facilities</option>
                    <?php while ($facility = mysqli_fetch_assoc($facilities)): ?>
                        <option value="<?= $facility['facility_id'] ?>" <?= $facility_filter === (int)$facility['facility_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($facility['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Date</label>
                <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Month</label>
                <select name="month" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                    <option value="all" <?= $month_filter === 'all' ? 'selected' : '' ?>>All Months</option>
                    <?php for ($month = 1; $month <= 12; $month++): ?>
                        <option value="<?= $month ?>" <?= (string)$month_filter === (string)$month ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $month, 1)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Year</label>
                <select name="year" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                    <option value="all" <?= $year_filter === 'all' ? 'selected' : '' ?>>All Years</option>
                    <?php while ($year = mysqli_fetch_assoc($years)): ?>
                        <option value="<?= $year['booking_year'] ?>" <?= (string)$year_filter === (string)$year['booking_year'] ? 'selected' : '' ?>><?= $year['booking_year'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="md:col-span-6 flex gap-2 justify-end">
                <a href="mng_booking.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-5 py-3 rounded-xl font-bold transition">Reset</a>
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-bold transition">
                    <i class="fa-solid fa-filter mr-2"></i>Apply Filter
                </button>
            </div>
        </form>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1120px] text-left table-fixed">
                    <colgroup>
                        <col class="w-[11%]">
                        <col class="w-[18%]">
                        <col class="w-[16%]">
                        <col class="w-[13%]">
                        <col class="w-[15%]">
                        <col class="w-[12%]">
                        <col class="w-[15%]">
                    </colgroup>
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Booking</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Facility</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Booked By</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Date / Time</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Purpose</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($bookings && mysqli_num_rows($bookings) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($bookings)): ?>
                                <tr class="hover:bg-gray-50 transition cursor-pointer booking-row" data-target="booking-admin-details-<?= $row['booking_id'] ?>">
                                    <td class="px-6 py-5 font-bold text-blue-600">#<?= $row['booking_id'] ?></td>
                                    <td class="px-6 py-5">
                                        <p class="font-bold text-slate-900"><?= htmlspecialchars($row['facility_name']) ?></p>
                                        <p class="text-xs text-gray-400"><?= htmlspecialchars($row['location']) ?></p>
                                    </td>
                                    <td class="px-6 py-5">
                                        <p class="font-bold text-slate-900 truncate"><?= htmlspecialchars($row['full_name'] ?: 'Unknown user') ?></p>
                                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($row['email'] ?: '-') ?></p>
                                    </td>
                                    <td class="px-6 py-5 text-sm text-gray-600 whitespace-nowrap">
                                        <?= date('D, M j, Y', strtotime($row['date'])) ?><br>
                                        <span class="text-xs text-gray-400"><?= date('g:i A', strtotime($row['time'])) ?></span>
                                    </td>
                                    <td class="px-6 py-5 text-sm text-gray-600">
                                        <div class="truncate" title="<?= htmlspecialchars($row['purpose'] ?: '-') ?>"><?= htmlspecialchars($row['purpose'] ?: '-') ?></div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <span class="px-3 py-1 text-xs font-bold rounded-full bg-green-50 text-green-600 border border-green-100">Confirmed</span>
                                    </td>
                                    <td class="px-6 py-5 text-right whitespace-nowrap">
                                        <a href="edit_booking.php?booking_id=<?= $row['booking_id'] ?>" onclick="event.stopPropagation()" class="text-blue-500 hover:text-blue-700 text-sm font-bold transition mr-4">Edit</a>
                                        <a href="mng_booking.php?delete_id=<?= $row['booking_id'] ?>" onclick="event.stopPropagation(); return confirm('Delete this booking?')" class="text-red-400 hover:text-red-600 text-sm font-bold transition">Delete</a>
                                    </td>
                                </tr>
                                <tr id="booking-admin-details-<?= $row['booking_id'] ?>" class="hidden bg-slate-50">
                                    <td colspan="7" class="px-6 py-6">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-sm">
                                            <div>
                                                <p class="text-xs font-black uppercase text-gray-400 mb-1">Facility Details</p>
                                                <p class="font-bold text-slate-900"><?= htmlspecialchars($row['facility_name']) ?></p>
                                                <p class="text-gray-600"><?= htmlspecialchars($row['location']) ?> - <?= htmlspecialchars($row['category']) ?></p>
                                                <p class="text-gray-600 mt-2"><?= nl2br(htmlspecialchars($row['facility_description'] ?: 'No description added.')) ?></p>
                                                <?php if (!empty($row['unique_details'])): ?>
                                                    <p class="font-bold text-xs uppercase text-gray-400 mt-3 mb-1">Unique Details</p>
                                                    <p class="text-gray-600"><?= nl2br(htmlspecialchars($row['unique_details'])) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black uppercase text-gray-400 mb-1">Peripherals & PIC</p>
                                                <p class="text-gray-600"><?= nl2br(htmlspecialchars($row['peripherals'] ?: 'No peripherals listed.')) ?></p>
                                                <p class="font-bold text-slate-900 mt-2"><?= htmlspecialchars($row['person_in_charge'] ?: 'Not assigned') ?></p>
                                                <p class="text-gray-600"><?= htmlspecialchars($row['emergency_phone'] ?: ($row['pic_phone'] ?: '-')) ?></p>
                                                <p class="text-gray-500"><?= nl2br(htmlspecialchars($row['pic_details'] ?: '')) ?></p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black uppercase text-gray-400 mb-1">Booking Details</p>
                                                <p><span class="font-bold">Booked By:</span> <?= htmlspecialchars($row['full_name'] ?: 'Unknown user') ?></p>
                                                <p><span class="font-bold">Matric ID:</span> <?= htmlspecialchars($row['matric_id'] ?: '-') ?></p>
                                                <p><span class="font-bold">Email:</span> <?= htmlspecialchars($row['email'] ?: '-') ?></p>
                                                <p class="mt-2"><span class="font-bold">Remarks:</span> <?= nl2br(htmlspecialchars($row['remarks'] ?: '-')) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center text-gray-400 italic">No bookings found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        document.querySelectorAll('.booking-row').forEach(row => {
            row.addEventListener('click', () => {
                const target = document.getElementById(row.dataset.target);
                if (target) {
                    target.classList.toggle('hidden');
                }
            });
        });
    </script>
</body>
</html>
