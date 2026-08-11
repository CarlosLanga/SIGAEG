<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

if (!$conn) {
    json_out(['ok' => false, 'rows' => []]);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

if (!in_array($nivel, [1, 2], true)) {
    json_out(['ok' => false, 'rows' => []]);
}

if ($nivel === 1) {
    $sql = "
        SELECT t.id, t.nome_turma, tr.nome_turno
        FROM turmas t
        LEFT JOIN turnos tr ON tr.id = t.turno_id
        ORDER BY t.nome_turma ASC
    ";
} else {
    $sql = "
        SELECT DISTINCT t.id, t.nome_turma, tr.nome_turno
        FROM turmas t
        INNER JOIN formador_modulo fm ON fm.turma_id = t.id
        INNER JOIN formadores f ON f.id = fm.formador_id
        LEFT JOIN turnos tr ON tr.id = t.turno_id
        WHERE f.usuario_id = $usuarioId
        ORDER BY t.nome_turma ASC
    ";
}

$rows = [];
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}

json_out(['ok' => true, 'rows' => $rows]);
