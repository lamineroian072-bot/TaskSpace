<?php
// reports.php - Admin Executive Reports & Financial Analytics Dashboard
require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDB();
$user = currentUser();

$totalRoomsCount = (int)$pdo->query("SELECT COUNT(*) FROM `rooms`")->fetchColumn();
$availableRoomsCount = (int)$pdo->query("SELECT COUNT(*) FROM `rooms` WHERE LOWER(`status`) = 'available'")->fetchColumn();
$occupiedRoomsCount = (int)$pdo->query("SELECT COUNT(*) FROM `rooms` WHERE LOWER(`status`) = 'occupied'")->fetchColumn();
$maintenanceRoomsCount = (int)$pdo->query("SELECT COUNT(*) FROM `rooms` WHERE LOWER(`status`) = 'maintenance'")->fetchColumn();

$occupancyRate = $totalRoomsCount > 0 ? round(($occupiedRoomsCount / $totalRoomsCount) * 100, 1) : 0;

$totalBookingsCount = (int)$pdo->query("SELECT COUNT(*) FROM `bookings`")->fetchColumn();
$pendingBookingsCount = (int)$pdo->query("SELECT COUNT(*) FROM `bookings` WHERE LOWER(`status`) = 'pending'")->fetchColumn();
$approvedBookingsCount = (int)$pdo->query("SELECT COUNT(*) FROM `bookings` WHERE LOWER(`status`) = 'approved'")->fetchColumn();

$monthlyRevenue = (float)$pdo->query("
    SELECT SUM(r.price) 
    FROM `bookings` b 
    JOIN `rooms` r ON b.room_id = r.id 
    WHERE LOWER(b.status) = 'approved'
")->fetchColumn();

$typeBreakdown = $pdo->query("
    SELECT type, COUNT(*) as count, SUM(price) as total_value
    FROM `rooms`
    GROUP BY type
")->fetchAll();

$pageTitle = "Executive Reports & Analytics - Admin Dashboard";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Executive Reports &amp; Analytics</h1>
            <p class="page-subtitle">Real-time revenue metrics, occupancy rates, and room inventory reports.</p>
        </div>
        <button onclick="window.print()" class="btn btn-primary no-print">🖨️ Print Analytics Report</button>
    </div>

    <!-- Metric KPI Cards -->
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-bottom: 32px;">
        <div class="card" style="border-left: 4px solid var(--primary);">
            <div style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Projected Monthly Revenue</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top: 4px;"><?= formatMoney($monthlyRevenue); ?></div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px;">From Approved Tenant Stays</div>
        </div>

        <div class="card" style="border-left: 4px solid var(--success);">
            <div style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Occupancy Rate</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #34d399; margin-top: 4px;"><?= $occupancyRate; ?>%</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px;"><?= $occupiedRoomsCount; ?> of <?= $totalRoomsCount; ?> Rooms Occupied</div>
        </div>

        <div class="card" style="border-left: 4px solid var(--warning);">
            <div style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Pending Applications</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #fbbf24; margin-top: 4px;"><?= $pendingBookingsCount; ?></div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px;">Out of <?= $totalBookingsCount; ?> Total Bookings</div>
        </div>

        <div class="card" style="border-left: 4px solid var(--accent);">
            <div style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Available Units</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #a78bfa; margin-top: 4px;"><?= $availableRoomsCount; ?></div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px;">Ready for immediate move in</div>
        </div>
    </div>

    <!-- Room Inventory Breakdown Table -->
    <div class="form-card" style="margin-bottom: 32px;">
        <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 16px;">📊 Inventory &amp; Revenue Breakdown by Room Type</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Room Category</th>
                        <th>Total Units</th>
                        <th>Potential Monthly Value</th>
                        <th>Status Distribution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($typeBreakdown as $tb): ?>
                        <tr>
                            <td><strong><?= e(ucfirst($tb['type'])); ?></strong></td>
                            <td><?= $tb['count']; ?> Room(s)</td>
                            <td style="color: var(--primary); font-weight: 700;"><?= formatMoney($tb['total_value']); ?></td>
                            <td>
                                <span class="badge badge-available">Active</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
