<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'msg' => 'Método inválido.']);
}

if (!$conn) {
    json_out(['ok' => false, 'msg' => 'Erro de ligação.']);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$turma_id = (int)($payload['turma_id'] ?? 0);
$formador_modulo_id = (int)($payload['formador_modulo_id'] ?? 0);
$data = trim((string)($payload['data'] ?? ''));
$slots = $payload['slots'] ?? [];
$registos = $payload['registos'] ?? [];

if ($turma_id <= 0 || $formador_modulo_id <= 0 || $data === '') {
    json_out(['ok' => false, 'msg' => 'Parâmetros inválidos.']);
}

$dt = DateTime::createFromFormat('Y-m-d', $data);
if (!$dt) {
    json_out(['ok' => false, 'msg' => 'Data inválida.']);
}

$dayNum = (int)$dt->format('N');
$dayMap = [1 => 'seg', 2 => 'ter', 3 => 'qua', 4 => 'qui', 5 => 'sex'];
$dia_semana = $dayMap[$dayNum] ?? '';
if ($dia_semana === '') {
    json_out(['ok' => false, 'msg' => 'Sem aulas nessa data.']);
}

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);

$formadorJoin = "";
$formadorWhere = "";
if ($nivel === 2) {
    $formadorJoin = "INNER JOIN formadores f ON f.id = fm.formador_id";
    $formadorWhere = "AND f.usuario_id = $usuario_id";
}

$resPermissao = $conn->query("
    SELECT fm.id
    FROM formador_modulo fm
    $formadorJoin
    WHERE fm.id = $formador_modulo_id
      AND fm.turma_id = $turma_id
      $formadorWhere
    LIMIT 1
");
if (!$resPermissao || $resPermissao->num_rows === 0) {
    json_out(['ok' => false, 'msg' => 'Sem permissao para marcar presencas nesta turma/modulo.']);
}

$conn->begin_transaction();
try {
    $sqlPlano = "
        INSERT INTO presencas_plano (turma_id, formador_modulo_id, data_aula, dia_semana, estado, criado_por, actualizado_em)
        VALUES ($turma_id, $formador_modulo_id, '$data', '$dia_semana', 'rascunho', " . ($usuario_id > 0 ? $usuario_id : "NULL") . ", NOW())
        ON DUPLICATE KEY UPDATE
            id = LAST_INSERT_ID(id),
            estado = 'rascunho',
            actualizado_em = NOW()
    ";
    if (!$conn->query($sqlPlano)) {
        throw new Exception($conn->error);
    }

    $plano_id = (int)$conn->insert_id;
    if ($plano_id <= 0) {
        throw new Exception('Falha ao obter plano.');
    }

    $conn->query("DELETE FROM presencas_intervalo WHERE plano_id = $plano_id");
    if (is_array($slots)) {
        foreach ($slots as $slot) {
            $slotEsc = mysqli_real_escape_string($conn, (string)$slot);
            if ($slotEsc === '') continue;
            $conn->query("INSERT INTO presencas_intervalo (plano_id, slot_codigo) VALUES ($plano_id, '$slotEsc')");
        }
    }

    if (is_array($registos)) {
        foreach ($registos as $reg) {
            $formando_id = (int)($reg['formando_id'] ?? 0);
            if ($formando_id <= 0) continue;

            $situacao = trim((string)($reg['situacao'] ?? 'Presente'));
            if ($situacao === '') $situacao = 'Presente';
            if (!in_array($situacao, ['Presente', 'Ausente', 'WD', 'D'], true)) {
                $situacao = 'Presente';
            }

            $obs = trim((string)($reg['observacao'] ?? ''));
            $obsEsc = mysqli_real_escape_string($conn, $obs);
            $sitEsc = mysqli_real_escape_string($conn, $situacao);

            $sqlReg = "
                INSERT INTO presencas_registo (plano_id, formando_id, situacao, observacao, actualizado_em)
                VALUES ($plano_id, $formando_id, '$sitEsc', " . ($obsEsc !== '' ? "'$obsEsc'" : "NULL") . ", NOW())
                ON DUPLICATE KEY UPDATE
                    situacao = VALUES(situacao),
                    observacao = VALUES(observacao),
                    actualizado_em = NOW()
            ";
            if (!$conn->query($sqlReg)) {
                throw new Exception($conn->error);
            }
        }
    }

    $conn->commit();
    json_out(['ok' => true, 'estado' => 'rascunho']);
} catch (Throwable $e) {
    $conn->rollback();
    json_out(['ok' => false, 'msg' => 'Erro ao guardar presenças.']);
}
