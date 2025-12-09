<?php
session_start();
require_once '../../engine/core/Auth.php';

if (Auth::isLoggedIn()) {
    header("Location: ../dashboard.php");
    exit();
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChamberSync • Login</title>

    <!-- Bootstrap 5 + Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy: #0A2D5C;
            --gold: #C9A227;
            --dark-navy: #071F44;
            --light-gray: #f8f9fa;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--navy) 0%, #0f3d6e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .login-container {
            background: white;
            width: 100%;
            max-width: 440px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
        }

        .login-header {
            background: var(--navy);
            color: white;
            text-align: center;
            padding: 40px 30px;
        }

        .login-header i {
            font-size: 70px;
            color: var(--gold);
            margin-bottom: 15px;
        }

        .login-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            margin: 0;
            font-weight: 700;
        }

        .login-header p {
            margin-top: 8px;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .login-body {
            padding: 45px 40px;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .form-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--navy);
            font-size: 1.2rem;
            z-index: 10;
        }

        .form-control {
            height: 58px;
            padding-left: 55px;
            border: 2px solid #e0e0e0;
            border-radius: 14px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 0.25rem rgba(201, 162, 39, 0.25);
            outline: none;
        }

        .btn-login {
            height: 58px;
            border-radius: 14px;
            background: var(--navy);
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            border: none;
            transition: all 0.4s;
        }

        .btn-login:hover {
            background: var(--dark-navy);
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(10, 45, 92, 0.4);
        }

        .footer-text {
            text-align: center;
            margin-top: 25px;
            color: #666;
            font-size: 0.95rem;
        }

        .alert {
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <i class="fas fa-balance-scale"></i>
        <h1>ChamberSync</h1>
        <p>Legal Case Management System</p>
    </div>

    <div class="login-body">

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">Invalid Staff ID or Password. Try again.</div>
        <?php endif; ?>

        <form action="process_login.php" method="POST">
            <div class="form-group">
                <i class="fas fa-id-card"></i>
                <input type="text" name="login_id" class="form-control" placeholder="Staff ID or Email" required autofocus>
            </div>

            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>

            <button type="submit" class="btn btn-login w-100">
                <i class="fas fa-sign-in-alt me-2"></i> Login to Dashboard
            </button>
        </form>

        <div class="footer-text">
            Default Admin → <strong>HOC001</strong> • Password: <strong>password</strong>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>