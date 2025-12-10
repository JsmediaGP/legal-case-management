<?php
session_start();
require_once '../engine/core/Auth.php';
require_once '../engine/config/db.php';

Auth::requireLogin();
$user = Auth::user();
$pdo = db();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile • ChamberSync</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

    <!-- App Styles -->
    <link href="../assets/css/style.css" rel="stylesheet">

</head>

<body class="bg-light">

<?php include '../engine/inc/sidebar.php'; ?>

<!-- TOPBAR -->
<div class="topbar d-flex align-items-center justify-content-between p-3">
    <h4 class="mb-0 text-navy fw-bold">My Profile</h4>
</div>


<!-- MAIN CONTENT -->
<div class="main-content p-3">

    <div class="row">

        <!-- PERSONAL INFO -->
        <div class="col-md-6 mb-4">
            <div class="card shadow border-0">
                <div class="card-header bg-navy text-white">
                    <h5 class="mb-0">Personal Information</h5>
                </div>

                <div class="card-body">

                    <form id="profileForm">

                        <label>Staff ID</label>
                        <input type="text" class="form-control mb-2" value="<?= htmlspecialchars($user['staff_id']) ?>" readonly>

                        <label>Designation</label>
                        <input type="text" class="form-control mb-2" value="<?= ucfirst($user['designation']) ?>" readonly>

                        <label>First Name</label>
                        <input type="text" class="form-control mb-2" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>

                        <label>Last Name</label>
                        <input type="text" class="form-control mb-2" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>

                        <label>Email</label>
                        <input type="email" class="form-control mb-2" name="email" value="<?= htmlspecialchars($user['email']) ?>">

                        <label>Phone</label>
                        <input type="text" class="form-control mb-3" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">

                        <input type="hidden" name="action" value="update_profile">

                        <button class="btn btn-gold w-100">Save Changes</button>
                    </form>

                </div>
            </div>
        </div>


        <!-- PASSWORD CHANGE -->
        <div class="col-md-6 mb-4">
            <div class="card shadow border-0">
                <div class="card-header bg-navy text-white">
                    <h5 class="mb-0">Change Password</h5>
                </div>

                <div class="card-body">

                    <form id="passwordForm">

                        <label>Current Password</label>
                        <input type="password" class="form-control mb-2" name="old_password" required>

                        <label>New Password</label>
                        <input type="password" class="form-control mb-2" name="new_password" required>

                        <label>Confirm New Password</label>
                        <input type="password" class="form-control mb-3" name="confirm_password" required>

                        <input type="hidden" name="action" value="change_password">

                        <button class="btn btn-navy w-100">Update Password</button>
                    </form>

                </div>
            </div>
        </div>

    </div>

</div>



<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
$(function(){

    /* ------------------------------------
       UPDATE PROFILE
    ------------------------------------ */
    $('#profileForm').submit(function(e){
        e.preventDefault();

        $.post('../engine/core/ProfileHandler.php', $(this).serialize(), function(res){

            Swal.fire({
                title: res.success ? "Profile Updated" : "Error",
                text: res.message,
                icon: res.success ? "success" : "error"
            }).then(()=>{
                if(res.success) location.reload();
            });

        }, 'json');
    });


    /* ------------------------------------
       CHANGE PASSWORD
    ------------------------------------ */
    $('#passwordForm').submit(function(e){
        e.preventDefault();

        $.post('../engine/core/ProfileHandler.php', $(this).serialize(), function(res){

            Swal.fire({
                title: res.success ? "Password Updated" : "Error",
                text: res.message,
                icon: res.success ? "success" : "error"
            });

        }, 'json');
    });

});
</script>

</body>
</html>
