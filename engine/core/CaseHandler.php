<?php
// engine/core/CaseHandler.php
session_start();
require_once '../config/db.php';
require_once 'Auth.php';

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$pdo = db();
$userId = $_SESSION['user_id'];
$uploadDir = '../../uploads/case_documents/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);


// CASE NUMBER GENERATOR

function generateCaseNumber($category) {
    $prefix = strtoupper(substr($category, 0, 3));
    $year = date('Y');
    $pdo = db();
    $stmt = $pdo->query("SELECT case_number FROM cases WHERE case_number LIKE '$prefix/%/$year' ORDER BY case_id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    $next = $last ? str_pad((int)explode('/', $last)[1] + 1, 3, '0', STR_PAD_LEFT) : '001';
    return "$prefix/$next/$year";
}


// BILLING MAP

$billingMap = [
    'civil' => 150000,
    'criminal' => 250000,
    'family' => 120000,
    'corporate' => 750000,
    'land' => 350000
];


// GENERATE CASE NUMBER REQUEST

if (isset($_POST['action']) && $_POST['action'] === 'generate') {
    echo generateCaseNumber($_POST['category']);
    exit();
}


// CREATE CASE

if ($_POST['action'] === 'create') {

    $case_number = generateCaseNumber($_POST['category']);
    $billing = $billingMap[$_POST['category']] ?? 0;

    // CORRECTED SQL — corrected placeholders and ordering
    $stmt = $pdo->prepare("INSERT INTO cases 
        (case_number, complainants, respondents, description, category, subcategory,
         petition_date, next_hearing, suit_number, assigned_by, assigned_lawyer_id,
         billing_amount, payment_status, current_status, hearing_notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, 'pending', 'pre-litigation', ?)");

    // EXECUTE WITH CORRECT ORDER
    $success = $stmt->execute([
        $case_number,
        $_POST['complainants'],
        $_POST['respondents'],
        $_POST['description'] ?? '',
        $_POST['category'],
        $_POST['subcategory'] ?? '',
        $_POST['petition_date'] ?: null,
        $_POST['next_hearing'] ?: null,
        $userId, // assigned_by
        $_POST['assigned_lawyer_id'] ?: null,
        $billing,
        $_POST['hearing_notes'] ?? ''
    ]);

    // DEBUGGING — report SQL error
    if (!$success) {
        echo json_encode([
            'success' => false,
            'message' => $stmt->errorInfo()[2]   // <-- SQL error
        ]);
        exit();
    }

    // GET INSERTED CASE ID
    $case_id = $pdo->lastInsertId();

    
    // FILE UPLOAD HANDLER
    
    if (!empty($_FILES['case_files']['name'][0])) {
        foreach ($_FILES['case_files']['name'] as $key => $name) {
            if ($_FILES['case_files']['error'][$key] === 0) {

                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $fileName = uniqid('doc_') . '.' . $ext;
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['case_files']['tmp_name'][$key], $filePath)) {
                    $stmt2 = $pdo->prepare("INSERT INTO case_documents 
                        (case_id, file_name, file_path, file_type, uploaded_by_user_id, upload_date)
                        VALUES (?, ?, ?, ?, ?, NOW())");

                    $stmt2->execute([
                        $case_id,
                        $name,
                        'uploads/case_documents/' . $fileName,
                        $ext,
                        $userId
                    ]);
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Case $case_number created successfully!"
    ]);
    exit();
}


// UPDATE CASE

if ($_POST['action'] === 'update') {
    $stmt = $pdo->prepare("UPDATE cases SET 
        complainants=?, respondents=?, description=?, category=?, subcategory=?,
        petition_date=?, next_hearing=?, suit_number=?, assigned_lawyer_id=?,
        current_status=?, payment_status=?, hearing_notes=?
        WHERE case_id=?");

    $success = $stmt->execute([
        $_POST['complainants'],
        $_POST['respondents'],
        $_POST['description'] ?? '',
        $_POST['category'],
        $_POST['subcategory'] ?? '',
        $_POST['petition_date'] ?: null,
        $_POST['next_hearing'] ?: null,
        $_POST['suit_number'],
        $_POST['assigned_lawyer_id'] ?: null,
        $_POST['current_status'],
        $_POST['payment_status'],
        $_POST['hearing_notes'] ?? '',
        $_POST['case_id']
    ]);

    // FILE UPLOADS ON UPDATE
    if ($success && !empty($_FILES['case_files']['name'][0])) {
        foreach ($_FILES['case_files']['name'] as $key => $name) {
            if ($_FILES['case_files']['error'][$key] === 0) {

                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $fileName = uniqid('doc_') . '.' . $ext;
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['case_files']['tmp_name'][$key], $filePath)) {
                    $stmt2 = $pdo->prepare("INSERT INTO case_documents 
                        (case_id, file_name, file_path, file_type, uploaded_by_user_id, upload_date)
                        VALUES (?, ?, ?, ?, ?, NOW())");

                    $stmt2->execute([
                        $_POST['case_id'],
                        $name,
                        'uploads/case_documents/' . $fileName,
                        $ext,
                        $userId
                    ]);
                }
            }
        }
    }

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Case updated!' : 'Update failed'
    ]);
    exit();
}

?>
