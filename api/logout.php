<?php
session_start();

if (isset($_COOKIE['iicaeg_remember'])) {
    require_once __DIR__ . '/../config/db.php';
    if ($conn) {
        $token = $conn->real_escape_string($_COOKIE['iicaeg_remember']);
        $conn->query("UPDATE usuarios SET remember_token = NULL WHERE remember_token = '$token'");
    }
    setcookie('iicaeg_remember', '', time() - 3600, '/');
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: ../index.php");
exit;
?>