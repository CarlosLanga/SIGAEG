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
    json_out(['ok' => false, 'msg' => 'Erro de conexao.']);
}

if (!ensure_horarios_plano_schema($conn)) {
    json_out(['ok' => false, 'msg' => 'Erro ao validar horario.']);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
if (!in_array($nivel, [1, 2], true)) {
    json_out(['ok' => false, 'msg' => 'Sem permissao.']);
}

$plano_id = (int)($_GET['id'] ?? 0);
if ($plano_id <= 0) {
    json_out(['ok' => false, 'msg' => 'Parametros invalidos.']);
}

$scopeWhere = $nivel === 2 ? "AND EXISTS (
    SELECT 1
    FROM formador_modulo fm_scope
    INNER JOIN formadores f_scope ON f_scope.id = fm_scope.formador_id
    WHERE fm_scope.turma_id = hp.turma_id
      AND f_scope.usuario_id = $usuario_id
)" : "";

$resPlano = $conn->query("
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
        tr.nome_turno
    FROM horarios_plano hp
    LEFT JOIN turmas t ON t.id = hp.turma_id
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    WHERE hp.id = $plano_id
    $scopeWhere
    LIMIT 1
");

if (!$resPlano || $resPlano->num_rows === 0) {
    json_out(['ok' => false, 'msg' => 'Horario nao encontrado.']);
}
$plano = $resPlano->fetch_assoc();
$turma_id = (int)$plano['turma_id'];

$modules = [];
$resModules = $conn->query("
    SELECT DISTINCT
        fm.id AS formador_modulo_id,
        fm.modulo_id,
        COALESCE(NULLIF(m.sigla_modulo, ''), m.codigo_modulo, CONCAT('MOD-', fm.modulo_id)) AS sigla_modulo,
        COALESCE(m.nome_modulo, '') AS nome_modulo,
        COALESCE(NULLIF(m.tipo_modulo, ''), 'generico') AS tipo_modulo,
        COALESCE(f.nome_completo, '') AS formador_nome,
        COALESCE(f.titulo, '') AS formador_titulo,
        fm.data_inicio,
        fm.data_fim
    FROM horarios_celula c
    INNER JOIN formador_modulo fm ON fm.id = c.formador_modulo_id
    INNER JOIN modulos m ON m.id = fm.modulo_id
    LEFT JOIN formadores f ON f.id = fm.formador_id
    WHERE c.plano_id = $plano_id
    ORDER BY m.sigla_modulo ASC
");
if ($resModules) {
    while ($row = $resModules->fetch_assoc()) {
        $modules[] = $row;
    }
}

$cells = [];
$resCells = $conn->query("
    SELECT c.dia_semana, c.slot_codigo, m.sigla_modulo
    FROM horarios_celula c
    INNER JOIN formador_modulo fm ON fm.id = c.formador_modulo_id
    INNER JOIN modulos m ON m.id = fm.modulo_id
    WHERE c.plano_id = $plano_id
");
if ($resCells) {
    while ($row = $resCells->fetch_assoc()) {
        $cells[$row['dia_semana'] . '__' . $row['slot_codigo']] = $row['sigla_modulo'];
    }
}

$turnoLower = mb_strtolower(trim((string)($plano['nome_turno'] ?? '')), 'UTF-8');
$isNocturno = strpos($turnoLower, 'nocturno') !== false;

$slots = $isNocturno ? [
    ['code' => '17:00-17:45', 'label' => '17:00 - 17:45'],
    ['code' => '17:45-18:30', 'label' => '17:45 - 18:30'],
    ['code' => '18:35-19:20', 'label' => '18:35 - 19:20'],
    ['code' => '19:20-20:05', 'label' => '19:20 - 20:05'],
    ['code' => '20:10-20:55', 'label' => '20:10 - 20:55'],
    ['code' => '21:00-21:45', 'label' => '21:00 - 21:45'],
] : [
    ['code' => '07:00-07:45', 'label' => '07:00 - 07:45'],
    ['code' => '07:45-08:30', 'label' => '07:45 - 08:30'],
    ['code' => '08:35-09:20', 'label' => '08:35 - 09:20'],
    ['code' => '09:20-10:05', 'label' => '09:20 - 10:05'],
    ['code' => '10:10-10:55', 'label' => '10:10 - 10:55'],
    ['code' => '11:00-11:45', 'label' => '11:00 - 11:45'],
    ['code' => '12:05-12:50', 'label' => '12:05 - 12:50'],
    ['code' => '12:50-13:35', 'label' => '12:50 - 13:35'],
    ['code' => '13:40-14:25', 'label' => '13:40 - 14:25'],
    ['code' => '14:25-15:10', 'label' => '14:25 - 15:10'],
    ['code' => '15:10-15:55', 'label' => '15:10 - 15:55'],
    ['code' => '15:55-16:40', 'label' => '15:55 - 16:40'],
];

$days = [
    ['key' => 'seg', 'label' => '2ª Feira'],
    ['key' => 'ter', 'label' => '3ª Feira'],
    ['key' => 'qua', 'label' => '4ª Feira'],
    ['key' => 'qui', 'label' => '5ª Feira'],
    ['key' => 'sex', 'label' => '6ª Feira'],
];

$dataInicio = null;
$dataFim = null;
foreach ($modules as $module) {
    if (strtoupper(trim((string)($module['sigla_modulo'] ?? ''))) === 'RT') {
        continue;
    }
    if (!empty($module['data_inicio']) && ($dataInicio === null || $module['data_inicio'] < $dataInicio)) {
        $dataInicio = $module['data_inicio'];
    }
    if (!empty($module['data_fim']) && ($dataFim === null || $module['data_fim'] > $dataFim)) {
        $dataFim = $module['data_fim'];
    }
}

json_out([
    'ok' => true,
    'plano' => $plano,
    'data_inicio' => $dataInicio,
    'data_fim' => $dataFim,
    'is_nocturno' => $isNocturno ? 1 : 0,
    'days' => $days,
    'slots' => $slots,
    'cells' => $cells,
    'modules' => $modules,
]);
