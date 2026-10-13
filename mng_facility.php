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

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = mysqli_real_escape_string($dataconn, $search);
$normalized_search = preg_replace('/[\s-]+/', '', strtolower($search));
$normalized_search_sql = mysqli_real_escape_string($dataconn, $normalized_search);
$message = "";

if (isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($dataconn, $_GET['delete_id']);
    $booking_check = mysqli_query($dataconn, "SELECT COUNT(*) AS total FROM booking WHERE facility_id = '$delete_id'");
    $booking_total = $booking_check ? (int)mysqli_fetch_assoc($booking_check)['total'] : 0;

    if ($booking_total > 0) {
        header("Location: mng_facility.php?status=inuse");
        exit();
    }

    spg_delete_facility_description($dataconn, $delete_id);
    mysqli_query($dataconn, "DELETE FROM facilities WHERE facility_id = '$delete_id'");
    header("Location: mng_facility.php?status=deleted");
    exit();
}

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'deleted') {
        $message = "<div class='mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 font-bold'>Facility deleted successfully.</div>";
    } else if ($_GET['status'] === 'updated') {
        $message = "<div class='mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 font-bold'>Facility updated successfully.</div>";
    } else if ($_GET['status'] === 'inuse') {
        $message = "<div class='mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 font-bold'>This facility has booking records and cannot be deleted.</div>";
    } else if ($_GET['status'] === 'added') {
        $message = "<div class='mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 font-bold'>Facility added successfully.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Facilities - GMI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .line-clamp-3, .line-clamp-4 {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 { -webkit-line-clamp: 3; }
        .line-clamp-4 { -webkit-line-clamp: 4; }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-800">

    <nav class="bg-slate-800 text-white shadow-md px-8 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <?= spg_brand_logo($dataconn, 'h-9 w-9') ?>
            <span class="text-xl font-bold">GMI Admin</span>
        </div>
        <a href="admin.php" class="text-sm hover:text-blue-400 transition"><i class="fa-solid fa-arrow-left mr-2"></i>Back to Dashboard</a>
    </nav>

    <main class="max-w-[1500px] mx-auto px-4 py-10">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Facility Management</h1>
                <p class="text-gray-500 mt-1">View and manage all registered campus facilities.</p>
            </div>
            <a href="add_facility.php" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold transition flex items-center shadow-lg shadow-blue-100">
                <i class="fa-solid fa-plus mr-2"></i> Add New Facility
            </a>
        </div>

        <?= $message ?>

        <form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-col md:flex-row gap-3">
            <div class="flex-1">
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Search by Lab / Facility Name</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Enter lab or facility name"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-bold transition">
                    <i class="fa-solid fa-magnifying-glass mr-2"></i>Search
                </button>
                <a href="mng_facility.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-5 py-3 rounded-xl font-bold transition">Reset</a>
            </div>
        </form>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1280px] text-left table-fixed">
                    <colgroup>
                        <col class="w-[5%]">
                        <col class="w-[24%]">
                        <col class="w-[16%]">
                        <col class="w-[12%]">
                        <col class="w-[22%]">
                        <col class="w-[13%]">
                        <col class="w-[8%]">
                    </colgroup>
                    <thead>
                        <tr class="bg-slate-50 border-b border-gray-100 text-xs font-bold uppercase tracking-widest text-slate-500">
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Facility Name</th>
                            <th class="px-6 py-4">Complex / Building</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4">Peripherals</th>
                            <th class="px-6 py-4">Person In Charge</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php
                        $query = "SELECT f.*, " . spg_facility_description_select('f', 'fd') . "
                            FROM facilities f
                            " . spg_facility_description_join('f', 'fd');
                        if ($search !== '') {
                            $query .= " WHERE f.name LIKE '%$search_sql%'
                                OR f.location LIKE '%$search_sql%'
                                OR f.category LIKE '%$search_sql%'
                                OR REPLACE(REPLACE(LOWER(f.name), ' ', ''), '-', '') LIKE '%$normalized_search_sql%'
                                OR REPLACE(REPLACE(LOWER(f.location), ' ', ''), '-', '') LIKE '%$normalized_search_sql%'";
                        }
                        $query .= " ORDER BY f.facility_id DESC";
                        $result = mysqli_query($dataconn, $query);

                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr class='hover:bg-blue-50/30 transition'>";
                                echo "<td class='px-6 py-4 font-mono text-xs text-blue-600 font-bold whitespace-nowrap'>#" . $row['facility_id'] . "</td>";
                                echo "<td class='px-6 py-4 align-top'>";
                                echo "<div class='font-bold text-gray-900'>" . htmlspecialchars($row['name']) . "</div>";
                                echo "<div class='text-xs text-gray-400 mt-1 line-clamp-3' title='" . htmlspecialchars($row['facility_description'] ?? '', ENT_QUOTES) . "'>" . htmlspecialchars($row['facility_description'] ?? '') . "</div>";
                                if (!empty($row['unique_details'])) {
                                    echo "<div class='text-xs text-slate-500 mt-2 line-clamp-3'><span class='font-bold'>Unique:</span> " . htmlspecialchars($row['unique_details']) . "</div>";
                                }
                                echo "</td>";
                                echo "<td class='px-6 py-4 text-gray-600 align-top'><div class='flex items-start gap-2'><i class='fa-solid fa-location-dot text-gray-400 text-xs mt-1'></i><span>" . htmlspecialchars($row['location']) . "</span></div></td>";
                                echo "<td class='px-6 py-4 align-top'><span class='inline-block px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold break-words'>" . htmlspecialchars($row['category']) . "</span></td>";
                                echo "<td class='px-6 py-4 text-gray-600 text-sm align-top'><div class='line-clamp-4' title='" . htmlspecialchars($row['peripherals'] ?? '', ENT_QUOTES) . "'>" . nl2br(htmlspecialchars($row['peripherals'] ?? '')) . "</div></td>";
                                echo "<td class='px-6 py-4 text-gray-600 text-sm align-top'>";
                                echo "<span class='font-bold text-slate-800'>" . htmlspecialchars($row['person_in_charge'] ?? '') . "</span><br>";
                                echo "<span class='text-xs text-gray-400'>" . htmlspecialchars($row['pic_phone'] ?? '') . "</span>";
                                echo "</td>";
                                echo "<td class='px-6 py-4 text-right whitespace-nowrap align-top'>";
                                echo "<a href='edit_facility.php?facility_id=" . $row['facility_id'] . "' class='text-blue-500 hover:text-blue-700 font-bold text-sm transition'>Edit</a>";
                                echo "<a href='mng_facility.php?delete_id=" . $row['facility_id'] . "' onclick=\"return confirm('Delete this facility? Facilities with existing bookings will be kept for reporting history.')\" class='text-red-400 hover:text-red-600 font-bold text-sm transition ml-3'>Delete</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='px-6 py-10 text-center text-gray-400 italic'>No facilities found in the database.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>
