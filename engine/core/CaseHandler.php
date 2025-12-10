<?php
// engine/core/CaseHandler.php
session_start();
ob_start(); // <-- IMPORTANT: prevents invalid JSON due to any stray output

require_once '../config/db.php';
require_once 'Auth.php';

// ALWAYS return JSON
header('Content-Type: application/json');

// If not logged in
if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$pdo = db();
$userId = $_SESSION['user_id'];

// Upload directory
$uploadDir = '../../uploads/case_documents/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

// JSON Response helper
function jsonResponse($arr) {
    ob_clean();               // clear any accidental output
    echo json_encode($arr);   // output clean JSON
    exit();
}

try {

    // ============================
    // 1. GENERATE CASE NUMBER
    // ============================
    if (!empty($_POST['action']) && $_POST['action'] === 'generate') {

        $category = $_POST['category'] ?? 'GEN';
        $prefix = strtoupper(substr($category, 0, 3));
        $year = date('Y');

        $stmt = $pdo->prepare("SELECT case_number FROM cases WHERE case_number LIKE ? ORDER BY case_id DESC LIMIT 1");
        $stmt->execute(["$prefix/%/$year"]);
        $last = $stmt->fetchColumn();

        $next = $last ? str_pad((int)explode('/', $last)[1] + 1, 3, '0', STR_PAD_LEFT) : '001';

        jsonResponse($prefix . "/" . $next . "/" . $year);
    }

    // Billing values
    $billingMap = [
        'civil' => 150000,
        'criminal' => 250000,
        'family' => 120000,
        'corporate' => 750000,
        'land' => 350000
    ];

    // ============================
    // 2. CREATE CASE
    // ============================
    if (!empty($_POST['action']) && $_POST['action'] === 'create') {

        $category = $_POST['category'];
        $billing = $billingMap[$category] ?? 0;

        // Generate case number
        $prefix = strtoupper(substr($category, 0, 3));
        $year = date('Y');

        $stmt = $pdo->prepare("SELECT case_number FROM cases WHERE case_number LIKE ? ORDER BY case_id DESC LIMIT 1");
        $stmt->execute(["$prefix/%/$year"]);
        $last = $stmt->fetchColumn();
        $next = $last ? str_pad((int)explode('/', $last)[1] + 1, 3, '0', STR_PAD_LEFT) : '001';

        $case_number = "$prefix/$next/$year";

        // Insert case
        $stmt = $pdo->prepare("
            INSERT INTO cases (
                case_number, complainants, respondents, description, category, subcategory,
                petition_date, next_hearing, suit_number, assigned_by, assigned_lawyer_id,
                billing_amount, payment_status, current_status, hearing_notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pre-litigation', ?)
        ");

        $ok = $stmt->execute([
            $case_number,
            $_POST['complainants'],
            $_POST['respondents'],
            $_POST['description'] ?? null,
            $category,
            $_POST['subcategory'] ?? null,
            $_POST['petition_date'] ?: null,
            $_POST['next_hearing'] ?: null,
            $_POST['suit_number'] ?? null,
            $userId,
            $_POST['assigned_lawyer_id'] ?: null,
            $billing,
            $_POST['hearing_notes'] ?? null
        ]);

        if (!$ok) jsonResponse(['success' => false, 'message' => $stmt->errorInfo()[2]]);

        $case_id = $pdo->lastInsertId();

        // Upload base files (update_id = NULL)
        if (!empty($_FILES['case_files']['name'][0])) {
            foreach ($_FILES['case_files']['name'] as $key => $name) {

                if ($_FILES['case_files']['error'][$key] !== 0) continue;

                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $fileName = uniqid('doc_') . ".$ext";
                $target = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['case_files']['tmp_name'][$key], $target)) {
                    $pdo->prepare("
                        INSERT INTO case_documents (case_id, file_name, file_path, file_type, uploaded_by_user_id, upload_date, update_id)
                        VALUES (?, ?, ?, ?, ?, NOW(), NULL)
                    ")->execute([
                        $case_id,
                        $name,
                        'uploads/case_documents/' . $fileName,
                        $ext,
                        $userId
                    ]);
                }
            }
        }

        jsonResponse(['success' => true, 'message' => "Case $case_number created", 'case_id' => $case_id]);
    }

    // ============================
    // 3. VIEW CASE + TIMELINE
    // ============================
    if (!empty($_POST['action']) && $_POST['action'] === 'view') {

        $case_id = $_POST['case_id'];

        // main case
        $stmt = $pdo->prepare("
            SELECT c.*, u.first_name, u.last_name
            FROM cases c
            LEFT JOIN users u ON c.assigned_lawyer_id = u.id
            WHERE c.case_id = ?
        ");
        $stmt->execute([$case_id]);
        $case = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$case) jsonResponse(['success' => false, 'message' => 'Case not found']);

        // base files
        $stmt = $pdo->prepare("SELECT * FROM case_documents WHERE case_id = ? AND update_id IS NULL");
        $stmt->execute([$case_id]);
        $baseFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // all updates
        $stmt = $pdo->prepare("
            SELECT cu.*, u.first_name, u.last_name
            FROM case_updates cu
            LEFT JOIN users u ON cu.updated_by = u.id
            WHERE cu.case_id = ?
            ORDER BY cu.created_at DESC
        ");
        $stmt->execute([$case_id]);
        $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // all files (frontend groups by update_id)
        $stmt = $pdo->prepare("SELECT * FROM case_documents WHERE case_id = ?");
        $stmt->execute([$case_id]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse([
            'success' => true,
            'case' => $case,
            'base_files' => $baseFiles,
            'updates' => $updates,
            'files' => $files
        ]);
    }

    // ============================
    // 4. UPDATE CASE (timeline entry)
    // ============================
    if (!empty($_POST['action']) && $_POST['action'] === 'update') {

        $case_id = $_POST['case_id'];

        $pdo->beginTransaction();

        // insert into case_updates
        $ins = $pdo->prepare("
            INSERT INTO case_updates (case_id, suit_number, next_hearing, status, notes, updated_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $ins->execute([
            $case_id,
            $_POST['suit_number'] ?: null,
            $_POST['next_hearing'] ?: null,
            $_POST['current_status'],
            $_POST['new_notes'] ?: null,
            $userId
        ]);

        $update_id = $pdo->lastInsertId();

        // upload files linked to update_id
        if (!empty($_FILES['case_files']['name'][0])) {
            foreach ($_FILES['case_files']['name'] as $key => $name) {

                if ($_FILES['case_files']['error'][$key] !== 0) continue;

                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $fileName = uniqid('doc_') . ".$ext";
                $target = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['case_files']['tmp_name'][$key], $target)) {
                    $pdo->prepare("
                        INSERT INTO case_documents (case_id, file_name, file_path, file_type, uploaded_by_user_id, upload_date, update_id)
                        VALUES (?, ?, ?, ?, ?, NOW(), ?)
                    ")->execute([
                        $case_id,
                        $name,
                        "uploads/case_documents/" . $fileName,
                        $ext,
                        $userId,
                        $update_id
                    ]);
                }
            }
        }

        // update main case row
        $pdo->prepare("
            UPDATE cases 
            SET suit_number = ?, next_hearing = ?, current_status = ?, payment_status = ?,
                hearing_notes = CONCAT(COALESCE(hearing_notes,''), '\n\n', ?)
            WHERE case_id = ?
        ")->execute([
            $_POST['suit_number'],
            $_POST['next_hearing'],
            $_POST['current_status'],
            $_POST['payment_status'],
            $_POST['new_notes'],
            $case_id
        ]);

        $pdo->commit();

        jsonResponse(['success' => true, 'message' => 'Case updated', 'update_id' => $update_id]);
    }

    // ============================
    // 5. DELETE CASE
    // ============================
    if (!empty($_POST['action']) && $_POST['action'] === 'delete') {

        $case_id = $_POST['case_id'];

        // Delete case (files remain unless you want full cleanup)
        $stmt = $pdo->prepare("DELETE FROM cases WHERE case_id = ?");
        $stmt->execute([$case_id]);

        jsonResponse(['success' => true, 'message' => 'Case deleted']);
    }

    // fallback
    jsonResponse(['success' => false, 'message' => 'Invalid action']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(['success' => false, 'message' => $e->getMessage()]);
}
