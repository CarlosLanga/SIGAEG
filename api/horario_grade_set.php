<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'msg' => 'Metodo invalido.']);
}

if (($_SESSION['nivel_acesso'] ?? 0) != 1) {
    json_out(['ok' => false, 'msg' => 'Sem permissao.']);
}

if (!$conn) {
    log_erro('horario_grade_set', 'Falha de conexao com BD');
    json_out(['ok' => false, 'msg' => 'Erro de conexao.']);
}

if (!ensure_horarios_plano_schema($conn)) {
    log_erro('horario_grade_set', 'Falha ao garantir schema de publicacao.');
    json_out(['ok' => false, 'msg' => 'Erro ao validar horario.']);
}

$turma_id = (int)($_POST['turma_id'] ?? 0);
$semestre = (int)($_POST['semestre'] ?? 0);
$bloco = (int)($_POST['bloco'] ?? 0);
$dia_semana = trim((string)($_POST['dia_semana'] ?? ''));
$slot_codigo = trim((string)($_POST['slot_codigo'] ?? ''));
$formador_modulo_id = (int)($_POST['formador_modulo_id'] ?? 0);

$validDays = ['seg', 'ter', 'qua', 'qui', 'sex'];
$validSlots = [
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
];

if (
    $turma_id <= 0 ||
    !in_array($semestre, [1, 2], true) ||
    !in_array($bloco, [1, 2], true) ||
    !in_array($dia_semana, $validDays, true) ||
    !in_array($slot_codigo, $validSlots, true) ||
    $formador_modulo_id <= 0
) {
    json_out(['ok' => false, 'msg' => 'Parametros invalidos.']);
}

$turmaRes = $conn->query("SELECT id FROM turmas WHERE id = $turma_id LIMIT 1");
if (!$turmaRes || $turmaRes->num_rows === 0) {
    json_out(['ok' => false, 'msg' => 'Turma nao encontrada.']);
}

$fmSql = "
    SELECT
        fm.id,
        fm.turma_id,
        COALESCE(m.sigla_modulo, '') AS sigla_modulo,
        COALESCE(m.tipo_modulo, '') AS tipo_modulo,
        CASE
            WHEN fm.data_inicio IS NULL OR fm.data_fim IS NULL THEN 'Por iniciar'
            WHEN CURDATE() < fm.data_inicio THEN 'Por iniciar'
            WHEN CURDATE() > fm.data_fim THEN 'Concluido'
            ELSE 'Em vigencia'
        END AS estado_atual
    FROM formador_modulo fm
    INNER JOIN modulos m ON m.id = fm.modulo_id
    WHERE fm.id = $formador_modulo_id
    LIMIT 1
";
$fmRes = $conn->query($fmSql);
if (!$fmRes || $fmRes->num_rows === 0) {
    json_out(['ok' => false, 'msg' => 'Modulo da turma nao encontrado.']);
}

$fm = $fmRes->fetch_assoc();
if ((int)$fm['turma_id'] !== $turma_id) {
    json_out(['ok' => false, 'msg' => 'Modulo seleccionado nao pertence a turma.']);
}

$estadoAtual = strtolower(trim((string)$fm['estado_atual']));
$sigla = strtolower(trim((string)$fm['sigla_modulo']));
$tipo = strtolower(trim((string)$fm['tipo_modulo']));
if (in_array($estadoAtual, ['concluido', 'em vigencia'], true) && $sigla !== 'rt' && $tipo !== 'outro') {
    json_out(['ok' => false, 'msg' => 'Nao pode agendar modulo concluido ou em vigencia.']);
}

$diaEsc = mysqli_real_escape_string($conn, $dia_semana);
$slotEsc = mysqli_real_escape_string($conn, $slot_codigo);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);

$conn->begin_transaction();

try {
    $sqlPlano = "
        INSERT INTO horarios_plano (turma_id, semestre, bloco, criado_por, actualizado_em, publicado, publicado_em)
        VALUES ($turma_id, $semestre, $bloco, " . ($usuario_id > 0 ? $usuario_id : "NULL") . ", NOW(), 0, NULL)
        ON DUPLICATE KEY UPDATE
            id = LAST_INSERT_ID(id),
            actualizado_em = NOW(),
            publicado = 0,
            publicado_em = NULL
    ";

    if (!$conn->query($sqlPlano)) {
        throw new Exception($conn->error);
    }

    $plano_id = (int)$conn->insert_id;
    if ($plano_id <= 0) {
        throw new Exception('Falha ao obter plano_id.');
    }

    $sqlCell = "
        INSERT INTO horarios_celula (plano_id, dia_semana, slot_codigo, formador_modulo_id, actualizado_em)
        VALUES ($plano_id, '$diaEsc', '$slotEsc', $formador_modulo_id, NOW())
        ON DUPLICATE KEY UPDATE
            formador_modulo_id = VALUES(formador_modulo_id),
            actualizado_em = NOW()
    ";

    if (!$conn->query($sqlCell)) {
        throw new Exception($conn->error);
    }

    $conn->commit();

    log_acao(
        'horario_grade_set',
        "Horario actualizado: turma $turma_id | semestre $semestre | bloco $bloco | $dia_semana $slot_codigo | fm $formador_modulo_id"
    );

    json_out(['ok' => true]);
} catch (Throwable $e) {
    $conn->rollback();
    log_erro('horario_grade_set', $e->getMessage());
    json_out(['ok' => false, 'msg' => 'Erro ao gravar horario.']);
}
