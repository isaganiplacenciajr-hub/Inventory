<?php
require 'ui/connectdb.php';
$id = 293;
$stmt = $pdo->prepare('SELECT invoice_id, branch, created_by, status FROM tbl_invoice WHERE invoice_id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
var_export($row);
?>
