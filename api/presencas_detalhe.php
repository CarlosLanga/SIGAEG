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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    json_out(['ok' => false]);
}
$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$scopeWhere = $nivel === 2 ? "AND f.usuario_id = $usuario_id" : "";

$res = $conn->query("
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
        u.nome_completo AS marcado_por
    FROM presencas_plano pp
    LEFT JOIN turmas t ON t.id = pp.turma_id
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    LEFT JOIN formador_modulo fm ON fm.id = pp.formador_modulo_id
    LEFT JOIN formadores f ON f.id = fm.formador_id
    LEFT JOIN modulos m ON m.id = fm.modulo_id
    LEFT JOIN usuarios u ON u.id = pp.criado_por
    WHERE pp.id = $id
    $scopeWhere
    LIMIT 1
");

if (!$res || $res->num_rows === 0) {
    json_out(['ok' => false]);
}

$data = $res->fetch_assoc();

$slots = [];
$resSlots = $conn->query("SELECT slot_codigo FROM presencas_intervalo WHERE plano_id = $id ORDER BY slot_codigo ASC");
if ($resSlots) {
    while ($row = $resSlots->fetch_assoc()) {
        $slots[] = $row['slot_codigo'];
    }
}

$rows = [];
$stats = [
    'total' => 0,
    'presentes' => 0,
    'ausentes' => 0,
    'wd' => 0,
    'd' => 0,
];

$resReg = $conn->query("
    SELECT f.id, f.nome_completo, f.codigo_formando, pr.situacao, pr.observacao
    FROM presencas_registo pr
    INNER JOIN formandos f ON f.id = pr.formando_id
    WHERE pr.plano_id = $id
    ORDER BY f.nome_completo ASC
");
if ($resReg) {
    while ($row = $resReg->fetch_assoc()) {
        $rows[] = $row;
        $stats['total']++;
        if ($row['situacao'] === 'Presente') $stats['presentes']++;
        if ($row['situacao'] === 'Ausente') $stats['ausentes']++;
        if ($row['situacao'] === 'WD') $stats['wd']++;
        if ($row['situacao'] === 'D') $stats['d']++;
    }
}

json_out([
    'ok' => true,
    'data' => $data,
    'slots' => $slots,
    'rows' => $rows,
    'stats' => $stats
]);
