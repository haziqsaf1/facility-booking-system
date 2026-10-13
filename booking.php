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

$message = "";
$user_id = $_SESSION['user_id'];
$selected_facility_id = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;

// Handle form submission
if (isset($_POST['submit_booking'])) {
    $facility_id = mysqli_real_escape_string($dataconn, $_POST['facility_id']);
    $booking_date = mysqli_real_escape_string($dataconn, $_POST['booking_date']);
    $booking_time = mysqli_real_escape_string($dataconn, $_POST['booking_time']);
    $purpose = mysqli_real_escape_string($dataconn, $_POST['purpose']);
    $remarks = mysqli_real_escape_string($dataconn, $_POST['remarks']);
    $facility_info_res = mysqli_query($dataconn, "SELECT pic_phone FROM facilities WHERE facility_id = '$facility_id' LIMIT 1");
    $facility_info = $facility_info_res ? mysqli_fetch_assoc($facility_info_res) : array();
    $emergency_phone = mysqli_real_escape_string($dataconn, $facility_info['pic_phone'] ?? '');

    // Check if the facility is already booked for that date and time
    $check_query = "SELECT * FROM booking
                    WHERE facility_id = '$facility_id'
                    AND date = '$booking_date'
                    AND time = '$booking_time'";

    $check_res = mysqli_query($dataconn, $check_query);

    if ($emergency_phone === '') {
        $message = "
        <div class='bg-red-100 text-red-600 p-4 rounded-xl mb-6'>
            This facility does not have a person in charge phone number yet. Please contact admin before booking.
        </div>";
    } else if (mysqli_num_rows($check_res) > 0) {
        $message = "
        <div class='bg-red-100 text-red-600 p-4 rounded-xl mb-6'>
            This facility is already booked for the selected date and time.
        </div>";
    } else {
        $insert_query = "INSERT INTO booking
                        (user_id, facility_id, date, time, purpose, emergency_phone, remarks)
                        VALUES
                        ('$user_id', '$facility_id', '$booking_date', '$booking_time', '$purpose', '$emergency_phone', '$remarks')";

        if (mysqli_query($dataconn, $insert_query)) {
            $message = "
            <div class='bg-green-100 text-green-600 p-4 rounded-xl mb-6'>
                Booking successful! View it in 'My Bookings'.
            </div>";
        } else {
            $message = "
            <div class='bg-red-100 text-red-600 p-4 rounded-xl mb-6'>
                Error: " . mysqli_error($dataconn) . "
            </div>";
        }
    }
}

// Fetch facilities for dropdown
$fac_query = "SELECT f.*, " . spg_facility_description_select('f', 'fd') . "
    FROM facilities f
    " . spg_facility_description_join('f', 'fd') . "
    ORDER BY f.location ASC, f.name ASC";
$fac_res = mysqli_query($dataconn, $fac_query);
$facilities_by_location = array();

