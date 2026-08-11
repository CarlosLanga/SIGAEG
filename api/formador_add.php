<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "pages/admin/formador_adicionar.php");
    exit;
}

if (!$conn) {
    log_erro('formador_add', 'Falha de conexão com BD');
    echo "erro_conexao";
    exit;
}

$nome = mysqli_real_escape_string($conn, $_POST['nome_completo']);
$sexo = mysqli_real_escape_string($conn, $_POST['sexo']);
$codigo = mysqli_real_escape_string($conn, $_POST['codigo_formador']);
$telefone = !empty($_POST['telefone']) ? mysqli_real_escape_string($conn, $_POST['telefone']) : null;
$titulo = !empty($_POST['titulo']) ? mysqli_real_escape_string($conn, $_POST['titulo']) : null;
$especialidade = !empty($_POST['especialidade']) ? mysqli_real_escape_string($conn, $_POST['especialidade']) : null;
$email = mysqli_real_escape_string($conn, $_POST['email']);
$codigo_convite = mysqli_real_escape_string($conn, $_POST['codigo_gerado']);
$cursos = $_POST['cursos'] ?? [];

if (!$codigo_convite) {
    log_erro('formador_add', "Codigo de convite nao gerado para: $email");
    echo "erro_codigo";
    exit;
}

$res = $conn->query("SELECT id FROM formadores WHERE codigo_formador = '$codigo' LIMIT 1");
if ($res && $res->num_rows > 0) {
    log_erro('formador_add', "Codigo duplicado: $codigo");
    echo "erro_codigo_formador";
    exit;
}

$telefone_sql = $telefone ? "'$telefone'" : "NULL";
$titulo_sql = $titulo ? "'$titulo'" : "NULL";
$especialidade_sql = $especialidade ? "'$especialidade'" : "NULL";

$sql = "INSERT INTO formadores (codigo_formador, nome_completo, sexo, titulo, email, telefone, especialidade)
        VALUES ('$codigo', '$nome', '$sexo', $titulo_sql, '$email', $telefone_sql, $especialidade_sql)";

if (!$conn->query($sql)) {
    log_erro('formador_add', $conn->error);
    echo "erro";
    exit;
}

$formador_id = $conn->insert_id;

if (!empty($cursos)) {
    foreach ($cursos as $cid) {
        $cid = (int)$cid;
        $conn->query("INSERT IGNORE INTO formador_curso (formador_id, curso_id) VALUES ($formador_id, $cid)");
    }
}

$conn->query("INSERT INTO codigos_autorizados (codigo_acesso, email_dono, nivel_destinado, estado)
              VALUES ('$codigo_convite', '$email', 2, 'disponivel')");

log_acao('formador_add', "Formador criado: $nome | $codigo");
echo "sucesso";
