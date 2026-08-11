<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

if (!$conn || !isset($_SESSION['usuario_id'])) {
    http_response_code(404);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit;
}

$res = $conn->query("SELECT nome_original, caminho, mime_type FROM ficheiros WHERE id = $id LIMIT 1");
if (!$res || $res->num_rows === 0) {
    http_response_code(404);
    exit;
}

$row = $res->fetch_assoc();
$path = realpath(__DIR__ . '/../' . $row['caminho']);
$base = realpath(__DIR__ . '/../assets/ficheiros');

if (!$path || !$base || strpos($path, $base) !== 0 || !is_file($path)) {
    http_response_code(404);
    exit;
}

$conn->query("UPDATE ficheiros SET downloads = downloads + 1 WHERE id = $id");

header('Content-Type: ' . ($row['mime_type'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . basename($row['nome_original']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
