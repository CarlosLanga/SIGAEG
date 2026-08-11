<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$turmaId = (int)($_GET['turma_id'] ?? 0);

if (!in_array($nivel, [1, 2], true) || $usuarioId <= 0 || $turmaId <= 0 || !$conn) {
    echo json_encode([]);
    exit;
}

$formadorId = getFormadorId($conn, $usuarioId);
if ($formadorId <= 0 || !formadorTemAcessoTurma($conn, $formadorId, $turmaId)) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT
        fm.id AS formador_modulo_id,
        m.id AS modulo_id,
        m.sigla_modulo,
        m.nome_modulo
    FROM formador_modulo fm
    INNER JOIN modulos m ON m.id = fm.modulo_id
    WHERE fm.formador_id = ? AND fm.turma_id = ?
    ORDER BY m.codigo_modulo ASC, m.sigla_modulo ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param('ii', $formadorId, $turmaId);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}

$stmt->close();
echo json_encode($rows);
