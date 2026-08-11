<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode([]);
    exit;
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

$where = "";
$joinFormador = "";
if ($nivel === 2) {
    $joinFormador = "
        INNER JOIN formador_modulo fm ON fm.turma_id = t.id
        INNER JOIN formadores f ON f.id = fm.formador_id
    ";
    $where = "WHERE f.usuario_id = $usuarioId";
}

$sql = "SELECT DISTINCT t.id, t.nome_turma, t.curso_id, tr.nome_turno
        FROM turmas t
        LEFT JOIN turnos tr ON tr.id = t.turno_id
        $joinFormador
        $where
        ORDER BY t.nome_turma ASC";
$res = $conn->query($sql);

$rows = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}

echo json_encode($rows);
