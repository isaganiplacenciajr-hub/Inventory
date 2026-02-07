<?php
include_once 'connectdb.php';
include_once 'ArchiveManager.php';
session_start();

// Check if user is admin
if (!isset($_SESSION['userid']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit;
}

// Check if archive tables exist
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'tbl_invoice_archive'");
    if ($checkTable->rowCount() === 0) {
        // Tables don't exist, redirect to setup
        header("Location: archive_setup.php");
        exit;
    }
} catch (Exception $e) {
    // If error checking, still try to proceed
}

include_once "header.php";

$archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);
$stats = $archiveManager->getArchiveStatistics();

// Get current filter from URL
$currentStatus = $_GET['status'] ?? 'archived';
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Archive</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Archive</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      
      <!-- Statistics Cards -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?php echo $stats['total_archived']; ?></h3>
              <p>Archived Orders</p>
            </div>
            <div class="icon">
              <i class="fas fa-archive"></i>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="small-box bg-success">
            <div class="inner">
              <h3><?php echo $stats['total_restored']; ?></h3>
              <p>Restored Orders</p>
            </div>
            <div class="icon">
              <i class="fas fa-undo"></i>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="small-box bg-danger">
            <div class="inner">
              <h3><?php echo $stats['total_deleted']; ?></h3>
              <p>Permanently Deleted</p>
            </div>
            <div class="icon">
              <i class="fas fa-trash"></i>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3>₱<?php echo number_format($stats['archived_value'], 2); ?></h3>
              <p>Archived Value</p>
            </div>
            <div class="icon">
              <i class="fas fa-coins"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Archive Table -->
      <div class="row">
        <div class="col-lg-12">
          <div class="card card-primary card-outline">
            <div class="card-header">
              <h5 class="m-0">Archive Records</h5>
            </div>

            <!-- Status Filter Tabs -->
            <div class="card-body" style="padding-bottom: 0;">
              <ul class="nav nav-tabs" id="archiveTabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link <?php echo $currentStatus === 'archived' ? 'active' : ''; ?>" 
                     href="?status=archived" role="tab">Archived (<?php echo $stats['total_archived']; ?>)</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link <?php echo $currentStatus === 'restored' ? 'active' : ''; ?>" 
                     href="?status=restored" role="tab">Restored (<?php echo $stats['total_restored']; ?>)</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link <?php echo $currentStatus === 'permanently_deleted' ? 'active' : ''; ?>" 
                     href="?status=permanently_deleted" role="tab">Permanently Deleted (<?php echo $stats['total_deleted']; ?>)</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link <?php echo $currentStatus === 'all' ? 'active' : ''; ?>" 
                     href="?status=all" role="tab">All History</a>
                </li>
              </ul>
            </div>

            <div class="card-body">
              <table class="table table-striped table-hover" id="archiveTable">
                <thead>
                  <tr>
                    <th style="width: 80px;">Invoice ID</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Items</th>
                    <th>Deleted By</th>
                    <th>Deleted Date</th>
                    <th>Status</th>
                    <th style="width: 180px;">Actions</th>
                  </tr>
                </thead>
                <tbody id="archiveTableBody">
                  <tr><td colspan="8" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Invoice Details - <span id="detailInvoiceId"></span></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="detailsContent">
          <i class="fas fa-spinner fa-spin"></i> Loading...
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success">
        <h5 class="modal-title">Restore Archived Invoice</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to restore invoice <strong id="restoreInvoiceId"></strong>?</p>
        <p>This will move the transaction back to the active Order List.</p>
        <div class="form-group">
          <label>Notes (optional):</label>
          <textarea class="form-control" id="restoreNotes" rows="3" placeholder="Add any notes about this restoration..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="confirmRestoreBtn">Restore Invoice</button>
      </div>
    </div>
  </div>
</div>

