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


$resUnread = $conn->query("SELECT COUNT(*) as unread FROM notificacoes WHERE usuario_id = $usuarioId AND lida = 0");
$unreadCount = $resUnread ? (int) $resUnread->fetch_assoc()['unread'] : 0;

$newNotifications = [];
$resNew = $conn->query("
    SELECT id, titulo, mensagem, tipo, link, data_criacao 
    FROM notificacoes 
    WHERE usuario_id = $usuarioId AND exibida_em_tela = 0 
    ORDER BY id ASC
");

if ($resNew && $resNew->num_rows > 0) {
    $ids = [];
    while ($row = $resNew->fetch_assoc()) {
        $newNotifications[] = $row;
        $ids[] = (int)$row['id'];
    }
    

    if (!empty($ids)) {
        $idsStr = implode(',', $ids);
        $conn->query("UPDATE notificacoes SET exibida_em_tela = 1 WHERE id IN ($idsStr)");
    }
}

json_out([
    'ok' => true,
    'unread_count' => $unreadCount,
    'new_notifications' => $newNotifications
]);
