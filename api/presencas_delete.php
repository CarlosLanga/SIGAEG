<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'msg' => 'Método inválido.']);
}

if (!$conn) {
    json_out(['ok' => false, 'msg' => 'Erro de ligação.']);
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    json_out(['ok' => false, 'msg' => 'Parâmetro inválido.']);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$scopeWhere = $nivel === 2 ? "AND f.usuario_id = $usuario_id" : "";

$resPermissao = $conn->query("
    SELECT pp.id
    FROM presencas_plano pp
    LEFT JOIN formador_modulo fm ON fm.id = pp.formador_modulo_id
    LEFT JOIN formadores f ON f.id = fm.formador_id
    WHERE pp.id = $id
    $scopeWhere
    LIMIT 1
");

if (!$resPermissao || $resPermissao->num_rows === 0) {
    json_out(['ok' => false, 'msg' => 'Sem permissao para eliminar este registo.']);
}

if (!$conn->query("DELETE FROM presencas_plano WHERE id = $id LIMIT 1")) {
    json_out(['ok' => false, 'msg' => 'Erro ao eliminar.']);
}

json_out(['ok' => true]);
