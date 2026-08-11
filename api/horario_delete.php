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
    json_out(['ok' => false, 'msg' => 'Erro de conexao.']);
}

if (!ensure_horarios_plano_schema($conn)) {
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

if (!$resPlano || $resPlano->num_rows === 0) {
    json_out(['ok' => false, 'msg' => 'Horario inexistente.']);
}

$resCheck = $conn->query("
    SELECT COUNT(*) AS total
    FROM formador_modulo fm
    WHERE fm.turma_id = $turma_id
      AND (
        fm.data_inicio IS NULL OR
        fm.data_fim IS NULL OR
        CURDATE() < fm.data_inicio OR
        CURDATE() <= fm.data_fim
      )
");
$total = $resCheck ? (int)$resCheck->fetch_assoc()['total'] : 0;
if ($total > 0) {
    json_out(['ok' => false, 'msg' => 'Horario tem modulos em vigencia ou por iniciar.']);
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
    log_acao('horario_delete', "Horario removido: turma $turma_id | semestre $semestre | bloco $bloco");
    json_out(['ok' => true]);
} catch (Throwable $e) {
    $conn->rollback();
    log_erro('horario_delete', $e->getMessage());
    json_out(['ok' => false, 'msg' => 'Erro ao remover horario.']);
}
