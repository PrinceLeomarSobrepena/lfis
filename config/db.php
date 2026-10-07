<!-- <?php
date_default_timezone_set('Asia/Manila');

$host = 'localhost';
$user = 'root';
$pass = '';
$db = "capstone1v1";

$conn = mysqli_connect($host, $user, $pass, $db);

if(!$conn){
    die("Connection failed: " . mysqli_connect_error());
};

$conn->query("SET time_zone = '+08:00'");

?> -->

<?php

date_default_timezone_set('Asia/Manila');

$host = getenv('MYSQLHOST');
$port = getenv('MYSQLPORT') ?: 3306;
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$db   = getenv('MYSQLDATABASE');

$conn = mysqli_connect(
    $host,
    $user,
    $pass,
    $db,
    $port
);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$conn->query("SET time_zone = '+08:00'");

?>