<!-- engine/inc/sidebar.php -->
<div class="sidebar">
    <div class="logo">
        <i class="fas fa-balance-scale"></i>
        <h3>ChamberSync</h3>
    </div>

    <nav class="mt-4 flex-grow-1">
        <a href="../pages/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-text">Dashboard</span>
        </a>

        <a href="../pages/cases.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'cases.php' ? 'active' : '' ?>">
            <i class="fas fa-gavel"></i>
            <span class="nav-text">Cases</span>
        </a>

        <?php if (Auth::isHeadOfChamber()): ?>
        <a href="../pages/users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
            <i class="fas fa-users-cog"></i>
            <span class="nav-text">Manage Staff</span>
        </a>
        <?php endif; ?>

        <a href="../pages/profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
            <i class="fas fa-user-tie"></i>
            <span class="nav-text">Profile</span>
        </a>
    </nav>

    <!-- LOGOUT BUTTON - FIXED AT BOTTOM -->
    <div class="p-3 border-top border-white border-opacity-10">
        <a href="../pages/auth/logout.php" class="nav-link text-danger d-flex align-items-center">
            <i class="fas fa-sign-out-alt me-3"></i>
            <span class="nav-text fw-medium">Logout</span>
        </a>
    </div>
</div>