<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'message' => 'Método inválido.']);
}

if (($_SESSION['nivel_acesso'] ?? 0) != 1) {
    json_out(['ok' => false, 'message' => 'Sem permissão.']);
}

if (!$conn) {
    json_out(['ok' => false, 'message' => 'Erro de ligação à base de dados.']);
}

$id = (int)($_POST['id'] ?? 0);
$turma_id = (int)($_POST['turma_id'] ?? 0);
$formador_modulo_id = (int)($_POST['formador_modulo_id'] ?? 0);
$modulo_id = (int)($_POST['modulo_id'] ?? 0);
$titulo = trim((string)($_POST['titulo'] ?? ''));
$tipo = trim((string)($_POST['tipo'] ?? 'individual'));
$descricao = trim((string)($_POST['descricao'] ?? ''));
$data_publicacao = trim((string)($_POST['data_publicacao'] ?? ''));
$data_entrega = trim((string)($_POST['data_entrega'] ?? ''));
$pontuacaoRaw = trim((string)($_POST['pontuacao_maxima'] ?? ''));
$estado = trim((string)($_POST['estado'] ?? 'rascunho'));

$tiposValidos = ['individual', 'grupo', 'pratico', 'projecto'];
$estadosValidos = ['rascunho', 'publicado', 'encerrado'];

if (
    $turma_id <= 0 ||
    $formador_modulo_id <= 0 ||
    $modulo_id <= 0 ||
    $titulo === '' ||
    $data_publicacao === '' ||
    $data_entrega === ''
) {
    json_out(['ok' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
}

if (!in_array($tipo, $tiposValidos, true)) {
    json_out(['ok' => false, 'message' => 'Tipo de trabalho inválido.']);
}

if (!in_array($estado, $estadosValidos, true)) {
    json_out(['ok' => false, 'message' => 'Estado inválido.']);
}

if ($data_entrega < $data_publicacao) {
    json_out(['ok' => false, 'message' => 'O prazo de entrega não pode ser anterior à data de publicação.']);
}

$pontuacao = null;
if ($pontuacaoRaw !== '') {
    if (!is_numeric($pontuacaoRaw) || (float)$pontuacaoRaw < 0 || (float)$pontuacaoRaw > 100) {
        json_out(['ok' => false, 'message' => 'A nota deve estar entre 0 e 100%.']);
    }
    $pontuacao = (float)$pontuacaoRaw;
}

$res = $conn->query("
    SELECT data_inicio, data_fim
    FROM formador_modulo
    WHERE id = $formador_modulo_id AND turma_id = $turma_id AND modulo_id = $modulo_id
    LIMIT 1
");

if (!$res || $res->num_rows === 0) {
    json_out(['ok' => false, 'message' => 'Módulo inválido para a turma seleccionada.']);
}

$duracao = $res->fetch_assoc();
$inicio = $duracao['data_inicio'];
$fim = $duracao['data_fim'];
if ($inicio && $data_publicacao < $inicio) {
    json_out(['ok' => false, 'message' => 'A data de publicação deve estar dentro da duração do módulo.']);
}
if ($fim && $data_entrega > $fim) {
    json_out(['ok' => false, 'message' => 'O prazo de entrega deve estar dentro da duração do módulo.']);
}

$tituloEsc = $conn->real_escape_string($titulo);
$tipoEsc = $conn->real_escape_string($tipo);
$descricaoSql = $descricao !== '' ? "'" . $conn->real_escape_string($descricao) . "'" : "NULL";
$dataPublicacaoEsc = $conn->real_escape_string($data_publicacao);
$dataEntregaEsc = $conn->real_escape_string($data_entrega);
$estadoEsc = $conn->real_escape_string($estado);
$pontuacaoSql = $pontuacao !== null ? number_format($pontuacao, 2, '.', '') : "NULL";
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$usuarioSql = $usuarioId > 0 ? (string)$usuarioId : "NULL";

if ($id > 0) {
    $sql = "
        UPDATE trabalhos SET
            turma_id = $turma_id,
            formador_modulo_id = $formador_modulo_id,
            modulo_id = $modulo_id,
            titulo = '$tituloEsc',
            tipo = '$tipoEsc',
            descricao = $descricaoSql,
            data_publicacao = '$dataPublicacaoEsc',
            data_entrega = '$dataEntregaEsc',
            pontuacao_maxima = $pontuacaoSql,
            estado = '$estadoEsc'
        WHERE id = $id
        LIMIT 1
    ";
} else {
    $sql = "
        INSERT INTO trabalhos (
            turma_id, formador_modulo_id, modulo_id, titulo, tipo, descricao,
            data_publicacao, data_entrega, pontuacao_maxima, estado, criado_por
        ) VALUES (
            $turma_id, $formador_modulo_id, $modulo_id, '$tituloEsc', '$tipoEsc', $descricaoSql,
            '$dataPublicacaoEsc', '$dataEntregaEsc', $pontuacaoSql, '$estadoEsc', $usuarioSql
        )
    ";
}

if (!$conn->query($sql)) {
    log_erro('trabalhos_save', $conn->error);
    json_out(['ok' => false, 'message' => 'Erro ao guardar o trabalho.']);
}

log_acao('trabalhos_save', "Trabalho guardado: $titulo | turma $turma_id | módulo $modulo_id");
json_out(['ok' => true]);
