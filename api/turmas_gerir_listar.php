<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['ok' => false]);
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, (int)($_GET['limit'] ?? 20));
$search = trim($_GET['search'] ?? '');

$offset = ($page - 1) * $limit;

$searchEsc = mysqli_real_escape_string($conn, $search);
$where = $search ? "WHERE 
    t.nome_turma LIKE '%$searchEsc%' OR
    tr.nome_turno LIKE '%$searchEsc%' OR
    t.certificado_vocacional LIKE '%$searchEsc%' OR
    f.nome_completo LIKE '%$searchEsc%' OR
    f.titulo LIKE '%$searchEsc%' OR
    t.ano_lectivo LIKE '%$searchEsc%'" : "";

$totalRes = $conn->query("
    SELECT COUNT(*) AS total
    FROM turmas t
");
$totalRow = $totalRes ? $totalRes->fetch_assoc() : ['total' => 0];
$total = (int)$totalRow['total'];

$searchEsc = mysqli_real_escape_string($conn, $search);
$where = $search ? "WHERE 
    t.nome_turma LIKE '%$searchEsc%' OR
    tr.nome_turno LIKE '%$searchEsc%' OR
    t.certificado_vocacional LIKE '%$searchEsc%' OR
    f.nome_completo LIKE '%$searchEsc%' OR
    f.titulo LIKE '%$searchEsc%' OR
    t.ano_lectivo LIKE '%$searchEsc%'" : "";

$totalFiltradoRes = $conn->query("
    SELECT COUNT(*) AS total_filtrado
    FROM turmas t
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    LEFT JOIN formadores f ON f.id = t.dt_id
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
    (SELECT COUNT(*) FROM formandos fo WHERE fo.turma_id = t.id) AS total_formandos,
    CONCAT(
        IFNULL(NULLIF(f.titulo,''), ''),
        CASE WHEN f.titulo IS NULL OR f.titulo = '' THEN '' ELSE ' ' END,
        f.nome_completo
    ) AS director_turma,
    t.ano_lectivo
FROM turmas t
LEFT JOIN turnos tr ON tr.id = t.turno_id
LEFT JOIN formadores f ON f.id = t.dt_id
$where
ORDER BY t.nome_turma ASC
LIMIT $limit OFFSET $offset
";

$res = $conn->query($sql);
$rows = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}

echo json_encode([
    'ok' => true,
    'rows' => $rows,
    'total' => $total,
    'total_filtrado' => $total_filtrado
]);

