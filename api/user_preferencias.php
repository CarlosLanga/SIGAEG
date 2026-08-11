<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Nao autenticado']);
    exit;
}

if (!$conn) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Sem conexao BD']);
    exit;
}

$tema = $_POST['tema'] ?? null;
$sidebar = $_POST['sidebar'] ?? null;

if ($tema !== null) {
    if ($tema !== 'light' && $tema !== 'dark') $tema = 'light';
    $stmt = $conn->prepare("UPDATE usuarios SET tema = ? WHERE id = ?");
    $stmt->bind_param("si", $tema, $_SESSION['usuario_id']);
    $stmt->execute();
    $_SESSION['tema'] = $tema;
    setcookie('iicaeg_tema', $tema, time() + (86400 * 365), "/");
}

if ($sidebar !== null) {
    if ($sidebar !== 'expandida' && $sidebar !== 'colapsada') $sidebar = 'expandida';
    $stmt = $conn->prepare("UPDATE usuarios SET sidebar_estado = ? WHERE id = ?");
    $stmt->bind_param("si", $sidebar, $_SESSION['usuario_id']);
    $stmt->execute();
    $_SESSION['sidebar_estado'] = $sidebar;
    setcookie('iicaeg_sidebar', $sidebar, time() + (86400 * 365), "/");
}

echo json_encode(['ok' => true]);