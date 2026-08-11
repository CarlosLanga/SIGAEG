<?php
declare(strict_types=1);

/* CONEXÃO COM A BD*/
$host = "localhost";
$user = "root";
$pass = "";
$db = "iicaeg_db";

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    $conn = false;
}

$conn->set_charset("utf8mb4");
?>