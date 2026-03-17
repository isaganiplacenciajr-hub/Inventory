<?php
// Set timezone for accurate time handling
date_default_timezone_set('Asia/Manila');

try{

    $pdo = new PDO('mysql:host=localhost;dbname=ganii','root','');
    // Set MySQL session timezone
    $pdo->exec("SET time_zone='+08:00'");

}catch(PDOException $e){

echo $e->getMessage();

}

// Initialize database schema
if (isset($pdo)) {
    include_once __DIR__ . '/dbinit.php';
}

?>