<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

if (!$conn) {
    json_out(['ok' => false, 'rows' => []]);
}

$turma_id = (int)($_GET['turma_id'] ?? 0);
if ($turma_id <= 0) {
    json_out(['ok' => false, 'rows' => []]);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$formadorJoin = "";
$formadorWhere = "";
if ($nivel === 2) {
    $formadorJoin = "INNER JOIN formadores f ON f.id = fm.formador_id";
    $formadorWhere = "AND f.usuario_id = $usuarioId";
}

$sql = "
    SELECT
        fm.id,
        fm.modulo_id,
        m.sigla_modulo,
        COALESCE(m.nome_modulo, '') AS nome_modulo,
        fm.data_inicio,
        fm.data_fim,
        CASE
            WHEN fm.data_inicio IS NOT NULL
                 AND fm.data_inicio <= CURDATE()
                 AND (fm.data_fim IS NULL OR fm.data_fim >= CURDATE())
            THEN 1 ELSE 0
    END AS activo
    FROM formador_modulo fm
    INNER JOIN modulos m ON m.id = fm.modulo_id
    $formadorJoin
    WHERE fm.turma_id = $turma_id
    $formadorWhere
    ORDER BY m.sigla_modulo ASC
";

$res = $conn->query($sql);
$rows = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}

json_out(['ok' => true, 'rows' => $rows]);
