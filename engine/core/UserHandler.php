<?php
session_start();
ob_start(); // prevent stray output

require_once '../config/db.php';
require_once 'Auth.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$pdo = db();
$isHead = Auth::isHeadOfChamber();

function jsonResponse($arr) {
    ob_clean();
    echo json_encode($arr);
    exit();
}

/* ============================================================
   1. GENERATE STAFF ID
   ============================================================ */
if ($_POST['action'] === 'generate_staff_id') {

    $prefix = $_POST['prefix']; // LWR / PRN / HOC

    // Query example: LWR-001, LWR-002, etc.
    $stmt = $pdo->prepare("SELECT staff_id FROM users WHERE staff_id LIKE ? ORDER BY staff_id DESC LIMIT 1");
    $stmt->execute([$prefix . '-%']);
    $last = $stmt->fetchColumn();

    if ($last) {
        // Extract last number -> LWR-015 -> 15
        $num = (int)substr($last, strlen($prefix) + 1);
        $next = str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $next = "001";
    }

    $newID = $prefix . "-" . $next;

    echo json_encode([
        "success" => true,
        "staff_id" => $newID
    ]);
    exit;
}



/* ============================================================
   2. CREATE USER  (HEAD ONLY)
   ============================================================ */
if (!empty($_POST['action']) && $_POST['action'] === 'create_user') {

    if (!$isHead) jsonResponse(['success' => false, 'message' => 'Permission denied']);

    $staff_id = $_POST['staff_id'];
    $first = $_POST['first_name'];
    $last = $_POST['last_name'];
    $email = $_POST['email'] ?: null;
    $phone = $_POST['phone'] ?: null;
    $designation = $_POST['designation'];

    // default password
    $password = password_hash("password", PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("
        INSERT INTO users (staff_id, first_name, last_name, email, phone, designation, password, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
    ");

    $ok = $stmt->execute([$staff_id, $first, $last, $email, $phone, $designation, $password]);

    if ($ok) {
        jsonResponse(['success' => true, 'message' => "User created successfully"]);
    }

    jsonResponse(['success' => false, 'message' => "Failed to create user"]);
}


/* ============================================================
   3. GET USER (for edit modal)
   ============================================================ */
if (!empty($_POST['action']) && $_POST['action'] === 'get_user') {

    $id = $_POST['id'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($u) {
        jsonResponse(['success' => true, 'data' => $u]);
    }

    jsonResponse(['success' => false, 'message' => "User not found"]);
}


/* ============================================================
   4. UPDATE USER
   ============================================================ */
if (!empty($_POST['action']) && $_POST['action'] === 'update_user') {

    if (!$isHead) jsonResponse(['success' => false, 'message' => "Only Head can update users"]);

    $id = $_POST['id'];

    $stmt = $pdo->prepare("
        UPDATE users SET first_name=?, last_name=?, email=?, phone=?, designation=? 
        WHERE id=?
    ");

    $ok = $stmt->execute([
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'] ?: null,
        $_POST['phone'] ?: null,
        $_POST['designation'],
        $id
    ]);

    if ($ok) jsonResponse(['success' => true, 'message' => "User updated"]);

    jsonResponse(['success' => false, 'message' => "Update failed"]);
}


/* ============================================================
   5. RESET PASSWORD (default = password)
   ============================================================ */
if (!empty($_POST['action']) && $_POST['action'] === 'reset_password') {

    if (!$isHead) jsonResponse(['success' => false, 'message' => "Only Head can reset passwords"]);

    $id = $_POST['id'];
    $newPass = password_hash("password", PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->execute([$newPass, $id]);

    jsonResponse(['success' => true, 'message' => "Password reset to 'password'"]);
}


/* ============================================================
   6. ACTIVATE / DEACTIVATE
   ============================================================ */
if (!empty($_POST['action']) && $_POST['action'] === 'toggle_status') {

    if (!$isHead) jsonResponse(['success' => false, 'message' => "Only Head can change status"]);

    $id = $_POST['id'];

    // Fetch current status
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id=?");
    $stmt->execute([$id]);
    $status = $stmt->fetchColumn();

    $new = ($status === 'active') ? 'inactive' : 'active';

    $stmt = $pdo->prepare("UPDATE users SET status=? WHERE id=?");
    $stmt->execute([$new, $id]);

    jsonResponse(['success' => true, 'message' => "Status changed to $new"]);
}


/* ============================================================
   7. DELETE USER
   ============================================================ */
if (!empty($_POST['action']) && $_POST['action'] === 'delete_user') {

    if (!$isHead) jsonResponse(['success' => false, 'message' => "Only Head can delete users"]);

    $id = $_POST['id'];

    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);

    jsonResponse(['success' => true, 'message' => "User deleted"]);
}


// fallback
jsonResponse(['success' => false, 'message' => "Invalid action"]);

?>