<!-- Permanent Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger">
        <h5 class="modal-title">⚠️ Permanently Delete from Archive</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger">
          <strong>Warning!</strong> This action is <strong>IRREVERSIBLE</strong> and cannot be undone.
        </div>
        <p>Are you sure you want to permanently delete invoice <strong id="deleteInvoiceId"></strong> from the archive?</p>
        <div class="form-group">
          <label>Notes (required):</label>
          <textarea class="form-control" id="deleteNotes" rows="3" placeholder="Please provide reason for permanent deletion..."></textarea>
        </div>
        <div class="form-check mt-3">
          <input type="checkbox" class="form-check-input" id="confirmDelete">
          <label class="form-check-label" for="confirmDelete">
            I understand this action is permanent and cannot be reversed
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>Permanently Delete</button>
      </div>
    </div>
  </div>
</div>

<?php include_once 'footer.php'; ?>

<script>
// Global variables
let currentStatus = '<?php echo $currentStatus; ?>';
let selectedArchiveId = null;
let selectedInvoiceId = null;

// Load archive data
function loadArchiveData() {
  const tbody = document.getElementById('archiveTableBody');
  tbody.innerHTML = '<tr><td colspan="8" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';

  fetch(`api/get_archives.php?status=${currentStatus}`)
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">${data.message}</td></tr>`;
        return;
      }

      if (data.count === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">No records found</td></tr>`;
        return;
      }

      tbody.innerHTML = data.data.map(record => `
        <tr>
          <td><strong>${record.invoice_id}</strong></td>
          <td>${new Date(record.order_date).toLocaleDateString()}</td>
          <td>₱${parseFloat(record.total).toFixed(2)}</td>
          <td><span class="badge badge-info">${record.item_count}</span></td>
          <td><span class="badge ${record.deleted_by_user === 'Admin' ? 'badge-danger' : 'badge-primary'}">${record.deleted_by_user || 'Unknown'}</span></td>
          <td><small>${new Date(record.deleted_at).toLocaleString()}</small></td>
          <td>
            <span class="badge ${getStatusBadgeClass(record.archive_status)}">
              ${getStatusLabel(record.archive_status)}
            </span>
          </td>
          <td>
            <button class="btn btn-sm btn-info" onclick="viewDetails(${record.archive_id}, ${record.invoice_id})">
              <i class="fas fa-eye"></i> Details
            </button>
            ${record.archive_status === 'archived' ? `
              <button class="btn btn-sm btn-success" onclick="showRestoreModal(${record.archive_id}, ${record.invoice_id})">
                <i class="fas fa-undo"></i> Restore
              </button>
              <button class="btn btn-sm btn-danger" onclick="showDeleteModal(${record.archive_id}, ${record.invoice_id})">
                <i class="fas fa-trash"></i> Delete
              </button>
            ` : ''}
          </td>
        </tr>
      `).join('');

      // Initialize DataTable if not already done
      if (!$.fn.dataTable.isDataTable('#archiveTable')) {
        $('#archiveTable').DataTable({
          destroy: true,
          paging: true,
          searching: true,
          ordering: true,
          info: true,
          lengthChange: true
        });
      }
    })
    .catch(error => {
      console.error('Error:', error);
      tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Error loading data</td></tr>`;
    });
}

// Get status badge class
function getStatusBadgeClass(status) {
  const classes = {
    'archived': 'badge-warning',
    'restored': 'badge-success',
    'permanently_deleted': 'badge-danger'
  };
  return classes[status] || 'badge-secondary';
}

// Get status label
function getStatusLabel(status) {
  const labels = {
    'archived': 'Archived',
    'restored': 'Restored',
    'permanently_deleted': 'Deleted'
  };
  return labels[status] || status;
}

// View invoice details
function viewDetails(archiveId, invoiceId) {
  selectedArchiveId = archiveId;
  selectedInvoiceId = invoiceId;

  document.getElementById('detailInvoiceId').textContent = invoiceId;
  document.getElementById('detailsContent').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

  fetch(`api/get_archive_details.php?invoice_id=${invoiceId}`)
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        document.getElementById('detailsContent').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        return;
      }

      let html = `
        <table class="table table-sm table-bordered">
          <thead class="bg-light">
            <tr>
              <th>Product</th>
              <th style="width: 80px;">Qty</th>
              <th style="width: 100px;">Rate</th>
              <th style="width: 100px;">Total</th>
              <th style="width: 120px;">Service Type</th>
            </tr>
          </thead>
          <tbody>
      `;

      data.data.forEach(item => {
        html += `
          <tr>
            <td>${item.product_name}</td>
            <td class="text-center">${item.qty}</td>
            <td class="text-right">₱${parseFloat(item.rate).toFixed(2)}</td>
            <td class="text-right">₱${parseFloat(item.saleprice).toFixed(2)}</td>
            <td>${item.servicetype || '-'}</td>
          </tr>
        `;
      });

      html += `
          </tbody>
        </table>
        <div class="alert alert-info">
          <small><i class="fas fa-lock"></i> This is an archived record and cannot be modified.</small>
        </div>
      `;

      document.getElementById('detailsContent').innerHTML = html;
    })
    .catch(error => {
      console.error('Error:', error);
      document.getElementById('detailsContent').innerHTML = `<div class="alert alert-danger">Error loading details</div>`;
    });

  $('#detailsModal').modal('show');
}

// Show restore modal
function showRestoreModal(archiveId, invoiceId) {
  selectedArchiveId = archiveId;
  selectedInvoiceId = invoiceId;
  document.getElementById('restoreInvoiceId').textContent = invoiceId;
  document.getElementById('restoreNotes').value = '';
  $('#restoreModal').modal('show');
}

// Show delete modal
function showDeleteModal(archiveId, invoiceId) {
  selectedArchiveId = archiveId;
  selectedInvoiceId = invoiceId;
  document.getElementById('deleteInvoiceId').textContent = invoiceId;
  document.getElementById('deleteNotes').value = '';
  document.getElementById('confirmDelete').checked = false;
  document.getElementById('confirmDeleteBtn').disabled = true;
  $('#deleteModal').modal('show');
}

// Confirm delete checkbox
document.getElementById('confirmDelete')?.addEventListener('change', function() {
  document.getElementById('confirmDeleteBtn').disabled = !this.checked || !document.getElementById('deleteNotes').value;
});

document.getElementById('deleteNotes')?.addEventListener('input', function() {
  document.getElementById('confirmDeleteBtn').disabled = !document.getElementById('confirmDelete').checked || !this.value;
});

// Restore invoice
document.getElementById('confirmRestoreBtn')?.addEventListener('click', function() {
  const notes = document.getElementById('restoreNotes').value;
  
  const formData = new FormData();
  formData.append('archive_id', selectedArchiveId);
  formData.append('notes', notes);

  fetch('api/restore_archive.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: data.message,
        timer: 2000
      }).then(() => {
        $('#restoreModal').modal('hide');
        loadArchiveData();
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: data.message
      });
    }
  })
  .catch(error => {
    console.error('Error:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'An error occurred during restoration'
    });
  });
});

// Permanently delete invoice
document.getElementById('confirmDeleteBtn')?.addEventListener('click', function() {
  const notes = document.getElementById('deleteNotes').value;
  
  const formData = new FormData();
  formData.append('archive_id', selectedArchiveId);
  formData.append('notes', notes);

  fetch('api/delete_archive.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Permanently Deleted!',
        text: data.message,
        timer: 2000
      }).then(() => {
        $('#deleteModal').modal('hide');
        loadArchiveData();
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: data.message
      });
    }
  })
  .catch(error => {
    console.error('Error:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'An error occurred during deletion'
    });
  });
});

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
  loadArchiveData();
});
</script>

<style>
  .archive-read-only {
    background-color: #f0f0f0;
    opacity: 0.85;
  }
  
  .badge {
    padding: 6px 10px;
    font-size: 11px;
  }
</style>
