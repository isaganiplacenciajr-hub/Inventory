<?php

include_once 'connectdb.php';

if(isset($_GET['id'])){

    $id = $_GET['id'];

    $delete=$pdo->prepare("delete from tbl_product where pid=" . $id);
    $delete->execute();
}


?>