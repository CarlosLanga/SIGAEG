<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload): void {
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'message' => 'Método inválido.']);
}

if (!$conn) {
    json_out(['ok' => false, 'message' => 'Erro de ligação à base de dados.']);
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    json_out(['ok' => false, 'message' => 'Avaliação inválida.']);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$scopeWhere = $nivel === 2 ? "AND f.usuario_id = $usuario_id" : "";

$resPermissao = $conn->query("
    SELECT a.id
    FROM avaliacoes a
    LEFT JOIN formador_modulo fm ON fm.turma_id = a.turma_id AND fm.modulo_id = a.modulo_id
    LEFT JOIN formadores f ON f.id = fm.formador_id
    WHERE a.id = $id
    $scopeWhere
    LIMIT 1
");

if (!$resPermissao || $resPermissao->num_rows === 0) {
    json_out(['ok' => false, 'message' => 'Sem permissao para remover esta avaliacao.']);
}

if (!$conn->query("DELETE FROM avaliacoes WHERE id = $id LIMIT 1")) {
    log_erro('avaliacoes_delete', $conn->error);
    json_out(['ok' => false, 'message' => 'Erro ao remover a avaliação.']);
}

json_out(['ok' => true]);
