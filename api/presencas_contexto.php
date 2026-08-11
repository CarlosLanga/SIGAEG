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
    json_out(['ok' => false, 'status' => 'erro', 'mensagem' => 'Erro de ligação.']);
}

$turma_id = (int)($_GET['turma_id'] ?? 0);
$formador_modulo_id = (int)($_GET['formador_modulo_id'] ?? 0);
$data = trim((string)($_GET['data'] ?? ''));

if ($turma_id <= 0 || $formador_modulo_id <= 0 || $data === '') {
    json_out(['ok' => false, 'status' => 'erro', 'mensagem' => 'Parâmetros inválidos.']);
}

$dt = DateTime::createFromFormat('Y-m-d', $data);
if (!$dt) {
    json_out(['ok' => false, 'status' => 'erro', 'mensagem' => 'Data inválida.']);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$formadorJoin = "";
$formadorWhere = "";
if ($nivel === 2) {
    $formadorJoin = "INNER JOIN formadores f ON f.id = fm.formador_id";
    $formadorWhere = "AND f.usuario_id = $usuarioId";
}

$resModulo = $conn->query("
    SELECT fm.data_inicio, fm.data_fim
    FROM formador_modulo fm
    $formadorJoin
    WHERE fm.id = $formador_modulo_id AND fm.turma_id = $turma_id
    $formadorWhere
    LIMIT 1
");
if (!$resModulo || $resModulo->num_rows === 0) {
    json_out(['ok' => true, 'status' => 'modulo_inactivo', 'mensagem' => 'Módulo não pertence à turma seleccionada.']);
}
$modulo = $resModulo->fetch_assoc();
$inicio = $modulo['data_inicio'] ?? null;
$fim = $modulo['data_fim'] ?? null;
if ($inicio && $data < $inicio) {
    json_out(['ok' => true, 'status' => 'modulo_inactivo', 'mensagem' => 'Módulo ainda não está em vigência.']);
}
if ($fim && $data > $fim) {
    json_out(['ok' => true, 'status' => 'modulo_inactivo', 'mensagem' => 'Módulo já não está em vigência.']);
}

$dayNum = (int)$dt->format('N'); // 1=Seg ... 7=Dom
$dayMap = [1 => 'seg', 2 => 'ter', 3 => 'qua', 4 => 'qui', 5 => 'sex'];
$dia_semana = $dayMap[$dayNum] ?? '';
if ($dia_semana === '') {
    json_out(['ok' => true, 'status' => 'sem_aulas', 'mensagem' => 'Sem aulas nessa data.']);
}

$resHorario = $conn->query("SELECT id FROM horarios_plano WHERE turma_id = $turma_id LIMIT 1");
if (!$resHorario || $resHorario->num_rows === 0) {
    json_out(['ok' => true, 'status' => 'sem_horario', 'mensagem' => 'Sem horário para esta turma.']);
}

$resPlan = $conn->query("
    SELECT p.id, p.actualizado_em
    FROM horarios_plano p
    INNER JOIN horarios_celula c ON c.plano_id = p.id
    WHERE p.turma_id = $turma_id AND c.formador_modulo_id = $formador_modulo_id
    GROUP BY p.id
    ORDER BY p.actualizado_em DESC
    LIMIT 1
");

if (!$resPlan || $resPlan->num_rows === 0) {
    json_out(['ok' => true, 'status' => 'modulo_nao_registado', 'mensagem' => 'Módulo não registado no horário.']);
}

$plano_horario = $resPlan->fetch_assoc();
$plano_id = (int)$plano_horario['id'];

$resSlots = $conn->query("
    SELECT c.slot_codigo
    FROM horarios_celula c
    WHERE c.plano_id = $plano_id
      AND c.formador_modulo_id = $formador_modulo_id
      AND c.dia_semana = '$dia_semana'
    ORDER BY c.slot_codigo ASC
");
$slots = [];
if ($resSlots) {
    while ($row = $resSlots->fetch_assoc()) {
        $slots[] = $row['slot_codigo'];
    }
}

if (!$slots) {
    json_out(['ok' => true, 'status' => 'sem_aulas', 'mensagem' => 'Sem aulas nessa data.']);
}

$resPres = $conn->query("
    SELECT id, estado
    FROM presencas_plano
    WHERE turma_id = $turma_id AND formador_modulo_id = $formador_modulo_id AND data_aula = '$data'
    LIMIT 1
");
$presenca_plano_id = 0;
$estado = 'rascunho';
if ($resPres && $resPres->num_rows > 0) {
    $pres = $resPres->fetch_assoc();
    $presenca_plano_id = (int)$pres['id'];
    $estado = $pres['estado'] ?? 'rascunho';
}

$selected_slots = [];
if ($presenca_plano_id > 0) {
    $resSel = $conn->query("
        SELECT slot_codigo
        FROM presencas_intervalo
        WHERE plano_id = $presenca_plano_id
    ");
    if ($resSel) {
        while ($row = $resSel->fetch_assoc()) {
            $selected_slots[] = $row['slot_codigo'];
        }
    }
}

$formandos = [];
$resFormandos = $conn->query("
    SELECT id, nome_completo, codigo_formando
    FROM formandos
    WHERE turma_id = $turma_id
    ORDER BY nome_completo ASC
");
if ($resFormandos) {
    while ($row = $resFormandos->fetch_assoc()) {
        $formandos[] = $row;
    }
}

$registos = [];
if ($presenca_plano_id > 0) {
    $resReg = $conn->query("
        SELECT formando_id, situacao, observacao
        FROM presencas_registo
        WHERE plano_id = $presenca_plano_id
    ");
    if ($resReg) {
        while ($row = $resReg->fetch_assoc()) {
            $registos[(int)$row['formando_id']] = $row;
        }
    }
}

$permanentes = [];
$resPerm = $conn->query("
    SELECT pr.formando_id, pr.situacao, pp.data_aula
    FROM presencas_registo pr
    INNER JOIN presencas_plano pp ON pp.id = pr.plano_id
    WHERE pp.turma_id = $turma_id
    ORDER BY pp.data_aula DESC, pr.id DESC
");
if ($resPerm) {
    $lastStatus = [];
    while ($row = $resPerm->fetch_assoc()) {
        $fid = (int)$row['formando_id'];
        if (!isset($lastStatus[$fid])) {
            $lastStatus[$fid] = $row['situacao'];
        }
    }
    foreach ($lastStatus as $fid => $situ) {
        if (in_array($situ, ['WD', 'D'], true)) {
            $permanentes[$fid] = $situ;
        }
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

foreach ($formandos as $f) {
    $fid = (int)$f['id'];
    $situacao = 'Presente';
    $observacao = '';

    if (isset($registos[$fid])) {
        $situacao = $registos[$fid]['situacao'] ?? 'Presente';
        $observacao = $registos[$fid]['observacao'] ?? '';
    }

    if (isset($permanentes[$fid]) && !isset($registos[$fid])) {
        $situacao = $permanentes[$fid];
    }

    $rows[] = [
        'id' => $fid,
        'nome_completo' => $f['nome_completo'],
        'codigo_formando' => $f['codigo_formando'],
        'situacao' => $situacao,
        'observacao' => $observacao,
    ];

    $stats['total']++;
    if ($situacao === 'Presente') $stats['presentes']++;
    if ($situacao === 'Ausente') $stats['ausentes']++;
    if ($situacao === 'WD') $stats['wd']++;
    if ($situacao === 'D') $stats['d']++;
}

json_out([
    'ok' => true,
    'status' => 'ok',
    'mensagem' => 'Horário encontrado para a data seleccionada.',
    'plano_id' => $presenca_plano_id,
    'estado' => $estado,
    'slots' => $slots,
    'selected_slots' => $selected_slots,
    'rows' => $rows,
    'stats' => $stats,
    'permanentes' => $permanentes,
]);
