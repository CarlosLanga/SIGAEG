<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "erro";
    exit;
}

$fm_id = (int)($_POST['fm_id'] ?? 0);
$formador_id = (int)($_POST['formador_id'] ?? 0);
$data_inicio = trim($_POST['data_inicio'] ?? '');
$data_fim = trim($_POST['data_fim'] ?? '');

if (!$fm_id || !$formador_id || !$data_inicio || !$data_fim) {
    log_erro('modulo_update', 'Campos obrigatórios em falta');
    echo "erro";
    exit;
}

$inicio_ts = strtotime($data_inicio);
$fim_ts = strtotime($data_fim);
if (!$inicio_ts || !$fim_ts || $fim_ts < $inicio_ts) {
    log_erro('modulo_update', 'Datas inválidas');
    echo "datas_invalidas";
    exit;
}

$hoje = date('Y-m-d');
$estado = 'Por iniciar';
if ($data_inicio <= $hoje && $data_fim >= $hoje) $estado = 'Em vigência';
if ($data_fim < $hoje) $estado = 'Concluído';

$sql = "UPDATE formador_modulo
        SET formador_id = $formador_id,
            data_inicio = '$data_inicio',
            data_fim = '$data_fim',
            estado = '$estado'
        WHERE id = $fm_id";

if (!$conn || !$conn->query($sql)) {
    $msg = $conn ? $conn->error : 'Falha de conexão com BD';
    log_erro('modulo_update', $msg);
    echo "erro";
    exit;
}

log_acao('modulo_update', "Módulo actualizado: ID $fm_id");
echo "sucesso";
