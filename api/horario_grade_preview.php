<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
if (!in_array($nivel, [1, 2, 3, 4], true)) {
    json_out(['ok' => false, 'msg' => 'Sem permissao.']);
}

if (!$conn) {
    json_out(['ok' => false, 'msg' => 'Erro de conexao.']);
}

if (!ensure_horarios_plano_schema($conn)) {
    json_out(['ok' => false, 'msg' => 'Erro ao validar horario.']);
}

$turma_id = (int)($_GET['turma_id'] ?? 0);
$semestre = (int)($_GET['semestre'] ?? 0);
$bloco = (int)($_GET['bloco'] ?? 0);

if ($turma_id <= 0 || !in_array($semestre, [1, 2], true) || !in_array($bloco, [1, 2], true)) {
    json_out(['ok' => false, 'msg' => 'Parametros invalidos.']);
}

$scopeJoin = $nivel === 2 ? "
    INNER JOIN formador_modulo fm_scope ON fm_scope.turma_id = t.id
    INNER JOIN formadores f_scope ON f_scope.id = fm_scope.formador_id
" : "";
$scopeWhere = "";
if ($nivel === 2) {
    $scopeWhere = "AND f_scope.usuario_id = $usuario_id";
} elseif ($nivel === 3) {
    $scopeWhere = "AND EXISTS (
        SELECT 1
        FROM formandos fo_scope
        WHERE fo_scope.turma_id = t.id
          AND fo_scope.usuario_id = $usuario_id
    )";
} elseif ($nivel === 4) {
    $educando_id = (int)($_GET['educando_id'] ?? 0);
    $scopeWhere = "AND EXISTS (
        SELECT 1
        FROM formandos fo_scope
        INNER JOIN encarregado_formando ef_scope ON ef_scope.formando_id = fo_scope.id
        INNER JOIN encarregados enc_scope ON enc_scope.id = ef_scope.encarregado_id
        WHERE fo_scope.turma_id = t.id
          AND enc_scope.usuario_id = $usuario_id
          " . ($educando_id > 0 ? "AND fo_scope.id = $educando_id" : "") . "
    )";
}

$turmaRes = $conn->query("
    SELECT t.nome_turma, t.ano_lectivo, tr.nome_turno
    FROM turmas t
    $scopeJoin
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    WHERE t.id = $turma_id
    $scopeWhere
    LIMIT 1
");
if (!$turmaRes || $turmaRes->num_rows === 0) {
    json_out(['ok' => false, 'msg' => 'Turma nao encontrada.']);
}
$turma = $turmaRes->fetch_assoc();

$resPlano = $conn->query("
    SELECT id, publicado, publicado_em, actualizado_em
    FROM horarios_plano
    WHERE turma_id = $turma_id AND semestre = $semestre AND bloco = $bloco
    LIMIT 1
");

$plano_id = 0;
$publicado = 0;
$publicado_em = null;
$actualizado_em = null;
if ($resPlano && $resPlano->num_rows > 0) {
    $plano = $resPlano->fetch_assoc();
    $plano_id = (int)$plano['id'];
    $publicado = (int)($plano['publicado'] ?? 0);
    $publicado_em = $plano['publicado_em'] ?? null;
    $actualizado_em = $plano['actualizado_em'] ?? null;
}

$cells = [];
$cellDetails = [];
if ($plano_id > 0) {
    $resCells = $conn->query("
        SELECT c.dia_semana, c.slot_codigo,
               COALESCE(m.sigla_modulo, '') AS sigla_modulo,
               COALESCE(m.nome_modulo, '') AS nome_modulo,
               COALESCE(m.tipo_modulo, '') AS tipo_modulo,
               TRIM(CONCAT(COALESCE(fr.titulo, ''), ' ', COALESCE(fr.nome_completo, ''))) AS formador_nome
        FROM horarios_celula c
        INNER JOIN formador_modulo fm ON fm.id = c.formador_modulo_id
        INNER JOIN modulos m ON m.id = fm.modulo_id
        LEFT JOIN formadores fr ON fr.id = fm.formador_id
        WHERE c.plano_id = $plano_id
    ");
    if ($resCells) {
        while ($row = $resCells->fetch_assoc()) {
            $key = $row['dia_semana'] . '__' . $row['slot_codigo'];
            $cells[$key] = $row['sigla_modulo'];
            $cellDetails[$key] = [
                'sigla_modulo' => $row['sigla_modulo'],
                'nome_modulo' => $row['nome_modulo'],
                'tipo_modulo' => $row['tipo_modulo'],
                'formador_nome' => $row['formador_nome'],
            ];
        }
    }
}

$turnoLower = mb_strtolower(trim((string)($turma['nome_turno'] ?? '')), 'UTF-8');
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

json_out([
    'ok' => true,
    'semestre' => $semestre,
    'bloco' => $bloco,
    'plano_id' => $plano_id,
    'publicado' => $publicado,
    'publicado_em' => $publicado_em,
    'actualizado_em' => $actualizado_em,
    'is_nocturno' => $isNocturno ? 1 : 0,
    'turma' => $turma,
    'slots' => $slots,
    'days' => $days,
    'cells' => $cells,
    'cell_details' => $cellDetails
]);
