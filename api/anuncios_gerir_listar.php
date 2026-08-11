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
    json_out(['ok' => false, 'rows' => [], 'message' => 'Erro de ligação à base de dados.']);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

if (!in_array($nivel, [1, 2], true) || $usuarioId <= 0) {
    json_out(['ok' => false, 'rows' => [], 'message' => 'Sem permissão.']);
}

ensure_anuncios_modulo_schema($conn);

$search = trim((string)($_GET['search'] ?? ''));
$prioridade = trim((string)($_GET['prioridade'] ?? ''));

$where = [];

if ($nivel === 2) {
    $where[] = "a.criado_por = $usuarioId";
}

if (in_array($prioridade, ['normal', 'importante', 'evento'], true)) {
    $prioridadeEsc = $conn->real_escape_string($prioridade);
    $where[] = "a.prioridade = '$prioridadeEsc'";
}

if ($search !== '') {
    $searchEsc = $conn->real_escape_string($search);
    $where[] = "(
        a.titulo LIKE '%$searchEsc%'
        OR a.publico_alvo LIKE '%$searchEsc%'
        OR t.nome_turma LIKE '%$searchEsc%'
        OR m.sigla_modulo LIKE '%$searchEsc%'
        OR m.nome_modulo LIKE '%$searchEsc%'
    )";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
    SELECT
        a.id,
        a.titulo,
        a.prioridade,
        a.publico_alvo,
        a.turma_id,
        a.modulo_id,
        a.data_publicacao,
        a.data_expiracao,
        a.evento_data_inicio,
        a.evento_data_fim,
        a.anexo_nome,
        a.anexo_caminho,
        a.criado_por,
        COALESCE(u.nome_completo, '') AS autor_nome,
        COALESCE(t.nome_turma, '') AS nome_turma,
        COALESCE(tu.nome_turno, '') AS nome_turno,
        COALESCE(m.sigla_modulo, '') AS sigla_modulo,
        COALESCE(m.nome_modulo, '') AS nome_modulo
    FROM anuncios a
    LEFT JOIN usuarios u ON u.id = a.criado_por
    LEFT JOIN turmas t ON t.id = a.turma_id
    LEFT JOIN turnos tu ON tu.id = t.turno_id
    LEFT JOIN modulos m ON m.id = a.modulo_id
    $whereSql
    ORDER BY a.data_publicacao DESC, a.id DESC
";

$rows = [];
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $expirado = false;
        if (!empty($row['data_expiracao'])) {
            $expirado = strtotime($row['data_expiracao']) < time();
        }
        $row['expirado'] = $expirado ? 1 : 0;
        $rows[] = $row;
    }
}

json_out(['ok' => true, 'rows' => $rows, 'total' => count($rows)]);
