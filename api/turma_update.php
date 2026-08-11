<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

if (!$conn) {
    echo "erro";
    exit;
}

$id = (int)($_POST['turma_id'] ?? 0);
$nome = trim($_POST['nome_turma'] ?? '');
$curso_id = (int)($_POST['curso_id'] ?? 0);
$turno = (int)($_POST['turno_id'] ?? 0);
$cert = trim($_POST['certificado_vocacional'] ?? '');
$dt = (int)($_POST['dt_id'] ?? 0);
$formador_id = (int)($_POST['formador_id'] ?? 0);
$ano = trim($_POST['ano_lectivo'] ?? '');

if (!$id || !$nome || !$curso_id || !$turno) {
    echo "erro";
    exit;
}

$nomeEsc = mysqli_real_escape_string($conn, $nome);
$cert = mysqli_real_escape_string($conn, $cert);

$check = $conn->query("
    SELECT id FROM turmas 
    WHERE nome_turma = '$nomeEsc' AND turno_id = $turno AND id <> $id
    LIMIT 1
");

if ($check && $check->num_rows > 0) {
    echo "turma_existente";
    exit;
}

$dt_final = $dt ?: $formador_id;

$sql = "UPDATE turmas 
        SET nome_turma = '$nomeEsc',
            curso_id = $curso_id,
            turno_id = $turno,
            certificado_vocacional = '$cert',
            dt_id = $dt_final,
            ano_lectivo = '$ano'
        WHERE id = $id";

if (!$conn->query($sql)) {
    echo "erro";
    exit;
}

echo "sucesso";
