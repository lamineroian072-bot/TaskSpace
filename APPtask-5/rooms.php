<?php
// rooms.php - Admin Room Management CRUD with Photo Upload / URL Management
require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDB();
$user = currentUser();
$message = '';
$error = '';

// CREATE Room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name        = trim($_POST['name'] ?? '');
    $type        = trim($_POST['type'] ?? 'Single');
    $price       = (float)($_POST['price'] ?? 0);
    $capacity    = (int)($_POST['capacity'] ?? 1);
    $floor       = trim($_POST['floor'] ?? '1st Floor');
    $status      = trim($_POST['status'] ?? 'Available');
    $description = trim($_POST['description'] ?? '');
    $image       = trim($_POST['image'] ?? '');

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp  = $_FILES['image_file']['tmp_name'];
        $fileName = basename($_FILES['image_file']['name']);
        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

        if (in_array($ext, $allowed)) {
            $newFileName = 'room_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $uploadPath  = __DIR__ . '/images/' . $newFileName;
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                $image = 'images/' . $newFileName;
            }
        }
    }

    if (empty($image)) {
        $typeLower = strtolower($type);
        if (str_contains($typeLower, 'double')) $image = 'images/double.svg';
        else if (str_contains($typeLower, 'studio')) $image = 'images/studio.svg';
        else if (str_contains($typeLower, 'dorm') || str_contains($typeLower, 'bedspace')) $image = 'images/dormitory.svg';
        else $image = 'images/single.svg';
    }

    if ($name !== '' && $price > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO `rooms` (`name`, `type`, `price`, `capacity`, `floor`, `status`, `image`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $type, $price, $capacity, $floor, $status, $image, $description]);
            $message = "Room created successfully!";
        } catch (Exception $e) {
            $error = "Error adding room: " . $e->getMessage();
        }
    } else {
        $error = "Please provide valid room name and price.";
    }
}

// UPDATE Room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $type        = trim($_POST['type'] ?? 'Single');
    $price       = (float)($_POST['price'] ?? 0);
    $capacity    = (int)($_POST['capacity'] ?? 1);
    $floor       = trim($_POST['floor'] ?? '1st Floor');
    $status      = trim($_POST['status'] ?? 'Available');
    $description = trim($_POST['description'] ?? '');
    $image       = trim($_POST['image'] ?? '');

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp  = $_FILES['image_file']['tmp_name'];
        $fileName = basename($_FILES['image_file']['name']);
        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

        if (in_array($ext, $allowed)) {
            $newFileName = 'room_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $uploadPath  = __DIR__ . '/images/' . $newFileName;
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                $image = 'images/' . $newFileName;
            }
        }
    }

    if ($id > 0 && $name !== '') {
        try {
            $stmt = $pdo->prepare("UPDATE `rooms` SET `name`=?, `type`=?, `price`=?, `capacity`=?, `floor`=?, `status`=?, `image`=?, `description`=? WHERE `id`=?");
            $stmt->execute([$name, $type, $price, $capacity, $floor, $status, $image, $description, $id]);
            $message = "Room #{$id} updated successfully!";
        } catch (Exception $e) {
            $error = "Error updating room: " . $e->getMessage();
        }
    }
}

// DELETE Room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `rooms` WHERE `id` = ?");
            $stmt->execute([$id]);
            $message = "Room deleted successfully!";
        } catch (Exception $e) {
            $error = "Error deleting room: " . $e->getMessage();
        }
    }
}

// Room to Edit
$editRoom = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM `rooms` WHERE `id` = ?");
    $stmt->execute([$editId]);
    $editRoom = $stmt->fetch();
}

$rooms = $pdo->query("SELECT * FROM `rooms` ORDER BY `id` DESC")->fetchAll();

