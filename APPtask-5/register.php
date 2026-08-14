<?php
// register.php - Tenant Registration Page
require_once __DIR__ . '/config.php';

$error = '';
$message = '';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($name !== '' && $email !== '' && $password !== '') {
        if ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            $pdo = getDB();
            
            $stmt = $pdo->prepare("SELECT `id` FROM `users` WHERE `email` = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "This email address is already registered.";
            } else {
                try {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $insert = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`, `phone`) VALUES (?, ?, ?, 'tenant', ?)");
                    $insert->execute([$name, $email, $hashed, $phone]);

                    $newId = $pdo->lastInsertId();

                    $_SESSION['user'] = [
                        'id'    => $newId,
                        'name'  => $name,
                        'email' => $email,
                        'role'  => 'tenant',
                        'phone' => $phone,
                    ];

                    header('Location: index.php');
                    exit;
                } catch (Exception $e) {
                    $error = "Registration failed: " . $e->getMessage();
                }
            }
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

$pageTitle = "Tenant Registration - Boarding House System";
include __DIR__ . '/includes/header.php';
?>
<div class="auth-body">
    <div class="glass-card" style="max-width: 480px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="font-size: 2.5rem; margin-bottom: 8px;">📋</div>
            <h2 style="font-size: 1.6rem; font-weight: 800;">Tenant Registration</h2>
            <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 4px;">Create your account to book rooms online</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?= e($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" required placeholder="e.g. Maria Santos" value="<?= e($_POST['name'] ?? ''); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" required placeholder="maria@example.com" value="<?= e($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="09123456789" value="<?= e($_POST['phone'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 8px;">
                Create Tenant Account
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border); font-size: 0.85rem; color: var(--text-muted); text-align: center;">
            <p>Already registered? <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Log in here</a></p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
