<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode([]);
    exit;
}

$res = $conn->query("SELECT DISTINCT ano_lectivo FROM turmas WHERE ano_lectivo IS NOT NULL ORDER BY ano_lectivo DESC");
$rows = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row['ano_lectivo'];
    }
}

echo json_encode($rows);
