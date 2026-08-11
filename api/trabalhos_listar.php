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

$turma_id = (int)($_GET['turma_id'] ?? 0);
$modulo_id = (int)($_GET['modulo_id'] ?? 0);
if ($turma_id <= 0 || $modulo_id <= 0) {
    json_out(['ok' => false, 'rows' => []]);
}

$sql = "
    SELECT
        t.id,
        t.titulo,
        t.tipo,
        t.descricao,
        t.data_publicacao,
        t.data_entrega,
        t.pontuacao_maxima,
        t.estado,
        COALESCE(m.sigla_modulo, '') AS sigla_modulo,
        COALESCE(m.nome_modulo, '') AS nome_modulo
    FROM trabalhos t
    INNER JOIN modulos m ON m.id = t.modulo_id
    WHERE t.turma_id = $turma_id AND t.modulo_id = $modulo_id
    ORDER BY t.data_entrega ASC, t.id ASC
";

$rows = [];
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}

json_out(['ok' => true, 'rows' => $rows]);
