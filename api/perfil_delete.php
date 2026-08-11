<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['estado' => 'erro', 'message' => 'Método inválido.']);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['estado' => 'erro', 'message' => 'Sessão expirada.']);
    exit;
}

if (!$conn) {
    log_erro('perfil_delete', 'Falha de ligação à base de dados');
    echo json_encode(['estado' => 'erro', 'message' => 'Erro de ligação à base de dados.']);
    exit;
}

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
if (!$usuarioId) {
    echo json_encode(['estado' => 'erro', 'message' => 'Utilizador inválido.']);
    exit;
}

$foto = '';
$resFoto = $conn->query("SELECT foto FROM usuarios WHERE id = $usuarioId LIMIT 1");
if ($resFoto && $resFoto->num_rows > 0) {
    $foto = $resFoto->fetch_assoc()['foto'] ?? '';
}

$conn->begin_transaction();
try {
    $conn->query("UPDATE administradores SET usuario_id = NULL WHERE usuario_id = $usuarioId");
    $conn->query("UPDATE formadores SET usuario_id = NULL WHERE usuario_id = $usuarioId");
    $conn->query("UPDATE formandos SET usuario_id = NULL WHERE usuario_id = $usuarioId");
    $conn->query("UPDATE encarregados SET usuario_id = NULL WHERE usuario_id = $usuarioId");

    $conn->query("DELETE FROM usuarios WHERE id = $usuarioId");

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    log_erro('perfil_delete', $e->getMessage());
    echo json_encode(['estado' => 'erro', 'message' => 'Erro ao remover a conta.']);
    exit;
}

if (!empty($foto) && $foto !== 'default.png') {
    $caminho = __DIR__ . '/../assets/img/' . $foto;
    if (file_exists($caminho)) {
        @unlink($caminho);
    }
}

session_destroy();

echo json_encode(['estado' => 'ok']);
