<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "pages/admin/formando_adicionar.php");
    exit;
}

if (!$conn) {
    log_erro('formando_add', 'Falha de conexão com BD');
    echo "erro_conexao";
    exit;
}

$nome = mysqli_real_escape_string($conn, $_POST['nome_completo']);
$numero_documento = mysqli_real_escape_string($conn, $_POST['numero_documento']);
$sexo = mysqli_real_escape_string($conn, $_POST['sexo']);
$data_nascimento = !empty($_POST['data_nascimento']) ? trim($_POST['data_nascimento']) : null;
if ($data_nascimento !== null) {
    if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $data_nascimento)) {
        [$dia, $mes, $ano] = explode('.', $data_nascimento);
        if (!checkdate((int)$mes, (int)$dia, (int)$ano)) {
            echo "erro_data_nascimento";
            exit;
        }
        $data_nascimento = "$ano-$mes-$dia";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_nascimento)) {
        echo "erro_data_nascimento";
        exit;
    }
}
$contacto = !empty($_POST['contacto']) ? mysqli_real_escape_string($conn, $_POST['contacto']) : null;
$codigo_formando = mysqli_real_escape_string($conn, $_POST['codigo_formando']);

$res_codigo = $conn->query("SELECT id FROM formandos WHERE codigo_formando = '$codigo_formando' LIMIT 1");
if ($res_codigo->num_rows > 0) {
    log_erro('formando_add', "Codigo duplicado: $codigo_formando");
    echo "erro_codigo_formando";
    exit;
}

$ano_ingresso = !empty($_POST['ano_ingresso']) ? (int)$_POST['ano_ingresso'] : null;
$ano_conclusao = !empty($_POST['ano_conclusao']) ? (int)$_POST['ano_conclusao'] : null;
$certificado = mysqli_real_escape_string($conn, $_POST['certificado_vocacional']);
$curso_id = !empty($_POST['curso_id']) ? (int)$_POST['curso_id'] : null;
$turno_id = !empty($_POST['turno_id']) ? (int)$_POST['turno_id'] : null;
$turma_id = !empty($_POST['turma_id']) ? (int)$_POST['turma_id'] : null;
$email = mysqli_real_escape_string($conn, $_POST['email']);
$codigo_convite = mysqli_real_escape_string($conn, $_POST['codigo_gerado']);

if (!$codigo_convite) {
    log_erro('formando_add', "Codigo de convite nao gerado para: $email");
    echo "erro_codigo";
    exit;
}


$data_sql = ($data_nascimento !== null) ? "'$data_nascimento'" : "NULL";
$contacto_sql = ($contacto !== null) ? "'$contacto'" : "NULL";
$ano_ingresso_sql = ($ano_ingresso !== null) ? $ano_ingresso : "NULL";
$ano_conclusao_sql = ($ano_conclusao !== null) ? $ano_conclusao : "NULL";

$sql = "INSERT INTO formandos (codigo_formando, nome_completo, numero_documento, sexo, data_nascimento, contacto, ano_ingresso, ano_conclusao, certificado_vocacional, curso_id, turma_id, turno_id, email) VALUES ('$codigo_formando', '$nome', '$numero_documento', '$sexo', $data_sql, $contacto_sql, $ano_ingresso_sql, $ano_conclusao_sql, '$certificado', $curso_id, $turma_id, $turno_id, '$email')";

if (!$conn->query($sql)) {
    log_erro('formando_add', $conn->error);
    echo "erro";
    exit;
}

$formando_id = $conn->insert_id;
log_acao('formando_add', "Formando criado: $nome | $codigo_formando");

// ENCARREGADO 
$enc_email = !empty($_POST['encarregado_email']) ? mysqli_real_escape_string($conn, $_POST['encarregado_email']) : '';
$enc_id = !empty($_POST['encarregado_id']) ? (int)$_POST['encarregado_id'] : 0;
$enc_nome = !empty($_POST['encarregado_nome']) ? mysqli_real_escape_string($conn, $_POST['encarregado_nome']) : '';
$enc_tipo = !empty($_POST['encarregado_tipo']) ? mysqli_real_escape_string($conn, $_POST['encarregado_tipo']) : '';
$enc_contacto = !empty($_POST['encarregado_contacto']) ? mysqli_real_escape_string($conn, $_POST['encarregado_contacto']) : '';
$enc_codigo = !empty($_POST['encarregado_codigo']) ? mysqli_real_escape_string($conn, $_POST['encarregado_codigo']) : '';

if ($enc_email) {
    if (!$enc_id) {
        if (!$enc_nome || !$enc_contacto || !$enc_tipo) {
            log_erro('formando_add', 'Encarregado incompleto: nome/contacto/parentesco vazio');
            echo "erro_encarregado";
            exit;
        }

        $ok = $conn->query("INSERT INTO encarregados (nome_completo, email, contacto, parentesco) VALUES ('$enc_nome', '$enc_email', '$enc_contacto', '$enc_tipo')");
        if (!$ok) {
            log_erro('formando_add', $conn->error);
            echo "erro_encarregado";
            exit;
        }
        $enc_id = $conn->insert_id;

        if ($enc_codigo) {
            $conn->query("INSERT INTO codigos_autorizados (codigo_acesso, email_dono, nivel_destinado, estado) VALUES ('$enc_codigo', '$enc_email', 4, 'disponivel')");
        }
    }

    $conn->query("INSERT INTO encarregado_formando (encarregado_id, formando_id, principal) VALUES ($enc_id, $formando_id, 1)");
}


$conn->query("INSERT INTO codigos_autorizados (codigo_acesso, email_dono, nivel_destinado, estado) VALUES ('$codigo_convite', '$email', 3, 'disponivel')");

echo "sucesso";
exit;
