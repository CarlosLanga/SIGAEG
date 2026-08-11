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
    log_erro('horario_grade_clear', 'Falha de conexao com BD');
    json_out(['ok' => false, 'msg' => 'Erro de conexao.']);
}

if (!ensure_horarios_plano_schema($conn)) {
    log_erro('horario_grade_clear', 'Falha ao garantir schema de publicacao.');
    json_out(['ok' => false, 'msg' => 'Erro ao validar horario.']);
}

$turma_id = (int)($_POST['turma_id'] ?? 0);
$semestre = (int)($_POST['semestre'] ?? 0);
$bloco = (int)($_POST['bloco'] ?? 0);

if ($turma_id <= 0 || !in_array($semestre, [1, 2], true) || !in_array($bloco, [1, 2], true)) {
    json_out(['ok' => false, 'msg' => 'Parametros invalidos.']);
}

$resPlano = $conn->query("
    SELECT id
    FROM horarios_plano
    WHERE turma_id = $turma_id AND semestre = $semestre AND bloco = $bloco
    LIMIT 1
");

if (!$resPlano) {
    log_erro('horario_grade_clear', $conn->error);
    json_out(['ok' => false, 'msg' => 'Erro ao localizar plano.']);
}

if ($resPlano->num_rows === 0) {
    json_out(['ok' => true]);
}

$plano_id = (int)$resPlano->fetch_assoc()['id'];

$conn->begin_transaction();

try {
    if (!$conn->query("DELETE FROM horarios_celula WHERE plano_id = $plano_id")) {
        throw new Exception($conn->error);
    }

    if (!$conn->query("DELETE FROM horarios_plano WHERE id = $plano_id LIMIT 1")) {
        throw new Exception($conn->error);
    }

    $conn->commit();
    log_acao('horario_grade_clear', "Horario limpo: turma $turma_id | semestre $semestre | bloco $bloco");
    json_out(['ok' => true]);
} catch (Throwable $e) {
    $conn->rollback();
    log_erro('horario_grade_clear', $e->getMessage());
    json_out(['ok' => false, 'msg' => 'Erro ao limpar horario.']);
}
