<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['ok' => false, 'erro' => 'conexao']);
    exit;
}

$turma_id = isset($_GET['turma_id']) && $_GET['turma_id'] !== 'all' ? (int)$_GET['turma_id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(5, (int)$_GET['limit']) : 20;
$offset = ($page - 1) * $limit;

$whereTurma = [];
if ($turma_id > 0) {
    $whereTurma[] = "f.turma_id = $turma_id";
}
$whereTurmaSql = $whereTurma ? ('WHERE ' . implode(' AND ', $whereTurma)) : '';

$where = $whereTurma;
if ($search !== '') {
    $search = mysqli_real_escape_string($conn, $search);
    $where[] = "(
        f.nome_completo LIKE '%$search%' 
        OR f.codigo_formando LIKE '%$search%'
        OR f.sexo LIKE '%$search%'
        OR t.nome_turma LIKE '%$search%'
        OR tr.nome_turno LIKE '%$search%'
        OR (f.usuario_id IS NOT NULL AND 'Cadastrado' LIKE '%$search%')
        OR (f.usuario_id IS NULL AND 'Não cadastrado' LIKE '%$search%')
    )";
}


$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countTurmaSql = "SELECT COUNT(*) AS total_turma
                  FROM formandos f
                  $whereTurmaSql";
$countTurmaRes = $conn->query($countTurmaSql);
$total_turma = $countTurmaRes ? (int)$countTurmaRes->fetch_assoc()['total_turma'] : 0;

$countSql = "SELECT COUNT(*) AS total_filtrado
             FROM formandos f
             LEFT JOIN turmas t ON t.id = f.turma_id
             LEFT JOIN turnos tr ON tr.id = t.turno_id
             $whereSql";

$countRes = $conn->query($countSql);
$total_filtrado = $countRes ? (int)$countRes->fetch_assoc()['total_filtrado'] : 0;

$sql = "SELECT 
            f.id,
            f.nome_completo,
            f.sexo,
            f.codigo_formando,
            f.turma_id,
            t.nome_turma,
            tr.nome_turno,
            f.usuario_id,
            f.data_criacao
        FROM formandos f
        LEFT JOIN turmas t ON t.id = f.turma_id
        LEFT JOIN turnos tr ON tr.id = t.turno_id
        $whereSql
        ORDER BY f.nome_completo ASC
        LIMIT $limit OFFSET $offset";

$res = $conn->query($sql);
$rows = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $row['estado'] = !empty($row['usuario_id']) ? 'Cadastrado' : 'Não cadastrado';
        $rows[] = $row;
    }
}

echo json_encode([
    'ok' => true,
    'total' => $total_turma,
    'total_filtrado' => $total_filtrado,
    'page' => $page,
    'limit' => $limit,
    'rows' => $rows,
]);
