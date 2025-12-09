<?php
// index.php - Main Entry Point

// Start session (must be first)
session_start();

// Include the core files
require_once 'engine/config/db.php';
require_once 'engine/core/Auth.php';

// Redirect logic
if (Auth::isLoggedIn()) {
    // User is already logged in → send to dashboard
    header("Location: pages/dashboard.php");
    exit();
} else {
    // Not logged in → send to login page
    header("Location: pages/auth/login.php");
    exit();
}