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

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
if (!in_array($nivel, [1, 2], true)) {
    json_out(['ok' => false, 'msg' => 'Sem permissao.']);
}

if (!ensure_horarios_plano_schema($conn)) {
    json_out(['ok' => false, 'msg' => 'Erro ao validar horario.']);
}

$turma_id = (int)($_GET['turma_id'] ?? 0);
if ($turma_id <= 0) {
    json_out(['ok' => false, 'msg' => 'Parametros invalidos.']);
}

$scopeJoin = $nivel === 2 ? "
    INNER JOIN formador_modulo fm_scope ON fm_scope.turma_id = t.id
    INNER JOIN formadores f_scope ON f_scope.id = fm_scope.formador_id
" : "";
$scopeWhere = $nivel === 2 ? "AND f_scope.usuario_id = $usuario_id" : "";

$resTurma = $conn->query("
    SELECT
        t.id,
        t.nome_turma,
        tr.nome_turno,
        t.certificado_vocacional,
        t.ano_lectivo,
        c.nome_curso,
        CONCAT(
            IFNULL(NULLIF(dt.titulo,''), ''),
            CASE WHEN dt.titulo IS NULL OR dt.titulo = '' THEN '' ELSE ' ' END,
            dt.nome_completo
        ) AS director_turma,
        COUNT(DISTINCT fo.id) AS total_formandos,
        COUNT(DISTINCT fm_mod.id) AS total_modulos
    FROM turmas t
    $scopeJoin
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    LEFT JOIN cursos c ON c.id = t.curso_id
    LEFT JOIN formadores dt ON dt.id = t.dt_id
    LEFT JOIN formandos fo ON fo.turma_id = t.id
    LEFT JOIN formador_modulo fm_mod ON fm_mod.turma_id = t.id
    WHERE t.id = $turma_id
    $scopeWhere
    GROUP BY t.id, t.nome_turma, tr.nome_turno, t.certificado_vocacional, t.ano_lectivo, c.nome_curso, dt.titulo, dt.nome_completo
    LIMIT 1
");

if (!$resTurma || $resTurma->num_rows === 0) {
    json_out(['ok' => false, 'msg' => 'Turma nao encontrada.']);
}
$turma = $resTurma->fetch_assoc();

$formandos = [];
$resFormandos = $conn->query("
    SELECT id, nome_completo, sexo, codigo_formando, data_criacao, usuario_id
    FROM formandos
    WHERE turma_id = $turma_id
    ORDER BY nome_completo ASC
");
if ($resFormandos) {
    while ($row = $resFormandos->fetch_assoc()) {
        $row['estado'] = !empty($row['usuario_id']) ? 'Cadastrado' : 'Nao cadastrado';
        $formandos[] = $row;
    }
}

$resPlano = $conn->query("
    SELECT id, semestre, bloco, publicado, publicado_em, actualizado_em
    FROM horarios_plano
    WHERE turma_id = $turma_id
    ORDER BY publicado DESC, COALESCE(publicado_em, actualizado_em) DESC, id DESC
    LIMIT 1
");

$plano = null;
$horarioRows = [];
$diaLabel = '';
if ($resPlano && $resPlano->num_rows > 0) {
    $plano = $resPlano->fetch_assoc();
    $plano_id = (int)$plano['id'];

    $diaMap = [1 => 'seg', 2 => 'ter', 3 => 'qua', 4 => 'qui', 5 => 'sex'];
    $diaLabelMap = [
        'seg' => 'Segunda-feira',
        'ter' => 'Terca-feira',
        'qua' => 'Quarta-feira',
        'qui' => 'Quinta-feira',
        'sex' => 'Sexta-feira',
    ];
    date_default_timezone_set('Africa/Maputo');
    $dia = $diaMap[(int)date('N')] ?? '';
    $diaLabel = $diaLabelMap[$dia] ?? 'Fim de semana';

    if ($dia !== '') {
        $slotOrder = "
            FIELD(c.slot_codigo,
                '07:00-07:45','07:45-08:30','08:35-09:20','09:20-10:05','10:10-10:55','11:00-11:45',
                '12:05-12:50','12:50-13:35','13:40-14:25','14:25-15:10','15:10-15:55','15:55-16:40',
                '17:00-17:45','17:45-18:30','18:35-19:20','19:20-20:05','20:10-20:55','21:00-21:45'
            )
        ";
        $resHorario = $conn->query("
            SELECT
                c.slot_codigo,
                c.formador_modulo_id,
                COALESCE(NULLIF(m.sigla_modulo, ''), NULLIF(m.codigo_modulo, ''), CONCAT('MOD-', fm.modulo_id)) AS sigla_modulo,
                COALESCE(m.nome_modulo, '') AS nome_modulo,
                TRIM(CONCAT(COALESCE(fr.titulo, ''), ' ', COALESCE(fr.nome_completo, ''))) AS formador_nome,
                " . ($nivel === 2 ? "CASE WHEN fr.usuario_id = $usuario_id THEN 1 ELSE 0 END" : "0") . " AS is_current_formador
            FROM horarios_celula c
            INNER JOIN formador_modulo fm ON fm.id = c.formador_modulo_id
            INNER JOIN modulos m ON m.id = fm.modulo_id
            LEFT JOIN formadores fr ON fr.id = fm.formador_id
            WHERE c.plano_id = $plano_id
              AND c.dia_semana = '$dia'
            ORDER BY $slotOrder
        ");
        if ($resHorario) {
            while ($row = $resHorario->fetch_assoc()) {
                $parts = explode('-', (string)$row['slot_codigo']);
                $row['inicio_hora'] = trim($parts[0] ?? '');
                $row['fim_hora'] = trim($parts[1] ?? '');
                $horarioRows[] = $row;
            }
        }
    }
}

json_out([
    'ok' => true,
    'turma' => $turma,
    'formandos' => $formandos,
    'horario' => [
        'has_schedule' => $plano !== null,
        'dia_label' => $diaLabel,
        'rows' => $horarioRows,
        'plano' => $plano ? [
            'id' => (int)$plano['id'],
            'semestre' => (int)$plano['semestre'],
            'bloco' => (int)$plano['bloco'],
            'publicado' => (int)$plano['publicado'],
        ] : null,
    ],
]);
