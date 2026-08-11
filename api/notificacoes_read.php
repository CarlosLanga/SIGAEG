<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

if (!$conn) {
    json_out(['ok' => false, 'message' => 'Erro de base de dados.']);
}

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
if ($usuarioId <= 0) {
    json_out(['ok' => false, 'message' => 'Não autenticado.']);
}

if ($conn->query("UPDATE notificacoes SET lida = 1 WHERE usuario_id = $usuarioId AND lida = 0")) {
    json_out(['ok' => true]);
} else {
    json_out(['ok' => false, 'message' => 'Erro ao atualizar notificações.']);
}