while ($fac_res && $fac = mysqli_fetch_assoc($fac_res)) {
    $location = $fac['location'] ?: 'Unassigned Complex';
    if (!isset($facilities_by_location[$location])) {
        $facilities_by_location[$location] = array();
    }
    $facilities_by_location[$location][] = $fac;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Facility - GMI</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-[#f8fafc] font-sans antialiased">

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

            <a href="booker.php"
            class="opacity-80 hover:opacity-100 transition">
                Dashboard
            </a>

            <a href="login.php?action=logout"
            class="bg-blue-700 px-4 py-1.5 rounded-lg hover:bg-blue-800 transition">
                Logout
            </a>

        </div>
    </nav>

    <!-- MAIN -->
    <main class="max-w-3xl mx-auto px-4 py-12">

        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">

            <!-- HEADER -->
            <div class="flex items-center space-x-4 mb-8">

                <div class="p-3 bg-green-50 text-green-600 rounded-2xl text-2xl">
                    <i class="fa-solid fa-calendar-plus"></i>
                </div>

                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Book a Facility
                    </h1>

                    <p class="text-sm text-gray-500">
                        Reserve a room for your academic activities
                    </p>
                </div>

            </div>

            <!-- MESSAGE -->
            <?= $message ?>

            <!-- FORM -->
            <form method="POST" class="space-y-6">

                <!-- FACILITY -->
                <div>

                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        Choose Facility
                    </label>

                    <select name="facility_id" id="facility_id" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50">

                        <option value="">
                            -- Select a Facility --
                        </option>

                        <?php foreach ($facilities_by_location as $location => $facilities): ?>
                            <optgroup label="<?= htmlspecialchars($location) ?>">
                                <?php foreach ($facilities as $fac): ?>
                                    <option value="<?= $fac['facility_id'] ?>"
                                        data-location="<?= htmlspecialchars($fac['location']) ?>"
                                        data-category="<?= htmlspecialchars($fac['category']) ?>"
                                        data-description="<?= htmlspecialchars($fac['facility_description'] ?? '') ?>"
                                        data-unique-details="<?= htmlspecialchars($fac['unique_details'] ?? '') ?>"
                                        data-peripherals="<?= htmlspecialchars($fac['peripherals'] ?? '') ?>"
                                        data-pic="<?= htmlspecialchars($fac['person_in_charge'] ?? '') ?>"
                                        data-pic-phone="<?= htmlspecialchars($fac['pic_phone'] ?? '') ?>"
                                        <?= $selected_facility_id === (int)$fac['facility_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($fac['name']) ?> - <?= htmlspecialchars($fac['category']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>

                    </select>

                </div>

                <div id="facility-details" class="hidden bg-slate-50 rounded-2xl p-5 border border-slate-100 text-sm text-slate-700">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase">Complex / Building</p>
                            <p id="detail-location" class="font-bold text-slate-900"></p>
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase">Category</p>
                            <p id="detail-category" class="font-bold text-slate-900"></p>
                        </div>
                    </div>
                    <p class="text-xs font-black text-slate-400 uppercase mb-1">Facility Description</p>
                    <p id="detail-description" class="mb-4"></p>
                    <p class="text-xs font-black text-slate-400 uppercase mb-1">Unique Details</p>
                    <p id="detail-unique-details" class="mb-4"></p>
                    <p class="text-xs font-black text-slate-400 uppercase mb-1">Peripherals</p>
                    <p id="detail-peripherals" class="mb-4"></p>
                    <p class="text-xs font-black text-slate-400 uppercase mb-1">Person In Charge</p>
                    <p><span id="detail-pic" class="font-bold"></span> <span id="detail-pic-phone" class="text-gray-500"></span></p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        Booking Purpose
                    </label>
                    <input type="text" name="purpose" required placeholder="Example: Class session, workshop, briefing"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50">
                </div>

                <div class="bg-blue-50 rounded-2xl p-5 border border-blue-100 text-sm">
                    <p class="text-xs font-black text-blue-400 uppercase mb-1">Emergency Contact For This Facility</p>
                    <p class="font-bold text-slate-900" id="emergency-pic">Select a facility to view the person in charge.</p>
                    <p class="text-gray-600" id="emergency-phone"></p>
                </div>

                <!-- DATE -->
                <div>

                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        Select Date
                    </label>

                    <input
                        type="date"
                        name="booking_date"
                        required
                        min="<?= date('Y-m-d') ?>"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50"
                    >

                </div>

                <!-- TIME -->
                <div>

                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        Select Time
                    </label>

                    <input
                        type="time"
                        name="booking_time"
                        required
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50"
                    >

                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        Remarks
                    </label>
                    <textarea name="remarks" rows="3" placeholder="Additional setup or usage details"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50"></textarea>
                </div>

                <!-- BUTTON -->
                <div class="pt-4">

                    <button
                        type="submit"
                        name="submit_booking"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl font-bold transition shadow-lg shadow-blue-100"
                    >
                        Confirm Reservation
                    </button>

                    <a href="booker.php"
                    class="block text-center mt-4 text-sm text-gray-400 hover:text-gray-600">

                        Cancel and go back

                    </a>

                </div>

            </form>

        </div>

    </main>

    <script>
        const facilitySelect = document.getElementById('facility_id');
        const details = document.getElementById('facility-details');

        function showFacilityDetails() {
            const selected = facilitySelect.options[facilitySelect.selectedIndex];
            if (!selected || !selected.value) {
                details.classList.add('hidden');
                return;
            }

            document.getElementById('detail-location').textContent = selected.dataset.location || 'Not specified';
            document.getElementById('detail-category').textContent = selected.dataset.category || 'Not specified';
            document.getElementById('detail-description').textContent = selected.dataset.description || 'No description has been added for this facility.';
            document.getElementById('detail-unique-details').textContent = selected.dataset.uniqueDetails || 'No unique details listed.';
            document.getElementById('detail-peripherals').textContent = selected.dataset.peripherals || 'No peripherals listed.';
            document.getElementById('detail-pic').textContent = selected.dataset.pic || 'Not assigned';
            document.getElementById('detail-pic-phone').textContent = selected.dataset.picPhone ? '(' + selected.dataset.picPhone + ')' : '';
            document.getElementById('emergency-pic').textContent = selected.dataset.pic || 'Person in charge not assigned';
            document.getElementById('emergency-phone').textContent = selected.dataset.picPhone || 'No emergency phone number has been added for this facility.';
            details.classList.remove('hidden');
        }

        facilitySelect.addEventListener('change', showFacilityDetails);
        showFacilityDetails();
    </script>

</body>
</html>
