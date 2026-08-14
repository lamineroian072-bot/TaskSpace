<?php
// pay.php - Online Payment Upload Portal (GCash / Maya / Bank Transfer)
require_once __DIR__ . '/config.php';
requireAuth();

$pdo = getDB();
$user = currentUser();
$message = '';
$error = '';

$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

if ($bookingId <= 0) {
    header('Location: my_bookings.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT b.*, r.name AS room_name, r.price AS room_price 
    FROM `bookings` b 
    JOIN `rooms` r ON b.room_id = r.id 
    WHERE b.id = ? AND (b.user_id = ? OR b.tenant_email = ? OR ? = 'admin')
");
$stmt->execute([$bookingId, $user['id'], $user['email'], $user['role']]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['flash_error'] = "Booking request not found.";
    header('Location: my_bookings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_payment') {
    $paymentMethod   = trim($_POST['payment_method'] ?? 'GCash');
    $referenceNumber = trim($_POST['reference_number'] ?? '');
    $amount          = (float)($_POST['amount'] ?? $booking['room_price']);
    $proofImage      = '';

    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp  = $_FILES['proof_image']['tmp_name'];
        $fileName = basename($_FILES['proof_image']['name']);
        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (in_array($ext, $allowed)) {
            $newFileName = 'pay_' . $bookingId . '_' . time() . '.' . $ext;
            $uploadPath  = __DIR__ . '/uploads/payments/' . $newFileName;
            
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                $proofImage = $newFileName;
            } else {
                $error = "Failed to save uploaded receipt image.";
            }
        } else {
            $error = "Invalid file type. Allowed types: JPG, PNG, WEBP, PDF.";
        }
    }

    if (empty($error)) {
        if (!empty($referenceNumber) || !empty($proofImage)) {
            try {
                $insert = $pdo->prepare("
                    INSERT INTO `payments` (`booking_id`, `payment_method`, `reference_number`, `proof_image`, `amount`, `status`) 
                    VALUES (?, ?, ?, ?, ?, 'Pending Verification')
                ");
                $insert->execute([$bookingId, $paymentMethod, $referenceNumber, $proofImage, $amount]);

                $message = "Payment receipt uploaded successfully! Admin will verify your payment shortly.";
            } catch (Exception $e) {
                $error = "Error submitting payment: " . $e->getMessage();
            }
        } else {
            $error = "Please provide a reference number or upload a proof of payment receipt.";
        }
    }
}

$pageTitle = "Submit Online Payment - Boarding House";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container" style="max-width: 600px;">
    <?php if ($message): ?>
        <div class="alert alert-success">
            ✓ <?= e($message); ?>
            <div style="margin-top:12px;">
                <a href="my_bookings.php" class="btn btn-primary btn-sm">Back to My Bookings</a>
                <a href="receipt.php?id=<?= $bookingId; ?>" target="_blank" class="btn btn-secondary btn-sm">View Receipt</a>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= e($error); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="font-size: 2.5rem; margin-bottom: 8px;">💳</div>
            <h2 style="font-size: 1.5rem; font-weight: 800;">Submit Online Payment</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Upload receipt or reference number for Booking #<?= sprintf('%04d', $booking['id']); ?></p>
        </div>

        <!-- Booking Summary Pill -->
        <div style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.2); padding: 16px; border-radius: var(--radius-md); margin-bottom: 24px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-weight:700; color:var(--text-main);"><?= e($booking['room_name']); ?></div>
                    <div style="font-size:0.85rem; color:var(--text-muted);">Monthly Room Rate</div>
                </div>
                <div style="font-size:1.3rem; font-weight:800; color:var(--primary);"><?= formatMoney($booking['room_price']); ?></div>
            </div>
        </div>

        <!-- Payment Account Guide -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); padding: 16px; border-radius: var(--radius-md); margin-bottom: 24px; font-size: 0.85rem;">
            <div style="font-weight: 700; color: var(--primary); margin-bottom: 8px;">📱 Official Payment Accounts:</div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span>🔵 <strong>GCash:</strong> 0917-123-4567</span>
                <span style="color:var(--text-muted);">Juan Dela Cruz</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span>🟢 <strong>Maya:</strong> 0918-987-6543</span>
                <span style="color:var(--text-muted);">BoardingHouse Hub</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>🏦 <strong>BDO Unibank:</strong> 0012-3456-7890</span>
                <span style="color:var(--text-muted);">BoardingHouse Inc.</span>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit_payment">
            <input type="hidden" name="booking_id" value="<?= $bookingId; ?>">

            <div class="form-group">
                <label class="form-label">Payment Method *</label>
                <select name="payment_method" class="form-control">
                    <option value="GCash">GCash</option>
                    <option value="Maya">Maya</option>
                    <option value="Bank Transfer">Bank Transfer (BDO/BPI)</option>
                    <option value="Cash">Cash on Hand / In Person</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Amount Paid (₱) *</label>
                <input type="number" step="0.01" name="amount" class="form-control" required value="<?= e($booking['room_price']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Transaction Reference Number *</label>
                <input type="text" name="reference_number" class="form-control" placeholder="e.g. 100293847561" required>
            </div>

            <div class="form-group">
                <label class="form-label">Upload Receipt Proof Photo (Screenshot / Image)</label>
                <input type="file" name="proof_image" class="form-control" accept="image/*,.pdf">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 8px;">
                Submit Payment Proof
            </button>
        </form>

        <div style="text-align: center; margin-top: 16px;">
            <a href="my_bookings.php" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none;">← Cancel &amp; Return to My Bookings</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
