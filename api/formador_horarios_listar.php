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
    json_out(['ok' => false, 'rows' => [], 'total' => 0, 'total_filtrado' => 0]);
}

if (!ensure_horarios_plano_schema($conn)) {
    json_out(['ok' => false, 'rows' => [], 'total' => 0, 'total_filtrado' => 0]);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
if (!in_array($nivel, [1, 2], true)) {
    json_out(['ok' => false, 'rows' => [], 'total' => 0, 'total_filtrado' => 0]);
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, (int)($_GET['limit'] ?? 20));
$offset = ($page - 1) * $limit;
$search = trim((string)($_GET['search'] ?? ''));

$searchEsc = mysqli_real_escape_string($conn, $search);
$whereParts = [];
if ($nivel === 2) {
    $whereParts[] = "EXISTS (
        SELECT 1
        FROM formador_modulo fm_scope
        INNER JOIN formadores f_scope ON f_scope.id = fm_scope.formador_id
        WHERE fm_scope.turma_id = hp.turma_id
          AND f_scope.usuario_id = $usuario_id
    )";
}
if ($search !== '') {
    $whereParts[] = "(
        t.nome_turma LIKE '%$searchEsc%' OR
        tr.nome_turno LIKE '%$searchEsc%' OR
        t.ano_lectivo LIKE '%$searchEsc%'
    )";
}
$where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

$scopeWhereParts = [];
if ($nivel === 2) {
    $scopeWhereParts[] = "EXISTS (
        SELECT 1
        FROM formador_modulo fm_scope
        INNER JOIN formadores f_scope ON f_scope.id = fm_scope.formador_id
        WHERE fm_scope.turma_id = hp.turma_id
          AND f_scope.usuario_id = $usuario_id
    )";
}
$scopeWhere = $scopeWhereParts ? 'WHERE ' . implode(' AND ', $scopeWhereParts) : '';

$baseFrom = "
    FROM horarios_plano hp
    LEFT JOIN turmas t ON t.id = hp.turma_id
    LEFT JOIN turnos tr ON tr.id = t.turno_id
";

$totalRes = $conn->query("SELECT COUNT(*) AS total $baseFrom $scopeWhere");
$totalRow = $totalRes ? $totalRes->fetch_assoc() : ['total' => 0];
$total = (int)$totalRow['total'];

$totalFiltradoRes = $conn->query("SELECT COUNT(*) AS total_filtrado $baseFrom $where");
$totalFiltradoRow = $totalFiltradoRes ? $totalFiltradoRes->fetch_assoc() : ['total_filtrado' => 0];
$total_filtrado = (int)$totalFiltradoRow['total_filtrado'];

$sql = "
    SELECT
        hp.id,
        hp.turma_id,
        hp.semestre,
        hp.bloco,
        hp.actualizado_em,
        hp.publicado,
        hp.publicado_em,
        t.nome_turma,
        t.ano_lectivo,
        tr.nome_turno
    $baseFrom
    $where
    ORDER BY hp.actualizado_em DESC, t.nome_turma ASC
    LIMIT $limit OFFSET $offset
";

$rows = [];
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $row['publicado'] = (int)($row['publicado'] ?? 0);
        $rows[] = $row;
    }
}

json_out([
    'ok' => true,
    'rows' => $rows,
    'total' => $total,
    'total_filtrado' => $total_filtrado
]);
