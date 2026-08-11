<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$turno_id = isset($_GET['turno_id']) ? (int)$_GET['turno_id'] : 0;

if (!$conn || !$curso_id || !$turno_id) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, nome_turma FROM turmas WHERE curso_id = $curso_id AND turno_id = $turno_id";
$res = $conn->query($sql);

$dados = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $dados[] = $row;
    }
}

echo json_encode($dados);