<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$dataconn = require 'connectdb.php';
require_once 'system_helpers.php';
// Force correct database
mysqli_select_db($dataconn, 'cookie');
spg_ensure_schema($dataconn);

$msg = "";
$categories = spg_get_categories($dataconn);

if (($_SERVER["REQUEST_METHOD"] ?? '') == "POST" && isset($_POST['submit'])) {
    // Collect and sanitize inputs using exact column names
    $name = mysqli_real_escape_string($dataconn, $_POST['name']);
    $location = mysqli_real_escape_string($dataconn, $_POST['location']);
    $category = mysqli_real_escape_string($dataconn, $_POST['category']);
    $description_raw = trim($_POST['description']);
    $unique_details_raw = trim($_POST['unique_details'] ?? '');
    $description = mysqli_real_escape_string($dataconn, $description_raw);
    $peripherals = mysqli_real_escape_string($dataconn, $_POST['peripherals']);
    $upload_error = '';
    $image_path = spg_handle_facility_image_upload('facility_image', trim($_POST['image_url']), $upload_error);
    $image_url = mysqli_real_escape_string($dataconn, $image_path);
    $person_in_charge = mysqli_real_escape_string($dataconn, $_POST['person_in_charge']);
    $pic_phone = mysqli_real_escape_string($dataconn, $_POST['pic_phone']);
    $pic_details = mysqli_real_escape_string($dataconn, $_POST['pic_details']);

    if ($upload_error !== '') {
        $msg = "<div class='bg-red-50 text-red-600 p-4 rounded-xl mb-6 border border-red-100'><i class='fa-solid fa-triangle-exclamation mr-2'></i> " . htmlspecialchars($upload_error) . "</div>";
    } else {
        $sql = "INSERT INTO facilities (name, location, category, description, peripherals, image_url, person_in_charge, pic_phone, pic_details)
            VALUES ('$name', '$location', '$category', '$description', '$peripherals', '$image_url', '$person_in_charge', '$pic_phone', '$pic_details')";

        if (mysqli_query($dataconn, $sql)) {
            spg_upsert_facility_description($dataconn, mysqli_insert_id($dataconn), $description_raw, $unique_details_raw);
            header("Location: mng_facility.php?status=added");
            exit();
        } else {
            $msg = "<div class='bg-red-50 text-red-600 p-4 rounded-xl mb-6 border border-red-100'><i class='fa-solid fa-triangle-exclamation mr-2'></i> Error adding facility: " . mysqli_error($dataconn) . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Facility - GMI Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-800">

    <nav class="bg-slate-800 text-white shadow-md px-8 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <?= spg_brand_logo($dataconn, 'h-9 w-9') ?>
            <span class="text-xl font-bold">GMI Admin</span>
        </div>
        <a href="mng_facility.php" class="text-sm hover:text-blue-400 transition"><i class="fa-solid fa-arrow-left mr-2"></i>Back to Management</a>
    </nav>

    <main class="max-w-2xl mx-auto px-4 py-12">
        <?php echo $msg; ?>
        
        <div class="mb-10 text-center">
            <i class="fa-solid fa-building-circle-plus text-6xl text-blue-600"></i>
            <h1 class="text-3xl font-bold text-gray-900 mt-4">Add New Facility</h1>
            <p class="text-gray-500 mt-1">Enter details for a new classroom, lab, or campus complex.</p>
        </div>

        <div class="bg-white p-8 rounded-3xl shadow-xl border border-gray-100">
            <form action="add_facility.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <div>
                    <label for="name" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Facility Name (name)</label>
                    <input type="text" id="name" name="name" placeholder="e.g. Lab C-04" required 
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

                <div>
                    <label for="location" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Complex / Building Name</label>
                    <input type="text" id="location" name="location" placeholder="e.g. Technology Complex, Block B" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

                <div>
                    <label for="category" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Facility Type (category)</label>
                    <select id="category" name="category" required 
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition bg-white cursor-pointer">
                        <option value="" disabled selected>Select a category...</option>
                        <?php foreach ($categories as $category_name): ?>
                            <option value="<?= htmlspecialchars($category_name) ?>"><?= htmlspecialchars($category_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Facility Description</label>
                    <textarea id="description" name="description" rows="4" required placeholder="Describe the lab, room capacity, usage, and unique details."
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"></textarea>
                </div>

                <div>
                    <label for="unique_details" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Unique Details</label>
                    <textarea id="unique_details" name="unique_details" rows="3" placeholder="Example: Specialized simulator, collaborative layout, dedicated training setup"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"></textarea>
                </div>

                <div>
                    <label for="peripherals" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Peripherals / Equipment</label>
                    <textarea id="peripherals" name="peripherals" rows="3" placeholder="Example: Projector, smart board, 30 PCs, speaker system"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"></textarea>
                </div>

                <div>
                    <label for="facility_image" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Upload Facility Image</label>
                    <input type="file" id="facility_image" name="facility_image" accept="image/*"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition bg-white">
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF, or WEBP. Maximum 5 MB.</p>
                </div>

                <div>
                    <label for="image_url" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Existing Image Path / URL</label>
                    <input type="text" id="image_url" name="image_url" placeholder="Example: KT/classroom.jpg"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="person_in_charge" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Person In Charge</label>
                        <input type="text" id="person_in_charge" name="person_in_charge" placeholder="PIC name" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>

                    <div>
                        <label for="pic_phone" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">PIC Phone Number</label>
                        <input type="text" id="pic_phone" name="pic_phone" placeholder="Emergency contact number" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>
                </div>

                <div>
                    <label for="pic_details" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Other PIC Details</label>
                    <textarea id="pic_details" name="pic_details" rows="3" placeholder="Office, department, backup contact, or notes"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"></textarea>
                </div>

                <div class="pt-4 flex items-center gap-3">
                    <button type="submit" name="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3.5 rounded-xl font-bold transition flex items-center justify-center gap-2 shadow-lg shadow-blue-100">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Save Facility to System
                    </button>
                    <a href="mng_facility.php" class="px-6 py-3.5 bg-gray-100 text-gray-600 rounded-xl font-bold hover:bg-gray-200 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
