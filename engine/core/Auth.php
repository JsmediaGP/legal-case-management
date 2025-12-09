<?php
// engine/core/Auth.php
require_once __DIR__ . '/../config/db.php';
class Auth {
    
    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    // Force login - redirect if not logged in
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header("Location: ../auth/login.php?error=login_required");
            exit();
        }
    }

    // Get current logged-in user details
    public static function user() {
        if (self::isLoggedIn()) {
            $pdo = db();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        }
        return null;
    }

    // Check if user has specific role
    public static function hasRole($allowed_roles) {
        $user = self::user();
        if ($user && in_array($user['designation'], (array)$allowed_roles)) {
            return true;
        }
        return false;
    }

    // Force specific role (e.g., only Head of Chamber)
    public static function requireRole($allowed_roles) {
        self::requireLogin();
        if (!self::hasRole($allowed_roles)) {
            $_SESSION['error'] = "Access Denied: You don't have permission to access this page.";
            header("Location: ../dashboard.php");
            exit();
        }
    }

    // Shortcut functions
    public static function isHeadOfChamber() {
        return self::hasRole('headofchamber');
    }

    public static function isPrincipal() {
        return self::hasRole('principal');
    }

    public static function isLawyer() {
        return self::hasRole('lawyer');
    }

    public static function requireHeadOfChamber() {
        self::requireRole('headofchamber');
    }

    public static function attempt($login_id, $password) {
        $pdo = db();
        
        $stmt = $pdo->prepare("
            SELECT id, staff_id, first_name, last_name, password, designation, status 
            FROM users 
            WHERE (staff_id = ? OR email = ?) 
            LIMIT 1
        ");
        $stmt->execute([$login_id, $login_id]);
        $user = $stmt->fetch();

        // Check if user exists, status active, and password correct
        if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
            
            // Set session variables
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['staff_id']      = $user['staff_id'];
            $_SESSION['name']          = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['designation']   = $user['designation'];

            return true;
        }

        return false;
    }

    public static function logout() {
        session_unset();
        session_destroy();
        
        // Start session again to show flash message
        session_start();
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'You have been logged out successfully.'
        ];
        
        header("Location: ../../pages/auth/login.php");
        exit();
    }
}

// Auto-include current user on every page (optional convenience)
if (Auth::isLoggedIn()) {
    $current_user = Auth::user();
}