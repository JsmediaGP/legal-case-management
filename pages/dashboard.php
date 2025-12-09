<?php
session_start();
require_once '../engine/core/Auth.php';
Auth::requireLogin();

$user = Auth::user();
$userId = $user['id'];
$role = $user['designation'];
$pdo = db();

// REAL-TIME STATS (Safe Queries)
$total_active = $pdo->query("SELECT COUNT(*) FROM cases WHERE current_status NOT IN ('closed', 'disposed')")->fetchColumn();

$my_cases = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE assigned_lawyer_id = ?")->execute([$userId])
    ? $pdo->query("SELECT COUNT(*) FROM cases WHERE assigned_lawyer_id = $userId")->fetchColumn() : 0;

$upcoming_hearings = $pdo->query("SELECT COUNT(*) FROM cases 
    WHERE next_hearing IS NOT NULL 
    AND next_hearing BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

$pending_fees = $pdo->prepare("SELECT COALESCE(SUM(billing_amount), 0) FROM cases 
    WHERE assigned_lawyer_id = ? AND payment_status = 'pending'")->execute([$userId])
    ? $pdo->query("SELECT COALESCE(SUM(billing_amount), 0) FROM cases WHERE assigned_lawyer_id = $userId AND payment_status = 'pending'")->fetchColumn() : 0;

$total_staff = Auth::isHeadOfChamber() 
    ? $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn() : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard • ChamberSync</title>

    <!-- Bootstrap 5.3.3 CSS + JS (CDN - 100% WORKING) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

    <!-- Your Custom Style -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Sidebar -->
    <?php include '../engine/inc/sidebar.php'; ?>

    <!-- Top Bar -->
    <div class="topbar d-flex align-items-center justify-content-between">
        <h4 class="mb-0 text-navy fw-bold">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </h4>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <small class="text-muted d-block">Signed in as</small>
                <strong class="text-navy"><?= htmlspecialchars($user['first_name']) ?></strong>
            </div>
            <!-- <a href="auth/logout.php" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-sign-out-alt"></i>
            </a> -->
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Slim Welcome Banner -->
        <div class="bg-white rounded-3 shadow-sm p-4 mb-4 border-start border-5 border-navy">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="mb-1 fw-bold text-navy">
                        Good <?= date('A') == 'AM' ? 'Morning' : 'Afternoon' ?>, <?= htmlspecialchars($user['first_name']) ?>!
                    </h4>
                    <p class="mb-0 text-muted">
                        <?= ucwords(str_replace('headofchamber', 'Head of Chamber', $role)) ?> 
                        • <?= $user['staff_id'] ?> • <?= date('l, j F Y') ?>
                    </p>
                </div>
                <div class="col-auto">
                    <small class="text-muted"><?= date('h:i A') ?></small>
                </div>
            </div>
        </div>

        <!-- 3 CARDS PER ROW - PERFECT -->
        <div class="row g-4">

            <!-- Active Cases -->
            <div class="col-lg-4 col-md-6">
                <div class="stat-card bg-white rounded-3 shadow-sm p-4 border-start border-4 border-navy">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 fw-bold text-navy"><?= $total_active ?></h3>
                            <p class="mb-0 text-muted mt-1">Total Active Cases</p>
                        </div>
                        <i class="fas fa-gavel fa-3x text-gold opacity-75"></i>
                    </div>
                </div>
            </div>

            <!-- My Cases -->
            <div class="col-lg-4 col-md-6">
                <div class="stat-card bg-white rounded-3 shadow-sm p-4 border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 fw-bold text-success"><?= $my_cases ?></h3>
                            <p class="mb-0 text-muted mt-1">My Assigned Cases</p>
                        </div>
                        <i class="fas fa-briefcase fa-3x text-success opacity-75"></i>
                    </div>
                </div>
            </div>

            <!-- Upcoming Hearings -->
            <div class="col-lg-4 col-md-6">
                <div class="stat-card bg-white rounded-3 shadow-sm p-4 border-start border-4 border-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 fw-bold text-warning"><?= $upcoming_hearings ?></h3>
                            <p class="mb-0 text-muted mt-1">Hearings in Next 7 Days</p>
                        </div>
                        <i class="fas fa-calendar-check fa-3x text-warning opacity-75"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Fees (Only if > 0) -->
            <?php if ($pending_fees > 0): ?>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card bg-white rounded-3 shadow-sm p-4 border-start border-4 border-danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 fw-bold text-danger">#<?= number_format($pending_fees) ?></h3>
                            <p class="mb-0 text-muted mt-1">Pending Fees</p>
                        </div>
                        <i class="fas fa-naira-sign fa-3x text-danger opacity-75"></i>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Team Members (HOC Only) -->
            <?php if (Auth::isHeadOfChamber()): ?>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card bg-white rounded-3 shadow-sm p-4 border-start border-4 border-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 fw-bold text-info"><?= $total_staff ?></h3>
                            <p class="mb-0 text-muted mt-1">Total Team Members</p>
                        </div>
                        <i class="fas fa-users fa-3x text-info opacity-75"></i>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Quick Actions -->
        <div class="text-center mt-5">
            <a href="cases.php?action=add" class="btn btn-navy text-white px-5 py-3 me-3 fs-5">
                <i class="fas fa-plus-circle me-2"></i> New Case
            </a>
            <?php if (Auth::isHeadOfChamber()): ?>
            <a href="users.php" class="btn btn-gold text-white px-5 py-3 fs-5">
                <i class="fas fa-user-plus me-2"></i> Add Staff
            </a>
            <?php endif; ?>
        </div>

        <div class="text-center text-muted mt-5">
            <small>&copy; <?= date('Y') ?> ChamberSync • Legal Case Management System</small>
        </div>
    </div>

</body>
</html>