<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    log_erro('formando_delete', 'Metodo invalido');
    echo "erro";
    exit;
}

if (!$conn) {
    log_erro('formando_delete', 'Falha de conexao com BD');
    echo "erro";
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    log_erro('formando_delete', 'ID invalido');
    echo "erro";
    exit;
}

$res = $conn->query("SELECT email FROM formandos WHERE id = $id LIMIT 1");
if (!$res || $res->num_rows === 0) {
    log_erro('formando_delete', 'Formando nao encontrado');
    echo "erro";
    exit;
}
$row = $res->fetch_assoc();
$email = mysqli_real_escape_string($conn, $row['email'] ?? '');

$conn->begin_transaction();
try {
    $conn->query("DELETE FROM encarregado_formando WHERE formando_id = $id");

    if ($email !== '') {
        $conn->query("DELETE FROM codigos_autorizados WHERE email_dono = '$email' AND nivel_destinado = 3");
    }

    $ok = $conn->query("DELETE FROM formandos WHERE id = $id");
    if (!$ok) {
        throw new Exception($conn->error);
    }

    $conn->commit();
    log_acao('formando_delete', "Formando removido: $email | ID $id");
    echo "sucesso";
} catch (Exception $e) {
    $conn->rollback();
    log_erro('formando_delete', $e->getMessage());
    echo "erro";
}
exit;
