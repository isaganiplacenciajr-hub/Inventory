<?php
include_once 'connectdb.php';
session_start();

if (empty($_SESSION['userid']) || ($_SESSION['role'] ?? '') === 'Admin') {
    header('Location: ../index.php');
    exit;
}

$status = '';
$status_code = '';

if (isset($_POST['btn_update_password'])) {
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $status = 'Please complete all password fields.';
        $status_code = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $status = 'New Password and Confirm Password do not match.';
        $status_code = 'error';
    } elseif (strlen($newPassword) < 8) {
        $status = 'New Password must be at least 8 characters.';
        $status_code = 'error';
    } else {
        $stmt = $pdo->prepare('SELECT userpassword FROM tbl_user WHERE userid = :userid LIMIT 1');
        $stmt->execute([':userid' => $_SESSION['userid']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $status = 'User not found.';
            $status_code = 'error';
        } else {
            $stored = $user['userpassword'];
            $currentMatch = false;

            if (password_needs_rehash($stored, PASSWORD_DEFAULT) || password_verify($currentPassword, $stored)) {
                // if hashed or rehash needed, check hash
                $currentMatch = password_verify($currentPassword, $stored);
            } elseif ($currentPassword === $stored) {
                // fallback for plain-text legacy password
                $currentMatch = true;
            }

            if (!$currentMatch) {
                $status = 'Current password is incorrect.';
                $status_code = 'error';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $pdo->prepare('UPDATE tbl_user SET userpassword = :pass WHERE userid = :userid');
                $update->execute([':pass' => $hash, ':userid' => $_SESSION['userid']]);

                $status = 'Password successfully updated.';
                $status_code = 'success';
            }
        }
    }
}

include_once 'headeruser.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Settings</h1>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 mb-3">
          <div class="card" style="max-width: 900px; margin: 0 auto;">
            <div class="card-header bg-info">
              <h3 class="card-title">Change Password</h3>
            </div>
            <div class="card-body">
              <form method="post" id="changePasswordForm">
                <div class="form-group">
                  <label for="current_password">Current Password</label>
                  <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                  <label for="new_password">New Password</label>
                  <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                  <small class="form-text text-muted">Minimum 8 characters.</small>
                </div>
                <div class="form-group">
                  <label for="confirm_password">Confirm New Password</label>
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                <button type="submit" name="btn_update_password" class="btn btn-success">Update Password</button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 mb-3">
          <div class="card" style="max-width: 900px; margin: 0 auto;">
            <div class="card-header bg-dark">
              <h3 class="card-title">Dark Mode</h3>
            </div>
            <div class="card-body">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="darkModeSwitch" aria-label="Enable Dark Mode">
                <label class="custom-control-label" for="darkModeSwitch">Enable Dark Mode</label>
              </div>
              <p class="text-muted mt-2 mb-0">Dark mode preference is stored in this browser and will persist.</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<style>
.card-title { font-weight: 600; }
.btn:hover { opacity: 0.9; transition: opacity 0.2s; }
.alert-custom { margin-top: 10px; }
@media (max-width: 768px) {
  .content-wrapper { padding: 1rem; }
}
</style>

<script>
  // Dark mode persistence in settings page
  document.addEventListener('DOMContentLoaded', function() {
    var enabled = localStorage.getItem('darkMode') === 'true';
    var sw = document.getElementById('darkModeSwitch');
    if (sw) {
      sw.checked = enabled;
      toggleDarkMode(enabled);
      sw.addEventListener('change', function() {
        toggleDarkMode(this.checked);
        localStorage.setItem('darkMode', this.checked ? 'true' : 'false');
      });
    }
  });

  function toggleDarkMode(enabled) {
    if (enabled) {
      document.body.classList.add('dark-mode');
    } else {
      document.body.classList.remove('dark-mode');
    }
  }
</script>

<?php if ($status !== ''): ?>
<script>
Swal.fire({
  icon: '<?php echo $status_code; ?>',
  title: '<?php echo htmlspecialchars($status, ENT_QUOTES); ?>',
  showConfirmButton: false,
  timer: 2500
});
</script>
<?php endif; ?>

<?php include_once 'footer.php'; ?>