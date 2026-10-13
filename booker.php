<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

include 'connectdb.php';
require_once 'system_helpers.php';

mysqli_select_db($dataconn, 'cookie');
spg_ensure_schema($dataconn);

$user_id = $_SESSION['user_id'];
$user_id_sql = mysqli_real_escape_string($dataconn, $user_id);

// Handle Deletion
if (isset($_GET['cancel_id'])) {
    $cancel_id = mysqli_real_escape_string($dataconn, $_GET['cancel_id']);

    // Only allow user to delete their own booking for security
    $del_query = "DELETE FROM booking
                  WHERE booking_id = '$cancel_id'
                  AND user_id = '$user_id_sql'";

    mysqli_query($dataconn, $del_query);

    header("Location: booker.php");
    exit();
}

$user_id_column = spg_user_id_column($dataconn);

// Fetch all bookings for this specific student
$query = "SELECT b.booking_id, b.date, b.time, b.purpose, b.emergency_phone, b.remarks,
                 f.name AS fac_name, f.location, f.category, " . spg_facility_description_select('f', 'fd') . ", f.peripherals,
                 f.person_in_charge, f.pic_phone, f.pic_details,
                 u.full_name, u.email, u.matric_id, u.phone_number
          FROM booking b
          INNER JOIN facilities f ON b.facility_id = f.facility_id
          " . spg_facility_description_join('f', 'fd') . "
          INNER JOIN users u ON b.user_id = u.$user_id_column
          WHERE b.user_id = '$user_id_sql'
          ORDER BY b.date DESC, b.time DESC";
