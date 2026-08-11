<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT t.id, t.nome_turma, tr.nome_turno
        FROM turmas t
        LEFT JOIN turnos tr ON tr.id = t.turno_id
        ORDER BY t.nome_turma ASC";

$res = $conn->query($sql);
$rows = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}

echo json_encode($rows);
