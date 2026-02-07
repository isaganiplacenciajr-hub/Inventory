<?php
include_once 'connectdb.php';

header('Content-Type: application/json');

if (isset($_GET['category'])) {
    $category = $_GET['category'];

    $select = $pdo->prepare("SELECT * FROM tbl_product WHERE category = :category LIMIT 1");
    $select->bindParam(':category', $category);
    $select->execute();

    $row = $select->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Map pid to id for frontend consistency if needed, or just use pid
        $row['id'] = $row['pid']; 
        echo json_encode($row);
    } else {
        echo json_encode(null);
    }
} else {
    echo json_encode(null);
}
?>