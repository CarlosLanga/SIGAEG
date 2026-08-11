<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!$conn) { echo json_encode(['ok' => false]); exit; }

$curso_id = isset($_GET['curso_id']) && $_GET['curso_id'] !== 'all' ? (int)$_GET['curso_id'] : 0;
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, (int)($_GET['limit'] ?? 20));
$offset = ($page - 1) * $limit;

$where = [];
if ($curso_id > 0) {
    $where[] = "fc.curso_id = $curso_id";
}

if ($search !== '') {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $where[] = "(
        f.nome_completo LIKE '%$searchEsc%' OR
        f.sexo LIKE '%$searchEsc%' OR
        f.titulo LIKE '%$searchEsc%' OR
        f.codigo_formador LIKE '%$searchEsc%' OR
        f.email LIKE '%$searchEsc%'
        OR (f.usuario_id IS NOT NULL AND 'Cadastrado' LIKE '%$searchEsc%')
        OR (f.usuario_id IS NULL AND 'Não cadastrado' LIKE '%$searchEsc%')
    )";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$totalRes = $conn->query("SELECT COUNT(*) AS total FROM formadores");
$total = $totalRes ? (int)$totalRes->fetch_assoc()['total'] : 0;

$countSql = "SELECT COUNT(DISTINCT f.id) AS total_filtrado
             FROM formadores f
             LEFT JOIN formador_curso fc ON fc.formador_id = f.id
             $whereSql";
$countRes = $conn->query($countSql);
$total_filtrado = $countRes ? (int)$countRes->fetch_assoc()['total_filtrado'] : 0;

$sql = "SELECT
            f.id,
            CONCAT(
                IFNULL(NULLIF(f.titulo,''), ''),
                CASE WHEN f.titulo IS NULL OR f.titulo = '' THEN '' ELSE ' ' END,
                f.nome_completo
            ) AS nome,
            f.sexo,
            f.codigo_formador,
            CASE WHEN f.usuario_id IS NULL THEN 'Não cadastrado' ELSE 'Cadastrado' END AS estado,
            (SELECT COUNT(*) FROM turmas t WHERE t.dt_id = f.id) AS total_turmas,
            (SELECT COUNT(*) FROM formador_modulo fm WHERE fm.formador_id = f.id) AS total_modulos
        FROM formadores f
        LEFT JOIN formador_curso fc ON fc.formador_id = f.id
        $whereSql
        GROUP BY f.id
        ORDER BY f.nome_completo ASC
        LIMIT $limit OFFSET $offset";

$res = $conn->query($sql);
$rows = [];
if ($res) {
    while ($row = $res->fetch_assoc()) $rows[] = $row;
}

echo json_encode([
    'ok' => true,
    'rows' => $rows,
    'total' => $total,
    'total_filtrado' => $total_filtrado
]);