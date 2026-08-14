<?php
// bookings.php - Admin Booking Requests & Online Payment Verification CRUD
require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDB();
$user = currentUser();
$message = '';
$error = '';

// UPDATE Booking Status (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? 'Pending');

    if ($bookingId > 0 && in_array(strtolower($newStatus), ['pending', 'approved', 'rejected'])) {
        try {
            $stmt = $pdo->prepare("UPDATE `bookings` SET `status` = ? WHERE `id` = ?");
            $stmt->execute([$newStatus, $bookingId]);

            $bStmt = $pdo->prepare("SELECT `room_id` FROM `bookings` WHERE `id` = ?");
            $bStmt->execute([$bookingId]);
            $roomId = $bStmt->fetchColumn();

            if ($roomId) {
                if (strtolower($newStatus) === 'approved') {
                    $pdo->prepare("UPDATE `rooms` SET `status` = 'Occupied' WHERE `id` = ?")->execute([$roomId]);
                } else if (strtolower($newStatus) === 'rejected') {
                    $pdo->prepare("UPDATE `rooms` SET `status` = 'Available' WHERE `id` = ?")->execute([$roomId]);
                }
            }

            $message = "Booking #{$bookingId} marked as '{$newStatus}'!";
        } catch (Exception $e) {
            $error = "Error updating booking: " . $e->getMessage();
        }
    }
}

// VERIFY Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_payment') {
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $bookingId = (int)($_POST['booking_id'] ?? 0);

    if ($paymentId > 0 && $bookingId > 0) {
        try {
            $pdo->prepare("UPDATE `payments` SET `status` = 'Verified' WHERE `id` = ?")->execute([$paymentId]);
            $pdo->prepare("UPDATE `bookings` SET `status` = 'Approved' WHERE `id` = ?")->execute([$bookingId]);
            
            $roomId = $pdo->query("SELECT `room_id` FROM `bookings` WHERE `id` = {$bookingId}")->fetchColumn();
            if ($roomId) {
                $pdo->prepare("UPDATE `rooms` SET `status` = 'Occupied' WHERE `id` = ?")->execute([$roomId]);
            }

            $message = "Payment for Booking #{$bookingId} VERIFIED successfully!";
        } catch (Exception $e) {
            $error = "Error verifying payment: " . $e->getMessage();
        }
    }
}

// DELETE Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    if ($bookingId > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `bookings` WHERE `id` = ?");
            $stmt->execute([$bookingId]);
            $message = "Booking request deleted successfully!";
        } catch (Exception $e) {
            $error = "Error deleting booking: " . $e->getMessage();
        }
    }
}

// READ Bookings
$sql = "SELECT b.*, r.name AS room_name, r.price AS room_price, r.type AS room_type, 
               u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
               p.id AS payment_id, p.payment_method AS payment_method, p.reference_number, p.proof_image AS proof_image, p.status AS payment_status, p.amount AS paid_amount
        FROM `bookings` b 
        JOIN `rooms` r ON b.room_id = r.id 
        LEFT JOIN `users` u ON b.user_id = u.id 
        LEFT JOIN `payments` p ON p.id = (
            SELECT id FROM `payments` WHERE booking_id = b.id ORDER BY id DESC LIMIT 1
        )
        ORDER BY b.id DESC";
$bookings = $pdo->query($sql)->fetchAll();

$pageTitle = "Booking & Payment Verification - Admin Dashboard";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-success">✓ <?= e($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= e($error); ?></div>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1 class="page-title">Booking &amp; Online Payment Management</h1>
            <p class="page-subtitle">Review booking applications, verify GCash / Maya / Bank payment receipts, and manage tenant stay approvals.</p>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tenant Information</th>
                    <th>Room Reserved</th>
                    <th>Check-in Date</th>
                    <th>Payment Status</th>
                    <th>Booking Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($bookings) === 0): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:var(--text-muted); padding:36px;">No booking requests submitted yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $b): ?>
                        <?php 
                            $tenantName  = !empty($b['tenant_name']) ? $b['tenant_name'] : ($b['user_name'] ?? 'Guest Tenant');
                            $tenantPhone = !empty($b['tenant_phone']) ? $b['tenant_phone'] : ($b['user_phone'] ?? 'N/A');
                            $tenantEmail = !empty($b['tenant_email']) ? $b['tenant_email'] : ($b['user_email'] ?? 'N/A');
                            $checkInDate = !empty($b['check_in_date']) ? $b['check_in_date'] : ($b['move_in_date'] ?? null);
                            $payStatus   = $b['payment_status'] ?? 'Unpaid';
                        ?>
                        <tr>
                            <td>#<?= $b['id']; ?></td>
                            <td>
                                <strong><?= e($tenantName); ?></strong>
                                <div style="font-size:0.8rem; color:var(--primary);">📞 <?= e($tenantPhone); ?></div>
                                <div style="font-size:0.8rem; color:var(--text-muted);"><?= e($tenantEmail); ?></div>
                            </td>
                            <td>
                                <strong><?= e($b['room_name']); ?></strong>
                                <div style="font-size:0.8rem; color:var(--primary); font-weight:600;"><?= formatMoney($b['room_price']); ?>/month</div>
                            </td>
                            <td><?= $checkInDate ? date('M d, Y', strtotime((string)$checkInDate)) : 'Pending Date'; ?></td>
                            <td>
                                <div><span class="badge badge-<?= strtolower(str_replace(' ', '', $payStatus)); ?>"><?= e($payStatus); ?></span></div>
                                <?php if (!empty($b['payment_method'])): ?>
                                    <div style="font-size:0.8rem; margin-top:4px;">Method: <strong><?= e($b['payment_method']); ?></strong></div>
                                    <?php if (!empty($b['reference_number'])): ?>
                                        <div style="font-size:0.75rem; color:var(--text-muted);">Ref: <?= e($b['reference_number']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($b['proof_image'])): ?>
                                        <div style="margin-top:4px;">
                                            <a href="uploads/payments/<?= e($b['proof_image']); ?>" target="_blank" style="color:var(--primary); font-size:0.8rem; font-weight:600; text-decoration:none;">🖼️ View Receipt Photo</a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= strtolower($b['status']); ?>">
                                    <?= e(ucfirst($b['status'])); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display:inline-flex; flex-direction:column; gap:4px; align-items:flex-end;">
                                    <div style="display:flex; gap:4px;">
                                        <a href="receipt.php?id=<?= $b['id']; ?>" target="_blank" class="btn btn-secondary btn-sm" title="View PDF Receipt">
                                            📄 Receipt
                                        </a>
                                        <?php if ($b['payment_id'] && strtolower($payStatus) !== 'verified'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="verify_payment">
                                                <input type="hidden" name="payment_id" value="<?= $b['payment_id']; ?>">
                                                <input type="hidden" name="booking_id" value="<?= $b['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">✓ Verify Pay</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>

                                    <div style="display:flex; gap:4px;">
                                        <?php if (strtolower($b['status']) !== 'approved'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="booking_id" value="<?= $b['id']; ?>">
                                                <input type="hidden" name="status" value="Approved">
                                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (strtolower($b['status']) !== 'rejected'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="booking_id" value="<?= $b['id']; ?>">
                                                <input type="hidden" name="status" value="Rejected">
                                                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" onsubmit="return confirm('Delete this booking record?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="booking_id" value="<?= $b['id']; ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm">Del</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
