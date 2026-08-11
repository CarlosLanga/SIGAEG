<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id || !$conn) {
    echo json_encode(['ok' => false]);
    exit;
}

$sql = "SELECT id, nome_turma, turno_id, certificado_vocacional, dt_id, ano_lectivo, curso_id
        FROM turmas WHERE id = $id LIMIT 1";
$res = $conn->query($sql);

if (!$res || $res->num_rows === 0) {
    echo json_encode(['ok' => false]);
    exit;
}

echo json_encode(['ok' => true, 'data' => $res->fetch_assoc()]);
