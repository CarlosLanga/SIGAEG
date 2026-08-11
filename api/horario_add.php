<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "erro";
    exit;
}

if (!$conn) {
    log_erro('horario_add', 'Falha de conexao com BD');
    echo "erro_conexao";
    exit;
}

$turma_id = (int)($_POST['turma_id'] ?? 0);
$modulo_id = (int)($_POST['modulo_id'] ?? 0);
$formador_id = (int)($_POST['formador_id'] ?? 0);
$dia = mysqli_real_escape_string($conn, $_POST['dia_semana'] ?? '');
$hora_inicio = mysqli_real_escape_string($conn, $_POST['hora_inicio'] ?? '');
$hora_fim = mysqli_real_escape_string($conn, $_POST['hora_fim'] ?? '');
$sala = !empty($_POST['sala']) ? mysqli_real_escape_string($conn, $_POST['sala']) : null;

if (!$turma_id || !$modulo_id || !$formador_id || !$dia || !$hora_inicio || !$hora_fim) {
    log_erro('horario_add', 'Campos obrigatorios em falta');
    echo "erro";
    exit;
}

if (strtotime($hora_fim) <= strtotime($hora_inicio)) {
    echo "datas_invalidas";
    exit;
}

$confTurma = $conn->query("SELECT id FROM horarios 
    WHERE turma_id = $turma_id AND dia_semana = '$dia'
    AND ('$hora_inicio' < hora_fim AND '$hora_fim' > hora_inicio)
    LIMIT 1");
if ($confTurma && $confTurma->num_rows > 0) {
    echo "conflito_turma";
    exit;
}

$confFormador = $conn->query("SELECT id FROM horarios
    WHERE formador_id = $formador_id AND dia_semana = '$dia'
    AND ('$hora_inicio' < hora_fim AND '$hora_fim' > hora_inicio)
    LIMIT 1");
if ($confFormador && $confFormador->num_rows > 0) {
    echo "conflito_formador";
    exit;
}

$sala_sql = $sala ? "'$sala'" : "NULL";
$user = (int)($_SESSION['usuario_id'] ?? 0);

$sql = "INSERT INTO horarios (turma_id, modulo_id, formador_id, dia_semana, hora_inicio, hora_fim, sala, criado_por)
        VALUES ($turma_id, $modulo_id, $formador_id, '$dia', '$hora_inicio', '$hora_fim', $sala_sql, $user)";

if (!$conn->query($sql)) {
    log_erro('horario_add', $conn->error);
    echo "erro";
    exit;
}

log_acao('horario_add', "Horario criado: turma $turma_id | modulo $modulo_id");
echo "sucesso";
