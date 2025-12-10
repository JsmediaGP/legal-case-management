<?php
session_start();
require_once '../engine/core/Auth.php';
require_once '../engine/config/db.php';

Auth::requireLogin();
$user = Auth::user();
$pdo = db();

$isHead = Auth::isHeadOfChamber();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management • ChamberSync</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

    <!-- Style -->
    <link href="../assets/css/style.css" rel="stylesheet">

</head>

<body class="bg-light">

<?php include '../engine/inc/sidebar.php'; ?>

<!-- TOPBAR -->
<div class="topbar d-flex align-items-center justify-content-between p-3">
    <h4 class="mb-0 text-navy fw-bold">User Management</h4>

    <?php if ($isHead): ?>
        <button class="btn btn-gold px-4" data-bs-toggle="modal" data-bs-target="#addModal">
            ADD USER
        </button>
    <?php endif; ?>
</div>

<!-- CONTENT -->
<div class="main-content p-3">

    <div class="user-table-card">
        <div class="table-responsive">
            <table id="usersTable" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Staff ID</th>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th class="text-center" style="width:150px;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php
                $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

                foreach ($users as $u):
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($u['staff_id']) ?></strong></td>

                    <td class="table-username">
                        <?= htmlspecialchars($u['first_name']." ".$u['last_name']) ?>
                    </td>

                    <td>
                        <?php if ($u['designation'] == "lawyer"): ?>
                            <span class="badge-role badge-lawyer">Lawyer</span>
                        <?php elseif ($u['designation'] == "principal"): ?>
                            <span class="badge-role badge-principal">Principal</span>
                        <?php else: ?>
                            <span class="badge-role badge-hoc">Head of Chamber</span>
                        <?php endif; ?>
                    </td>

                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone']) ?></td>

                    <td>
                        <span class="status-chip <?= $u['status']=='active' ? 'status-active':'status-inactive' ?>">
                            <?= $u['status'] ?>
                        </span>
                    </td>

                    <td class="text-center">

                        <!-- EDIT -->
                        <button class="action-btn action-edit edit-btn" data-id="<?= $u['id'] ?>" title="Edit User">
                            <i class="fa fa-pen"></i>
                        </button>

                        <?php if ($isHead): ?>

                        <!-- STATUS -->
                        <button class="action-btn action-status status-btn"
                            data-id="<?= $u['id'] ?>" 
                            title="<?= $u['status']=='active' ? 'Deactivate':'Activate' ?>">
                            <i class="fa <?= $u['status']=='active' ? 'fa-user-slash':'fa-user-check' ?>"></i>
                        </button>

                        <!-- RESET -->
                        <button class="action-btn action-reset reset-btn" data-id="<?= $u['id'] ?>" title="Reset Password">
                            <i class="fa fa-key"></i>
                        </button>

                        <!-- DELETE -->
                        <button class="action-btn action-delete delete-btn" data-id="<?= $u['id'] ?>" title="Delete User">
                            <i class="fa fa-trash"></i>
                        </button>

                        <?php endif; ?>

                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>

            </table>
        </div>
    </div>

</div>



<!-- =======================================================
     ADD USER MODAL
======================================================= -->
<div class="modal fade" id="addModal">
  <div class="modal-dialog modal-md">
    <div class="modal-content">

      <div class="modal-header bg-navy text-white">
        <h5 class="modal-title">Add New User</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form id="addUserForm">
        <div class="modal-body">

            <label>Designation</label>
            <select id="add_designation" name="designation" class="form-select" required>
                <option value="">-- Select --</option>
                <option value="lawyer">Lawyer</option>
                <option value="principal">Principal</option>
                <option value="headofchamber">Head of Chamber</option>
            </select>

            <label class="mt-2">Staff ID</label>
            <input type="text" id="add_staff_id" name="staff_id" class="form-control" readonly required>

            <label class="mt-2">First Name</label>
            <input type="text" name="first_name" class="form-control" required>

            <label class="mt-2">Last Name</label>
            <input type="text" name="last_name" class="form-control" required>

            <label class="mt-2">Email</label>
            <input type="email" name="email" class="form-control">

            <label class="mt-2">Phone</label>
            <input type="text" name="phone" class="form-control">

            <input type="hidden" name="action" value="create_user">

        </div>

        <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-gold" type="submit">Add User</button>
        </div>

      </form>

    </div>
  </div>
</div>




<!-- =======================================================
     EDIT USER MODAL
