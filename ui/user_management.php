<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../plugins/sweetalert2/sweetalert2.min.js"></script>
<script>
$(function() {
	$('.delete-user').click(function() {
		var userid = $(this).data('id');
		Swal.fire({
			title: 'Delete User',
			text: 'Are you sure you want to delete this user?',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: 'Yes, Delete',
			cancelButtonText: 'Cancel'
		}).then((result) => {
			if (result.isConfirmed) {
				$.post('delete_user.php', {id: userid}, function(resp) {
					if (resp.success) {
						Swal.fire('Deleted!', 'User has been deleted.', 'success').then(() => location.reload());
					} else {
						Swal.fire('Error', resp.message || 'Failed to delete user.', 'error');
					}
				}, 'json');
			}
		});
	});
});
</script>
<?php
include_once 'connectdb.php';
session_start();
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Admin') {
		header('location:../index.php');
		exit;
}
include_once "header.php";
?>
<div class="content-wrapper">
	<section class="content pt-3">
		<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-lg-12">
					<div class="card card-primary card-outline">
						<div class="card-header d-flex justify-content-between align-items-center">
							<h5 class="m-0">User Management</h5>
							<button class="btn btn-success" data-toggle="modal" data-target="#addUserModal"><i class="fas fa-user-plus"></i> Add User</button>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-striped table-hover" id="table_userlist">
									<thead>
										<tr>
											<th>Username</th>
											<th>Full Name</th>
											<th>Role</th>
											<th>Status</th>
											<th>COD Self Completion Permission</th>
											<th>Permission Expiry</th>
											<th>Admin Consent</th>
											<th>Actions</th>
											<th>Notes</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$stmt = $pdo->prepare("SELECT * FROM tbl_user WHERE role = 'User'");
										$stmt->execute();
										$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
										foreach ($users as $user) {
											echo '<tr>';
											echo '<td>' . htmlspecialchars($user['username']) . '</td>';
											echo '<td>' . htmlspecialchars($user['fullname']) . '</td>';
											echo '<td>User</td>';
											echo '<td>' . ($user['status'] == 1 ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>') . '</td>';
											echo '<td>' . ($user['cod_self_completion_permission'] ? '<span class="badge badge-info">Enabled</span>' : '<span class="badge badge-secondary">Disabled</span>') . '</td>';
											// Format Permission Expiry nicely or show '-'
											if (!empty($user['cod_permission_expiry']) && $user['cod_permission_expiry'] !== '0000-00-00 00:00:00') {
												$expiry = date('Y-m-d H:i', strtotime($user['cod_permission_expiry']));
												echo '<td>' . htmlspecialchars($expiry) . '</td>';
											} else {
												echo '<td>-</td>';
											}
											echo '<td>' . ($user['admin_consent'] ? '<span class="badge badge-success">Confirmed</span>' : '<span class="badge badge-warning">Not Confirmed</span>') . '</td>';
											echo '<td>';
											echo '<a href="edit_user.php?id=' . $user['userid'] . '" class="btn btn-sm btn-primary mr-1"><i class="fas fa-edit"></i> Edit</a>';
											echo '<button class="btn btn-sm btn-danger delete-user" data-id="' . $user['userid'] . '"><i class="fas fa-trash"></i> Delete</button>';
											echo '</td>';
											echo '<td>' . htmlspecialchars($user['notes'] ?? '') . '</td>';
											echo '</tr>';
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<form method="post" action="add_user.php" id="addUserForm">
				<div class="modal-header">
					<h5 class="modal-title" id="addUserModalLabel">Add User</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label>Username</label>
						<input type="text" name="username" class="form-control" required>
					</div>
					<div class="form-group">
						<label>Full Name</label>
						<input type="text" name="fullname" class="form-control" required>
					</div>
					<div class="form-group">
						<label>Password</label>
						<input type="password" name="password" class="form-control" required>
					</div>
					<div class="form-group">
						<label>Status</label>
						<select name="status" class="form-control" required>
							<option value="1">Active</option>
							<option value="0">Inactive</option>
						</select>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-primary">Create User</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php include_once "footer.php"; ?>
