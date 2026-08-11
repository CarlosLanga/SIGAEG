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
    log_erro('horario_grade_remove', 'Falha de conexao com BD');
    json_out(['ok' => false, 'msg' => 'Erro de conexao.']);
}

if (!ensure_horarios_plano_schema($conn)) {
    log_erro('horario_grade_remove', 'Falha ao garantir schema de publicacao.');
    json_out(['ok' => false, 'msg' => 'Erro ao validar horario.']);
}

$turma_id = (int)($_POST['turma_id'] ?? 0);
$semestre = (int)($_POST['semestre'] ?? 0);
$bloco = (int)($_POST['bloco'] ?? 0);
$dia_semana = trim((string)($_POST['dia_semana'] ?? ''));
$slot_codigo = trim((string)($_POST['slot_codigo'] ?? ''));

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
    !in_array($slot_codigo, $validSlots, true)
) {
    json_out(['ok' => false, 'msg' => 'Parametros invalidos.']);
}

$diaEsc = mysqli_real_escape_string($conn, $dia_semana);
$slotEsc = mysqli_real_escape_string($conn, $slot_codigo);

$resPlano = $conn->query("
    SELECT id
    FROM horarios_plano
    WHERE turma_id = $turma_id AND semestre = $semestre AND bloco = $bloco
    LIMIT 1
");

if (!$resPlano) {
    log_erro('horario_grade_remove', $conn->error);
    json_out(['ok' => false, 'msg' => 'Erro ao localizar plano.']);
}

if ($resPlano->num_rows === 0) {
    json_out(['ok' => true, 'msg' => 'Plano inexistente.']);
}

$plano = $resPlano->fetch_assoc();
$plano_id = (int)$plano['id'];

$conn->begin_transaction();

try {
    $sqlDelete = "
        DELETE FROM horarios_celula
        WHERE plano_id = $plano_id
          AND dia_semana = '$diaEsc'
          AND slot_codigo = '$slotEsc'
        LIMIT 1
    ";

    if (!$conn->query($sqlDelete)) {
        throw new Exception($conn->error);
    }

    $resCount = $conn->query("SELECT COUNT(*) AS total FROM horarios_celula WHERE plano_id = $plano_id");
    if (!$resCount) {
        throw new Exception($conn->error);
    }

    $total = (int)$resCount->fetch_assoc()['total'];

    if ($total === 0) {
        if (!$conn->query("DELETE FROM horarios_plano WHERE id = $plano_id LIMIT 1")) {
            throw new Exception($conn->error);
        }
    } else {
        if (!$conn->query("UPDATE horarios_plano SET actualizado_em = NOW(), publicado = 0, publicado_em = NULL WHERE id = $plano_id")) {
            throw new Exception($conn->error);
        }
    }

    $conn->commit();

    log_acao(
        'horario_grade_remove',
        "Slot removido: turma $turma_id | semestre $semestre | bloco $bloco | $dia_semana $slot_codigo"
    );

    json_out(['ok' => true]);
} catch (Throwable $e) {
    $conn->rollback();
    log_erro('horario_grade_remove', $e->getMessage());
    json_out(['ok' => false, 'msg' => 'Erro ao remover slot.']);
}