$result = mysqli_query($dataconn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>Facility Bookings - GMI</title>

    <!-- TAILWIND -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- FONT AWESOME -->
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body class="bg-[#f8fafc] font-sans antialiased text-slate-800">

    <!-- NAVBAR -->
    <nav class="bg-[#2563eb] text-white shadow-md px-8 py-3 flex justify-between items-center">

        <div class="flex items-center space-x-2">
            <?= spg_brand_logo($dataconn, 'h-9 w-9') ?>

            <span class="text-lg font-bold tracking-wide">
                GMI Booking
            </span>

        </div>

        <div class="flex items-center space-x-6 text-sm font-medium">
            <span class="opacity-90"><i class="fa-solid fa-user mr-1.5"></i><?= htmlspecialchars($_SESSION['username']) ?></span>

            <a href="login.php?action=logout"
            class="bg-blue-700 px-4 py-1.5 rounded-lg hover:bg-blue-800 transition">

                Logout

            </a>

        </div>

    </nav>

    <!-- MAIN -->
    <main class="max-w-7xl mx-auto px-4 py-10">

        <!-- HEADER -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">

            <div>

                <h1 class="text-3xl font-bold text-gray-900">
                    Facility Bookings
                </h1>

                <p class="text-gray-500 text-sm">
                    View your reservations and click any booking row for full details.
                </p>

            </div>

            <div class="flex flex-col sm:flex-row gap-3">

                <!-- FACILITY INFO -->
                <a href="facilitylist.php"
                class="inline-flex items-center justify-center gap-2 bg-slate-700 hover:bg-slate-800 text-white px-6 py-3 rounded-2xl font-bold transition shadow-lg">

                    <i class="fa-solid fa-building"></i>

                    <span>
                        View Facilities Info
                    </span>

                </a>

                <!-- NEW BOOKING -->
                <a href="booking.php"
                class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-2xl font-bold transition shadow-lg shadow-blue-100">

                    <i class="fa-solid fa-plus"></i>

                    <span>
                        Make New Booking
                    </span>

                </a>

            </div>

        </div>

        <!-- TABLE -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">

            <div class="overflow-x-auto">

                <table class="w-full min-w-[1050px] text-left table-fixed">
                    <colgroup>
                        <col class="w-[22%]">
                        <col class="w-[14%]">
                        <col class="w-[18%]">
                        <col class="w-[12%]">
                        <col class="w-[16%]">
                        <col class="w-[10%]">
                        <col class="w-[8%]">
                    </colgroup>

                    <!-- TABLE HEADER -->
                    <thead class="bg-gray-50 border-b border-gray-100">

                        <tr>

                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                Facility
                            </th>

                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                Booked By
                            </th>

                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                Date
                            </th>

                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                Time
                            </th>

                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                Purpose
                            </th>

                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                Status
                            </th>

                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <!-- TABLE BODY -->
                    <tbody class="divide-y divide-gray-100">

                        <?php if ($result && mysqli_num_rows($result) > 0): ?>

                            <?php while ($row = mysqli_fetch_assoc($result)): ?>

                                <tr class="hover:bg-gray-50 transition cursor-pointer booking-row" data-target="booking-details-<?= $row['booking_id'] ?>">

                                    <!-- FACILITY -->
                                    <td class="px-6 py-5">

                                        <div class="flex items-center gap-3">

                                            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg text-lg">

                                                <i class="fa-solid fa-chalkboard-user"></i>

                                            </div>

                                            <div>
                                                <span class="font-bold text-slate-800 block">
                                                    <?= htmlspecialchars($row['fac_name']) ?>
                                                </span>
                                                <span class="text-xs text-gray-400">
                                                    <?= htmlspecialchars($row['location']) ?>
                                                </span>
                                            </div>

                                        </div>

                                    </td>

                                    <!-- BOOKED BY -->
                                    <td class="px-6 py-5 text-sm text-gray-600 whitespace-nowrap">

                                        <i class="fa-solid fa-user mr-1.5 text-gray-400"></i>

                                        <?= htmlspecialchars($row['full_name']) ?>

                                    </td>

                                    <!-- DATE -->
                                    <td class="px-6 py-5 text-sm text-gray-600 whitespace-nowrap">

                                        <i class="fa-regular fa-calendar mr-1.5 text-gray-400"></i>

                                        <?= date('D, M j, Y', strtotime($row['date'])) ?>

                                    </td>

                                    <!-- TIME -->
                                    <td class="px-6 py-5 text-sm text-gray-600 whitespace-nowrap">

                                        <i class="fa-regular fa-clock mr-1.5 text-gray-400"></i>

                                        <?= date('g:i A', strtotime($row['time'])) ?>

                                    </td>

                                    <td class="px-6 py-5 text-sm text-gray-600">
                                        <div class="truncate" title="<?= htmlspecialchars($row['purpose'] ?: '-') ?>">
                                        <?= htmlspecialchars($row['purpose'] ?: '-') ?>
                                        </div>
                                    </td>

                                    <!-- STATUS -->
                                    <td class="px-6 py-5">

                                        <span class="px-3 py-1 text-xs font-bold rounded-full bg-green-50 text-green-600 border border-green-100">

                                            Confirmed

                                        </span>

                                    </td>

                                    <!-- ACTION -->
                                    <td class="px-6 py-5 text-right whitespace-nowrap">

                                        <a href="booker.php?cancel_id=<?= $row['booking_id'] ?>"
                                           onclick="event.stopPropagation(); return confirm('Are you sure you want to cancel this booking?')"
                                           class="text-red-400 hover:text-red-600 text-sm font-medium transition">

                                            Cancel

                                        </a>

                                    </td>

                                </tr>

                                <tr id="booking-details-<?= $row['booking_id'] ?>" class="hidden bg-slate-50">
                                    <td colspan="7" class="px-6 py-6">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-sm table-fixed">
                                            <div>
                                                <p class="text-xs font-black uppercase text-gray-400 mb-1">Facility Details</p>
                                                <p class="font-bold text-slate-900"><?= htmlspecialchars($row['fac_name']) ?></p>
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
                                                <p class="text-gray-600"><?= htmlspecialchars($row['pic_phone'] ?: 'No phone number') ?></p>
                                                <p class="text-gray-500"><?= nl2br(htmlspecialchars($row['pic_details'] ?: '')) ?></p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black uppercase text-gray-400 mb-1">Booking Details</p>
                                                <p><span class="font-bold">PIC Emergency Phone:</span> <?= htmlspecialchars($row['emergency_phone'] ?: $row['pic_phone']) ?></p>
                                                <p><span class="font-bold">Email:</span> <?= htmlspecialchars($row['email']) ?></p>
                                                <p><span class="font-bold">Matric ID:</span> <?= htmlspecialchars($row['matric_id']) ?></p>
                                                <p class="mt-2"><span class="font-bold">Remarks:</span> <?= nl2br(htmlspecialchars($row['remarks'] ?: '-')) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <!-- EMPTY -->
                            <tr>

                                <td colspan="7"
                                class="px-6 py-20 text-center text-gray-400 italic">

                                    <div class="text-4xl mb-4 opacity-20">

                                        <i class="fa-solid fa-calendar-xmark"></i>

                                    </div>

                                    No bookings found.

                                </td>

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
