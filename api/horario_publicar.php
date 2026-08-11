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

$plano_id = (int)$resPlano->fetch_assoc()['id'];
$resCount = $conn->query("SELECT COUNT(*) AS total FROM horarios_celula WHERE plano_id = $plano_id");
$total = $resCount ? (int)$resCount->fetch_assoc()['total'] : 0;
if ($total === 0) {
    json_out(['ok' => false, 'msg' => 'Horario vazio.']);
}

if (!$conn->query("UPDATE horarios_plano SET publicado = 1, publicado_em = NOW() WHERE id = $plano_id")) {
    log_erro('horario_publicar', $conn->error);
    json_out(['ok' => false, 'msg' => 'Erro ao publicar horario.']);
}

log_acao('horario_publicar', "Horario publicado: turma $turma_id | semestre $semestre | bloco $bloco");
json_out(['ok' => true]);
