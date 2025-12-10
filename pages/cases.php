<?php
session_start();
require_once '../engine/core/Auth.php';
Auth::requireLogin();
$user = Auth::user();
$pdo = db();

// Debug mode (optional, useful during testing)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cases • ChamberSync</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .btn-navy { background:#0A2D5C; color:white; border:none; }
        .btn-navy:hover { background:#071F44; }
        .btn-gold { background:#C9A227; color:white; font-weight:600; }
        .btn-gold:hover { background:#b28e1e; }
        .modal-dialog { margin-left:280px; max-width:calc(100% - 280px); }
        .form-control[readonly] { background:#f8f9fa; }
    </style>
</head>

<body class="bg-light">

<?php include '../engine/inc/sidebar.php'; ?>

<div class="topbar d-flex align-items-center justify-content-between">
    <h4 class="mb-0 text-navy fw-bold">Case Management</h4>
    <button class="btn btn-gold px-5 py-3 shadow" data-bs-toggle="modal" data-bs-target="#caseModal">NEW CASE</button>
</div>

<div class="main-content">
    <div class="card shadow border-0">
        <div class="card-body p-0">
            <table id="casesTable" class="table table-hover mb-0">
                <thead class="bg-navy text-white">
                    <tr>
                        <th>Case No.</th>
                        <th>Parties</th>
                        <th>Category</th>
                        <th>Lawyer</th>
                        <th>Status</th>
                        <th>Next Hearing</th>
                        <th>Files</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $cases = $pdo->query("
                        SELECT c.*, u.first_name, u.last_name,
                        (SELECT COUNT(*) FROM case_documents d WHERE d.case_id = c.case_id) AS doc_count
                        FROM cases c 
                        LEFT JOIN users u ON c.assigned_lawyer_id = u.id
                        ORDER BY c.created_at DESC
                    ")->fetchAll();

                    foreach ($cases as $c): ?>
                    <tr>
                        <td><strong class="text-navy"><?= $c['case_number'] ?></strong></td>
                        <td><?= substr($c['complainants'],0,30) ?> vs <?= substr($c['respondents'],0,30) ?>...</td>
                        <td><span class="badge bg-primary"><?= ucfirst($c['category']) ?></span></td>
                        <td><?= $c['first_name'] ? $c['first_name'].' '.$c['last_name'] : '—' ?></td>
                        <td><span class="badge bg-warning text-dark"><?= ucwords(str_replace('-',' ',$c['current_status'])) ?></span></td>
                        <td><?= $c['next_hearing'] ? date('d M Y', strtotime($c['next_hearing'])) : '—' ?></td>
                        <td><span class="badge bg-success"><?= $c['doc_count'] ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-info view-btn me-1" data-id="<?= $c['case_id'] ?>">View</button>
                            <button class="btn btn-sm btn-warning edit-btn me-1" data-case='<?= json_encode($c) ?>'>Edit</button>
                            <?php if (Auth::isHeadOfChamber()): ?>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $c['case_id'] ?>">Delete</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- NEW CASE MODAL -->
<div class="modal fade" id="caseModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header bg-navy text-white">
                <h5 class="modal-title fw-bold">Add New Case</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="caseForm" enctype="multipart/form-data">
                <div class="modal-body">

                    <input type="hidden" id="case_id" name="case_id">

                    <div class="row g-3">

                        <div class="col-md-5">
                            <label>Category</label>
                            <select id="category" name="category" class="form-select" required>
                                <option value="civil">Civil</option>
                                <option value="criminal">Criminal</option>
                                <option value="family">Family</option>
                                <option value="corporate">Corporate</option>
                                <option value="land">Land Dispute</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label>Case Number</label>
                            <input type="text" id="case_number" name="case_number" class="form-control" readonly>
                        </div>

                        <div class="col-md-3">
                            <label>Billing Amount</label>
                            <input type="text" id="billing_amount" name="billing_amount" class="form-control" readonly>
                        </div>

                        <div class="col-12">
                            <label>Complainants</label>
                            <textarea id="complainants" name="complainants" class="form-control" rows="3" required></textarea>
                        </div>

                        <div class="col-12">
                            <label>Respondents</label>
                            <textarea id="respondents" name="respondents" class="form-control" rows="3" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label>Petition Date</label>
                            <input type="date" id="petition_date" name="petition_date" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label>Next Hearing</label>
                            <input type="date" id="next_hearing" name="next_hearing" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label>Assign Lawyer</label>
                            <select id="assigned_lawyer_id" name="assigned_lawyer_id" class="form-select">
                                <option value="">-- Select Lawyer --</option>
                                <?php 
                                foreach ($pdo->query("SELECT id, staff_id, first_name, last_name FROM users WHERE designation IN ('lawyer','principal')") as $l): ?>
                                    <option value="<?= $l['id'] ?>"><?= $l['staff_id'] ?> - <?= $l['first_name'] ?> <?= $l['last_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label>Upload Files</label>
                            <input type="file" name="case_files[]" class="form-control" multiple>
                        </div>

                        <div class="col-12">
                            <label>Notes</label>
                            <textarea id="hearing_notes" name="hearing_notes" class="form-control" rows="3"></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold px-5">Create Case</button>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {

    $('#casesTable').DataTable({ pageLength: 10 });

    const billingMap = { 
        civil:150000,
        criminal:250000,
        family:120000,
        corporate:750000,
        land:350000 
    };

    // Auto-generate billing + case number on category change
    $('#category').change(function() {

        const cat = $(this).val();
        $('#billing_amount').val('₦' + billingMap[cat].toLocaleString());

        $.post('../engine/core/CaseHandler.php',
        { action: 'generate', category: cat },
        function(num) {
            $('#case_number').val(num);
        });

    }).trigger('change');

    // SUBMIT FORM (CREATE CASE)
    $('#caseForm').submit(function(e) {
        e.preventDefault();

        let formData = new FormData(this);
        formData.append('action', 'create');

        $.ajax({
            url: '../engine/core/CaseHandler.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,

            success: function(response) {

                console.log("RAW RESPONSE:");
                console.log(response);

                let res = {};

                try {
                    res = JSON.parse(response);
                } catch (e) {
                    Swal.fire('Error', 'Server returned invalid JSON', 'error');
                    return;
                }

                if (res.success) {
                    Swal.fire('Success!', res.message, 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error!', res.message || 'Failed to create case', 'error');
                }
            },

            error: function(xhr) {
                Swal.fire('Error', 'AJAX request failed', 'error');
                console.log(xhr.responseText);
            }
        });
    });

});
</script>

</body>
</html>
