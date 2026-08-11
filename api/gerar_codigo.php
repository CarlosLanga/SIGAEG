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
$nivel = isset($_POST['nivel_destinado']) ? (int)$_POST['nivel_destinado'] : 0;

$tab_email = ['usuarios','formandos','formadores','administradores','encarregados'];
foreach ($tab_email as $t) {
    $res = $conn->query("SELECT id FROM $t WHERE email = '$email' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        echo json_encode(['estado' => 'utilizado']);
        exit;
    }
}

if ($nivel > 0) {
    $checkCodigo = $conn->query("SELECT codigo_acesso FROM codigos_autorizados WHERE email_dono = '$email' AND nivel_destinado = $nivel LIMIT 1");
    if ($checkCodigo && $checkCodigo->num_rows > 0) {
        $row = $checkCodigo->fetch_assoc();
        echo json_encode(['estado' => 'codigo_existente', 'codigo' => $row['codigo_acesso']]);
        exit;
    }
}

do {
    $codigo = str_pad(strval(random_int(0, 999999)), 6, "0", STR_PAD_LEFT);
    $check = $conn->query("SELECT id FROM codigos_autorizados WHERE codigo_acesso = '$codigo' LIMIT 1");
} while ($check && $check->num_rows > 0);

echo json_encode(['estado' => 'ok', 'codigo' => $codigo]);
