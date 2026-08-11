<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    log_erro('turma_add', 'Metodo invalido');
    echo "erro";
    exit;
}

if (!$conn) {
    log_erro('turma_add', 'Falha de conexao com BD');
    echo "erro";
    exit;
}

$ano = isset($_POST['ano_lectivo']) ? trim($_POST['ano_lectivo']) : '';
$cert = isset($_POST['certificado_vocacional']) ? trim($_POST['certificado_vocacional']) : '';
$curso_id = isset($_POST['curso_id']) ? (int)$_POST['curso_id'] : 0;
$turno_id = isset($_POST['turno_id']) ? (int)$_POST['turno_id'] : 0;
$formador_id = isset($_POST['formador_id']) ? (int)$_POST['formador_id'] : 0;
$nome_turma = isset($_POST['nome_turma']) ? trim($_POST['nome_turma']) : '';

if (!$ano || !$cert || !$curso_id || !$turno_id || !$formador_id || !$nome_turma) {
    log_erro('turma_add', 'Dados obrigatorios em falta');
    echo "erro";
    exit;
}

$nome_turma = mysqli_real_escape_string($conn, $nome_turma);

$check = $conn->query("SELECT id FROM turmas WHERE nome_turma = '$nome_turma' AND turno_id = $turno_id LIMIT 1");
if ($check && $check->num_rows > 0) {
    echo "turma_existente";
    exit;
}

$sql = "INSERT INTO turmas (nome_turma, curso_id, turno_id, ano_lectivo, dt_id, certificado_vocacional) VALUES ('$nome_turma', $curso_id, $turno_id, '$ano', $formador_id, '$cert')";


if (!$conn->query($sql)) {
    log_erro('turma_add', $conn->error);
    echo "erro";
    exit;
}

$turma_id = (int)$conn->insert_id;

$rt_sigla = 'RT';
$rt_nome = 'Reunião de Turma';
$rt_codigo = 'MOD-RT-' . $curso_id;
$rt_id = 0;

$resRt = $conn->query("SELECT id FROM modulos WHERE sigla_modulo = '$rt_sigla' AND curso_id = $curso_id LIMIT 1");
if ($resRt && $resRt->num_rows > 0) {
    $rt_id = (int)$resRt->fetch_assoc()['id'];
} else {
    $sqlRt = "INSERT INTO modulos (nome_modulo, tipo_modulo, codigo_modulo, curso_id, sigla_modulo)
              VALUES ('$rt_nome', 'outro', '$rt_codigo', $curso_id, '$rt_sigla')";
    if ($conn->query($sqlRt)) {
        $rt_id = (int)$conn->insert_id;
    } else {
        log_erro('turma_add', 'Falha ao criar modulo RT: ' . $conn->error);
    }
}

if ($rt_id > 0) {
    $data_inicio = date('Y-m-d');
    $data_fim = '2099-12-31';
    $estado = 'Em vigência';

    $sqlFm = "INSERT INTO formador_modulo (formador_id, turma_id, modulo_id, data_inicio, data_fim, estado)
              VALUES ($formador_id, $turma_id, $rt_id, '$data_inicio', '$data_fim', '$estado')
              ON DUPLICATE KEY UPDATE
                  formador_id = VALUES(formador_id),
                  data_inicio = VALUES(data_inicio),
                  data_fim = VALUES(data_fim),
                  estado = VALUES(estado)";

    if (!$conn->query($sqlFm)) {
        log_erro('turma_add', 'Falha ao vincular modulo RT: ' . $conn->error);
    }
}

log_acao('turma_add', "Turma criada: $nome_turma");
echo "sucesso";
