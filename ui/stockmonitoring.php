<?php
include_once 'connectdb.php';
session_start();

if ($_SESSION['useremail'] == "") {
    header('location:../index.php');
}

include_once 'header.php';

// Fetch products for monitoring
$products = [];
try {
    $stmt = $pdo->query('SELECT product, category, brand, COALESCE(stock,0) as stock FROM tbl_product ORDER BY stock ASC, product ASC');
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $products = [];
}

// Low stock threshold (default 5)
$lowThreshold = isset($_GET['low']) ? max(0, (int)$_GET['low']) : 5;
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Stock Monitoring</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right"></ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-body">
                            <form method="get" class="form-inline mb-3">
                                <label class="mr-2">Low stock threshold</label>
                                <input type="number" class="form-control mr-2" name="low" value="<?php echo (int)$lowThreshold; ?>" min="0" step="1">
                                <button class="btn btn-secondary" type="submit">Apply</button>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Product Code</th>
                                            <th>Category</th>
                                            <th>Brand</th>
                                            <th class="text-right">Stock</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!$products): ?>
                                            <tr><td colspan="5" class="text-center">No data</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($products as $p): ?>
                                                <?php
                                                $stock = (int)$p['stock'];
                                                $low = $stock <= $lowThreshold;
                                                ?>
                                                <tr <?php echo $low ? 'style="background-color:#fff3cd"' : ''; ?>>
                                                    <td><?php echo htmlspecialchars($p['product']); ?></td>
                                                    <td><?php echo htmlspecialchars($p['category']); ?></td>
                                                    <td><?php echo htmlspecialchars((string)$p['brand']); ?></td>
                                                    <td class="text-right"><?php echo number_format($stock); ?></td>
                                                    <td>
                                                        <?php if ($low): ?>
                                                            <span class="badge badge-warning">Low</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-success">OK</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>