$pageTitle = "Room Management CRUD - Admin Dashboard";
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
            <h1 class="page-title">Room Management (CRUD)</h1>
            <p class="page-subtitle">Add, edit, view, or remove rooms and upload room photos.</p>
        </div>
        <button class="btn btn-primary" onclick="toggleAddForm()">
            <?= $editRoom ? 'Editing Room #' . $editRoom['id'] : '+ Add New Room'; ?>
        </button>
    </div>

    <!-- Create / Edit Form -->
    <div id="roomFormCard" class="form-card" style="margin-bottom: 32px; <?= $editRoom ? 'display:block;' : 'display:none;'; ?>">
        <h3 style="font-size: 1.25rem; font-weight: 800;"><?= $editRoom ? '✏️ Edit Room #' . $editRoom['id'] : '➕ Create New Room'; ?></h3>
        <form method="POST" enctype="multipart/form-data" style="margin-top:20px;">
            <input type="hidden" name="action" value="<?= $editRoom ? 'update' : 'create'; ?>">
            <?php if ($editRoom): ?>
                <input type="hidden" name="id" value="<?= $editRoom['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Room Name *</label>
                <input type="text" name="name" class="form-control" required value="<?= e($editRoom['name'] ?? ''); ?>" placeholder="e.g. Room 203 - Executive Single">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Room Type *</label>
                    <select name="type" class="form-control">
                        <?php foreach (['Single', 'Double', 'Studio', 'Bedspace'] as $t): ?>
                            <option value="<?= $t; ?>" <?= ($editRoom['type'] ?? '') === $t ? 'selected' : ''; ?>><?= $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Monthly Price (₱) *</label>
                    <input type="number" step="0.01" name="price" class="form-control" required value="<?= e($editRoom['price'] ?? '3500'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Capacity (Persons)</label>
                    <input type="number" name="capacity" class="form-control" value="<?= e($editRoom['capacity'] ?? '1'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Floor Level</label>
                    <input type="text" name="floor" class="form-control" value="<?= e($editRoom['floor'] ?? '1st Floor'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-control">
                        <option value="Available" <?= ($editRoom['status'] ?? '') === 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Occupied" <?= ($editRoom['status'] ?? '') === 'Occupied' ? 'selected' : ''; ?>>Occupied</option>
                        <option value="Maintenance" <?= ($editRoom['status'] ?? '') === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Photo File Upload or Path</label>
                    <input type="file" name="image_file" class="form-control" accept="image/*">
                    <input type="hidden" name="image" value="<?= e($editRoom['image'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Room Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Describe room amenities, fan/aircon, furniture..."><?= e($editRoom['description'] ?? ''); ?></textarea>
            </div>

            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <?php if ($editRoom): ?>
                    <a href="rooms.php" class="btn btn-secondary">Cancel Edit</a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary"><?= $editRoom ? 'Save Changes' : 'Create Room'; ?></button>
            </div>
        </form>
    </div>

    <!-- READ Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>ID</th>
                    <th>Room Name</th>
                    <th>Type</th>
                    <th>Monthly Price</th>
                    <th>Capacity</th>
                    <th>Floor</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rooms) === 0): ?>
                    <tr>
                        <td colspan="9" style="text-align:center; color:var(--text-muted); padding:32px;">No rooms found in database. Click "+ Add New Room" to create one.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <?php 
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
                        <tr>
                            <td>
                                <img src="<?= e($imagePath); ?>" style="width: 65px; height: 48px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border);" alt="Room" onerror="this.onerror=null; this.src='<?= $defaultImage; ?>';">
                            </td>
                            <td>#<?= $room['id']; ?></td>
                            <td>
                                <strong><?= e($room['name']); ?></strong>
                                <div style="font-size:0.8rem; color:var(--text-muted);"><?= e(substr($room['description'], 0, 45)); ?>...</div>
                            </td>
                            <td><?= e($room['type']); ?></td>
                            <td style="color:var(--primary); font-weight:700;"><?= formatMoney($room['price']); ?></td>
                            <td><?= e($room['capacity']); ?> Pax</td>
                            <td><?= e($room['floor']); ?></td>
                            <td>
                                <span class="badge badge-<?= strtolower($room['status']); ?>">
                                    <?= e(ucfirst($room['status'])); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display:inline-flex; gap:6px;">
                                    <a href="rooms.php?edit=<?= $room['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <form method="POST" onsubmit="return confirm('Delete this room?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $room['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
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
