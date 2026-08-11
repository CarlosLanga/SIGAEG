<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

if (!in_array($nivel, [1, 2], true) || $usuarioId <= 0 || !$conn) {
    echo json_encode([]);
    exit;
}

$formadorId = getFormadorId($conn, $usuarioId);
if ($formadorId <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT DISTINCT t.id, t.nome_turma, tr.nome_turno
    FROM turmas t
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    WHERE t.id IN (
        SELECT fm.turma_id FROM formador_modulo fm WHERE fm.formador_id = ?
        UNION
        SELECT t2.id FROM turmas t2 WHERE t2.dt_id = ?
    )
    ORDER BY t.nome_turma ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param('ii', $formadorId, $formadorId);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}

$stmt->close();
echo json_encode($rows);
