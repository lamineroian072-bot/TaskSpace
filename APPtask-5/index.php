<?php
// index.php - Public & Tenant Room Catalog & Online Booking
require_once __DIR__ . '/config.php';

$pdo = getDB();
$user = currentUser();
$message = '';
$newBookingId = null;
$error = '';

// Handle Booking Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book') {
    $roomId      = (int)($_POST['room_id'] ?? 0);
    $tenantName  = trim($_POST['tenant_name'] ?? '');
    $tenantPhone = trim($_POST['tenant_phone'] ?? '');
    $tenantEmail = trim($_POST['tenant_email'] ?? '');
    $checkInDate = trim($_POST['check_in_date'] ?? '');
    $notes       = trim($_POST['notes'] ?? '');
    $userId      = $user['id'] ?? null;

    if ($roomId > 0 && $tenantName !== '' && $tenantPhone !== '' && $checkInDate !== '') {
        try {
            $stmt = $pdo->prepare("INSERT INTO `bookings` (`room_id`, `user_id`, `tenant_name`, `tenant_phone`, `tenant_email`, `check_in_date`, `move_in_date`, `notes`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([$roomId, $userId, $tenantName, $tenantPhone, $tenantEmail, $checkInDate, $checkInDate, $notes]);
            
            $newBookingId = $pdo->lastInsertId();
            $_SESSION['last_booking_id'] = $newBookingId;

            $message = "Your booking request for Room #{$roomId} has been submitted successfully!";
        } catch (Exception $e) {
            $error = "Error submitting booking: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields (Name, Phone, and Check-in Date).";
    }
}

// Fetch Rooms
$typeFilter = $_GET['type'] ?? '';
$searchQuery = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM `rooms` WHERE 1=1";
$params = [];

if ($typeFilter !== '') {
    $sql .= " AND LOWER(`type`) = ?";
    $params[] = strtolower($typeFilter);
}
if ($searchQuery !== '') {
    $sql .= " AND (`name` LIKE ? OR `floor` LIKE ? OR `description` LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}
$sql .= " ORDER BY `id` DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$pageTitle = "Browse Available Rooms - Boarding House System";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-success" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>✓ <?= e($message); ?></div>
            <?php if ($newBookingId): ?>
                <div style="display:flex; gap:8px;">
                    <a href="pay.php?booking_id=<?= $newBookingId; ?>" class="btn btn-primary btn-sm">
                        💳 Pay Online (GCash / Maya)
                    </a>
                    <a href="receipt.php?id=<?= $newBookingId; ?>" target="_blank" class="btn btn-secondary btn-sm">
                        🖨️ View Receipt
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= e($error); ?></div>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title">Available Boarding House Rooms</h1>
            <p class="page-subtitle">Find your perfect stay and submit a booking request online instantly.</p>
        </div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <form method="GET" style="display: flex; gap: 8px;">
                <input type="text" name="q" class="form-control" placeholder="Search room..." value="<?= e($searchQuery); ?>" style="width: 180px;">
                <select name="type" class="form-control" onchange="this.form.submit()" style="width: 160px;">
                    <option value="">All Room Types</option>
                    <option value="single" <?= strtolower($typeFilter) === 'single' ? 'selected' : ''; ?>>Single</option>
                    <option value="double" <?= strtolower($typeFilter) === 'double' ? 'selected' : ''; ?>>Double</option>
                    <option value="studio" <?= strtolower($typeFilter) === 'studio' ? 'selected' : ''; ?>>Studio</option>
                    <option value="dormitory" <?= strtolower($typeFilter) === 'dormitory' ? 'selected' : ''; ?>>Dormitory / Bedspace</option>
                </select>
            </form>
        </div>
    </div>

    <?php if (count($rooms) === 0): ?>
        <div class="form-card" style="text-align: center; padding: 48px; color: var(--text-muted);">
            <div style="font-size: 3rem; margin-bottom: 12px;">🔍</div>
            <h3>No Rooms Found</h3>
            <p style="margin-top: 8px;">No rooms match your filter criteria. Try searching for a different term.</p>
            <a href="index.php" class="btn btn-secondary" style="margin-top: 16px;">Clear Filters</a>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($rooms as $room): ?>
                <?php 
                    $isAvailable = strtolower($room['status']) === 'available';
                    $roomTypeLower = strtolower($room['type']);
                    $defaultImage = 'images/single.svg';
                    if (str_contains($roomTypeLower, 'double')) $defaultImage = 'images/double.svg';
                    else if (str_contains($roomTypeLower, 'studio')) $defaultImage = 'images/studio.svg';
                    else if (str_contains($roomTypeLower, 'dorm') || str_contains($roomTypeLower, 'bedspace')) $defaultImage = 'images/dormitory.svg';
                    
                    $imagePath = !empty($room['image']) ? $room['image'] : $defaultImage;
                    if (str_starts_with($imagePath, 'images/') && str_ends_with($imagePath, '.png')) {
                        $imagePath = str_replace('.png', '.svg', $imagePath);
                    }
                ?>
                <div class="card">
                    <!-- Room Photo -->
                    <div style="width: 100%; height: 190px; overflow: hidden; border-radius: var(--radius-md); margin-bottom: 16px; position: relative; background: #0f172a;">
                        <img src="<?= e($imagePath); ?>" alt="<?= e($room['name']); ?>" style="width:100%; height:100%; object-fit:cover; transition:transform 0.4s ease;" onerror="this.onerror=null; this.src='<?= $defaultImage; ?>';">
                    </div>

                    <div class="card-header">
                        <span class="badge badge-<?= strtolower($room['status']); ?>">
                            <?= e(ucfirst($room['status'])); ?>
                        </span>
                        <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;"><?= e($room['floor']); ?></span>
                    </div>
                    <h3 class="card-title"><?= e($room['name']); ?></h3>
                    <div class="card-price"><?= formatMoney($room['price']); ?> <span style="font-size:0.8rem; font-weight:normal; color:var(--text-muted);">/ month</span></div>
                    <p class="card-desc"><?= e($room['description']); ?></p>
                    <div class="card-meta">
                        <span>👤 Capacity: <?= e($room['capacity']); ?> Person(s)</span>
                        <span>🏷️ Type: <?= e(ucfirst($room['type'])); ?></span>
                    </div>

                    <?php if ($isAvailable): ?>
                        <button class="btn btn-primary" onclick="openBookingModal(<?= $room['id']; ?>, '<?= e(addslashes($room['name'])); ?>')">
                            Book This Room
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
                            <?= e(ucfirst($room['status'])); ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Booking Modal Form -->
<div id="bookingModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:200; backdrop-filter:blur(6px); justify-content:center; align-items:center; padding: 20px;">
    <div class="form-card" style="width: 100%; max-width: 480px; position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 id="modalRoomTitle" style="font-size: 1.25rem; font-weight: 800;">Reserve Room</h3>
            <span onclick="closeBookingModal()" style="cursor:pointer; font-size:1.6rem; color:var(--text-muted);">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="book">
            <input type="hidden" name="room_id" id="modalRoomId">

            <div class="form-group">
                <label class="form-label">Tenant Full Name *</label>
                <input type="text" name="tenant_name" class="form-control" required placeholder="e.g. Maria Santos" value="<?= e($user['name'] ?? ''); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" name="tenant_phone" class="form-control" required placeholder="09123456789" value="<?= e($user['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="tenant_email" class="form-control" placeholder="maria@example.com" value="<?= e($user['email'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Target Check-in Date *</label>
                <input type="date" name="check_in_date" class="form-control" required min="<?= date('Y-m-d'); ?>" value="<?= date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Special Notes / Requests</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Request lower bed, early move in..."></textarea>
            </div>

            <div style="display:flex; gap:12px; justify-content:flex-end; margin-top: 24px;">
                <button type="button" class="btn btn-secondary" onclick="closeBookingModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Booking Request</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>