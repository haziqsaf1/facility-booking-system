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

$facility_id = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;
$msg = "";
$categories = spg_get_categories($dataconn);

if ($facility_id <= 0) {
    header("Location: mng_facility.php");
    exit();
}

if (($_SERVER["REQUEST_METHOD"] ?? '') == "POST" && isset($_POST['update'])) {
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
        $sql = "UPDATE facilities SET
                name = '$name',
                location = '$location',
                category = '$category',
                description = '$description',
                peripherals = '$peripherals',
                image_url = '$image_url',
                person_in_charge = '$person_in_charge',
                pic_phone = '$pic_phone',
                pic_details = '$pic_details'
            WHERE facility_id = '$facility_id'";

        if (mysqli_query($dataconn, $sql) && spg_upsert_facility_description($dataconn, $facility_id, $description_raw, $unique_details_raw)) {
            header("Location: mng_facility.php?status=updated");
            exit();
        }

        $msg = "<div class='bg-red-50 text-red-600 p-4 rounded-xl mb-6 border border-red-100'><i class='fa-solid fa-triangle-exclamation mr-2'></i> Error updating facility: " . mysqli_error($dataconn) . "</div>";
    }
}

$facility_res = mysqli_query($dataconn, "SELECT f.*, " . spg_facility_description_select('f', 'fd') . "
    FROM facilities f
    " . spg_facility_description_join('f', 'fd') . "
    WHERE f.facility_id = '$facility_id'
    LIMIT 1");
$facility = $facility_res ? mysqli_fetch_assoc($facility_res) : null;

if (!$facility) {
    header("Location: mng_facility.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Facility - GMI Admin</title>
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
        <?= $msg ?>

        <div class="mb-10 text-center">
            <i class="fa-solid fa-pen-to-square text-6xl text-blue-600"></i>
            <h1 class="text-3xl font-bold text-gray-900 mt-4">Edit Facility</h1>
            <p class="text-gray-500 mt-1">Update facility, image, equipment, and person in charge details.</p>
        </div>

        <div class="bg-white p-8 rounded-3xl shadow-xl border border-gray-100">
            <form action="edit_facility.php?facility_id=<?= $facility_id ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="name" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Facility Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($facility['name']) ?>" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

                <div>
                    <label for="location" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Complex / Building Name</label>
                    <input type="text" id="location" name="location" value="<?= htmlspecialchars($facility['location']) ?>" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

                <div>
                    <label for="category" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Facility Type</label>
                    <select id="category" name="category" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition bg-white cursor-pointer">
                        <?php foreach ($categories as $category_name): ?>
                            <option value="<?= htmlspecialchars($category_name) ?>" <?= $facility['category'] === $category_name ? 'selected' : '' ?>><?= htmlspecialchars($category_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Facility Description</label>
                    <textarea id="description" name="description" rows="4" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"><?= htmlspecialchars($facility['facility_description'] ?? '') ?></textarea>
                </div>

                <div>
                    <label for="unique_details" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Unique Details</label>
                    <textarea id="unique_details" name="unique_details" rows="3"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"><?= htmlspecialchars($facility['unique_details'] ?? '') ?></textarea>
                </div>

                <div>
                    <label for="peripherals" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Peripherals / Equipment</label>
                    <textarea id="peripherals" name="peripherals" rows="3"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"><?= htmlspecialchars($facility['peripherals'] ?? '') ?></textarea>
                </div>

                <div>
                    <label for="facility_image" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Upload New Facility Image</label>
                    <input type="file" id="facility_image" name="facility_image" accept="image/*"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition bg-white">
                    <p class="text-xs text-gray-400 mt-1">Leave empty to keep the current image. JPG, PNG, GIF, or WEBP. Maximum 5 MB.</p>
                </div>

                <?php if (!empty($facility['image_url'])): ?>
                    <div>
                        <p class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Current Image</p>
                        <img src="<?= htmlspecialchars($facility['image_url']) ?>" alt="<?= htmlspecialchars($facility['name']) ?>" class="w-full h-48 object-cover rounded-2xl border border-gray-100">
                    </div>
                <?php endif; ?>

                <div>
                    <label for="image_url" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Existing Image Path / URL</label>
                    <input type="text" id="image_url" name="image_url" value="<?= htmlspecialchars($facility['image_url'] ?? '') ?>"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="person_in_charge" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Person In Charge</label>
                        <input type="text" id="person_in_charge" name="person_in_charge" value="<?= htmlspecialchars($facility['person_in_charge'] ?? '') ?>" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>

                    <div>
                        <label for="pic_phone" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">PIC Phone Number</label>
                        <input type="text" id="pic_phone" name="pic_phone" value="<?= htmlspecialchars($facility['pic_phone'] ?? '') ?>" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>
                </div>

                <div>
                    <label for="pic_details" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Other PIC Details</label>
                    <textarea id="pic_details" name="pic_details" rows="3"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"><?= htmlspecialchars($facility['pic_details'] ?? '') ?></textarea>
                </div>

                <div class="pt-4 flex items-center gap-3">
                    <button type="submit" name="update" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3.5 rounded-xl font-bold transition flex items-center justify-center gap-2 shadow-lg shadow-blue-100">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
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
