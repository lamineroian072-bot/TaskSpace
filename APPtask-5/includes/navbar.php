<?php
// includes/navbar.php
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="brand">🏠 <span>BoardingHouse</span> Hub</a>
        <div class="nav-links">
            <a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : ''; ?>">Browse Rooms</a>
            <a href="availability.php" class="nav-link <?= $currentPage === 'availability.php' ? 'active' : ''; ?>">Availability Matrix</a>
            
            <?php if (isLoggedIn()): ?>
                <a href="my_bookings.php" class="nav-link <?= $currentPage === 'my_bookings.php' ? 'active' : ''; ?>">My Bookings</a>
                <?php if (isAdmin()): ?>
                    <a href="rooms.php" class="nav-link <?= $currentPage === 'rooms.php' ? 'active' : ''; ?>">Rooms CRUD</a>
                    <a href="bookings.php" class="nav-link <?= $currentPage === 'bookings.php' ? 'active' : ''; ?>">Bookings CRUD</a>
                    <a href="reports.php" class="nav-link <?= $currentPage === 'reports.php' ? 'active' : ''; ?>">Reports</a>
                <?php endif; ?>
                <div style="display:flex; align-items:center; gap:8px; margin-left:12px;">
                    <span style="font-size:0.85rem; color:var(--primary); font-weight:600;">
                        <?= isAdmin() ? '👑 Admin' : '👤'; ?> (<?= e($user['name']); ?>)
                    </span>
                    <a href="logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-link <?= $currentPage === 'login.php' ? 'active' : ''; ?>">Log In</a>
                <a href="register.php" class="btn btn-primary btn-sm">Register Tenant</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
