<?php

function spg_column_exists($conn, $table, $column) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function spg_table_exists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $result && mysqli_num_rows($result) > 0;
}

function spg_ensure_schema($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
        setting_value VARCHAR(255) NULL
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS facility_categories (
        category_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(50) NOT NULL UNIQUE
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS facility_descriptions (
        description_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        facility_id INT NOT NULL UNIQUE,
        description TEXT NULL,
        unique_details TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $default_categories = array('Classroom', 'Computer Lab', 'Lecture Hall', 'Meeting Room', 'Auditorium', 'Workshop', 'Laboratory', 'Mini Cinema');
    foreach ($default_categories as $category) {
        $category = mysqli_real_escape_string($conn, $category);
        mysqli_query($conn, "INSERT IGNORE INTO facility_categories (category_name) VALUES ('$category')");
    }

    mysqli_query($conn, "INSERT IGNORE INTO facility_categories (category_name)
        SELECT DISTINCT category FROM facilities WHERE category IS NOT NULL AND category <> ''");

    $facility_columns = array(
        'description' => "ALTER TABLE facilities ADD description TEXT NULL",
        'peripherals' => "ALTER TABLE facilities ADD peripherals TEXT NULL",
        'image_url' => "ALTER TABLE facilities ADD image_url VARCHAR(255) NULL",
        'person_in_charge' => "ALTER TABLE facilities ADD person_in_charge VARCHAR(100) NULL",
        'pic_phone' => "ALTER TABLE facilities ADD pic_phone VARCHAR(30) NULL",
        'pic_details' => "ALTER TABLE facilities ADD pic_details TEXT NULL"
    );

    foreach ($facility_columns as $column => $sql) {
        if (!spg_column_exists($conn, 'facilities', $column)) {
            mysqli_query($conn, $sql);
        }
    }

    mysqli_query($conn, "INSERT INTO facility_descriptions (facility_id, description)
        SELECT facility_id, description
        FROM facilities
        WHERE description IS NOT NULL AND description <> ''
        ON DUPLICATE KEY UPDATE
            description = IF(facility_descriptions.description IS NULL OR facility_descriptions.description = '', VALUES(description), facility_descriptions.description)");

    mysqli_query($conn, "ALTER TABLE facilities MODIFY name VARCHAR(100) NOT NULL");
    mysqli_query($conn, "ALTER TABLE facilities MODIFY location VARCHAR(100) NOT NULL");
    mysqli_query($conn, "ALTER TABLE facilities MODIFY category VARCHAR(50) NOT NULL");

    if (spg_column_exists($conn, 'users', 'phone_number')) {
        mysqli_query($conn, "ALTER TABLE users MODIFY phone_number VARCHAR(30) NOT NULL");
    }

    if (spg_column_exists($conn, 'booking', 'time')) {
        mysqli_query($conn, "ALTER TABLE booking MODIFY time TIME NOT NULL");
    }

    $booking_columns = array(
        'purpose' => "ALTER TABLE booking ADD purpose VARCHAR(255) NULL",
        'emergency_phone' => "ALTER TABLE booking ADD emergency_phone VARCHAR(30) NULL",
        'remarks' => "ALTER TABLE booking ADD remarks TEXT NULL"
    );

    foreach ($booking_columns as $column => $sql) {
        if (!spg_column_exists($conn, 'booking', $column)) {
            mysqli_query($conn, $sql);
        }
    }
}

function spg_facility_description_join($facility_alias = 'f', $description_alias = 'fd') {
    return "LEFT JOIN facility_descriptions $description_alias ON $description_alias.facility_id = $facility_alias.facility_id";
}

function spg_facility_description_select($facility_alias = 'f', $description_alias = 'fd') {
    return "COALESCE($description_alias.description, $facility_alias.description) AS facility_description, $description_alias.unique_details AS unique_details";
}

function spg_upsert_facility_description($conn, $facility_id, $description, $unique_details = '') {
    $facility_id = (int)$facility_id;
    $description = mysqli_real_escape_string($conn, $description);
    $unique_details = mysqli_real_escape_string($conn, $unique_details);

    return mysqli_query($conn, "INSERT INTO facility_descriptions (facility_id, description, unique_details)
        VALUES ('$facility_id', '$description', '$unique_details')
        ON DUPLICATE KEY UPDATE
            description = VALUES(description),
            unique_details = VALUES(unique_details)");
}

function spg_delete_facility_description($conn, $facility_id) {
    $facility_id = (int)$facility_id;
    return mysqli_query($conn, "DELETE FROM facility_descriptions WHERE facility_id = '$facility_id'");
}

function spg_handle_facility_image_upload($field_name, $current_path = '', &$error = '') {
    if (!isset($_FILES[$field_name]) || ($_FILES[$field_name]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $current_path;
    }

    if ($_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
        $error = 'Image upload failed. Please try again with a valid image file.';
        return $current_path;
    }

    if ($_FILES[$field_name]['size'] > 5 * 1024 * 1024) {
        $error = 'Image upload failed. Maximum image size is 5 MB.';
        return $current_path;
    }

    $original_name = $_FILES[$field_name]['name'];
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');

    if (!in_array($extension, $allowed_extensions, true) || !getimagesize($_FILES[$field_name]['tmp_name'])) {
        $error = 'Image upload failed. Only JPG, PNG, GIF, and WEBP images are allowed.';
        return $current_path;
    }

    $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'facilities';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true)) {
        $error = 'Image upload failed. The upload folder could not be created.';
        return $current_path;
    }

    $safe_name = 'facility_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $target_path = $upload_dir . DIRECTORY_SEPARATOR . $safe_name;

    if (!move_uploaded_file($_FILES[$field_name]['tmp_name'], $target_path)) {
        $error = 'Image upload failed. The uploaded file could not be saved.';
        return $current_path;
    }

    return 'uploads/facilities/' . $safe_name;
}

function spg_get_categories($conn) {
    spg_ensure_schema($conn);
    $categories = array();
    $result = mysqli_query($conn, "SELECT category_name FROM facility_categories ORDER BY category_name ASC");
    while ($result && $row = mysqli_fetch_assoc($result)) {
        $categories[] = $row['category_name'];
    }
    return $categories;
}

function spg_get_setting($conn, $key) {
    spg_ensure_schema($conn);
    $key = mysqli_real_escape_string($conn, $key);
    $result = mysqli_query($conn, "SELECT setting_value FROM system_settings WHERE setting_key = '$key' LIMIT 1");
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['setting_value'];
    }
    return '';
}

function spg_brand_logo($conn, $classes = 'h-9 w-9') {
    $logo_url = trim(spg_get_setting($conn, 'gmi_logo_url'));
    if ($logo_url !== '') {
        return "<img src='" . htmlspecialchars($logo_url) . "' alt='GMI Logo' class='" . htmlspecialchars($classes) . " object-contain bg-white rounded-md p-1'>";
    }

    return "<i class='fa-solid fa-building-shield text-xl'></i>";
}

function spg_user_id_column($conn) {
    return spg_column_exists($conn, 'users', 'user_id') ? 'user_id' : 'id';
}

?>
