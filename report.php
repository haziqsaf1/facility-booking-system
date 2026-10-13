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
$selected_month = isset($_GET['month']) ? $_GET['month'] : 'all';
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$where = "WHERE YEAR(b.date) = '$selected_year'";
if ($selected_month !== 'all') {
    $month_sql = (int)$selected_month;
    $where .= " AND MONTH(b.date) = '$month_sql'";
}

$years_result = mysqli_query($dataconn, "SELECT DISTINCT YEAR(date) AS report_year FROM booking ORDER BY report_year DESC");
$report_query = "SELECT b.booking_id, b.date, b.time, b.purpose, b.emergency_phone, b.remarks,
                        f.name AS facility_name, f.location, f.category, " . spg_facility_description_select('f', 'fd') . ", f.peripherals,
                        f.person_in_charge, f.pic_phone, f.pic_details,
                        u.full_name, u.matric_id, u.email, u.phone_number
                 FROM booking b
                 INNER JOIN facilities f ON b.facility_id = f.facility_id
                 " . spg_facility_description_join('f', 'fd') . "
                 LEFT JOIN users u ON b.user_id = u.$user_id_column
                 $where
                 ORDER BY b.date ASC, b.time ASC";
$report_result = mysqli_query($dataconn, $report_query);

$rows = array();
$facility_counts = array();
while ($report_result && $row = mysqli_fetch_assoc($report_result)) {
    $rows[] = $row;
    $facility_name = $row['facility_name'] ?: 'Unknown';
    $facility_counts[$facility_name] = ($facility_counts[$facility_name] ?? 0) + 1;
}

$total_bookings = count($rows);
$unique_facilities = count($facility_counts);
$top_facility = '-';
if (!empty($facility_counts)) {
    arsort($facility_counts);
    $top_facility = array_key_first($facility_counts);
}

