<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['estado' => 'erro', 'message' => 'Método inválido.']);
    exit;
}

if (!isset($_SESSION['usuario_id']) || (int)($_SESSION['nivel_acesso'] ?? 0) !== 1) {
    echo json_encode(['estado' => 'erro', 'message' => 'Sem permissões.']);
    exit;
}

if (!$conn) {
    log_erro('admin_contacto_update', 'Falha de ligação à base de dados');
    echo json_encode(['estado' => 'erro', 'message' => 'Erro de ligação à base de dados.']);
    exit;
}

$contacto = trim($_POST['contacto'] ?? '');
if ($contacto === '') {
    echo json_encode(['estado' => 'erro', 'message' => 'Informe o contacto.']);
    exit;
}

$usuarioId = (int)$_SESSION['usuario_id'];

$temCol = $conn->query("SHOW COLUMNS FROM administradores LIKE 'contacto'");
if ($temCol && $temCol->num_rows === 0) {
    if (!$conn->query("ALTER TABLE administradores ADD COLUMN contacto varchar(30) DEFAULT NULL")) {
        log_erro('admin_contacto_update', $conn->error);
        echo json_encode(['estado' => 'erro', 'message' => 'Erro ao preparar o campo de contacto.']);
        exit;
    }
}

$stmt = $conn->prepare("UPDATE administradores SET contacto = ? WHERE usuario_id = ? LIMIT 1");
$stmt->bind_param("si", $contacto, $usuarioId);
if (!$stmt->execute()) {
    log_erro('admin_contacto_update', $stmt->error);
    $stmt->close();
    echo json_encode(['estado' => 'erro', 'message' => 'Erro ao actualizar o contacto.']);
    exit;
}
$stmt->close();

echo json_encode(['estado' => 'ok']);
