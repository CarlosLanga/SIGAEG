<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    log_erro('formador_delete', 'Metodo invalido');
    echo json_encode(['ok' => false, 'msg' => 'Método inválido.']);
    exit;
}

if (!$conn) {
    log_erro('formador_delete', 'Falha de conexao com BD');
    echo json_encode(['ok' => false, 'msg' => 'Falha de conexão com BD.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    log_erro('formador_delete', 'ID invalido');
    echo json_encode(['ok' => false, 'msg' => 'ID inválido.']);
    exit;
}

$res = $conn->query("SELECT id, nome_completo, email FROM formadores WHERE id = $id LIMIT 1");
if (!$res || $res->num_rows === 0) {
    log_erro('formador_delete', 'Formador nao encontrado');
    echo json_encode(['ok' => false, 'msg' => 'Formador não encontrado.']);
    exit;
}
$formador = $res->fetch_assoc();

$temTurmas = $conn->query("SELECT id FROM turmas WHERE dt_id = $id LIMIT 1");
if ($temTurmas && $temTurmas->num_rows > 0) {
    log_erro('formador_delete', "Tentativa de remover formador com turmas: {$formador['nome_completo']} | ID $id");
    echo json_encode(['ok' => false, 'msg' => 'Não é possível remover: o formador é director de turma.']);
    exit;
}

$temModulos = $conn->query("SELECT id FROM formador_modulo WHERE formador_id = $id LIMIT 1");
if ($temModulos && $temModulos->num_rows > 0) {
    log_erro('formador_delete', "Tentativa de remover formador com módulos: {$formador['nome_completo']} | ID $id");
    echo json_encode(['ok' => false, 'msg' => 'Não é possível remover: o formador lecciona módulos.']);
    exit;
}

$conn->query("DELETE FROM formador_curso WHERE formador_id = $id");
$conn->query("DELETE FROM codigos_autorizados WHERE email_dono = '{$formador['email']}' AND nivel_destinado = 2");

if (!$conn->query("DELETE FROM formadores WHERE id = $id")) {
    log_erro('formador_delete', $conn->error);
    echo json_encode(['ok' => false, 'msg' => 'Erro ao remover formador.']);
    exit;
}

log_acao('formador_delete', "Formador removido: {$formador['nome_completo']} | ID $id");
echo json_encode(['ok' => true]);
