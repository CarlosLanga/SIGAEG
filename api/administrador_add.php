<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "pages/admin/administrador_adicionar.php");
    exit;
}

if (!$conn) {
    log_erro('administrador_add', 'Falha de conexao com BD');
    echo "erro_conexao";
    exit;
}

$nome = trim((string)($_POST['nome_completo'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$contacto = trim((string)($_POST['contacto'] ?? ''));
$codigo = trim((string)($_POST['codigo_gerado'] ?? ''));

if ($nome === '' || $email === '' || $codigo === '') {
    echo "erro";
    exit;
}

$nomeEsc = mysqli_real_escape_string($conn, $nome);
$emailEsc = mysqli_real_escape_string($conn, $email);
$contactoEsc = mysqli_real_escape_string($conn, $contacto);
$codigoEsc = mysqli_real_escape_string($conn, $codigo);
$contactoSql = $contacto !== '' ? "'$contactoEsc'" : "NULL";

$res = $conn->query("SELECT id FROM administradores WHERE email = '$emailEsc' LIMIT 1");
if ($res && $res->num_rows > 0) {
    echo "registro_existente";
    exit;
}

$res = $conn->query("SELECT id FROM usuarios WHERE email = '$emailEsc' LIMIT 1");
if ($res && $res->num_rows > 0) {
    echo "registro_existente";
    exit;
}

if (!$conn->query("INSERT INTO administradores (nome_completo, email, contacto) VALUES ('$nomeEsc', '$emailEsc', $contactoSql)")) {
    log_erro('administrador_add', $conn->error);
    echo "erro";
    exit;
}

$conn->query("INSERT INTO codigos_autorizados (codigo_acesso, email_dono, nivel_destinado, estado)
              VALUES ('$codigoEsc', '$emailEsc', 1, 'disponivel')
              ON DUPLICATE KEY UPDATE email_dono = VALUES(email_dono), nivel_destinado = VALUES(nivel_destinado), estado = 'disponivel'");

log_acao('administrador_add', "Administrador criado: $nome | $email");
echo "sucesso";