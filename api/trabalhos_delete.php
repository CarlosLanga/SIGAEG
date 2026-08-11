<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'message' => 'Metodo invalido.']);
}

if (($_SESSION['nivel_acesso'] ?? 0) != 1) {
    json_out(['ok' => false, 'message' => 'Sem permissao.']);
}

if (!$conn) {
    json_out(['ok' => false, 'message' => 'Erro de ligacao a base de dados.']);
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    json_out(['ok' => false, 'message' => 'Trabalho invalido.']);
}

if (!$conn->query("DELETE FROM trabalhos WHERE id = $id LIMIT 1")) {
    log_erro('trabalhos_delete', $conn->error);
    json_out(['ok' => false, 'message' => 'Erro ao remover o trabalho.']);
}

log_acao('trabalhos_delete', "Trabalho removido: $id");
json_out(['ok' => true]);
