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

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$msg = "";

if ($booking_id <= 0) {
    header("Location: mng_booking.php");
    exit();
}

if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST" && isset($_POST['update_booking'])) {
    $facility_id = mysqli_real_escape_string($dataconn, $_POST['facility_id']);
    $booking_date = mysqli_real_escape_string($dataconn, $_POST['booking_date']);
    $booking_time = mysqli_real_escape_string($dataconn, $_POST['booking_time']);
    $purpose = mysqli_real_escape_string($dataconn, $_POST['purpose']);
    $remarks = mysqli_real_escape_string($dataconn, $_POST['remarks']);

    $facility_res = mysqli_query($dataconn, "SELECT pic_phone FROM facilities WHERE facility_id = '$facility_id' LIMIT 1");
    $facility = $facility_res ? mysqli_fetch_assoc($facility_res) : array();
    $emergency_phone = mysqli_real_escape_string($dataconn, $facility['pic_phone'] ?? '');

    $check = mysqli_query($dataconn, "SELECT booking_id FROM booking
        WHERE facility_id = '$facility_id'
        AND date = '$booking_date'
        AND time = '$booking_time'
        AND booking_id <> '$booking_id'
        LIMIT 1");

    if ($check && mysqli_num_rows($check) > 0) {
        $msg = "<div class='bg-red-50 text-red-600 p-4 rounded-xl mb-6 border border-red-100'>Another booking already uses this facility, date, and time.</div>";
    } else if ($emergency_phone === '') {
        $msg = "<div class='bg-red-50 text-red-600 p-4 rounded-xl mb-6 border border-red-100'>The selected facility has no PIC phone number. Update the facility before assigning this booking.</div>";
    } else {
        $sql = "UPDATE booking SET
                    facility_id = '$facility_id',
                    date = '$booking_date',
                    time = '$booking_time',
                    purpose = '$purpose',
                    emergency_phone = '$emergency_phone',
                    remarks = '$remarks'
                WHERE booking_id = '$booking_id'";

        if (mysqli_query($dataconn, $sql)) {
            header("Location: mng_booking.php?status=updated");
            exit();
        }

        $msg = "<div class='bg-red-50 text-red-600 p-4 rounded-xl mb-6 border border-red-100'>Error updating booking: " . mysqli_error($dataconn) . "</div>";
    }
}

$booking_res = mysqli_query($dataconn, "SELECT * FROM booking WHERE booking_id = '$booking_id' LIMIT 1");
$booking = $booking_res ? mysqli_fetch_assoc($booking_res) : null;

if (!$booking) {
    header("Location: mng_booking.php");
    exit();
}

$facilities = mysqli_query($dataconn, "SELECT facility_id, name, location, category FROM facilities ORDER BY location ASC, name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Booking - GMI Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-800">

    <nav class="bg-slate-800 text-white shadow-md px-8 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <?= spg_brand_logo($dataconn, 'h-9 w-9') ?>
            <span class="text-xl font-bold">GMI Admin</span>
        </div>
        <a href="mng_booking.php" class="text-sm hover:text-blue-400 transition"><i class="fa-solid fa-arrow-left mr-2"></i>Back to Booking Management</a>
    </nav>

    <main class="max-w-2xl mx-auto px-4 py-12">
        <?= $msg ?>

        <div class="mb-10 text-center">
            <i class="fa-solid fa-calendar-pen text-6xl text-blue-600"></i>
            <h1 class="text-3xl font-bold text-gray-900 mt-4">Edit Booking #<?= $booking_id ?></h1>
            <p class="text-gray-500 mt-1">Update booked place, date, time, purpose, and remarks.</p>
        </div>

        <div class="bg-white p-8 rounded-3xl shadow-xl border border-gray-100">
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Facility</label>
                    <select name="facility_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition bg-white">
                        <?php while ($facility = mysqli_fetch_assoc($facilities)): ?>
                            <option value="<?= $facility['facility_id'] ?>" <?= (string)$booking['facility_id'] === (string)$facility['facility_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($facility['name']) ?> - <?= htmlspecialchars($facility['category']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Date</label>
                        <input type="date" name="booking_date" value="<?= htmlspecialchars($booking['date']) ?>" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Time</label>
                        <input type="time" name="booking_time" value="<?= htmlspecialchars(substr($booking['time'], 0, 5)) ?>" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Purpose</label>
                    <input type="text" name="purpose" value="<?= htmlspecialchars($booking['purpose'] ?? '') ?>" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Remarks</label>
                    <textarea name="remarks" rows="4" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"><?= htmlspecialchars($booking['remarks'] ?? '') ?></textarea>
                </div>

                <div class="pt-4 flex items-center gap-3">
                    <button type="submit" name="update_booking" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3.5 rounded-xl font-bold transition flex items-center justify-center gap-2 shadow-lg shadow-blue-100">
                        <i class="fa-solid fa-floppy-disk"></i> Save Booking
                    </button>
                    <a href="mng_booking.php" class="px-6 py-3.5 bg-gray-100 text-gray-600 rounded-xl font-bold hover:bg-gray-200 transition">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
