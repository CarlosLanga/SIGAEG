<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'message' => 'Método inválido.']);
}

if (!$conn) {
    json_out(['ok' => false, 'message' => 'Erro de ligação à base de dados.']);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

if (!in_array($nivel, [1, 2], true) || $usuarioId <= 0) {
    json_out(['ok' => false, 'message' => 'Sem permissão.']);
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    json_out(['ok' => false, 'message' => 'Anúncio inválido.']);
}

// Busca o anúncio para validar permissão e localizar o anexo.
$stmt = $conn->prepare('SELECT criado_por, anexo_caminho FROM anuncios WHERE id = ? LIMIT 1');
if (!$stmt) {
    json_out(['ok' => false, 'message' => 'Erro ao localizar o anúncio.']);
}
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$anuncio = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$anuncio) {
    json_out(['ok' => false, 'message' => 'Anúncio não encontrado.']);
}

// Formador só pode remover os próprios anúncios.
if ($nivel === 2 && (int)$anuncio['criado_por'] !== $usuarioId) {
    json_out(['ok' => false, 'message' => 'Só pode remover os anúncios que criou.']);
}

$del = $conn->prepare('DELETE FROM anuncios WHERE id = ? LIMIT 1');
if (!$del) {
    json_out(['ok' => false, 'message' => 'Erro ao remover o anúncio.']);
}
$del->bind_param('i', $id);
if (!$del->execute()) {
    log_erro('anuncios_delete', $conn->error);
    $del->close();
    json_out(['ok' => false, 'message' => 'Erro ao remover o anúncio.']);
}
$del->close();

// Remove o anexo do disco, se existir.
if (!empty($anuncio['anexo_caminho'])) {
    $caminhoFisico = __DIR__ . '/../' . ltrim((string)$anuncio['anexo_caminho'], '/');
    if (is_file($caminhoFisico)) {
        @unlink($caminhoFisico);
    }
}

log_acao('anuncios_delete', "Anúncio removido: $id");
json_out(['ok' => true]);
