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
    log_erro('perfil_update', 'Falha de ligação à base de dados');
    echo json_encode(['estado' => 'erro', 'message' => 'Erro de ligação à base de dados.']);
    exit;
}

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$nome = trim($_POST['nome_completo'] ?? '');
$senhaActual = trim($_POST['senha_actual'] ?? '');
$senhaNova = trim($_POST['senha_nova'] ?? '');
$senhaConfirmar = trim($_POST['senha_confirmar'] ?? '');
$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);

if (!$usuarioId || $nome === '') {
    echo json_encode(['estado' => 'erro', 'message' => 'Nome completo é obrigatório.']);
    exit;
}

$nomeEsc = mysqli_real_escape_string($conn, $nome);

if ($senhaActual !== '' || $senhaNova !== '' || $senhaConfirmar !== '') {
    if ($senhaActual === '' || $senhaNova === '' || $senhaConfirmar === '') {
        echo json_encode(['estado' => 'erro', 'message' => 'Preencha todos os campos de senha.']);
        exit;
    }

    if ($senhaNova !== $senhaConfirmar) {
        echo json_encode(['estado' => 'erro', 'message' => 'As senhas não coincidem.']);
        exit;
    }

    $res = $conn->query("SELECT senha FROM usuarios WHERE id = $usuarioId LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        echo json_encode(['estado' => 'erro', 'message' => 'Utilizador não encontrado.']);
        exit;
    }
    $row = $res->fetch_assoc();
    if (!password_verify($senhaActual, $row['senha'])) {
        echo json_encode(['estado' => 'erro', 'message' => 'Senha actual incorrecta.']);
        exit;
    }

    $senhaHash = password_hash($senhaNova, PASSWORD_DEFAULT);
    $senhaHashEsc = mysqli_real_escape_string($conn, $senhaHash);
    $conn->query("UPDATE usuarios SET senha = '$senhaHashEsc' WHERE id = $usuarioId");
}

$conn->query("UPDATE usuarios SET nome_completo = '$nomeEsc' WHERE id = $usuarioId");

$_SESSION['usuario_nome'] = $nome;

echo json_encode(['estado' => 'ok']);
