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
    log_erro('perfil_foto_update', 'Falha de ligação à base de dados');
    echo json_encode(['estado' => 'erro', 'message' => 'Erro de ligação à base de dados.']);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'remove') {
    $usuarioId = (int)$_SESSION['usuario_id'];
    $fotoAntiga = '';
    $stmt = $conn->prepare("SELECT foto FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $usuarioId);
    if ($stmt->execute()) {
        $stmt->bind_result($fotoDb);
        if ($stmt->fetch()) {
            $fotoAntiga = $fotoDb ?: '';
        }
    }
    $stmt->close();

    $fotoDefault = 'default.png';
    $stmt = $conn->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
    $stmt->bind_param("si", $fotoDefault, $usuarioId);
    if (!$stmt->execute()) {
        $stmt->close();
        echo json_encode(['estado' => 'erro', 'message' => 'Erro ao remover a foto.']);
        exit;
    }
    $stmt->close();

    if (!empty($fotoAntiga) && $fotoAntiga !== 'default.png') {
        $caminhoAntigo = __DIR__ . '/../assets/img/' . $fotoAntiga;
        if (file_exists($caminhoAntigo)) {
            @unlink($caminhoAntigo);
        }
    }

    $_SESSION['usuario_foto'] = $fotoDefault;
    $iniciais = getInitials($_SESSION['usuario_nome'] ?? 'U');

    echo json_encode([
        'estado' => 'ok',
        'removed' => true,
        'initials' => $iniciais
    ]);
    exit;
}

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['estado' => 'erro', 'message' => 'Nenhuma foto foi enviada.']);
    exit;
}

$file = $_FILES['foto'];
$maxSize = 2 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode(['estado' => 'erro', 'message' => 'A foto não pode exceder 2MB.']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png'];
if (!in_array($ext, $allowed, true)) {
    echo json_encode(['estado' => 'erro', 'message' => 'Formato inválido. Use JPG ou PNG.']);
    exit;
}

$usuarioId = (int)$_SESSION['usuario_id'];
$novoNome = 'user_' . $usuarioId . '_' . time() . '.' . $ext;
$dirUpload = __DIR__ . '/../assets/img/uploads/';
$destino = $dirUpload . $novoNome;

if (!is_dir($dirUpload)) {
    @mkdir($dirUpload, 0775, true);
}

if (!move_uploaded_file($file['tmp_name'], $destino)) {
    echo json_encode(['estado' => 'erro', 'message' => 'Falha ao guardar a foto.']);
    exit;
}

$fotoAntiga = '';
$stmt = $conn->prepare("SELECT foto FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $usuarioId);
if ($stmt->execute()) {
    $stmt->bind_result($fotoDb);
    if ($stmt->fetch()) {
        $fotoAntiga = $fotoDb ?: '';
    }
}
$stmt->close();

$caminhoRelativo = 'uploads/' . $novoNome;
$stmt = $conn->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
$stmt->bind_param("si", $caminhoRelativo, $usuarioId);
if (!$stmt->execute()) {
    $stmt->close();
    echo json_encode(['estado' => 'erro', 'message' => 'Erro ao actualizar a foto.']);
    exit;
}
$stmt->close();

if (!empty($fotoAntiga) && $fotoAntiga !== 'default.png' && $fotoAntiga !== $caminhoRelativo) {
    $caminhoAntigo = __DIR__ . '/../assets/img/' . $fotoAntiga;
    if (file_exists($caminhoAntigo)) {
        @unlink($caminhoAntigo);
    }
}

$_SESSION['usuario_foto'] = $caminhoRelativo;

echo json_encode([
    'estado' => 'ok',
    'filename' => $caminhoRelativo,
    'url' => BASE_URL . 'assets/img/' . $caminhoRelativo
]);
