<?php
// setup.php - Automatic Database Table Creation & Schema Migration
require_once __DIR__ . '/config.php';

$pdo = getDB();
$message = '';
$error = '';

try {
    // 1. Create uploads and images directories
    $uploadDir = __DIR__ . '/uploads/payments';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $imgDir = __DIR__ . '/images';
    if (!is_dir($imgDir)) {
        mkdir($imgDir, 0777, true);
    }

    // Generate SVG image placeholders if missing
    $placeholders = [
        'single.svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400"><rect width="600" height="400" fill="#1e293b"/><path d="M100 280 L500 280 L500 320 L100 320 Z" fill="#3b82f6"/><rect x="140" y="180" width="320" height="100" rx="10" fill="#38bdf8"/><rect x="160" y="200" width="100" height="40" rx="5" fill="#f8fafc"/><text x="300" y="120" font-family="sans-serif" font-size="28" font-weight="bold" fill="#f8fafc" text-anchor="middle">Cozy Single Room</text><text x="300" y="360" font-family="sans-serif" font-size="18" fill="#94a3b8" text-anchor="middle">Boarding House Accommodation</text></svg>',
        'double.svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400"><rect width="600" height="400" fill="#0f172a"/><path d="M80 280 L520 280 L520 320 L80 320 Z" fill="#8b5cf6"/><rect x="110" y="170" width="170" height="110" rx="10" fill="#a78bfa"/><rect x="320" y="170" width="170" height="110" rx="10" fill="#a78bfa"/><rect x="130" y="190" width="70" height="35" rx="5" fill="#f8fafc"/><rect x="340" y="190" width="70" height="35" rx="5" fill="#f8fafc"/><text x="300" y="110" font-family="sans-serif" font-size="28" font-weight="bold" fill="#f8fafc" text-anchor="middle">Deluxe Double Room</text><text x="300" y="360" font-family="sans-serif" font-size="18" fill="#94a3b8" text-anchor="middle">2-Bed Arrangement</text></svg>',
        'studio.svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400"><rect width="600" height="400" fill="#1e1b4b"/><rect x="80" y="150" width="440" height="140" rx="12" fill="#6366f1"/><rect x="100" y="170" width="120" height="50" rx="6" fill="#f8fafc"/><circle cx="450" cy="180" r="30" fill="#fbbf24"/><text x="300" y="100" font-family="sans-serif" font-size="28" font-weight="bold" fill="#f8fafc" text-anchor="middle">Executive Studio Room</text><text x="300" y="350" font-family="sans-serif" font-size="18" fill="#c7d2fe" text-anchor="middle">Private Bathroom &amp; Kitchenette</text></svg>',
        'dormitory.svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400"><rect width="600" height="400" fill="#064e3b"/><rect x="80" y="120" width="200" height="160" rx="8" fill="#10b981"/><rect x="320" y="120" width="200" height="160" rx="8" fill="#10b981"/><rect x="100" y="140" width="160" height="40" rx="4" fill="#f8fafc"/><rect x="340" y="140" width="160" height="40" rx="4" fill="#f8fafc"/><text x="300" y="80" font-family="sans-serif" font-size="28" font-weight="bold" fill="#f8fafc" text-anchor="middle">Shared Dormitory Bedspace</text><text x="300" y="350" font-family="sans-serif" font-size="18" fill="#a7f3d0" text-anchor="middle">4-Person Aircon Shared Room</text></svg>',
    ];

    foreach ($placeholders as $fileName => $svg) {
        $filePath = $imgDir . '/' . $fileName;
        if (!file_exists($filePath)) {
            file_put_contents($filePath, $svg);
        }
    }

    // 2. Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('admin', 'tenant') NOT NULL DEFAULT 'tenant',
        `phone` VARCHAR(30) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $defaultUsers = [
        [
            'name' => 'Admin User',
            'email' => 'admin@boardinghouse.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'phone' => '09100000001',
        ],
        [
            'name' => 'Tenant User',
            'email' => 'tenant@boardinghouse.com',
            'password' => password_hash('tenant123', PASSWORD_DEFAULT),
            'role' => 'tenant',
            'phone' => '09100000002',
        ],
    ];

    foreach ($defaultUsers as $user) {
        $check = $pdo->prepare("SELECT `id` FROM `users` WHERE `email` = ?");
        $check->execute([$user['email']]);
        if (!$check->fetch()) {
            $insert = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`, `phone`) VALUES (?, ?, ?, ?, ?)");
            $insert->execute([$user['name'], $user['email'], $user['password'], $user['role'], $user['phone']]);
        }
    }

    // 3. Create rooms table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `rooms` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `type` VARCHAR(50) NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `capacity` INT NOT NULL DEFAULT 1,
        `floor` VARCHAR(20) DEFAULT '1st Floor',
        `status` ENUM('Available', 'Occupied', 'Maintenance') DEFAULT 'Available',
        `image` VARCHAR(255) NULL,
        `description` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Check if `image` column exists in `rooms` table
    $roomCols = array_column($pdo->query("SHOW COLUMNS FROM `rooms`")->fetchAll(), 'Field');
    if (!in_array('image', $roomCols)) {
        $pdo->exec("ALTER TABLE `rooms` ADD `image` VARCHAR(255) NULL AFTER `status`");
    }

    // Seed sample rooms if rooms table is empty
    $roomCount = (int)$pdo->query("SELECT COUNT(*) FROM `rooms`")->fetchColumn();
    if ($roomCount === 0) {
        $defaultRooms = [
            ['Room 101 - Cozy Single', 'Single', 3500.00, 1, '1st Floor', 'Available', 'images/single.svg', 'Cozy single room with study desk, private fan, and high-speed Wi-Fi.'],
            ['Room 102 - Deluxe Double', 'Double', 5500.00, 2, '1st Floor', 'Available', 'images/double.svg', 'Spacious room suitable for 2 tenants with twin beds and built-in closets.'],
            ['Room 201 - Executive Studio', 'Studio', 8000.00, 2, '2nd Floor', 'Available', 'images/studio.svg', 'Modern studio room with private bathroom, kitchenette, and air conditioner.'],
            ['Room 202 - Shared Dormitory', 'Dormitory', 2500.00, 4, '2nd Floor', 'Available', 'images/dormitory.svg', 'Affordable bedspace in a shared 4-person aircon room with individual lockers.'],
        ];
        $insertRoom = $pdo->prepare("INSERT INTO `rooms` (`name`, `type`, `price`, `capacity`, `floor`, `status`, `image`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($defaultRooms as $r) {
            $insertRoom->execute($r);
        }
    }

    // Update room images fallback and migrate any legacy .png image references to .svg
    $pdo->exec("UPDATE `rooms` SET `image` = REPLACE(`image`, '.png', '.svg') WHERE `image` LIKE 'images/%.png'");
    $pdo->exec("UPDATE `rooms` SET `image` = 'images/single.svg' WHERE `image` IS NULL OR `image` = '' OR LOWER(`type`) = 'single'");
    $pdo->exec("UPDATE `rooms` SET `image` = 'images/double.svg' WHERE LOWER(`type`) = 'double'");
    $pdo->exec("UPDATE `rooms` SET `image` = 'images/studio.svg' WHERE LOWER(`type`) = 'studio'");
    $pdo->exec("UPDATE `rooms` SET `image` = 'images/dormitory.svg' WHERE LOWER(`type`) IN ('dormitory', 'bedspace')");

    // 4. Create bookings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `bookings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `room_id` INT NOT NULL,
        `user_id` INT NULL,
        `tenant_name` VARCHAR(100) NOT NULL,
        `tenant_phone` VARCHAR(30) NOT NULL,
        `tenant_email` VARCHAR(100) NOT NULL,
        `check_in_date` DATE NOT NULL,
        `move_in_date` DATE NULL,
        `notes` TEXT,
        `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_bookings_room` (`room_id`),
        INDEX `idx_bookings_user` (`user_id`),
        INDEX `idx_bookings_status` (`status`),
        CONSTRAINT `fk_bookings_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Ensure `move_in_date` exists in `bookings`
    $bookingCols = array_column($pdo->query("SHOW COLUMNS FROM `bookings`")->fetchAll(), 'Field');
    if (!in_array('move_in_date', $bookingCols)) {
        $pdo->exec("ALTER TABLE `bookings` ADD `move_in_date` DATE NULL AFTER `check_in_date`");
        $pdo->exec("UPDATE `bookings` SET `move_in_date` = `check_in_date` WHERE `move_in_date` IS NULL");
    }

    // 5. Create payments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `payments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `booking_id` INT NOT NULL,
        `payment_method` VARCHAR(50) NOT NULL,
        `reference_number` VARCHAR(100) NULL,
        `proof_image` VARCHAR(255) NULL,
        `amount` DECIMAL(10,2) NOT NULL,
        `status` ENUM('Pending Verification', 'Verified', 'Rejected') DEFAULT 'Pending Verification',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_payments_booking` (`booking_id`),
        INDEX `idx_payments_status` (`status`),
        CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $payCols = array_column($pdo->query("SHOW COLUMNS FROM `payments`")->fetchAll(), 'Field');
    if (!in_array('payment_method', $payCols)) {
        $pdo->exec("ALTER TABLE `payments` ADD `payment_method` VARCHAR(50) NULL AFTER `booking_id`");
    }
    if (!in_array('proof_image', $payCols)) {
        $pdo->exec("ALTER TABLE `payments` ADD `proof_image` VARCHAR(255) NULL AFTER `reference_number`");
    }

    $message = "Database schema updated and database tables synchronized successfully!";
} catch (Exception $e) {
    $error = "Database setup failed: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Boarding House System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">
    <div class="glass-card" style="max-width: 540px; margin: 60px auto; text-align: center;">
        <div style="font-size: 3rem; margin-bottom: 12px;">🏠</div>
        <h2 style="margin-bottom: 8px;">Database Auto-Migration</h2>
        <p style="color: var(--text-muted); margin-bottom: 24px;">Boarding House System Database Setup &amp; Seeding</p>

        <?php if ($message): ?>
            <div class="alert alert-success" style="text-align: left;">
                <strong>✓ Success!</strong> <?= e($message); ?>
            </div>
            <div style="background: rgba(255,255,255,0.05); padding: 16px; border-radius: 12px; margin: 20px 0; text-align: left; font-size: 0.9rem;">
                <div style="font-weight: 700; color: var(--primary); margin-bottom: 8px;">🔑 Seeded Default Accounts:</div>
                <div><strong>Admin:</strong> admin@boardinghouse.com | <strong>Pass:</strong> admin123</div>
                <div><strong>Tenant:</strong> tenant@boardinghouse.com | <strong>Pass:</strong> tenant123</div>
            </div>
            <div style="display: flex; gap: 12px; justify-content: center; margin-top: 24px;">
                <a href="login.php" class="btn btn-primary">Go to Login</a>
                <a href="index.php" class="btn btn-secondary">Browse Rooms</a>
            </div>
        <?php else: ?>
            <div class="alert alert-danger" style="text-align: left;">
                <strong>❌ Error!</strong> <?= e($error); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
