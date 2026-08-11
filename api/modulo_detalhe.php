<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id || !$conn) {
    echo json_encode(['ok' => false]);
    exit;
}

$sql = "SELECT 
            fm.id,
            fm.turma_id,
            fm.modulo_id,
            fm.formador_id,
            fm.data_inicio,
            fm.data_fim,
            t.nome_turma,
            tr.nome_turno,
            m.sigla_modulo,
            m.codigo_modulo,
            m.nome_modulo,
            m.tipo_modulo,
            m.curso_id
        FROM formador_modulo fm
        LEFT JOIN turmas t ON t.id = fm.turma_id
        LEFT JOIN turnos tr ON tr.id = t.turno_id
        LEFT JOIN modulos m ON m.id = fm.modulo_id
        WHERE fm.id = $id
        LIMIT 1";

$res = $conn->query($sql);
if (!$res || $res->num_rows === 0) {
    echo json_encode(['ok' => false]);
    exit;
}

echo json_encode(['ok' => true, 'data' => $res->fetch_assoc()]);
