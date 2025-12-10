<?php
session_start();
ob_start();

require_once '../config/db.php';
require_once 'Auth.php';

// MUST return JSON
header('Content-Type: application/json');

// Must be logged in
if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$pdo = db();
$userId = $_SESSION['user_id'];

// JSON cleaner
function jsonResponse($data){
    ob_clean();
    echo json_encode($data);
    exit();
}

try {

    /* =====================================================
       1) UPDATE PROFILE INFORMATION
    ====================================================== */
    if (!empty($_POST['action']) && $_POST['action'] === 'update_profile') {

        $first = trim($_POST['first_name']);
        $last = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        if ($first === '' || $last === '') {
            jsonResponse(['success' => false, 'message' => 'Name fields cannot be empty']);
        }

        $stmt = $pdo->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$first, $last, $email, $phone, $userId]);

        jsonResponse([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    }


    /* =====================================================
       2) CHANGE PASSWORD
    ====================================================== */
    if (!empty($_POST['action']) && $_POST['action'] === 'change_password') {

        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new === '' || $confirm === '') {
            jsonResponse(['success'=>false, 'message'=>'New password is required']);
        }

        if ($new !== $confirm) {
            jsonResponse(['success'=>false, 'message'=>'New passwords do not match']);
        }

        // Fetch current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $current = $stmt->fetchColumn();

        if (!$current || !password_verify($old, $current)) {
            jsonResponse(['success'=>false, 'message'=>'Current password is incorrect']);
        }

        // Save new password
        $newHash = password_hash($new, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?
        ");
        $stmt->execute([$newHash, $userId]);

        jsonResponse([
            'success'=>true,
            'message'=>'Password updated successfully'
        ]);
    }


    /* =====================================================
       Invalid Action
    ====================================================== */
    jsonResponse(['success'=>false, 'message'=>'Invalid action']);

} catch (Exception $e) {
    jsonResponse(['success'=>false, 'message'=>$e->getMessage()]);
}
