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
    json_out(['ok' => false, 'msg' => 'Método inválido.']);
}

if (!$conn) {
    json_out(['ok' => false, 'msg' => 'Erro de ligação.']);
}

$turma_id = (int)($_POST['turma_id'] ?? 0);
$formador_modulo_id = (int)($_POST['formador_modulo_id'] ?? 0);
$data = trim((string)($_POST['data'] ?? ''));

if ($turma_id <= 0 || $formador_modulo_id <= 0 || $data === '') {
    json_out(['ok' => false, 'msg' => 'Parâmetros inválidos.']);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$formadorJoin = "";
$formadorWhere = "";
if ($nivel === 2) {
    $formadorJoin = "INNER JOIN formadores f ON f.id = fm.formador_id";
    $formadorWhere = "AND f.usuario_id = $usuarioId";
}

$resPermissao = $conn->query("
    SELECT fm.id
    FROM formador_modulo fm
    $formadorJoin
    WHERE fm.id = $formador_modulo_id
      AND fm.turma_id = $turma_id
      $formadorWhere
    LIMIT 1
");
if (!$resPermissao || $resPermissao->num_rows === 0) {
    json_out(['ok' => false, 'msg' => 'Sem permissao para publicar presencas nesta turma/modulo.']);
}

$res = $conn->query("
    SELECT id
    FROM presencas_plano
    WHERE turma_id = $turma_id AND formador_modulo_id = $formador_modulo_id AND data_aula = '$data'
    LIMIT 1
");
if (!$res || $res->num_rows === 0) {
    json_out(['ok' => false, 'msg' => 'Não existe rascunho para publicar.']);
}

$plano_id = (int)$res->fetch_assoc()['id'];
if (!$conn->query("UPDATE presencas_plano SET estado = 'publicado', publicado_em = NOW() WHERE id = $plano_id")) {
    json_out(['ok' => false, 'msg' => 'Erro ao publicar presenças.']);
}

json_out(['ok' => true]);
