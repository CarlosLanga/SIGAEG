<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (!$conn || !$email) {
    echo json_encode(['estado' => 'erro']);
    exit;
}

$email = mysqli_real_escape_string($conn, $email);

$tab_email = ['usuarios','formandos','formadores','administradores'];
foreach ($tab_email as $t) {
    $res = $conn->query("SELECT id FROM $t WHERE email = '$email' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        echo json_encode(['estado' => 'bloqueado']);
        exit;
    }
}

$res = $conn->query("SELECT id, nome_completo, contacto, parentesco FROM encarregados WHERE email = '$email' LIMIT 1");
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo json_encode(['estado' => 'existente', 'dados' => $row]);
    exit;
}

echo json_encode(['estado' => 'novo']);
