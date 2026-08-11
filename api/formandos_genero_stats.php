<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['ok' => false]);
    exit;
}

$masc = 0;
$fem = 0;

$res = $conn->query("SELECT sexo, COUNT(*) AS total FROM formandos GROUP BY sexo");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $sexo = strtolower($row['sexo']);
        if ($sexo === 'masculino') $masc = (int)$row['total'];
        if ($sexo === 'feminino') $fem = (int)$row['total'];
    }
}

echo json_encode([
    'ok' => true,
    'masculino' => $masc,
    'feminino' => $fem
]);
