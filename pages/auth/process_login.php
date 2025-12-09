<?php
// pages/auth/process_login.php
session_start();
require_once '../../engine/core/Auth.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$login_id = trim($_POST['login_id'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($login_id) || empty($password)) {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Please fill in all fields.'
    ];
    header("Location: login.php");
    exit();
}

// Use the Auth::attempt() method we built
if (Auth::attempt($login_id, $password)) {
    // SUCCESS → Redirect to dashboard
    header("Location: ../dashboard.php");
    exit();
} else {
    // FAILED → Show error
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Invalid Staff ID, Email or Password.'
    ];
    header("Location: login.php");
    exit();
}