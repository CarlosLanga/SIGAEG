<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    log_erro('turma_delete', 'Metodo invalido');
    echo json_encode(['ok' => false, 'msg' => 'Método inválido']);
    exit;
}

if (!$conn) {
    log_erro('turma_delete', 'Falha de conexao com BD');
    echo json_encode(['ok' => false, 'msg' => 'Falha de conexão com a base de dados']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    log_erro('turma_delete', 'ID invalido');
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit;
}

$res = $conn->query("SELECT nome_turma FROM turmas WHERE id = $id LIMIT 1");
if (!$res || $res->num_rows === 0) {
    log_erro('turma_delete', 'Turma nao encontrada');
    echo json_encode(['ok' => false, 'msg' => 'Turma não encontrada']);
    exit;
}

$row = $res->fetch_assoc();
$nome_turma = $row['nome_turma'] ?? '';

$countRes = $conn->query("SELECT COUNT(*) AS total FROM formandos WHERE turma_id = $id");
$totalFormandos = $countRes ? (int)$countRes->fetch_assoc()['total'] : 0;
if ($totalFormandos > 0) {
    log_erro('turma_delete', "Tentativa de remover turma com formandos: $nome_turma | ID $id");
    echo json_encode(['ok' => false, 'msg' => 'Não é possível remover: existem formandos nesta turma.']);
    exit;
}

if (!$conn->query("DELETE FROM turmas WHERE id = $id")) {
    log_erro('turma_delete', $conn->error);
    echo json_encode(['ok' => false, 'msg' => 'Erro ao remover turma']);
    exit;
}

log_acao('turma_delete', "Turma removida: $nome_turma | ID $id");
echo json_encode(['ok' => true]);
exit;
