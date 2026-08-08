<?php
$host = "localhost";
$user = "root";
// $password = "123";
$password = "!bootsNcats1";
$dbname = "Staffire";

$connection = mysqli_connect($host, $user, $password, $dbname);

    if (!$connection) {
        die("Database Connection Failed: " . mysqli_connect_error());
    } 
    
    ?>
