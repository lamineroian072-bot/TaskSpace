<?php
// my_bookings.php - Tenant Personal Dashboard for Bookings & Online Payments
require_once __DIR__ . '/config.php';
requireAuth();

$pdo = getDB();
$user = currentUser();

$stmt = $pdo->prepare("
    SELECT b.*, r.name AS room_name, r.price AS room_price, r.type AS room_type, r.floor AS room_floor, r.image AS room_image,
           p.id AS payment_id, p.payment_method, p.reference_number, p.proof_image, p.status AS payment_status, p.amount AS paid_amount
    FROM `bookings` b
    JOIN `rooms` r ON b.room_id = r.id
    LEFT JOIN `payments` p ON p.id = (
        SELECT id FROM `payments` WHERE booking_id = b.id ORDER BY id DESC LIMIT 1
    )
    WHERE b.user_id = ? OR b.tenant_email = ?
    ORDER BY b.id DESC
");
$stmt->execute([$user['id'], $user['email']]);
$myBookings = $stmt->fetchAll();

$pageTitle = "My Bookings - Tenant Portal";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">My Room Reservations</h1>
            <p class="page-subtitle">Track your room application status, upload payment proof, and print official receipts.</p>
        </div>
        <a href="index.php" class="btn btn-primary">+ Reserve Another Room</a>
    </div>

    <?php if (count($myBookings) === 0): ?>
        <div class="form-card" style="text-align: center; padding: 48px; color: var(--text-muted);">
            <div style="font-size: 3rem; margin-bottom: 12px;">📑</div>
            <h3>No Active Bookings Found</h3>
            <p style="margin-top: 8px;">You haven't submitted any room booking requests yet.</p>
            <a href="index.php" class="btn btn-primary" style="margin-top: 16px;">Browse Available Rooms</a>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($myBookings as $b): ?>
                <?php 
                    $payStatus = $b['payment_status'] ?? 'Unpaid';
                    $checkInDate = !empty($b['check_in_date']) ? $b['check_in_date'] : ($b['move_in_date'] ?? null);
                    $defaultImg = 'images/single.svg';
                    $roomImg = !empty($b['room_image']) ? $b['room_image'] : $defaultImg;
                    if (str_starts_with($roomImg, 'images/') && str_ends_with($roomImg, '.png')) {
                        $roomImg = str_replace('.png', '.svg', $roomImg);
                    }
                ?>
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <span class="badge badge-<?= strtolower($b['status']); ?>">
                            Booking: <?= e(ucfirst($b['status'])); ?>
                        </span>
                        <span style="font-size:0.8rem; font-weight:700; color:var(--text-muted);">#ORD-<?= sprintf('%04d', $b['id']); ?></span>
                    </div>

                    <h3 class="card-title"><?= e($b['room_name']); ?></h3>
                    <div class="card-price"><?= formatMoney($b['room_price']); ?> <span style="font-size:0.8rem; font-weight:normal; color:var(--text-muted);">/ month</span></div>

                    <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px;">
                        <div>📅 Check-in Date: <strong><?= $checkInDate ? date('F j, Y', strtotime((string)$checkInDate)) : 'Pending'; ?></strong></div>
                        <div style="margin-top:4px;">💳 Payment Status: <span class="badge badge-<?= strtolower(str_replace(' ', '', $payStatus)); ?>"><?= e($payStatus); ?></span></div>
                        <?php if (!empty($b['reference_number'])): ?>
                            <div style="margin-top:4px;">🔢 Ref Number: <strong><?= e($b['reference_number']); ?></strong></div>
                        <?php endif; ?>
                    </div>

                    <div style="display:flex; gap:8px; margin-top:auto; padding-top:16px; border-top:1px solid var(--border);">
                        <a href="pay.php?booking_id=<?= $b['id']; ?>" class="btn btn-primary btn-sm" style="flex:1;">
                            💳 Pay Online
                        </a>
                        <a href="receipt.php?id=<?= $b['id']; ?>" target="_blank" class="btn btn-secondary btn-sm" style="flex:1;">
                            📄 Receipt
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
