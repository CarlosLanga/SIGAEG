<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Metodo invalido.']);
    exit;
}

if (!$conn) {
    log_erro('modulo_delete', 'Falha de conexao com BD');
    echo json_encode(['ok' => false, 'msg' => 'Falha de conexao com BD.']);
    exit;
}

$fm_id = (int)($_POST['fm_id'] ?? 0);
$modulo_id = (int)($_POST['modulo_id'] ?? 0);

if (!$fm_id && !$modulo_id) {
    echo json_encode(['ok' => false, 'msg' => 'ID invalido.']);
    exit;
}

if ($fm_id > 0) {
    $res = $conn->query("SELECT id FROM formador_modulo WHERE id = $fm_id LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        echo json_encode(['ok' => false, 'msg' => 'Registo de modulo nao encontrado.']);
        exit;
    }

    if (!$conn->query("DELETE FROM formador_modulo WHERE id = $fm_id")) {
        log_erro('modulo_delete', $conn->error);
        echo json_encode(['ok' => false, 'msg' => 'Erro ao remover modulo iniciado.']);
        exit;
    }

    log_acao('modulo_delete', "Vinculo de modulo removido: fm_id $fm_id");
    echo json_encode(['ok' => true]);
    exit;
}

$resMod = $conn->query("SELECT id, sigla_modulo FROM modulos WHERE id = $modulo_id LIMIT 1");
if (!$resMod || $resMod->num_rows === 0) {
    echo json_encode(['ok' => false, 'msg' => 'Modulo nao encontrado.']);
    exit;
}
$mod = $resMod->fetch_assoc();

$countVinc = $conn->query("SELECT COUNT(*) AS total FROM formador_modulo WHERE modulo_id = $modulo_id");
$totalVinc = $countVinc ? (int)$countVinc->fetch_assoc()['total'] : 0;
if ($totalVinc > 0) {
    echo json_encode(['ok' => false, 'msg' => 'Nao e possivel remover modulo base: ja possui vinculos.']);
    exit;
}

if (!$conn->query("DELETE FROM modulos WHERE id = $modulo_id")) {
    log_erro('modulo_delete', $conn->error);
    echo json_encode(['ok' => false, 'msg' => 'Erro ao remover modulo.']);
    exit;
}

log_acao('modulo_delete', "Modulo base removido: {$mod['sigla_modulo']} | ID $modulo_id");
echo json_encode(['ok' => true]);

