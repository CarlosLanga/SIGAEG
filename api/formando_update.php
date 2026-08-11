<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "erro";
    exit;
}

if (!$conn) {
    echo "erro";
    exit;
}

$id = (int)($_POST['formando_id'] ?? 0);
if (!$id) {
    echo "erro";
    exit;
}

$resOld = $conn->query("SELECT email FROM formandos WHERE id = $id LIMIT 1");
$antigo_email = $resOld ? $resOld->fetch_assoc()['email'] : '';

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

$ano_ingresso = !empty($_POST['ano_ingresso']) ? (int)$_POST['ano_ingresso'] : null;
$ano_conclusao = !empty($_POST['ano_conclusao']) ? (int)$_POST['ano_conclusao'] : null;
$certificado = mysqli_real_escape_string($conn, $_POST['certificado_vocacional']);
$curso_id = !empty($_POST['curso_id']) ? (int)$_POST['curso_id'] : null;
$turno_id = !empty($_POST['turno_id']) ? (int)$_POST['turno_id'] : null;
$turma_id = !empty($_POST['turma_id']) ? (int)$_POST['turma_id'] : null;
$email = mysqli_real_escape_string($conn, $_POST['email']);
$codigo_gerado = !empty($_POST['codigo_gerado']) ? mysqli_real_escape_string($conn, $_POST['codigo_gerado']) : '';

$data_sql = ($data_nascimento !== null) ? "'$data_nascimento'" : "NULL";
$contacto_sql = ($contacto !== null) ? "'$contacto'" : "NULL";
$ano_ingresso_sql = ($ano_ingresso !== null) ? $ano_ingresso : "NULL";
$ano_conclusao_sql = ($ano_conclusao !== null) ? $ano_conclusao : "NULL";

$sql = "UPDATE formandos SET 
            codigo_formando = '$codigo_formando',
            nome_completo = '$nome',
            numero_documento = '$numero_documento',
            sexo = '$sexo',
            data_nascimento = $data_sql,
            contacto = $contacto_sql,
            ano_ingresso = $ano_ingresso_sql,
            ano_conclusao = $ano_conclusao_sql,
            certificado_vocacional = '$certificado',
            curso_id = $curso_id,
            turno_id = $turno_id,
            turma_id = $turma_id,
            email = '$email'
        WHERE id = $id";

if (!$conn->query($sql)) {
    log_erro('formando_update', $conn->error);
    echo "erro";
    exit;
}

if ($antigo_email && $email && $antigo_email !== $email && !empty($codigo_gerado)) {
    $conn->query("DELETE FROM codigos_autorizados 
        WHERE email_dono = '$antigo_email' AND nivel_destinado = 3");

    $conn->query("INSERT INTO codigos_autorizados (codigo_acesso, email_dono, nivel_destinado, estado) VALUES ('$codigo_gerado', '$email', 3, 'disponivel')");
}


// === ENCARREGADO ===
$enc_email = !empty($_POST['encarregado_email']) ? mysqli_real_escape_string($conn, $_POST['encarregado_email']) : '';
$enc_codigo = !empty($_POST['encarregado_codigo']) ? mysqli_real_escape_string($conn, $_POST['encarregado_codigo']) : '';
$enc_id = !empty($_POST['encarregado_id']) ? (int)$_POST['encarregado_id'] : 0;
$enc_nome = !empty($_POST['encarregado_nome']) ? mysqli_real_escape_string($conn, $_POST['encarregado_nome']) : '';
$enc_tipo = !empty($_POST['encarregado_tipo']) ? mysqli_real_escape_string($conn, $_POST['encarregado_tipo']) : '';
$enc_contacto = !empty($_POST['encarregado_contacto']) ? mysqli_real_escape_string($conn, $_POST['encarregado_contacto']) : '';

if ($enc_email) {
    $enc_email_antigo = '';
    if ($enc_id) {
        $resEnc = $conn->query("SELECT email FROM encarregados WHERE id = $enc_id LIMIT 1");
        $enc_email_antigo = $resEnc ? $resEnc->fetch_assoc()['email'] : '';
    }
    if (!$enc_id) {
        $ok = $conn->query("INSERT INTO encarregados (nome_completo, email, contacto, parentesco) VALUES ('$enc_nome', '$enc_email', '$enc_contacto', '$enc_tipo')");
        if ($ok) {
            $enc_id = $conn->insert_id;
        }
    } else {
        $conn->query("UPDATE encarregados SET nome_completo='$enc_nome', contacto='$enc_contacto', parentesco='$enc_tipo' WHERE id=$enc_id");
    }

    if ($enc_codigo && $enc_email_antigo && $enc_email_antigo !== $enc_email) {
        $conn->query("DELETE FROM codigos_autorizados 
                    WHERE email_dono = '$enc_email_antigo' AND nivel_destinado = 4");

        $conn->query("INSERT INTO codigos_autorizados (codigo_acesso, email_dono, nivel_destinado, estado) VALUES ('$enc_codigo', '$enc_email', 4, 'disponivel')");
    }

    $conn->query("DELETE FROM encarregado_formando WHERE formando_id = $id");
    $conn->query("INSERT INTO encarregado_formando (encarregado_id, formando_id, principal) VALUES ($enc_id, $id, 1)");
}

log_acao('formando_update', "Formando actualizado: $nome | $codigo_formando");
echo "sucesso";
