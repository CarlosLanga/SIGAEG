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
    json_out(['ok' => false]);
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, (int)($_GET['limit'] ?? 20));
$search = trim((string)($_GET['search'] ?? ''));
$turma_id = (int)($_GET['turma_id'] ?? 0);
$offset = ($page - 1) * $limit;
$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);

$searchEsc = mysqli_real_escape_string($conn, $search);
$whereParts = [];
$scopeWhereParts = [];

if ($nivel === 2) {
    $whereParts[] = "f.usuario_id = $usuario_id";
    $scopeWhereParts[] = "f.usuario_id = $usuario_id";
}

if ($search !== '') {
    $whereParts[] = "(
        t.nome_turma LIKE '%$searchEsc%' OR
        m.sigla_modulo LIKE '%$searchEsc%' OR
        m.nome_modulo LIKE '%$searchEsc%' OR
        u.nome_completo LIKE '%$searchEsc%'
    )";
}

if ($turma_id > 0) {
    $whereParts[] = "pp.turma_id = $turma_id";
}

$where = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';
$scopeWhere = $scopeWhereParts ? ('WHERE ' . implode(' AND ', $scopeWhereParts)) : '';

$totalRes = $conn->query("
    SELECT COUNT(*) AS total
    FROM presencas_plano pp
    LEFT JOIN formador_modulo fm ON fm.id = pp.formador_modulo_id
    LEFT JOIN formadores f ON f.id = fm.formador_id
    $scopeWhere
");
$totalRow = $totalRes ? $totalRes->fetch_assoc() : ['total' => 0];
$total = (int)$totalRow['total'];

$totalFiltradoRes = $conn->query("
    SELECT COUNT(*) AS total_filtrado
    FROM presencas_plano pp
    LEFT JOIN turmas t ON t.id = pp.turma_id
    LEFT JOIN formador_modulo fm ON fm.id = pp.formador_modulo_id
    LEFT JOIN formadores f ON f.id = fm.formador_id
    LEFT JOIN modulos m ON m.id = fm.modulo_id
    LEFT JOIN usuarios u ON u.id = pp.criado_por
    $where
");
$totalFiltradoRow = $totalFiltradoRes ? $totalFiltradoRes->fetch_assoc() : ['total_filtrado' => 0];
$total_filtrado = (int)$totalFiltradoRow['total_filtrado'];

$sql = "
    SELECT
        pp.id,
        pp.turma_id,
        pp.formador_modulo_id,
        pp.data_aula,
        pp.dia_semana,
        pp.estado,
        pp.actualizado_em,
        t.nome_turma,
        tr.nome_turno,
        m.sigla_modulo,
        m.nome_modulo,
        u.nome_completo AS marcado_por,
        GROUP_CONCAT(pi.slot_codigo ORDER BY pi.slot_codigo SEPARATOR ', ') AS aulas
    FROM presencas_plano pp
    LEFT JOIN turmas t ON t.id = pp.turma_id
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    LEFT JOIN formador_modulo fm ON fm.id = pp.formador_modulo_id
    LEFT JOIN formadores f ON f.id = fm.formador_id
    LEFT JOIN modulos m ON m.id = fm.modulo_id
    LEFT JOIN usuarios u ON u.id = pp.criado_por
    LEFT JOIN presencas_intervalo pi ON pi.plano_id = pp.id
    $where
    GROUP BY pp.id
    ORDER BY pp.actualizado_em DESC
    LIMIT $limit OFFSET $offset
";

$res = $conn->query($sql);
$rows = [];
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
