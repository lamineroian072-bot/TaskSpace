<?php
// login.php - Login for Admin & Tenant
require_once __DIR__ . '/config.php';

$error = '';
$flashMessage = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'rooms.php' : 'index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email !== '' && $password !== '') {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
                'phone' => $user['phone'],
            ];

            if ($user['role'] === 'admin') {
                header('Location: rooms.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please enter both email and password.";
    }
}

$pageTitle = "Login - Boarding House Booking System";
include __DIR__ . '/includes/header.php';
?>
<div class="auth-body">
    <div class="glass-card" style="max-width: 440px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="font-size: 2.5rem; margin-bottom: 8px;">🔐</div>
            <h2 style="font-size: 1.6rem; font-weight: 800;">Account Login</h2>
            <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 4px;">Sign in to manage room bookings and payments</p>
        </div>

        <?php if ($flashMessage): ?>
            <div class="alert alert-danger">⚠️ <?= e($flashMessage); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?= e($error); ?></div>
        <?php endif; ?>

        <!-- Quick Fill Pills for Easy Testing -->
        <div style="background: rgba(255,255,255,0.03); border: 1px dashed var(--border); padding: 12px; border-radius: var(--radius-md); margin-bottom: 20px;">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; text-align: center;">⚡ Demo Accounts Quick Fill:</div>
            <div style="display: flex; gap: 8px; justify-content: center;">
                <button type="button" onclick="fillCreds('admin@boardinghouse.com', 'admin123')" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">
                    👑 Admin Fill
                </button>
                <button type="button" onclick="fillCreds('tenant@boardinghouse.com', 'tenant123')" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">
                    👤 Tenant Fill
                </button>
            </div>
        </div>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" id="loginEmail" name="email" class="form-control" required placeholder="Enter your email address" value="<?= e($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" id="loginPassword" name="password" class="form-control" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 8px;">
                Log In
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border); font-size: 0.85rem; color: var(--text-muted); text-align: center;">
            <p>Don't have a tenant account? <a href="register.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Register here</a></p>
            <p style="margin-top: 8px;"><a href="index.php" style="color: var(--text-muted); text-decoration: none;">← Back to Public Rooms Catalog</a></p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>