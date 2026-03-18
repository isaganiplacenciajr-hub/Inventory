<?php
// system_backup.php: Standalone admin page for system file backup
session_start();
if (!isset($_SESSION['userid'])) {
    header('location:../index.php');
    exit;
}
$currentPage = 'system_backup.php';
require_once 'header.php';
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>System File Backup</h1>
        </div>
      </div>
    </div>
  </section>
  <section class="content">
    <div class="container-fluid">
      <div class="card card-primary card-outline">
        <div class="card-header"><h3 class="card-title">Backup System Files</h3></div>
        <div class="card-body">
          <p>Create a backup of all system files (PHP, CSS, JS, images, and other project files) to prevent data loss.</p>
          <div id="backupStatusMsg" class="mb-2"></div>
          <button type="button" class="btn btn-primary" id="btnCreateBackup">Create Backup</button>
        </div>
      </div>
    </div>
  </section>
</div>




<?php require_once 'footer.php'; ?>
<script>
$(function(){
  $('#btnCreateBackup').on('click', function(){
    var $btn = $(this);
    $btn.prop('disabled', true).text('Creating...');
    $('#backupStatusMsg').removeClass().text('');
    $.ajax({
      url: 'backup_system.php',
      method: 'POST',
      dataType: 'json',
      success: function(res){
        console.log('Backup response:', res);  // Debug
        if(res.success && res.filename){
          // Show success status immediately once ZIP is generated on server
          var msg = `Backup created successfully. Files: ${res.fileCount || '?'}; Size: ${(res.size / 1024 / 1024).toFixed(1)}MB.`;
          $('#backupStatusMsg').addClass('alert alert-success').text(msg);

          // Immediately report that zipper is ready
          $('#backupStatusMsg').append(' <strong>ZIP sent to download process.</strong>');

          var link = document.createElement('a');
          link.href = 'download_backup.php?file=' + encodeURIComponent(res.filename);
          link.download = res.filename;
          document.body.appendChild(link);
          link.click();
          setTimeout(function(){ document.body.removeChild(link); }, 1000);
        }else{
          $('#backupStatusMsg').addClass('alert alert-danger').text(res.message || 'Backup failed: ' + JSON.stringify(res));
        }
      },
      error: function(xhr){
        $('#backupStatusMsg').addClass('alert alert-danger').text('Backup failed.');
      },
      complete: function(){
        $btn.prop('disabled', false).text('Create Backup');
      }
    });
  });
});
</script>
