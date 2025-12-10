<?php
session_start();
require_once '../engine/core/Auth.php';
Auth::requireLogin();
$user = Auth::user();
$pdo = db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Cases • ChamberSync</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .btn-navy { background:#0A2D5C; color:white; border:none; }
        .btn-gold { background:#C9A227; color:white; font-weight:600; }
        .modal-dialog { margin-left:280px; max-width:calc(100% - 280px); }
        .file-chip { display:inline-flex; align-items:center; gap:.5rem; padding:.3rem .6rem; border-radius:.4rem; background:#f1f1f1; margin:.25rem; }
    </style>
</head>
<body class="bg-light">

<?php include '../engine/inc/sidebar.php'; ?>

<div class="topbar d-flex align-items-center justify-content-between p-3">
    <h4 class="mb-0 text-navy fw-bold">Case Management</h4>
    <button class="btn btn-gold px-4" data-bs-toggle="modal" data-bs-target="#caseModal">NEW CASE</button>
</div>

<div class="main-content p-3">
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

                foreach ($cases as $c):
                    $caseJson = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr>
                        <td><strong class="text-navy"><?= htmlspecialchars($c['case_number']) ?></strong></td>
                        <td><?= htmlspecialchars(substr($c['complainants'],0,30)) ?> vs <?= htmlspecialchars(substr($c['respondents'],0,30)) ?>...</td>
                        <td><span class="badge bg-primary"><?= htmlspecialchars(ucfirst($c['category'])) ?></span></td>
                        <td><?= $c['first_name'] ? htmlspecialchars($c['first_name'].' '.$c['last_name']) : '—' ?></td>
                        <td><span class="badge bg-warning text-dark"><?= htmlspecialchars(ucwords(str_replace('-',' ',$c['current_status']))) ?></span></td>
                        <td><?= $c['next_hearing'] ? date('d M Y', strtotime($c['next_hearing'])) : '—' ?></td>
                        <td><span class="badge bg-success"><?= (int)$c['doc_count'] ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-info view-btn me-1" data-id="<?= (int)$c['case_id'] ?>">View</button>
                            <button class="btn btn-sm btn-warning edit-btn me-1" data-case='<?= $caseJson ?>'>Update</button>
                            <?php if (Auth::isHeadOfChamber()): ?>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="<?= (int)$c['case_id'] ?>">Delete</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- CREATE CASE MODAL -->
<div class="modal fade" id="caseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-navy text-white">
        <h5 class="modal-title">Add New Case</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form id="caseForm" enctype="multipart/form-data">
        <div class="modal-body">
            <div class="row g-3">
                <input type="hidden" id="case_id" name="case_id">

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
                        <?php foreach ($pdo->query("SELECT id, staff_id, first_name, last_name FROM users WHERE designation IN ('lawyer','principal')") as $l): ?>
                            <option value="<?= (int)$l['id'] ?>"><?= htmlspecialchars($l['staff_id'].' - '.$l['first_name'].' '.$l['last_name']) ?></option>
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
            <button type="submit" id="caseSubmitBtn" class="btn btn-gold">Create Case</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- UPDATE CASE MODAL -->
<div class="modal fade" id="updateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-navy text-white">
        <h5 class="modal-title">Update Case</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form id="updateForm" enctype="multipart/form-data">
        <div class="modal-body">
            <input type="hidden" id="u_case_id" name="case_id">

            <div class="row g-3">
                <div class="col-md-4">
                    <label>Suit Number</label>
                    <input type="text" id="u_suit_number" name="suit_number" class="form-control">
                </div>

                <div class="col-md-4">
                    <label>Next Hearing</label>
                    <input type="date" id="u_next_hearing" name="next_hearing" class="form-control">
                </div>

                <div class="col-md-4">
                    <label>Case Status</label>
                    <select id="u_current_status" name="current_status" class="form-select">
                        <option value="pre-litigation">Pre-litigation</option>
                        <option value="in-court">In Court</option>
                        <option value="adjourned">Adjourned</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label>Payment Status</label>
                    <select id="u_payment_status" name="payment_status" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="partial">Partially Paid</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>

                <div class="col-12">
                    <label>Existing Files (reference)</label>
                    <div id="u_existing_files" class="p-2 border rounded"></div>
                </div>

                <div class="col-12">
                    <label>Upload Additional Files</label>
                    <input type="file" name="case_files[]" class="form-control" multiple>
                </div>

                <div class="col-12">
                    <label>Add Notes (appended to history)</label>
                    <textarea id="u_new_notes" name="new_notes" class="form-control" rows="4"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" id="updateSubmitBtn" class="btn btn-gold">Save Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- VIEW CASE MODAL (with timeline) -->
<div class="modal fade" id="viewCaseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-navy text-white">
        <h5 class="modal-title">Case Details & Timeline</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="v_main" class="mb-3">
            <h5 id="v_case_number" class="fw-bold text-navy"></h5>
            <p><strong>Category:</strong> <span id="v_category"></span></p>
            <p><strong>Complainants:</strong> <span id="v_complainants"></span></p>
            <p><strong>Respondents:</strong> <span id="v_respondents"></span></p>
            <p><strong>Assigned Lawyer:</strong> <span id="v_lawyer"></span></p>
            <p><strong>Billing:</strong> ₦<span id="v_billing"></span> &nbsp; <strong>Payment:</strong> <span id="v_payment"></span></p>
            <p><strong>Current Status:</strong> <span id="v_status"></span> &nbsp; <strong>Suit No:</strong> <span id="v_suit"></span></p>
            <p><strong>Next Hearing:</strong> <span id="v_next_hearing"></span></p>
            <hr>
            <h6>Main Files</h6>
            <div id="v_base_files" class="mb-3"></div>
        </div>

        <hr>

        <h5 class="mb-3">Case Timeline</h5>
        <div id="v_timeline"></div>
      </div>

    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function(){

    $('#casesTable').DataTable({ pageLength: 10 });

    const billingMap = { civil:150000, criminal:250000, family:120000, corporate:750000, land:350000 };

    // Category change - create case
    $('#category').change(function() {
        const cat = $(this).val();
        $('#billing_amount').val('₦' + billingMap[cat].toLocaleString());
        $.ajax({
            url: '../engine/core/CaseHandler.php',
            method: 'POST',
            data: { action: 'generate', category: cat },
            dataType: 'json'
        }).done(function(resp){
            // resp may be a string (JSON string) or {success:false,...}
            if (typeof resp === 'string') {
                $('#case_number').val(resp);
            } else if (resp && resp.length) {
                // if jsonResponse returned a JSON string it will appear as a string; fallback:
                $('#case_number').val(resp);
            } else {
                $('#case_number').val('');
            }
        }).fail(function(xhr){ console.error('Generate failed', xhr.responseText); });
    }).trigger('change');

    // CREATE CASE
    $('#caseForm').submit(function(e){
        e.preventDefault();
        let fd = new FormData(this);
        fd.append('action', 'create');

        $.ajax({
            url: '../engine/core/CaseHandler.php',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function(res){
            if (res && res.success) {
                Swal.fire('Success', res.message, 'success').then(()=> location.reload());
            } else {
                Swal.fire('Error', (res && res.message) ? res.message : 'Failed to create case', 'error');
            }
        }).fail(function(xhr){
            console.error('Create AJAX error', xhr.responseText);
            Swal.fire('Error', 'Request failed, check console', 'error');
        });
    });

    // VIEW case + timeline
    $(document).on('click', '.view-btn', function(){
        const caseId = $(this).data('id');
        $('#viewCaseModal').modal('show');
        $('#v_timeline').html('<div class="text-center py-4"><div class="spinner-border"></div></div>');
        $('#v_base_files').html('');

        $.ajax({
            url: '../engine/core/CaseHandler.php',
            method: 'POST',
            data: { action:'view', case_id: caseId },
            dataType: 'json'
        }).done(function(res){
            if (!res || !res.success) {
                Swal.fire('Error', (res && res.message) ? res.message : 'Failed to load case', 'error');
                return;
            }

            const c = res.case;
            $('#v_case_number').text(c.case_number);
            $('#v_category').text(c.category);
            $('#v_complainants').text(c.complainants);
            $('#v_respondents').text(c.respondents);
            $('#v_lawyer').text(c.first_name ? c.first_name + ' ' + c.last_name : '—');
            $('#v_billing').text(Number(c.billing_amount).toLocaleString());
            $('#v_payment').text(c.payment_status);
            $('#v_status').text(c.current_status);
            $('#v_suit').text(c.suit_number || '—');
            $('#v_next_hearing').text(c.next_hearing || '—');

            // base files
            let baseHtml = '';
            if (!res.base_files || res.base_files.length === 0) baseHtml = '<p>No files</p>';
            else {
                res.base_files.forEach(f=>{
                    baseHtml += `<div class="file-chip">${escapeHtml(f.file_name)} <a class="btn btn-sm btn-link" href="../${f.file_path}" target="_blank">Open</a></div>`;
                });
            }
            $('#v_base_files').html(baseHtml);

            // timeline
            let timelineHtml = '';
            if (!res.updates || res.updates.length === 0) timelineHtml = '<p>No updates yet.</p>';
            else {
                // map files by update_id
                const filesMap = {};
                (res.files || []).forEach(f => {
                    const uid = f.update_id ? f.update_id : 'base';
                    if (!filesMap[uid]) filesMap[uid] = [];
                    filesMap[uid].push(f);
                });

                res.updates.forEach(u => {
                    const date = new Date(u.created_at);
                    const dateStr = date.toLocaleString();
                    const updater = u.first_name ? u.first_name + ' ' + u.last_name : 'Unknown';
                    const status = u.status || '—';
                    const nh = u.next_hearing || '—';
                    const suit = u.suit_number || '—';
                    const notes = u.notes || '—';

                    timelineHtml += `
                        <div class="mb-3 p-3 border rounded">
                            <div class="d-flex justify-content-between mb-2">
                                <div><strong>${escapeHtml(dateStr)}</strong> &nbsp; <small>by ${escapeHtml(updater)}</small></div>
                                <div><span class="badge bg-secondary">${escapeHtml(status)}</span></div>
                            </div>
                            <p><strong>Next Hearing:</strong> ${escapeHtml(nh)} &nbsp; <strong>Suit No:</strong> ${escapeHtml(suit)}</p>
                            <p><strong>Notes:</strong><br>${escapeHtml(notes).replace(/\n/g,'<br>')}</p>
                            <div><strong>Files:</strong> <div class="mt-2">`;

                    const attached = filesMap[u.update_id] || [];
                    if (attached.length === 0) {
                        timelineHtml += '<div class="text-muted">No files for this update.</div>';
                    } else {
                        attached.forEach(f => {
                            timelineHtml += `<div class="file-chip">${escapeHtml(f.file_name)} <a class="btn btn-sm btn-link" href="../${f.file_path}" target="_blank">Open</a></div>`;
                        });
                    }

                    timelineHtml += `</div></div></div>`;
                });
            }

            $('#v_timeline').html(timelineHtml);

        }).fail(function(xhr){
            console.error('View AJAX error', xhr.responseText);
            Swal.fire('Error','Failed to load case (check console)','error');
        });
    });

    // OPEN update modal (prefill + load files)
    $(document).on('click', '.edit-btn', function(){
        let caseData = $(this).data('case');
        caseData = (typeof caseData === 'string') ? JSON.parse(caseData) : caseData;

        $('#u_case_id').val(caseData.case_id);
        $('#u_suit_number').val(caseData.suit_number);
        $('#u_next_hearing').val(caseData.next_hearing);
        $('#u_current_status').val(caseData.current_status || 'pre-litigation');
        $('#u_payment_status').val(caseData.payment_status || 'pending');
        $('#u_existing_files').html('<div class="text-muted">Loading files...</div>');

        // fetch files & updates for reference
        $.ajax({
            url: '../engine/core/CaseHandler.php',
            method: 'POST',
            data: { action: 'view', case_id: caseData.case_id },
            dataType: 'json'
        }).done(function(res){
            if (!res || !res.success) {
                $('#u_existing_files').html('<div class="text-danger">Failed to load files</div>');
                return;
            }

            let html = '';
            const base = res.base_files || [];
            if (base.length === 0) html += '<p class="text-muted">No base files.</p>';
            else base.forEach(f => {
                html += `<div class="file-chip">${escapeHtml(f.file_name)} <a href="../${f.file_path}" target="_blank" class="btn btn-sm btn-link">Open</a></div>`;
            });

            // files from updates
            const updates = res.updates || [];
            if (updates.length > 0) {
                html += '<hr><div class="text-muted">Files from previous updates:</div>';
                const filesByUpdate = {};
                (res.files || []).forEach(f => {
                    if (f.update_id) {
                        if (!filesByUpdate[f.update_id]) filesByUpdate[f.update_id] = [];
                        filesByUpdate[f.update_id].push(f);
                    }
                });
                updates.forEach(u => {
                    const attached = filesByUpdate[u.update_id] || [];
                    if (attached.length === 0) return;
                    html += `<div class="mt-2"><small><strong>Update on ${escapeHtml(new Date(u.created_at).toLocaleDateString())}</strong></small><div>`;
                    attached.forEach(f => {
                        html += `<div class="file-chip">${escapeHtml(f.file_name)} <a href="../${f.file_path}" target="_blank" class="btn btn-sm btn-link">Open</a></div>`;
                    });
                    html += `</div></div>`;
                });
            }

            $('#u_existing_files').html(html);
            $('#updateModal').modal('show');
        }).fail(function(xhr){
            console.error('Load files error', xhr.responseText);
            $('#u_existing_files').html('<div class="text-danger">Failed to load files</div>');
        });
    });

    // SUBMIT UPDATE
    $('#updateForm').submit(function(e){
        e.preventDefault();
        let fd = new FormData(this);
        fd.append('action','update');

        $.ajax({
            url: '../engine/core/CaseHandler.php',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function(res){
            if (res && res.success) {
                Swal.fire('Success', res.message, 'success').then(()=> location.reload());
            } else {
                Swal.fire('Error', (res && res.message) ? res.message : 'Update failed', 'error');
            }
        }).fail(function(xhr){
            console.error('Update AJAX error', xhr.responseText);
            Swal.fire('Error','Update failed (check console)','error');
        });
    });

    // DELETE CASE
    $(document).on('click', '.delete-btn', function(){
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete case?',
            text: 'This action is permanent.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: '../engine/core/CaseHandler.php',
                method: 'POST',
                data: { action: 'delete', case_id: id },
                dataType: 'json'
            }).done(function(res){
                if (res && res.success) {
                    Swal.fire('Deleted', res.message, 'success').then(()=> location.reload());
                } else {
                    Swal.fire('Error', (res && res.message) ? res.message : 'Delete failed', 'error');
                }
            }).fail(function(xhr){
                console.error('Delete AJAX error', xhr.responseText);
                Swal.fire('Error','Delete failed (check console)','error');
            });
        });
    });

    // small helper to escape text for injection into HTML
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

});
</script>
</body>
</html>
