<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

if (($_SESSION['nivel_acesso'] ?? 0) != 1) {
    json_out(['ok' => false, 'msg' => 'Sem permissao.']);
}

if (!$conn) {
    log_erro('horario_grade_get', 'Falha de conexao com BD');
    json_out(['ok' => false, 'msg' => 'Erro de conexao.']);
}

if (!ensure_horarios_plano_schema($conn)) {
    log_erro('horario_grade_get', 'Falha ao garantir schema de publicacao.');
    json_out(['ok' => false, 'msg' => 'Erro ao validar horario.']);
}

$turma_id = (int)($_GET['turma_id'] ?? 0);
$semestre = (int)($_GET['semestre'] ?? 0);
$bloco = (int)($_GET['bloco'] ?? 0);

if ($turma_id <= 0 || !in_array($semestre, [1, 2], true) || !in_array($bloco, [1, 2], true)) {
    json_out(['ok' => false, 'msg' => 'Parametros invalidos.']);
}

$turmaRes = $conn->query("
    SELECT
        t.id,
        t.nome_turma,
        t.ano_lectivo,
        tr.nome_turno,
        CONCAT(
            COALESCE(NULLIF(f.titulo, ''), ''),
            CASE WHEN f.titulo IS NULL OR f.titulo = '' THEN '' ELSE ' ' END,
            COALESCE(f.nome_completo, '')
        ) AS director_turma
    FROM turmas t
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    LEFT JOIN formadores f ON f.id = t.dt_id
    WHERE t.id = $turma_id
    LIMIT 1
");
if (!$turmaRes || $turmaRes->num_rows === 0) {
    json_out(['ok' => false, 'msg' => 'Turma nao encontrada.']);
}
$turma = $turmaRes->fetch_assoc();

$modules = [];
$sqlModules = "
    SELECT
        fm.id AS formador_modulo_id,
        fm.modulo_id,
        COALESCE(NULLIF(m.sigla_modulo, ''), m.codigo_modulo, CONCAT('MOD-', fm.modulo_id)) AS sigla_modulo,
        COALESCE(m.nome_modulo, '') AS nome_modulo,
        COALESCE(NULLIF(m.tipo_modulo, ''), 'generico') AS tipo_modulo,
        COALESCE(f.nome_completo, '') AS formador_nome,
        COALESCE(f.titulo, '') AS formador_titulo,
        fm.data_inicio,
        fm.data_fim,
        CASE
            WHEN fm.data_inicio IS NULL OR fm.data_fim IS NULL THEN 'Por iniciar'
            WHEN CURDATE() < fm.data_inicio THEN 'Por iniciar'
            WHEN CURDATE() > fm.data_fim THEN 'Concluido'
            ELSE 'Em vigencia'
        END AS estado_atual
    FROM formador_modulo fm
    INNER JOIN turmas t ON t.id = fm.turma_id
    INNER JOIN modulos m ON m.id = fm.modulo_id AND m.curso_id = t.curso_id
    LEFT JOIN formadores f ON f.id = fm.formador_id
    WHERE fm.turma_id = $turma_id
    ORDER BY
        CASE WHEN COALESCE(NULLIF(m.tipo_modulo, ''), 'generico') = 'generico' THEN 0 ELSE 1 END,
        m.sigla_modulo ASC
";

$resModules = $conn->query($sqlModules);
if (!$resModules) {
    log_erro('horario_grade_get', $conn->error);
    json_out(['ok' => false, 'msg' => 'Erro ao carregar modulos.']);
}

while ($row = $resModules->fetch_assoc()) {
    $estado = strtolower(trim((string)$row['estado_atual']));
    $sigla = strtolower(trim((string)($row['sigla_modulo'] ?? '')));
    $tipo = strtolower(trim((string)($row['tipo_modulo'] ?? '')));
    $row['disabled'] = (in_array($estado, ['concluido', 'em vigencia'], true) && $sigla !== 'rt' && $tipo !== 'outro') ? 1 : 0;
    $modules[] = $row;
}

$plano_id = 0;
$publicado = 0;
$publicado_em = null;
$actualizado_em = null;
$resPlano = $conn->query("
    SELECT id, publicado, publicado_em, actualizado_em
    FROM horarios_plano
    WHERE turma_id = $turma_id AND semestre = $semestre AND bloco = $bloco
    LIMIT 1
");

if (!$resPlano) {
    log_erro('horario_grade_get', $conn->error);
    json_out(['ok' => false, 'msg' => 'Erro ao carregar plano.']);
}

if ($resPlano->num_rows > 0) {
    $plano = $resPlano->fetch_assoc();
    $plano_id = (int)$plano['id'];
    $publicado = (int)($plano['publicado'] ?? 0);
    $publicado_em = $plano['publicado_em'] ?? null;
    $actualizado_em = $plano['actualizado_em'] ?? null;
}

$cells = [];
if ($plano_id > 0) {
    $sqlCells = "
        SELECT
            c.dia_semana,
            c.slot_codigo,
            c.formador_modulo_id
        FROM horarios_celula c
        WHERE c.plano_id = $plano_id
        ORDER BY
            FIELD(c.dia_semana, 'seg', 'ter', 'qua', 'qui', 'sex'),
            FIELD(c.slot_codigo,
                '07:00-07:45',
                '07:45-08:30',
                '08:35-09:20',
                '09:20-10:05',
                '10:10-10:55',
                '11:00-11:45',
                '12:05-12:50',
                '12:50-13:35',
                '13:40-14:25',
                '14:25-15:10',
                '15:10-15:55',
                '15:55-16:40',
                '17:00-17:45',
                '17:45-18:30',
                '18:35-19:20',
                '19:20-20:05',
                '20:10-20:55',
                '21:00-21:45'
            )
    ";

    $resCells = $conn->query($sqlCells);
    if (!$resCells) {
        log_erro('horario_grade_get', $conn->error);
        json_out(['ok' => false, 'msg' => 'Erro ao carregar celulas.']);
    }

    while ($row = $resCells->fetch_assoc()) {
        $cells[] = $row;
    }
}

json_out([
    'ok' => true,
    'exists' => count($cells) > 0,
    'plano_id' => $plano_id,
    'publicado' => $publicado,
    'publicado_em' => $publicado_em,
    'actualizado_em' => $actualizado_em,
    'turma' => $turma,
    'modules' => $modules,
    'cells' => $cells
]);
