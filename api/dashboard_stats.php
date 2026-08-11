<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['ok' => false]);
    exit;
}

$tot_formandos = 0;
$tot_formadores = 0;
$tot_turmas = 0;

$res = $conn->query("SELECT COUNT(*) AS total FROM formandos");
if ($res) $tot_formandos = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM formadores");
if ($res) $tot_formadores = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM turmas");
if ($res) $tot_turmas = (int)$res->fetch_assoc()['total'];

echo json_encode([
    'ok' => true,
    'formandos' => $tot_formandos,
    'formadores' => $tot_formadores,
    'turmas' => $tot_turmas
]);
