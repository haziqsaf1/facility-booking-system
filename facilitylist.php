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

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = mysqli_real_escape_string($dataconn, $search);
$normalized_search = preg_replace('/[\s-]+/', '', strtolower($search));
$normalized_search_sql = mysqli_real_escape_string($dataconn, $normalized_search);

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
$query .= " ORDER BY f.location ASC, f.name ASC";
$facilities = mysqli_query($dataconn, $query);

$complexes = array();
while ($facilities && $facility = mysqli_fetch_assoc($facilities)) {
    $complex_name = $facility['location'] ?: 'Unassigned Complex';
    if (!isset($complexes[$complex_name])) {
        $complexes[$complex_name] = array();
    }
    $complexes[$complex_name][] = $facility;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GMI Technology Complex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-[#f8fafc] font-sans antialiased">

    <nav class="bg-[#2563eb] text-white shadow-md px-8 py-3 flex justify-between items-center">
        <div class="flex items-center space-x-2">
            <?= spg_brand_logo($dataconn, 'h-9 w-9') ?>
            <span class="text-lg font-bold tracking-wide">GMI Technology Complex</span>
        </div>

        <div class="flex items-center space-x-6 text-sm font-medium">
            <span class="opacity-90"><i class="fa-solid fa-user mr-1.5"></i><?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="booker.php" class="opacity-80 hover:opacity-100 transition">Home</a>
            <a href="booking.php" class="bg-blue-700 px-4 py-1.5 rounded-lg hover:bg-blue-800 transition">Book Facility</a>
        </div>
    </nav>

    <section class="max-w-[1500px] mx-auto px-6 py-12">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 text-blue-600 rounded-3xl text-4xl mb-5">
                <i class="fa-solid fa-building"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-3">GMI Technology Complexes</h1>
            <p class="text-gray-500 text-lg">Explore facilities and learning environments available at GMI</p>
        </div>

        <form method="GET" class="max-w-5xl mx-auto bg-white rounded-3xl shadow-sm border border-gray-100 p-4 mb-10 flex flex-col md:flex-row gap-3">
            <div class="flex-1">
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Search by Lab / Facility Name</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Example: CAD Lab, Networking Lab, Classroom A"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-bold transition">
                    <i class="fa-solid fa-magnifying-glass mr-2"></i>Search
                </button>
                <a href="facilitylist.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-5 py-3 rounded-xl font-bold transition">Reset</a>
            </div>
        </form>

        <div class="space-y-10">
            <?php if (!empty($complexes)): ?>
                <?php foreach ($complexes as $complex_name => $items): ?>
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($complex_name) ?></h2>
                                <p class="text-gray-500 text-sm mt-1"><?= count($items) ?> facility record<?= count($items) > 1 ? 's' : '' ?></p>
                            </div>
                            <div class="h-12 w-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                                <i class="fa-solid fa-building text-xl"></i>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
                            <?php foreach ($items as $facility): ?>
                                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition">
                                    <?php if (!empty($facility['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($facility['image_url']) ?>" alt="<?= htmlspecialchars($facility['name']) ?>" class="w-full h-48 object-cover border-b border-gray-100">
                                    <?php else: ?>
                                        <div class="w-full h-48 bg-slate-100 text-slate-300 flex items-center justify-center border-b border-gray-100">
                                            <i class="fa-solid fa-image text-4xl"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="p-5">
                                    <button type="button" class="facility-toggle w-full flex items-start justify-between gap-3 text-left" data-target="facility-details-<?= $facility['facility_id'] ?>">
                                        <div>
                                            <h3 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($facility['name']) ?></h3>
                                            <span class="inline-block mt-1 px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-black uppercase"><?= htmlspecialchars($facility['category']) ?></span>
                                        </div>
                                        <span class="text-blue-600 text-sm font-bold flex items-center gap-2">
                                            Details <i class="fa-solid fa-chevron-down text-xs"></i>
                                        </span>
                                    </button>

                                    <div id="facility-details-<?= $facility['facility_id'] ?>" class="hidden mt-4">
                                        <p class="text-gray-600 text-sm mb-3"><?= nl2br(htmlspecialchars($facility['facility_description'] ?: 'No description has been added for this facility.')) ?></p>

                                        <?php if (!empty($facility['unique_details'])): ?>
                                            <div class="text-sm text-slate-700 mb-3">
                                                <p class="font-bold text-xs uppercase text-gray-400 mb-1">Unique Details</p>
                                                <p><?= nl2br(htmlspecialchars($facility['unique_details'])) ?></p>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($facility['peripherals'])): ?>
                                            <div class="text-sm text-slate-700 mb-3">
                                                <p class="font-bold text-xs uppercase text-gray-400 mb-1">Peripherals</p>
                                                <p><?= nl2br(htmlspecialchars($facility['peripherals'])) ?></p>
                                            </div>
                                        <?php endif; ?>

                                        <div class="bg-slate-50 rounded-2xl p-4 text-sm mb-3">
                                            <p class="font-bold text-xs uppercase text-gray-400 mb-1">Person In Charge</p>
                                            <p class="font-bold text-slate-800"><?= htmlspecialchars($facility['person_in_charge'] ?: 'Not assigned') ?></p>
                                            <p class="text-gray-500"><i class="fa-solid fa-phone mr-1.5"></i><?= htmlspecialchars($facility['pic_phone'] ?: 'No phone number') ?></p>
                                            <?php if (!empty($facility['pic_details'])): ?>
                                                <p class="text-gray-500 mt-1"><?= nl2br(htmlspecialchars($facility['pic_details'])) ?></p>
                                            <?php endif; ?>
                                        </div>

                                        <a href="booking.php?facility_id=<?= $facility['facility_id'] ?>" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-bold text-sm transition">
                                            <i class="fa-solid fa-calendar-plus"></i> Book This Facility
                                        </a>
                                    </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full bg-white rounded-3xl shadow-sm border border-gray-100 p-12 text-center text-gray-400 italic">
                    <i class="fa-solid fa-building-circle-xmark text-5xl opacity-30 mb-4"></i>
                    <p>No facilities found in the database.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
        document.querySelectorAll('.facility-toggle').forEach(button => {
            button.addEventListener('click', () => {
                const target = document.getElementById(button.dataset.target);
                if (target) {
                    target.classList.toggle('hidden');
                }
            });
        });
    </script>

</body>
</html>
