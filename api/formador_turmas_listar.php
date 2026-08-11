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

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
if (!in_array($nivel, [1, 2], true)) {
    json_out(['ok' => false, 'rows' => [], 'total' => 0, 'total_filtrado' => 0]);
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, (int)($_GET['limit'] ?? 20));
$search = trim((string)($_GET['search'] ?? ''));
$offset = ($page - 1) * $limit;

$searchEsc = mysqli_real_escape_string($conn, $search);
$whereParts = [];
if ($nivel === 2) {
    $whereParts[] = "f_scope.usuario_id = $usuario_id";
}
if ($search !== '') {
    $whereParts[] = "(
        t.nome_turma LIKE '%$searchEsc%' OR
        tr.nome_turno LIKE '%$searchEsc%' OR
        t.certificado_vocacional LIKE '%$searchEsc%' OR
        c.nome_curso LIKE '%$searchEsc%' OR
        t.ano_lectivo LIKE '%$searchEsc%'
    )";
}
$where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

$scopeWhere = $nivel === 2 ? "WHERE f_scope.usuario_id = $usuario_id" : "";

$baseFrom = "
    FROM turmas t
    INNER JOIN formador_modulo fm_scope ON fm_scope.turma_id = t.id
    INNER JOIN formadores f_scope ON f_scope.id = fm_scope.formador_id
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    LEFT JOIN cursos c ON c.id = t.curso_id
";

$totalRes = $conn->query("
    SELECT COUNT(DISTINCT t.id) AS total
    $baseFrom
    $scopeWhere
");
$totalRow = $totalRes ? $totalRes->fetch_assoc() : ['total' => 0];
$total = (int)$totalRow['total'];

$totalFiltradoRes = $conn->query("
    SELECT COUNT(DISTINCT t.id) AS total_filtrado
    $baseFrom
    $where
");
$totalFiltradoRow = $totalFiltradoRes ? $totalFiltradoRes->fetch_assoc() : ['total_filtrado' => 0];
$total_filtrado = (int)$totalFiltradoRow['total_filtrado'];

$sql = "
    SELECT
        t.id,
        t.nome_turma,
        tr.nome_turno,
        t.certificado_vocacional,
        c.nome_curso,
        t.ano_lectivo,
        CONCAT(
            IFNULL(NULLIF(dt.titulo,''), ''),
            CASE WHEN dt.titulo IS NULL OR dt.titulo = '' THEN '' ELSE ' ' END,
            dt.nome_completo
        ) AS director_turma,
        (SELECT COUNT(*) FROM formandos fo WHERE fo.turma_id = t.id) AS total_formandos
    $baseFrom
    LEFT JOIN formadores dt ON dt.id = t.dt_id
    $where
    GROUP BY t.id, t.nome_turma, tr.nome_turno, t.certificado_vocacional, c.nome_curso, t.ano_lectivo, dt.titulo, dt.nome_completo
    ORDER BY t.nome_turma ASC
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
    'total_filtrado' => $total_filtrado
]);