$period_label = ($selected_month === 'all' ? 'All Months' : date('F', mktime(0, 0, 0, (int)$selected_month, 1))) . " " . $selected_year;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Reports - GMI Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-900">

    <nav class="bg-slate-900 text-white px-8 py-4 flex justify-between items-center shadow-lg border-b border-slate-700">
        <div class="flex items-center space-x-3">
            <?= spg_brand_logo($dataconn, 'h-9 w-9') ?>
            <span class="text-xl font-bold tracking-tight">GMI Admin Reports</span>
        </div>
        <div class="flex items-center space-x-4 text-sm">
            <span class="opacity-90"><i class="fa-solid fa-user mr-1.5"></i><?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="admin.php" class="hover:text-blue-300 transition"><i class="fa-solid fa-arrow-left mr-2"></i>Dashboard</a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-10">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-5 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Booking Reports</h1>
                <p class="text-slate-500 mt-1">Compact booking summary with expandable full details.</p>
            </div>

            <form method="GET" class="bg-white border border-gray-100 rounded-2xl p-4 flex flex-col sm:flex-row gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Month</label>
                    <select name="month" class="px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                        <option value="all" <?= $selected_month === 'all' ? 'selected' : '' ?>>All Months</option>
                        <?php for ($month = 1; $month <= 12; $month++): ?>
                            <option value="<?= $month ?>" <?= (string)$month === (string)$selected_month ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $month, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Year</label>
                    <select name="year" class="px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                        <?php if ($years_result && mysqli_num_rows($years_result) > 0): ?>
                            <?php while ($year = mysqli_fetch_assoc($years_result)): ?>
                                <option value="<?= $year['report_year'] ?>" <?= (int)$year['report_year'] === $selected_year ? 'selected' : '' ?>><?= $year['report_year'] ?></option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="flex items-end">
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-bold transition">
                        <i class="fa-solid fa-filter mr-2"></i>Generate
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-8 py-6 bg-slate-50 border-b flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <h3 class="font-bold text-lg text-slate-800 flex items-center">
                    <i class="fa-solid fa-table mr-3 text-slate-400"></i>
                    <?= htmlspecialchars($period_label) ?> Booking Report
                </h3>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Click a row to view full details</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left text-sm table-fixed">
                    <colgroup>
                        <col class="w-[10%]">
                        <col class="w-[16%]">
                        <col class="w-[18%]">
                        <col class="w-[18%]">
                        <col class="w-[18%]">
                        <col class="w-[14%]">
                        <col class="w-[6%]">
                    </colgroup>
                    <thead class="bg-white text-slate-400 text-[10px] font-black uppercase tracking-widest border-b">
                        <tr>
                            <th class="px-5 py-4">Booking</th>
                            <th class="px-5 py-4">Date / Time</th>
                            <th class="px-5 py-4">Facility</th>
                            <th class="px-5 py-4">Booked By</th>
                            <th class="px-5 py-4">Purpose</th>
                            <th class="px-5 py-4">PIC</th>
                            <th class="px-5 py-4 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (!empty($rows)): ?>
                            <?php foreach ($rows as $row): ?>
                                <tr class="hover:bg-slate-50 cursor-pointer report-row" data-target="report-details-<?= $row['booking_id'] ?>">
                                    <td class="px-5 py-5 font-bold text-blue-600">#<?= $row['booking_id'] ?></td>
                                    <td class="px-5 py-5 text-gray-600 whitespace-nowrap">
                                        <?= date('D, M j, Y', strtotime($row['date'])) ?><br>
                                        <span class="text-xs text-gray-400"><?= date('g:i A', strtotime($row['time'])) ?></span>
                                    </td>
                                    <td class="px-5 py-5">
                                        <p class="font-bold text-slate-900 truncate"><?= htmlspecialchars($row['facility_name']) ?></p>
                                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($row['location']) ?> - <?= htmlspecialchars($row['category']) ?></p>
                                    </td>
                                    <td class="px-5 py-5">
                                        <p class="font-bold text-slate-900 truncate"><?= htmlspecialchars($row['full_name'] ?: 'Unknown user') ?></p>
                                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($row['matric_id'] ?: '-') ?></p>
                                    </td>
                                    <td class="px-5 py-5 text-gray-600">
                                        <div class="truncate" title="<?= htmlspecialchars($row['purpose'] ?: '-') ?>"><?= htmlspecialchars($row['purpose'] ?: '-') ?></div>
                                    </td>
                                    <td class="px-5 py-5">
                                        <p class="font-bold text-slate-900 truncate"><?= htmlspecialchars($row['person_in_charge'] ?: 'Not assigned') ?></p>
                                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($row['emergency_phone'] ?: ($row['pic_phone'] ?: '-')) ?></p>
                                    </td>
                                    <td class="px-5 py-5 text-right text-blue-600">
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </td>
                                </tr>
                                <tr id="report-details-<?= $row['booking_id'] ?>" class="hidden bg-slate-50">
                                    <td colspan="7" class="px-6 py-6">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-sm">
                                            <div>
                                                <p class="text-xs font-black uppercase text-gray-400 mb-1">Facility Details</p>
                                                <p class="font-bold text-slate-900"><?= htmlspecialchars($row['facility_name']) ?></p>
                                                <p class="text-gray-600"><?= htmlspecialchars($row['location']) ?> - <?= htmlspecialchars($row['category']) ?></p>
                                                <p class="text-gray-600 mt-2"><?= nl2br(htmlspecialchars($row['facility_description'] ?: '-')) ?></p>
                                                <?php if (!empty($row['unique_details'])): ?>
                                                    <p class="font-bold text-xs uppercase text-gray-400 mt-3 mb-1">Unique Details</p>
                                                    <p class="text-gray-600"><?= nl2br(htmlspecialchars($row['unique_details'])) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black uppercase text-gray-400 mb-1">Peripherals & PIC</p>
                                                <p class="text-gray-600"><?= nl2br(htmlspecialchars($row['peripherals'] ?: '-')) ?></p>
                                                <p class="font-bold text-slate-900 mt-2"><?= htmlspecialchars($row['person_in_charge'] ?: 'Not assigned') ?></p>
                                                <p class="text-gray-600"><?= htmlspecialchars($row['emergency_phone'] ?: ($row['pic_phone'] ?: '-')) ?></p>
                                                <p class="text-gray-500"><?= nl2br(htmlspecialchars($row['pic_details'] ?: '')) ?></p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black uppercase text-gray-400 mb-1">Booker & Remarks</p>
                                                <p><span class="font-bold">Booked By:</span> <?= htmlspecialchars($row['full_name'] ?: 'Unknown user') ?></p>
                                                <p><span class="font-bold">Matric ID:</span> <?= htmlspecialchars($row['matric_id'] ?: '-') ?></p>
                                                <p><span class="font-bold">Email:</span> <?= htmlspecialchars($row['email'] ?: '-') ?></p>
                                                <p class="mt-2"><span class="font-bold">Remarks:</span> <?= nl2br(htmlspecialchars($row['remarks'] ?: '-')) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center text-gray-400 italic">No booking data found for the selected period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        document.querySelectorAll('.report-row').forEach(row => {
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
