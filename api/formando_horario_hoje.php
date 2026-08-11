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

if (!in_array($nivel, [1, 3], true)) {
    json_out(['ok' => false, 'msg' => 'Sem permissao.']);
}

if (!$conn) {
    json_out(['ok' => false, 'msg' => 'Erro de conexao.']);
}

if (!ensure_horarios_plano_schema($conn)) {
    json_out(['ok' => false, 'msg' => 'Erro ao validar horario.']);
}

$formando_id = (int)($_GET['formando_id'] ?? 0);
if ($nivel === 1 && $formando_id <= 0) {
    json_out(['ok' => false, 'msg' => 'Parametros invalidos.']);
}

$formandoWhere = $nivel === 3
    ? "f.usuario_id = $usuario_id"
    : "f.id = $formando_id";

$resFormando = $conn->query("
    SELECT f.id, f.turma_id, t.nome_turma, tr.nome_turno
    FROM formandos f
    LEFT JOIN turmas t ON t.id = f.turma_id
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    WHERE $formandoWhere
    LIMIT 1
");

if (!$resFormando || $resFormando->num_rows === 0) {
    json_out(['ok' => false, 'msg' => 'Formando nao encontrado.']);
}

$formando = $resFormando->fetch_assoc();
$turma_id = (int)($formando['turma_id'] ?? 0);
if ($turma_id <= 0) {
    json_out(['ok' => true, 'has_schedule' => false, 'rows' => [], 'turma_id' => 0, 'msg' => 'Formando sem turma.']);
}

$resPlano = $conn->query("
    SELECT id, semestre, bloco, publicado, publicado_em, actualizado_em
    FROM horarios_plano
    WHERE turma_id = $turma_id
    ORDER BY publicado DESC, COALESCE(publicado_em, actualizado_em) DESC, id DESC
    LIMIT 1
");

if (!$resPlano || $resPlano->num_rows === 0) {
    json_out([
        'ok' => true,
        'has_schedule' => false,
        'rows' => [],
        'turma' => $formando['nome_turma'] ?? '',
        'turno' => $formando['nome_turno'] ?? '',
        'turma_id' => $turma_id,
        'msg' => 'Turma sem horário publicado.'
    ]);
}

$plano = $resPlano->fetch_assoc();
$plano_id = (int)$plano['id'];

$diaMap = [
    1 => 'seg',
    2 => 'ter',
    3 => 'qua',
    4 => 'qui',
    5 => 'sex',
];

$diaLabelMap = [
    'seg' => 'Segunda-feira',
    'ter' => 'Terça-feira',
    'qua' => 'Quarta-feira',
    'qui' => 'Quinta-feira',
    'sex' => 'Sexta-feira',
];

$dayNum = (int)date('N');
$dia = $diaMap[$dayNum] ?? '';
$diaLabel = $diaLabelMap[$dia] ?? '';

if ($dia === '') {
    json_out([
        'ok' => true,
        'has_schedule' => true,
        'rows' => [],
        'turma' => $formando['nome_turma'] ?? '',
        'turno' => $formando['nome_turno'] ?? '',
        'turma_id' => $turma_id,
        'dia_semana' => '',
        'dia_label' => 'Fim de semana',
        'msg' => 'Sem aulas marcadas para hoje.',
        'plano' => [
            'id' => $plano_id,
            'semestre' => (int)($plano['semestre'] ?? 0),
            'bloco' => (int)($plano['bloco'] ?? 0),
            'publicado' => (int)($plano['publicado'] ?? 0),
        ],
    ]);
}

$slotOrder = "
    FIELD(c.slot_codigo,
        '07:00-07:45','07:45-08:30','08:35-09:20','09:20-10:05','10:10-10:55','11:00-11:45',
        '12:05-12:50','12:50-13:35','13:40-14:25','14:25-15:10','15:10-15:55','15:55-16:40',
        '17:00-17:45','17:45-18:30','18:35-19:20','19:20-20:05','20:10-20:55','21:00-21:45'
    )
";

$sql = "
    SELECT
        c.slot_codigo,
        c.formador_modulo_id,
        COALESCE(NULLIF(m.sigla_modulo, ''), NULLIF(m.codigo_modulo, ''), CONCAT('MOD-', fm.modulo_id)) AS sigla_modulo,
        COALESCE(m.nome_modulo, '') AS nome_modulo,
        COALESCE(NULLIF(m.tipo_modulo, ''), 'generico') AS tipo_modulo,
        COALESCE(fm.data_inicio, '') AS data_inicio,
        COALESCE(fm.data_fim, '') AS data_fim,
        TRIM(CONCAT(COALESCE(fr.titulo, ''), ' ', COALESCE(fr.nome_completo, ''))) AS formador_nome
    FROM horarios_celula c
    INNER JOIN formador_modulo fm ON fm.id = c.formador_modulo_id
    INNER JOIN modulos m ON m.id = fm.modulo_id
    LEFT JOIN formadores fr ON fr.id = fm.formador_id
    WHERE c.plano_id = $plano_id
      AND c.dia_semana = '$dia'
    ORDER BY $slotOrder
";

$res = $conn->query($sql);
if (!$res) {
    json_out(['ok' => false, 'msg' => 'Erro ao carregar horario.']);
}

date_default_timezone_set('Africa/Maputo');
$nowMinutes = ((int)date('H') * 60) + (int)date('i');
$rows = [];

while ($row = $res->fetch_assoc()) {
    $slot = (string)$row['slot_codigo'];
    $parts = explode('-', $slot);
    $inicio = trim($parts[0] ?? '');
    $fim = trim($parts[1] ?? '');

    $startMinutes = null;
    $endMinutes = null;
    if (preg_match('/^\d{2}:\d{2}$/', $inicio) && preg_match('/^\d{2}:\d{2}$/', $fim)) {
        $startMinutes = ((int)substr($inicio, 0, 2) * 60) + (int)substr($inicio, 3, 2);
        $endMinutes = ((int)substr($fim, 0, 2) * 60) + (int)substr($fim, 3, 2);
    }

    $status = 'upcoming';
    $progress = 0;
    if ($startMinutes !== null && $endMinutes !== null) {
        if ($nowMinutes < $startMinutes) {
            $status = 'upcoming';
            $progress = 0;
        } elseif ($nowMinutes >= $endMinutes) {
            $status = 'completed';
            $progress = 100;
        } else {
            $status = 'current';
            $duration = max(1, $endMinutes - $startMinutes);
            $elapsed = max(0, $nowMinutes - $startMinutes);
            $progress = (int)round(($elapsed / $duration) * 100);
        }
    }

    $row['inicio_hora'] = $inicio;
    $row['fim_hora'] = $fim;
    $row['status'] = $status;
    $row['progress_percent'] = $progress;
    $rows[] = $row;
}

json_out([
    'ok' => true,
    'has_schedule' => true,
    'rows' => $rows,
    'turma' => $formando['nome_turma'] ?? '',
    'turno' => $formando['nome_turno'] ?? '',
    'turma_id' => $turma_id,
    'dia_semana' => $dia,
    'dia_label' => $diaLabel,
    'plano' => [
        'id' => $plano_id,
        'semestre' => (int)($plano['semestre'] ?? 0),
        'bloco' => (int)($plano['bloco'] ?? 0),
        'publicado' => (int)($plano['publicado'] ?? 0),
    ],
    'now_hm' => date('H:i'),
]);
