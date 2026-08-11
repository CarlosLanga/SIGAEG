<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload): void {
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'message' => 'Método inválido.']);
}

if (!$conn) {
    json_out(['ok' => false, 'message' => 'Erro de ligação à base de dados.']);
}

$turma_id = (int)($_POST['turma_id'] ?? 0);
$formador_modulo_id = (int)($_POST['formador_modulo_id'] ?? 0);
$modulo_id = (int)($_POST['modulo_id'] ?? 0);
$titulo = trim($_POST['titulo'] ?? '');
$data_avaliacao = trim($_POST['data_avaliacao'] ?? '');
$hora_avaliacao = trim($_POST['hora_avaliacao'] ?? '');

if ($turma_id <= 0 || $formador_modulo_id <= 0 || $modulo_id <= 0 || $titulo === '' || $data_avaliacao === '' || $hora_avaliacao === '') {
    json_out(['ok' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$formadorJoin = $nivel === 2 ? "INNER JOIN formadores f ON f.id = fm.formador_id" : "";
$formadorWhere = $nivel === 2 ? "AND f.usuario_id = $usuario_id" : "";

$res = $conn->query("
    SELECT fm.data_inicio, fm.data_fim
    FROM formador_modulo fm
    $formadorJoin
    WHERE fm.id = $formador_modulo_id
      AND fm.turma_id = $turma_id
      AND fm.modulo_id = $modulo_id
      $formadorWhere
    LIMIT 1
");
if (!$res || $res->num_rows === 0) {
    json_out(['ok' => false, 'message' => 'Módulo inválido para a turma seleccionada.']);
}
$vigencia = $res->fetch_assoc();
$inicio = $vigencia['data_inicio'];
$fim = $vigencia['data_fim'];
if ($inicio && $data_avaliacao < $inicio) {
    json_out(['ok' => false, 'message' => 'A data deve estar dentro da vigência do módulo.']);
}
if ($fim && $data_avaliacao > $fim) {
    json_out(['ok' => false, 'message' => 'A data deve estar dentro da vigência do módulo.']);
}

$hasHora = false;
$col = $conn->query("SHOW COLUMNS FROM avaliacoes LIKE 'hora_avaliacao'");
if ($col && $col->num_rows > 0) {
    $hasHora = true;
} else {
    if (!$conn->query("ALTER TABLE avaliacoes ADD COLUMN hora_avaliacao TIME NULL DEFAULT NULL AFTER data_avaliacao")) {
        log_erro('avaliacoes_save', $conn->error);
        json_out(['ok' => false, 'message' => 'Erro ao preparar o campo de hora.']);
    }
    $hasHora = true;
}

$tituloEsc = $conn->real_escape_string($titulo);
$dataEsc = $conn->real_escape_string($data_avaliacao);
$horaEsc = $conn->real_escape_string($hora_avaliacao);

$sql = "
    INSERT INTO avaliacoes (titulo, modulo_id, turma_id, data_avaliacao, hora_avaliacao, criado_por)
    VALUES ('$tituloEsc', $modulo_id, $turma_id, '$dataEsc', '$horaEsc', $formador_modulo_id)
";

if (!$conn->query($sql)) {
    log_erro('avaliacoes_save', $conn->error);
    json_out(['ok' => false, 'message' => 'Erro ao guardar a avaliação.']);
}

json_out(['ok' => true]);
