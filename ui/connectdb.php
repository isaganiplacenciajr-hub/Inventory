<?php

try{

    $pdo = new PDO('mysql:host=localhost;dbname=ganii','root','');

}catch(PDOException $e){

echo $e->getMessage();

}


?>