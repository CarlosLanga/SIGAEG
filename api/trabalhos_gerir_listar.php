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
$search = trim((string)($_GET['search'] ?? ''));

if ($turma_id <= 0 || $modulo_id <= 0) {
    json_out(['ok' => false, 'rows' => [], 'message' => 'Seleccione a turma e o módulo.']);
}

$where = [
    "tr.turma_id = $turma_id",
    "tr.modulo_id = $modulo_id"
];

if ($search !== '') {
    $searchEsc = $conn->real_escape_string($search);
    $where[] = "(
        tr.titulo LIKE '%$searchEsc%'
        OR tr.tipo LIKE '%$searchEsc%'
        OR tr.estado LIKE '%$searchEsc%'
        OR m.sigla_modulo LIKE '%$searchEsc%'
        OR m.nome_modulo LIKE '%$searchEsc%'
    )";
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT
        tr.id,
        tr.turma_id,
        tr.formador_modulo_id,
        tr.modulo_id,
        tr.titulo,
        tr.tipo,
        tr.descricao,
        tr.data_publicacao,
        tr.data_entrega,
        tr.pontuacao_maxima,
        tr.estado,
        tr.criado_em,
        t.nome_turma,
        tu.nome_turno,
        COALESCE(m.sigla_modulo, '') AS sigla_modulo,
        COALESCE(m.nome_modulo, '') AS nome_modulo
    FROM trabalhos tr
    INNER JOIN turmas t ON t.id = tr.turma_id
    LEFT JOIN turnos tu ON tu.id = t.turno_id
    INNER JOIN modulos m ON m.id = tr.modulo_id
    $whereSql
    ORDER BY tr.data_entrega ASC, tr.id ASC
";

$rows = [];
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}

json_out(['ok' => true, 'rows' => $rows, 'total' => count($rows)]);
