<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

if (($_SESSION['nivel_acesso'] ?? 0) != 1) {
    json_out(['ok' => false, 'msg' => 'Sem permissao.']);
}

if (!$conn) {
    json_out(['ok' => false, 'msg' => 'Erro de conexao.']);
}

if (!ensure_horarios_plano_schema($conn)) {
    json_out(['ok' => false, 'msg' => 'Erro ao validar horario.']);
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, (int)($_GET['limit'] ?? 20));
$offset = ($page - 1) * $limit;

$search = trim((string)($_GET['search'] ?? ''));
$turma_id = (int)($_GET['turma_id'] ?? 0);

$searchEsc = mysqli_real_escape_string($conn, $search);
$whereParts = [];

if ($search !== '') {
    $whereParts[] = "(
        t.nome_turma LIKE '%$searchEsc%' OR
        tr.nome_turno LIKE '%$searchEsc%' OR
        t.ano_lectivo LIKE '%$searchEsc%' OR
        f.nome_completo LIKE '%$searchEsc%' OR
        f.titulo LIKE '%$searchEsc%'
    )";
}

if ($turma_id > 0) {
    $whereParts[] = "hp.turma_id = $turma_id";
}

$where = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

$totalRes = $conn->query("SELECT COUNT(*) AS total FROM horarios_plano");
$totalRow = $totalRes ? $totalRes->fetch_assoc() : ['total' => 0];
$total = (int)$totalRow['total'];

$totalFiltradoRes = $conn->query("
    SELECT COUNT(*) AS total_filtrado
    FROM horarios_plano hp
    LEFT JOIN turmas t ON t.id = hp.turma_id
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    LEFT JOIN formadores f ON f.id = t.dt_id
    $where
");
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
        tr.nome_turno,
        CONCAT(
            COALESCE(NULLIF(f.titulo, ''), ''),
            CASE WHEN f.titulo IS NULL OR f.titulo = '' THEN '' ELSE ' ' END,
            COALESCE(f.nome_completo, '')
        ) AS director_turma
    FROM horarios_plano hp
    LEFT JOIN turmas t ON t.id = hp.turma_id
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    LEFT JOIN formadores f ON f.id = t.dt_id
    $where
    ORDER BY hp.actualizado_em DESC, t.nome_turma ASC
    LIMIT $limit OFFSET $offset
";

$res = $conn->query($sql);
$rows = [];
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
