<?php
include_once 'connectdb.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['useremail'] == "") {
    header('location:../index.php');
    exit;
}

if ($_SESSION['role'] == "Admin") {
    include_once "header.php";
} else {
    include_once "headeruser.php";
}
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Activity Log</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Activity</h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Module</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $stmt = $pdo->query("SELECT * FROM activity_logs ORDER BY datetime DESC LIMIT 500");
                                        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        if ($logs) {
                                            foreach ($logs as $log) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($log['datetime']) . "</td>";
                                                echo "<td>" . htmlspecialchars($log['user']) . "</td>";
                                                echo "<td>" . htmlspecialchars($log['action']) . "</td>";
                                                echo "<td>" . htmlspecialchars($log['module']) . "</td>";
                                                echo "<td>" . htmlspecialchars($log['description']) . "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='5' class='text-center'>No activity recorded yet</td></tr>";
                                        }
                                    } catch (Exception $e) {
                                        echo "<tr><td colspan='5' class='text-center text-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col-md-12 -->
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php
include_once "footer.php";
?>
