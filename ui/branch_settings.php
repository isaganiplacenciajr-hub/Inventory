<?php
// Branch Settings Page for Admin Panel
require_once 'connectdb.php';
session_start();
if (!isset($_SESSION['userid']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('location:../index.php');
    exit;
}
require_once 'header.php';

// Branch options
$branches = [
    'Matain Branch' => [
        'address' => 'Matain, Subic, Zambales',
        'display' => 'Matain Branch',
        'full' => 'Matain Branch – Matain, Subic, Zambales',
    ],
    'San Isidro Main Branch' => [
        'address' => 'National Govic Highway, San Isidro, Zambales',
        'display' => 'San Isidro Main Branch',
        'full' => 'San Isidro Main Branch – National Govic Highway, San Isidro, Zambales',
    ],
    'Sawmill Branch' => [
        'address' => 'Sawmill Area, Subic, Zambales',
        'display' => 'Sawmill Branch',
        'full' => 'Sawmill Branch – Sawmill Area, Subic, Zambales',
    ],
];

// Load current branch from file or default
$branchFile = __DIR__ . '/../config/active_branch.json';
if (!file_exists(dirname($branchFile))) {
    mkdir(dirname($branchFile), 0777, true);
}
$activeBranch = array_keys($branches)[0];
if (file_exists($branchFile)) {
    $data = json_decode(file_get_contents($branchFile), true);
    if (isset($data['branch']) && isset($branches[$data['branch']])) {
        $activeBranch = $data['branch'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['branch'])) {
    $selected = $_POST['branch'];
    if (isset($branches[$selected])) {
        file_put_contents($branchFile, json_encode(['branch' => $selected]));
        $activeBranch = $selected;
        $_SESSION['branch'] = $selected;
        $msg = 'Branch updated!';
    }
}

// Keep session branch in sync with current config
if (empty($_SESSION['branch']) || $_SESSION['branch'] !== $activeBranch) {
    $_SESSION['branch'] = $activeBranch;
}

?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6"><h2 class="m-0">Branch Settings</h2></div>
                <div class="col-sm-6"><ol class="breadcrumb float-sm-right"></ol></div>
            </div>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="col-lg-8 mx-auto">
                <?php if (!empty($msg)): ?>
                    <div class="alert alert-success"><?php echo $msg; ?></div>
                <?php endif; ?>
                <div class="card card-primary card-outline">
                    <div class="card-header"><h5 class="m-0">Branch Settings</h5></div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="branch">Branch Name</label>
                                <select class="form-control" id="branch" name="branch" onchange="updateAddress()">
                                    <?php foreach ($branches as $key => $b): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>" <?php if ($activeBranch === $key) echo 'selected'; ?>><?php echo htmlspecialchars($b['display']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Branch Address</label>
                                <input type="text" class="form-control" id="branch_address" value="<?php echo htmlspecialchars($branches[$activeBranch]['address']); ?>" readonly>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<script>
const branchData = <?php echo json_encode($branches); ?>;
function updateAddress() {
    var sel = document.getElementById('branch');
    var addr = branchData[sel.value]['address'];
    document.getElementById('branch_address').value = addr;
}

document.addEventListener('DOMContentLoaded', function() {
    var switchControl = document.getElementById('darkModeSwitch');
    if (!switchControl) return;
    var saved = localStorage.getItem('darkMode') === 'true';
    switchControl.checked = saved;
    switchControl.addEventListener('change', function() {
      if (this.checked) {
        document.body.classList.add('dark-mode');
        localStorage.setItem('darkMode', 'true');
      } else {
        document.body.classList.remove('dark-mode');
        localStorage.setItem('darkMode', 'false');
      }
    });
});
</script>

