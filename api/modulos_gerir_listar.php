<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['ok' => false]);
    exit;
}

$turma_id = isset($_GET['turma_id']) && $_GET['turma_id'] !== 'all' ? (int)$_GET['turma_id'] : 0;
$search = trim($_GET['search'] ?? '');
$tipo = $_GET['tipo_modulo'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, (int)($_GET['limit'] ?? 20));
$offset = ($page - 1) * $limit;

$estadoExpr = "CASE
    WHEN fm.data_inicio IS NULL OR fm.data_fim IS NULL THEN 'Por iniciar'
    WHEN CURDATE() < fm.data_inicio THEN 'Por iniciar'
    WHEN CURDATE() > fm.data_fim THEN 'Concluido'
    ELSE 'Em vigencia'
END";

$joins = "FROM formador_modulo fm
          INNER JOIN modulos m ON m.id = fm.modulo_id
          LEFT JOIN turmas t ON t.id = fm.turma_id
          LEFT JOIN turnos tr ON tr.id = t.turno_id
          LEFT JOIN formadores f ON f.id = fm.formador_id";

$baseWhere = [];
if ($turma_id > 0) {
    $baseWhere[] = "fm.turma_id = $turma_id";
}
if ($tipo === 'generico' || $tipo === 'vocacional') {
    $tipoEsc = mysqli_real_escape_string($conn, $tipo);
    $baseWhere[] = "m.tipo_modulo = '$tipoEsc'";
}

$searchWhere = [];
if ($search !== '') {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $searchWhere[] = "(
        m.sigla_modulo LIKE '%$searchEsc%' OR
        m.codigo_modulo LIKE '%$searchEsc%' OR
        m.nome_modulo LIKE '%$searchEsc%' OR
        m.tipo_modulo LIKE '%$searchEsc%' OR
        t.nome_turma LIKE '%$searchEsc%' OR
        tr.nome_turno LIKE '%$searchEsc%' OR
        f.nome_completo LIKE '%$searchEsc%' OR
        f.titulo LIKE '%$searchEsc%' OR
        ($estadoExpr) LIKE '%$searchEsc%'
    )";
}

$totalWhereSql = $baseWhere ? ('WHERE ' . implode(' AND ', $baseWhere)) : '';
$filteredWhere = array_merge($baseWhere, $searchWhere);
$filteredWhereSql = $filteredWhere ? ('WHERE ' . implode(' AND ', $filteredWhere)) : '';

$countTotalSql = "SELECT COUNT(*) AS total $joins $totalWhereSql";
$countTotalRes = $conn->query($countTotalSql);
$total = $countTotalRes ? (int)$countTotalRes->fetch_assoc()['total'] : 0;

$countFilteredSql = "SELECT COUNT(*) AS total_filtrado $joins $filteredWhereSql";
$countFilteredRes = $conn->query($countFilteredSql);
$total_filtrado = $countFilteredRes ? (int)$countFilteredRes->fetch_assoc()['total_filtrado'] : 0;

$sql = "SELECT
            fm.id,
            m.id AS modulo_id,
            m.sigla_modulo,
            m.tipo_modulo,
            t.nome_turma,
            tr.nome_turno,
            CONCAT(
                IFNULL(NULLIF(f.titulo, ''), ''),
                CASE WHEN f.titulo IS NULL OR f.titulo = '' THEN '' ELSE ' ' END,
                IFNULL(f.nome_completo, '')
            ) AS formador,
            ($estadoExpr) AS estado
        $joins
        $filteredWhereSql
        ORDER BY m.sigla_modulo ASC, t.nome_turma ASC
        LIMIT $limit OFFSET $offset";

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
