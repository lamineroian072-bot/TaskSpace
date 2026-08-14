<?php
// receipt.php - Printable Official Booking & Payment Receipt
require_once __DIR__ . '/config.php';

$pdo = getDB();
$bookingId = (int)($_GET['id'] ?? 0);

if ($bookingId <= 0) {
    die("Invalid receipt request.");
}

// Fetch booking, room, user & payment details
$stmt = $pdo->prepare("
    SELECT b.*, r.name AS room_name, r.price AS room_price, r.type AS room_type, r.floor AS room_floor, r.description AS room_desc,
           u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
           p.id AS payment_id, p.payment_method, p.reference_number, p.proof_image, p.status AS payment_status, p.amount AS paid_amount, p.created_at AS payment_date
    FROM `bookings` b
    JOIN `rooms` r ON b.room_id = r.id
    LEFT JOIN `users` u ON b.user_id = u.id
    LEFT JOIN `payments` p ON p.id = (
        SELECT id FROM `payments` WHERE booking_id = b.id ORDER BY id DESC LIMIT 1
    )
    WHERE b.id = ?
");
$stmt->execute([$bookingId]);
$b = $stmt->fetch();

if (!$b) {
    die("Receipt not found for Booking #{$bookingId}.");
}

$tenantName  = !empty($b['tenant_name']) ? $b['tenant_name'] : ($b['user_name'] ?? 'Guest Tenant');
$tenantPhone = !empty($b['tenant_phone']) ? $b['tenant_phone'] : ($b['user_phone'] ?? 'N/A');
$tenantEmail = !empty($b['tenant_email']) ? $b['tenant_email'] : ($b['user_email'] ?? 'N/A');
$checkInDate = !empty($b['check_in_date']) ? $b['check_in_date'] : ($b['move_in_date'] ?? null);
$payStatus   = $b['payment_status'] ?? 'Unpaid';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #RCT-<?= sprintf('%04d', $b['id']); ?> - Boarding House System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #0f172a;
            padding: 40px 20px;
        }
        .receipt-card {
            background: #ffffff;
            color: #0f172a;
            max-width: 680px;
            margin: 0 auto;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            font-family: 'Plus Jakarta Sans', sans-serif;
            position: relative;
        }
        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 24px;
            margin-bottom: 24px;
        }
        .receipt-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
        }
        .receipt-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-approved-rct { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-pending-rct  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-rejected-rct { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .receipt-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        .info-label {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
        }
        .receipt-table th {
            background: #f8fafc;
            color: #475569;
            padding: 12px 16px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
        }
        .receipt-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #1e293b;
        }
        .receipt-total {
            background: #f8fafc;
            padding: 16px 20px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
        }
        .watermark-stamp {
            position: absolute;
            right: 40px;
            bottom: 40px;
            opacity: 0.12;
            font-size: 4.5rem;
            font-weight: 900;
            text-transform: uppercase;
            transform: rotate(-15deg);
            pointer-events: none;
        }
        @media print {
            body { background: #ffffff !important; padding: 0 !important; }
            .receipt-card { box-shadow: none !important; border: none !important; max-width: 100% !important; border-radius: 0 !important; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="max-width: 680px; margin: 0 auto 20px auto; display: flex; justify-content: space-between; align-items: center;">
        <a href="index.php" style="color: #94a3b8; text-decoration: none; font-weight: 600; font-size: 0.9rem;">← Back to App</a>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-primary btn-sm">🖨️ Print Receipt / Save PDF</button>
        </div>
    </div>

    <div class="receipt-card">
        <div class="receipt-header">
            <div>
                <div class="receipt-title">🏠 BoardingHouse Hub</div>
                <div style="font-size: 0.85rem; color: #64748b; margin-top: 2px;">Official Booking &amp; Payment Confirmation</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.85rem; font-weight: 800; color: #0284c7;">#RCT-<?= sprintf('%04d', $b['id']); ?></div>
                <div style="font-size: 0.8rem; color: #64748b;"><?= date('M d, Y, h:i A', strtotime($b['created_at'])); ?></div>
                <div style="margin-top: 8px;">
                    <span class="receipt-badge badge-<?= strtolower($b['status']); ?>-rct">
                        <?= e(ucfirst($b['status'])); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="receipt-grid">
            <div>
                <div class="info-label">Tenant Information</div>
                <div class="info-value"><?= e($tenantName); ?></div>
                <div style="font-size: 0.85rem; color: #475569;">📞 <?= e($tenantPhone); ?></div>
                <div style="font-size: 0.85rem; color: #475569;">✉️ <?= e($tenantEmail); ?></div>
            </div>
            <div>
                <div class="info-label">Stay Details</div>
                <div class="info-value">Check-in Date: <?= $checkInDate ? date('F j, Y', strtotime((string)$checkInDate)) : 'Pending'; ?></div>
                <div style="font-size: 0.85rem; color: #475569;">Room Floor: <?= e($b['room_floor']); ?></div>
                <div style="font-size: 0.85rem; color: #475569;">Status: <strong><?= e($b['status']); ?></strong></div>
            </div>
        </div>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Item / Description</th>
                    <th>Room Type</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?= e($b['room_name']); ?></strong>
                        <div style="font-size: 0.8rem; color: #64748b;"><?= e($b['room_desc']); ?></div>
                    </td>
                    <td><?= e($b['room_type']); ?></td>
                    <td style="text-align: right; font-weight: 700;"><?= formatMoney($b['room_price']); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="receipt-grid" style="margin-bottom: 0;">
            <div>
                <div class="info-label">Payment Information</div>
                <div style="font-size: 0.85rem; color: #1e293b;">Method: <strong><?= e($b['payment_method'] ?? 'Pending Payment'); ?></strong></div>
                <?php if (!empty($b['reference_number'])): ?>
                    <div style="font-size: 0.85rem; color: #1e293b;">Ref No: <strong><?= e($b['reference_number']); ?></strong></div>
                <?php endif; ?>
                <div style="font-size: 0.85rem; color: #1e293b; margin-top: 2px;">Verification: <strong><?= e($payStatus); ?></strong></div>
            </div>
            <div class="receipt-total">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b;">Total Amount</div>
                    <div style="font-size: 1.4rem; font-weight: 800; color: #0f172a;"><?= formatMoney($b['room_price']); ?></div>
                </div>
                <div style="font-size: 0.8rem; color: #10b981; font-weight: 700; text-align: right;">
                    ✓ Verified Official
                </div>
            </div>
        </div>

        <div class="watermark-stamp">
            <?= strtoupper($b['status']); ?>
        </div>

        <div style="margin-top: 32px; padding-top: 16px; border-top: 1px dashed #cbd5e1; text-align: center; font-size: 0.78rem; color: #64748b;">
            Thank you for booking with BoardingHouse Hub! Please present this receipt upon check-in.
        </div>
    </div>
</body>
</html>
