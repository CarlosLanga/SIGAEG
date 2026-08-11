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
$whereParts = ["UPPER(COALESCE(m.sigla_modulo, '')) <> 'RT'"];
if ($nivel === 2) {
    $whereParts[] = "f.usuario_id = $usuario_id";
}
if ($search !== '') {
    $whereParts[] = "(
        m.sigla_modulo LIKE '%$searchEsc%' OR
        m.nome_modulo LIKE '%$searchEsc%' OR
        t.nome_turma LIKE '%$searchEsc%' OR
        fr.nome_completo LIKE '%$searchEsc%'
    )";
}
$where = 'WHERE ' . implode(' AND ', $whereParts);

$scopeWhereParts = ["UPPER(COALESCE(m.sigla_modulo, '')) <> 'RT'"];
if ($nivel === 2) {
    $scopeWhereParts[] = "f.usuario_id = $usuario_id";
}
$scopeWhere = 'WHERE ' . implode(' AND ', $scopeWhereParts);

$baseFrom = "
    FROM formador_modulo fm
    INNER JOIN modulos m ON m.id = fm.modulo_id
    INNER JOIN turmas t ON t.id = fm.turma_id
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    INNER JOIN formadores fr ON fr.id = fm.formador_id
    INNER JOIN formadores f ON f.id = fm.formador_id
";

$totalRes = $conn->query("SELECT COUNT(*) AS total $baseFrom $scopeWhere");
$totalRow = $totalRes ? $totalRes->fetch_assoc() : ['total' => 0];
$total = (int)$totalRow['total'];

$totalFiltradoRes = $conn->query("SELECT COUNT(*) AS total_filtrado $baseFrom $where");
$totalFiltradoRow = $totalFiltradoRes ? $totalFiltradoRes->fetch_assoc() : ['total_filtrado' => 0];
$total_filtrado = (int)$totalFiltradoRow['total_filtrado'];

$sql = "
    SELECT
        fm.id,
        fm.modulo_id,
        fm.turma_id,
        fm.data_inicio,
        fm.data_fim,
        m.sigla_modulo,
        m.nome_modulo,
        COALESCE(NULLIF(m.tipo_modulo, ''), 'generico') AS tipo_modulo,
        t.nome_turma,
        tr.nome_turno,
        TRIM(CONCAT(COALESCE(fr.titulo, ''), ' ', COALESCE(fr.nome_completo, ''))) AS formador_nome,
        CASE
            WHEN fm.data_inicio IS NOT NULL AND fm.data_inicio > CURDATE() THEN 'por_iniciar'
            WHEN fm.data_fim IS NOT NULL AND fm.data_fim < CURDATE() THEN 'concluido'
            ELSE 'em_vigencia'
        END AS estado
    $baseFrom
    $where
    ORDER BY t.nome_turma ASC, m.sigla_modulo ASC
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
