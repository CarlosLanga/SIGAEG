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

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

if (!in_array($nivel, [1, 2], true) || $usuarioId <= 0) {
    json_out(['ok' => false, 'message' => 'Sem permissão.']);
}

ensure_anuncios_modulo_schema($conn);

$isFormador = ($nivel === 2);

$titulo = trim((string)($_POST['titulo'] ?? ''));
$prioridade = trim((string)($_POST['prioridade'] ?? 'normal'));
$publicoAlvo = trim((string)($_POST['publico_alvo'] ?? 'todos'));
$turmaId = (int)($_POST['turma_id'] ?? 0);
$moduloId = (int)($_POST['modulo_id'] ?? 0);
$dataExpiracao = trim((string)($_POST['data_expiracao'] ?? ''));
$descricao = trim((string)($_POST['descricao'] ?? ''));
$eventoDataInicio = trim((string)($_POST['evento_data_inicio'] ?? ''));
$eventoDataFim = trim((string)($_POST['evento_data_fim'] ?? ''));

if ($isFormador) {
    $publicoAlvo = 'turma';
}

if ($titulo === '') {
    json_out(['ok' => false, 'message' => 'Informe o título do anúncio.']);
}

if ($descricao === '' || $descricao === '<p><br></p>') {
    json_out(['ok' => false, 'message' => 'O conteúdo do anúncio não pode estar vazio.']);
}

if ($publicoAlvo === 'turma' && $turmaId <= 0) {
    json_out(['ok' => false, 'message' => 'Seleccione a turma.']);
}

if ($isFormador) {
    $formadorId = getFormadorId($conn, $usuarioId);
    if ($formadorId <= 0) {
        json_out(['ok' => false, 'message' => 'Formador não encontrado.']);
    }

    if (!formadorTemAcessoTurma($conn, $formadorId, $turmaId)) {
        json_out(['ok' => false, 'message' => 'Não tem permissão para anunciar nesta turma.']);
    }

    if ($moduloId > 0 && !formadorTemModuloNaTurma($conn, $formadorId, $turmaId, $moduloId)) {
        json_out(['ok' => false, 'message' => 'Módulo inválido para esta turma.']);
    }
} elseif ($moduloId > 0) {
    json_out(['ok' => false, 'message' => 'Módulo só pode ser definido por formadores.']);
}

if ($prioridade === 'evento' && $eventoDataInicio === '') {
    json_out(['ok' => false, 'message' => 'Por favor, selecione as datas do evento.']);
}

$anexoCaminho = '';
$anexoNome = '';

if (isset($_FILES['anexo']) && $_FILES['anexo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['anexo'];
    $maxSize = 10 * 1024 * 1024;
    if ((int)$file['size'] > $maxSize) {
        json_out(['ok' => false, 'message' => 'O anexo não pode exceder 10MB.']);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'jpg', 'jpeg', 'png'];
    if (!in_array($ext, $allowed, true)) {
        json_out(['ok' => false, 'message' => 'Formato de anexo não permitido.']);
    }

    $dirUpload = __DIR__ . '/../assets/ficheiros/anuncios/';
    if (!is_dir($dirUpload)) {
        @mkdir($dirUpload, 0775, true);
    }

    $safeName = 'anuncio_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destino = $dirUpload . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        json_out(['ok' => false, 'message' => 'Falha ao guardar o anexo.']);
    }

    $anexoNome = $file['name'];
    $anexoCaminho = 'assets/ficheiros/anuncios/' . $safeName;
}

$tituloEsc = $conn->real_escape_string($titulo);
$descricaoEsc = $conn->real_escape_string($descricao);
$prioridadeEsc = $conn->real_escape_string($prioridade);
$publicoAlvoEsc = $conn->real_escape_string($publicoAlvo);
$turmaSql = $publicoAlvo === 'turma' ? (string)$turmaId : 'NULL';
$moduloSql = ($publicoAlvo === 'turma' && $moduloId > 0) ? (string)$moduloId : 'NULL';
$dataExpiracaoSql = $dataExpiracao !== '' ? "'" . $conn->real_escape_string($dataExpiracao) . " 23:59:59'" : 'NULL';
$anexoNomeSql = $anexoNome !== '' ? "'" . $conn->real_escape_string($anexoNome) . "'" : 'NULL';
$anexoCaminhoSql = $anexoCaminho !== '' ? "'" . $conn->real_escape_string($anexoCaminho) . "'" : 'NULL';
$eventoDataInicioSql = $eventoDataInicio !== '' ? "'" . $conn->real_escape_string($eventoDataInicio) . "'" : 'NULL';
$eventoDataFimSql = $eventoDataFim !== '' ? "'" . $conn->real_escape_string($eventoDataFim) . "'" : 'NULL';

$sql = "
    INSERT INTO anuncios (
        titulo, descricao, criado_por, prioridade, publico_alvo,
        turma_id, modulo_id, data_expiracao, anexo_nome, anexo_caminho,
        evento_data_inicio, evento_data_fim
    ) VALUES (
        '$tituloEsc', '$descricaoEsc', $usuarioId, '$prioridadeEsc', '$publicoAlvoEsc',
        $turmaSql, $moduloSql, $dataExpiracaoSql, $anexoNomeSql, $anexoCaminhoSql,
        $eventoDataInicioSql, $eventoDataFimSql
    )
";

if (!$conn->query($sql)) {
    if ($anexoCaminho !== '') {
        @unlink(__DIR__ . '/../' . $anexoCaminho);
    }
    log_erro('anuncios_save', $conn->error);
    json_out(['ok' => false, 'message' => 'Erro ao registar o anúncio.']);
}

log_acao('anuncios_save', "Anúncio criado: $titulo");
json_out(['ok' => true]);
