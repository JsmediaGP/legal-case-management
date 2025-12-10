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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link href="../assets/css/style.css" rel="stylesheet">

    <style>
        .btn-navy { background:#0A2D5C; color:white; }
        .btn-gold { background:#C9A227; color:white; font-weight:600; }
        .bg-navy { background:#0A2D5C !important; }
        .table thead th { white-space: nowrap; }
    </style>
</head>

<body class="bg-light">

<?php include '../engine/inc/sidebar.php'; ?>

<div class="topbar d-flex align-items-center justify-content-between p-3">
    <h4 class="mb-0 text-navy fw-bold">User Management</h4>

    <?php if ($isHead): ?>
    <button class="btn btn-gold px-4" data-bs-toggle="modal" data-bs-target="#addModal">
        ADD USER
    </button>
    <?php endif; ?>
</div>

<div class="main-content p-3">

    <div class="card shadow border-0">
        <div class="card-body p-0">
            <table id="usersTable" class="table table-hover mb-0">
                <thead class="bg-navy text-white">
                    <tr>
                        <th>Staff ID</th>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th style="width:180px">Actions</th>
                    </tr>
                </thead>
                <tbody>

                <?php
                $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

                foreach ($users as $u):
                ?>
                <tr>
                    <td><?= htmlspecialchars($u['staff_id']) ?></td>
                    <td><?= htmlspecialchars($u['first_name']." ".$u['last_name']) ?></td>
                    <td>
                        <span class="badge bg-primary"><?= htmlspecialchars(ucfirst($u['designation'])) ?></span>
                    </td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone']) ?></td>

                    <td>
                        <span class="badge bg-<?= $u['status']=='active' ? 'success' : 'secondary' ?>">
                            <?= htmlspecialchars($u['status']) ?>
                        </span>
                    </td>

                    <td>
                        <button class="btn btn-sm btn-info edit-btn"
                                data-id="<?= $u['id'] ?>">
                            Edit
                        </button>

                        <?php if ($isHead): ?>

                            <button class="btn btn-sm btn-warning status-btn"
                                    data-id="<?= $u['id'] ?>"
                                    data-status="<?= $u['status'] ?>">
                                <?= $u['status']==='active' ? 'Deactivate' : 'Activate' ?>
                            </button>

                            <button class="btn btn-sm btn-dark reset-btn"
                                    data-id="<?= $u['id'] ?>">
                                Reset
                            </button>

                            <button class="btn btn-sm btn-danger delete-btn"
                                    data-id="<?= $u['id'] ?>">
                                Delete
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

<!-- ============================= -->
<!-- ADD USER MODAL -->
<!-- ============================= -->
<div class="modal fade" id="addModal">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header bg-navy text-white">
        <h5 class="modal-title">Add New User</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form id="addUserForm">
        <div class="modal-body">

            <label>Staff ID</label>
            <input type="text" id="add_staff_id" name="staff_id" class="form-control" readonly required>

            <label class="mt-2">First Name</label>
            <input type="text" name="first_name" class="form-control" required>

            <label class="mt-2">Last Name</label>
            <input type="text" name="last_name" class="form-control" required>

            <label class="mt-2">Email</label>
            <input type="email" name="email" class="form-control">

            <label class="mt-2">Phone</label>
            <input type="text" name="phone" class="form-control">

            <label class="mt-2">Designation</label>
            <select id="add_designation" name="designation" class="form-select" required>
                <option value="">-- Select --</option>
                <option value="lawyer">Lawyer</option>
                <option value="principal">Principal</option>
                <option value="headofchamber">Head of Chamber</option>
            </select>

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

<!-- ============================= -->
<!-- EDIT USER MODAL -->
<!-- ============================= -->
<div class="modal fade" id="editModal">
  <div class="modal-dialog">
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
            <button class="btn btn-gold">Save Changes</button>
        </div>
      </form>

    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function() {

    let table = $('#usersTable').DataTable({ pageLength: 10 });

    //---------------------------------------------------------
    // 1. AUTO-GENERATE STAFF ID
    //---------------------------------------------------------
    $('#add_designation').change(function () {
        let des = $(this).val();

        if (!des) return;

        let prefix = "";
        if (des === "headofchamber") prefix = "HOC";
        if (des === "principal") prefix = "PRN";
        if (des === "lawyer") prefix = "LWR";

        $.post("../engine/core/UserHandler.php", 
        { 
            action: "generate_staff_id",
            prefix: prefix
        }, 
        function(res){
            console.log(res);
            if (res.success) {
                $('#add_staff_id').val(res.staff_id);
            } else {
                Swal.fire("Error", res.message, "error");
            }
        }, "json")
        .fail(function(xhr){
            console.log(xhr.responseText);
            Swal.fire("Error", "Server error generating staff ID", "error");
        });
    });


    //---------------------------------------------------------
    // 2. ADD USER (NOW SHOWS SWEETALERT + CLOSES MODAL)
    //---------------------------------------------------------
    $("#addUserForm").submit(function(e){
    e.preventDefault();

    $.post("../engine/core/UserHandler.php", $(this).serialize(), function(res){

        $("#addModal").modal("hide"); // Close modal

        setTimeout(() => {
            if (res.success){
                Swal.fire({
                    title: "User Added Successfully",
                    text: res.message,
                    icon: "success",
                    confirmButtonText: "OK"
                }).then(() => {
                    location.reload();
                });
            } 
            else {
                Swal.fire({
                    title: "Error",
                    text: res.message,
                    icon: "error",
                    confirmButtonText: "OK"
                });
            }
        }, 200);

    }, "json");
});




    //---------------------------------------------------------
    // 3. LOAD USER FOR EDIT
    //---------------------------------------------------------
    $(".edit-btn").click(function(){
        let id = $(this).data("id");

        $.post("../engine/core/UserHandler.php",
        { action:"get_user", id:id },
        function(res){

            if(res.success){
                $("#edit_id").val(res.data.id);
                $("#edit_first").val(res.data.first_name);
                $("#edit_last").val(res.data.last_name);
                $("#edit_email").val(res.data.email);
                $("#edit_phone").val(res.data.phone);
                $("#edit_designation").val(res.data.designation);

                $("#editModal").modal("show");
            } else {
                Swal.fire("Error", res.message, "error");
            }

        }, "json")
        .fail(function(xhr){
            console.log(xhr.responseText);
            Swal.fire("Error","Could not load user", "error");
        });
    });


    //---------------------------------------------------------
    // 4. SAVE EDIT
    //---------------------------------------------------------
   $("#editUserForm").submit(function(e){
    e.preventDefault();

    $.post("../engine/core/UserHandler.php", $(this).serialize(), function(res){

        $("#editModal").modal("hide");

        setTimeout(() => {
            if (res.success){
                Swal.fire({
                    title: "User Updated Successfully",
                    text: res.message,
                    icon: "success",
                    confirmButtonText: "OK"
                }).then(() => {
                    location.reload();
                });
            } 
            else {
                Swal.fire({
                    title: "Error",
                    text: res.message,
                    icon: "error",
                    confirmButtonText: "OK"
                });
            }
        }, 200);

    }, "json");
});



    //---------------------------------------------------------
    // 5. TOGGLE STATUS
    //---------------------------------------------------------
    $(".status-btn").click(function(){
        let id = $(this).data("id");

        $.post("../engine/core/UserHandler.php",
        { action:"toggle_status", id:id },
        function(res){
            Swal.fire("Updated", res.message, "success")
                .then(()=>location.reload());
        }, "json");
    });


    //---------------------------------------------------------
    // 6. RESET PASSWORD
    //---------------------------------------------------------
    $(".reset-btn").click(function(){
        let id = $(this).data("id");

        Swal.fire({
            title: "Reset password to 'password'?",
            icon: "warning",
            showCancelButton: true
        }).then(result=>{
            if(!result.isConfirmed) return;

            $.post("../engine/core/UserHandler.php",
                { action:"reset_password", id:id },
                function(res){
                    Swal.fire("Done", res.message, "success");
                }, "json"
            );
        });
    });


    //---------------------------------------------------------
    // 7. DELETE USER
    //---------------------------------------------------------
    $(".delete-btn").click(function(){
        let id = $(this).data("id");

        Swal.fire({
            title: "Delete this user?",
            icon: "warning",
            showCancelButton: true
        }).then(result=>{
            if(!result.isConfirmed) return;

            $.post("../engine/core/UserHandler.php",
                { action:"delete_user", id:id },
                function(res){
                    Swal.fire("Deleted", res.message, "success")
                        .then(()=>location.reload());
                }, "json"
            );
        });
    });

});
</script>


</body>
</html>
