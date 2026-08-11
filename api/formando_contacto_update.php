<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['estado' => 'erro', 'message' => 'Método inválido.']);
    exit;
}

if (!isset($_SESSION['usuario_id']) || (int)($_SESSION['nivel_acesso'] ?? 0) !== 3) {
    echo json_encode(['estado' => 'erro', 'message' => 'Sem permissões.']);
    exit;
}

if (!$conn) {
    log_erro('formando_contacto_update', 'Falha de ligação à base de dados');
    echo json_encode(['estado' => 'erro', 'message' => 'Erro de ligação à base de dados.']);
    exit;
}

$contacto = trim($_POST['contacto'] ?? '');
if ($contacto === '') {
    echo json_encode(['estado' => 'erro', 'message' => 'Informe o contacto.']);
    exit;
}

$usuarioId = (int)$_SESSION['usuario_id'];
$stmt = $conn->prepare("UPDATE formandos SET contacto = ? WHERE usuario_id = ? LIMIT 1");
$stmt->bind_param("si", $contacto, $usuarioId);
if (!$stmt->execute()) {
    log_erro('formando_contacto_update', $stmt->error);
    $stmt->close();
    echo json_encode(['estado' => 'erro', 'message' => 'Erro ao actualizar o contacto.']);
    exit;
}
$stmt->close();

echo json_encode(['estado' => 'ok']);
