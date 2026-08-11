<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "erro";
    exit;
}

if (!$conn) {
    log_erro('formador_update', 'Falha de conexão com BD');
    echo "erro_conexao";
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$nome = mysqli_real_escape_string($conn, $_POST['nome_completo'] ?? '');
$sexo = mysqli_real_escape_string($conn, $_POST['sexo'] ?? '');
$codigo = mysqli_real_escape_string($conn, $_POST['codigo_formador'] ?? '');
$email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
$telefone = !empty($_POST['telefone']) ? mysqli_real_escape_string($conn, $_POST['telefone']) : null;
$especialidade = !empty($_POST['especialidade']) ? mysqli_real_escape_string($conn, $_POST['especialidade']) : null;
$titulo = !empty($_POST['titulo']) ? mysqli_real_escape_string($conn, $_POST['titulo']) : null;
$cursos = $_POST['cursos'] ?? [];
$codigo_convite = mysqli_real_escape_string($conn, $_POST['codigo_gerado'] ?? '');

if (!$id || !$nome || !$sexo || !$codigo || !$email) {
    log_erro('formador_update', 'Campos obrigatórios em falta');
    echo "erro";
    exit;
}

$resOld = $conn->query("SELECT email FROM formadores WHERE id = $id LIMIT 1");
$antigo_email = $resOld ? $resOld->fetch_assoc()['email'] : '';


$dup = $conn->query("SELECT id FROM formadores WHERE codigo_formador = '$codigo' AND id <> $id LIMIT 1");
if ($dup && $dup->num_rows > 0) {
    log_erro('formador_update', "Código duplicado: $codigo");
    echo "erro_codigo_formador";
    exit;
}

$telefone_sql = $telefone ? "'$telefone'" : "NULL";
$especialidade_sql = $especialidade ? "'$especialidade'" : "NULL";
$titulo_sql = $titulo ? "'$titulo'" : "NULL";

$sql = "UPDATE formadores
        SET nome_completo = '$nome',
            sexo = '$sexo',
            codigo_formador = '$codigo',
            email = '$email',
            telefone = $telefone_sql,
            especialidade = $especialidade_sql,
            titulo = $titulo_sql
        WHERE id = $id";

if (!$conn->query($sql)) {
    log_erro('formador_update', $conn->error);
    echo "erro";
    exit;
}

if ($antigo_email && $email && $antigo_email !== $email && !empty($codigo_convite)) {
    $conn->query("DELETE FROM codigos_autorizados WHERE email_dono = '$antigo_email' AND nivel_destinado = 2");

    $conn->query("INSERT INTO codigos_autorizados (codigo_acesso, email_dono, nivel_destinado, estado) VALUES ('$codigo_convite', '$email', 2, 'disponivel')");
}


$conn->query("DELETE FROM formador_curso WHERE formador_id = $id");
if (!empty($cursos) && is_array($cursos)) {
    foreach ($cursos as $cid) {
        $cid = (int)$cid;
        if ($cid > 0) {
            $conn->query("INSERT IGNORE INTO formador_curso (formador_id, curso_id) VALUES ($id, $cid)");
        }
    }
}

if (!empty($codigo_convite)) {
    $codigo_convite = mysqli_real_escape_string($conn, $codigo_convite);
    $check = $conn->query("SELECT id FROM codigos_autorizados WHERE codigo_acesso = '$codigo_convite' LIMIT 1");
    if (!$check || $check->num_rows === 0) {
        $conn->query("INSERT INTO codigos_autorizados (codigo_acesso, email_dono, nivel_destinado, estado)
                      VALUES ('$codigo_convite', '$email', 2, 'disponivel')");
    }
}

log_acao('formador_update', "Formador actualizado: $nome | $codigo");
echo "sucesso";
