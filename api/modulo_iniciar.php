<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "erro";
    exit;
}

$turma_id = (int)($_POST['turma_id'] ?? 0);
$modulo_id = (int)($_POST['modulo_id'] ?? 0);
$formador_id = (int)($_POST['formador_id'] ?? 0);
$data_inicio = trim($_POST['data_inicio'] ?? '');
$data_fim = trim($_POST['data_fim'] ?? '');

log_erro('modulo_iniciar', json_encode($_POST));
if (!$turma_id || !$modulo_id || !$formador_id || !$data_inicio || !$data_fim) {
    log_erro('modulo_iniciar', 'Campos obrigatórios em falta');
    echo "erro";
    exit;
}

$inicio_ts = strtotime($data_inicio);
$fim_ts = strtotime($data_fim);
if (!$inicio_ts || !$fim_ts) {
    log_erro('modulo_iniciar', 'Datas inválidas');
    echo "datas_invalidas";
    exit;
}
if ($fim_ts < $inicio_ts) {
    log_erro('modulo_iniciar', 'Data de conclusão anterior à data de início');
    echo "datas_invalidas";
    exit;
}

$hoje = date('Y-m-d');
$estado = 'Por iniciar';
if ($data_inicio <= $hoje && $data_fim >= $hoje) $estado = 'Em vigência';
if ($data_fim < $hoje) $estado = 'Concluído';

$sql = "INSERT INTO formador_modulo (formador_id, turma_id, modulo_id, data_inicio, data_fim, estado)
        VALUES ($formador_id, $turma_id, $modulo_id, '$data_inicio', '$data_fim', '$estado')";

if (!$conn->query($sql)) {
    log_erro('modulo_iniciar', $conn->error);
    echo "modulo_existente";
    exit;
}


echo "sucesso";
