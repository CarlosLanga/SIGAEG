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
    json_out(['ok' => false, 'rows' => [], 'total' => 0, 'total_filtrado' => 0]);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
if (!in_array($nivel, [1, 3], true) || $usuarioId <= 0) {
    json_out(['ok' => false, 'rows' => [], 'total' => 0, 'total_filtrado' => 0]);
}

$formandoId = $nivel === 3 ? getFormandoId($conn, $usuarioId) : 0;
if ($nivel === 3 && $formandoId <= 0) {
    json_out(['ok' => true, 'rows' => [], 'total' => 0, 'total_filtrado' => 0]);
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, (int)($_GET['limit'] ?? 20));
$search = trim((string)($_GET['search'] ?? ''));
$offset = ($page - 1) * $limit;

if ($nivel === 1) {
    $turmaIds = [];
    $currentTurmaId = 0;
    $resAll = $conn->query('SELECT id FROM turmas ORDER BY ano_lectivo DESC, nome_turma ASC');
    if ($resAll) {
        while ($row = $resAll->fetch_assoc()) {
            $turmaIds[] = (int)$row['id'];
        }
    }
} else {
    $turmaIds = formandoTurmaIdsAcessiveis($conn, $formandoId);
    $currentTurmaId = formandoTurmaAtualId($conn, $formandoId);
}

if (!$turmaIds) {
    json_out(['ok' => true, 'rows' => [], 'total' => 0, 'total_filtrado' => 0]);
}

$idSql = implode(',', array_map('intval', $turmaIds));
$searchEsc = mysqli_real_escape_string($conn, $search);
$whereParts = ["t.id IN ($idSql)"];

if ($search !== '') {
    $whereParts[] = "(
        t.nome_turma LIKE '%$searchEsc%' OR
        tr.nome_turno LIKE '%$searchEsc%' OR
        t.certificado_vocacional LIKE '%$searchEsc%' OR
        t.ano_lectivo LIKE '%$searchEsc%' OR
        dt.nome_completo LIKE '%$searchEsc%'
    )";
}

$where = 'WHERE ' . implode(' AND ', $whereParts);
$orderCurrent = $currentTurmaId > 0 ? "CASE WHEN t.id = $currentTurmaId THEN 0 ELSE 1 END," : '';

$baseFrom = "
    FROM turmas t
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    LEFT JOIN formadores dt ON dt.id = t.dt_id
";

$total = count($turmaIds);

$totalFiltradoRes = $conn->query("SELECT COUNT(*) AS total_filtrado $baseFrom $where");
$totalFiltradoRow = $totalFiltradoRes ? $totalFiltradoRes->fetch_assoc() : ['total_filtrado' => 0];
$totalFiltrado = (int)$totalFiltradoRow['total_filtrado'];

$sql = "
    SELECT
        t.id,
        t.nome_turma,
        tr.nome_turno,
        t.certificado_vocacional,
        t.ano_lectivo,
        CONCAT(
            IFNULL(NULLIF(dt.titulo, ''), ''),
            CASE WHEN dt.titulo IS NULL OR dt.titulo = '' THEN '' ELSE ' ' END,
            dt.nome_completo
        ) AS director_turma,
        CASE WHEN t.id = $currentTurmaId THEN 1 ELSE 0 END AS actual
    $baseFrom
    $where
    ORDER BY $orderCurrent t.ano_lectivo DESC, t.nome_turma ASC
    LIMIT $limit OFFSET $offset
";

$rows = [];
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}

json_out([
    'ok' => true,
    'rows' => $rows,
    'total' => $total,
    'total_filtrado' => $totalFiltrado,
]);
