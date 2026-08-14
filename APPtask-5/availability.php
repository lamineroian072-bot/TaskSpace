<?php
// availability.php - Real-Time Room Availability Matrix & Floor Map
require_once __DIR__ . '/config.php';

$pdo = getDB();
$user = currentUser();

$rooms = $pdo->query("SELECT * FROM `rooms` ORDER BY `floor` ASC, `id` ASC")->fetchAll();

$floors = [];
foreach ($rooms as $r) {
    $floorName = !empty($r['floor']) ? $r['floor'] : 'General Floor';
    $floors[$floorName][] = $r;
}

$pageTitle = "Room Availability Matrix - Boarding House";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Room Availability Matrix</h1>
            <p class="page-subtitle">Interactive floor map of available, occupied, and maintenance rooms.</p>
        </div>
        <a href="index.php" class="btn btn-primary">Reserve a Room</a>
    </div>

    <?php foreach ($floors as $floorName => $floorRooms): ?>
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 24px;">
            <div style="font-size: 1.2rem; font-weight: 800; margin-bottom: 16px; color: var(--primary); display: flex; align-items: center; gap: 8px;">
                🏢 <?= e($floorName); ?> (<?= count($floorRooms); ?> Units)
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px;">
                <?php foreach ($floorRooms as $r): ?>
                    <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px; text-align: center;">
                        <div style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin-bottom: 4px;"><?= e($r['name']); ?></div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px;"><?= e($r['type']); ?> — <?= formatMoney($r['price']); ?>/mo</div>
                        <div>
                            <span class="badge badge-<?= strtolower($r['status']); ?>">
                                <?= e(ucfirst($r['status'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
