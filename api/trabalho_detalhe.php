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
    json_out(['ok' => false]);
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    json_out(['ok' => false]);
}

$sql = "
    SELECT
        tr.*,
        t.nome_turma,
        t.ano_lectivo,
        tu.nome_turno,
        COALESCE(m.sigla_modulo, '') AS sigla_modulo,
        COALESCE(m.nome_modulo, '') AS nome_modulo,
        fm.data_inicio,
        fm.data_fim,
        f.nome_completo AS formador_nome,
        u.nome_completo AS criado_por_nome
    FROM trabalhos tr
    INNER JOIN turmas t ON t.id = tr.turma_id
    LEFT JOIN turnos tu ON tu.id = t.turno_id
    INNER JOIN modulos m ON m.id = tr.modulo_id
    INNER JOIN formador_modulo fm ON fm.id = tr.formador_modulo_id
    LEFT JOIN formadores f ON f.id = fm.formador_id
    LEFT JOIN usuarios u ON u.id = tr.criado_por
    WHERE tr.id = $id
    LIMIT 1
";

$res = $conn->query($sql);
if (!$res || $res->num_rows === 0) {
    json_out(['ok' => false]);
}

$data = $res->fetch_assoc();

json_out([
    'ok' => true,
    'data' => $data,
    'submissoes' => [],
    'ficheiros' => []
]);
