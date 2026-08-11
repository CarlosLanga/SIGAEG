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

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

if (!in_array($nivel, [1, 2], true) || $usuarioId <= 0) {
    json_out(['ok' => false, 'message' => 'Sem permissão.']);
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    json_out(['ok' => false, 'message' => 'ID inválido.']);
}

$resCheck = $conn->query("SELECT * FROM ficheiros WHERE id = $id");
if (!$resCheck || $resCheck->num_rows === 0) {
    json_out(['ok' => false, 'message' => 'Ficheiro não encontrado.']);
}
$fileInfo = $resCheck->fetch_assoc();

if ($nivel === 2 && (int)$fileInfo['criado_por'] !== $usuarioId) {
    json_out(['ok' => false, 'message' => 'Não pode eliminar este ficheiro.']);
}

if (!$conn->query("DELETE FROM ficheiros WHERE id = $id")) {
    log_erro('ficheiros_delete', $conn->error);
    json_out(['ok' => false, 'message' => 'Erro ao eliminar ficheiro.']);
}

if (!empty($fileInfo['caminho'])) {
    $caminho = __DIR__ . '/../' . $fileInfo['caminho'];
    if (file_exists($caminho)) {
        @unlink($caminho);
    }
}

log_acao('ficheiros_delete', "Ficheiro eliminado: " . $fileInfo['titulo']);
json_out(['ok' => true]);