======================================================= -->
<div class="modal fade" id="editModal">
  <div class="modal-dialog modal-md">
    <div class="modal-content">

      <div class="modal-header bg-navy text-white">
        <h5 class="modal-title">Edit User</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form id="editUserForm">
        <div class="modal-body">

            <input type="hidden" name="id" id="edit_id">

            <label>First Name</label>
            <input type="text" id="edit_first" name="first_name" class="form-control" required>

            <label class="mt-2">Last Name</label>
            <input type="text" id="edit_last" name="last_name" class="form-control" required>

            <label class="mt-2">Email</label>
            <input type="email" id="edit_email" name="email" class="form-control">

            <label class="mt-2">Phone</label>
            <input type="text" id="edit_phone" name="phone" class="form-control">

            <label class="mt-2">Designation</label>
            <select id="edit_designation" name="designation" class="form-select" required>
                <option value="lawyer">Lawyer</option>
                <option value="principal">Principal</option>
                <option value="headofchamber">Head of Chamber</option>
            </select>

            <input type="hidden" name="action" value="update_user">

        </div>

        <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-gold">Save</button>
        </div>

      </form>

    </div>
  </div>
</div>





<!-- SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
$(function () {

    let table = $('#usersTable').DataTable({
        pageLength: 10
    });

    /* --------------------------------------
       GENERATE STAFF ID
    -------------------------------------- */
    $('#add_designation').change(function () {
        let des = $(this).val();
        if (!des) return;

        let prefix = des === "headofchamber" ? "HOC"
                   : des === "principal" ? "PRN"
                   : "LWR";

        $.post("../engine/core/UserHandler.php",
        { action: "generate_staff_id", prefix: prefix },
        function (res) {
            if (res.success) {
                $("#add_staff_id").val(res.staff_id);
            } else {
                Swal.fire("Error", res.message, "error");
            }
        }, "json");
    });

    /* --------------------------------------
       ADD USER
    -------------------------------------- */
    $("#addUserForm").submit(function(e){
        e.preventDefault();

        $.post("../engine/core/UserHandler.php", $(this).serialize(), function(res){

            $("#addModal").modal("hide");

            setTimeout(() => {
                Swal.fire({
                    title: res.success ? "User Added" : "Error",
                    text: res.message,
                    icon: res.success ? "success" : "error"
                }).then(()=> res.success && location.reload());
            }, 200);

        }, "json");
    });

    /* --------------------------------------
       LOAD USER FOR EDIT
    -------------------------------------- */
    $(".edit-btn").click(function(){

        $.post("../engine/core/UserHandler.php",
        { action:"get_user", id:$(this).data('id') },
        function(res){

            if (!res.success) return Swal.fire("Error", res.message, "error");

            $("#edit_id").val(res.data.id);
            $("#edit_first").val(res.data.first_name);
            $("#edit_last").val(res.data.last_name);
            $("#edit_email").val(res.data.email);
            $("#edit_phone").val(res.data.phone);
            $("#edit_designation").val(res.data.designation);

            $("#editModal").modal("show");

        }, "json");

    });

    /* --------------------------------------
       UPDATE USER
    -------------------------------------- */
    $("#editUserForm").submit(function(e){
        e.preventDefault();

        $.post("../engine/core/UserHandler.php", $(this).serialize(), function(res){

            $("#editModal").modal("hide");

            setTimeout(()=>{
                Swal.fire({
                    title: res.success ? "Updated" : "Error",
                    text: res.message,
                    icon: res.success ? "success" : "error"
                }).then(()=> res.success && location.reload());
            },200);

        }, "json");
    });

    /* --------------------------------------
       TOGGLE STATUS
    -------------------------------------- */
    $(".status-btn").click(function(){
        $.post("../engine/core/UserHandler.php",
        { action:"toggle_status", id:$(this).data('id') },
        function(res){
            Swal.fire("Updated", res.message, "success")
                .then(()=>location.reload());
        }, "json");
    });

    /* --------------------------------------
       RESET PASSWORD
    -------------------------------------- */
    $(".reset-btn").click(function(){

        Swal.fire({
            title: "Reset password to 'password'?",
            icon: "warning",
            showCancelButton: true
        }).then(result=>{
            if(!result.isConfirmed) return;

            $.post("../engine/core/UserHandler.php",
            { action:"reset_password", id:$(this).data('id') },
            function(res){
                Swal.fire("Done", res.message, "success");
            }, "json");
        });

    });

    /* --------------------------------------
       DELETE USER
    -------------------------------------- */
    $(".delete-btn").click(function(){

        Swal.fire({
            title: "Delete this user?",
            icon: "warning",
            showCancelButton: true
        }).then(result=>{
            if(!result.isConfirmed) return;

            $.post("../engine/core/UserHandler.php",
            { action:"delete_user", id:$(this).data('id') },
            function(res){
                Swal.fire("Deleted", res.message, "success")
                    .then(()=>location.reload());
            }, "json");
        });

    });

});
</script>

</body>
</html>
