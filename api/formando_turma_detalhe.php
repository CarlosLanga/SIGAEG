<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

if (!$conn) {
    json_out(['ok' => false, 'msg' => 'Erro de conexao.']);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$turmaId = (int)($_GET['turma_id'] ?? 0);

if (!in_array($nivel, [1, 3], true) || $usuarioId <= 0 || $turmaId <= 0) {
    json_out(['ok' => false, 'msg' => 'Sem permissao.']);
}

$formandoId = $nivel === 3 ? getFormandoId($conn, $usuarioId) : 0;
if ($nivel === 3) {
    if ($formandoId <= 0 || !formandoPodeAcederTurma($conn, $formandoId, $turmaId)) {
        json_out(['ok' => false, 'msg' => 'Turma nao encontrada.']);
    }
}

$resTurma = $conn->query("
    SELECT
        t.id,
        t.nome_turma,
        tr.nome_turno,
        t.certificado_vocacional,
        t.ano_lectivo,
        c.nome_curso,
        CONCAT(
            IFNULL(NULLIF(dt.titulo, ''), ''),
            CASE WHEN dt.titulo IS NULL OR dt.titulo = '' THEN '' ELSE ' ' END,
            dt.nome_completo
        ) AS director_turma,
        COUNT(DISTINCT fo.id) AS total_formandos
    FROM turmas t
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    LEFT JOIN cursos c ON c.id = t.curso_id
    LEFT JOIN formadores dt ON dt.id = t.dt_id
    LEFT JOIN formandos fo ON fo.turma_id = t.id
    WHERE t.id = $turmaId
    GROUP BY t.id, t.nome_turma, tr.nome_turno, t.certificado_vocacional, t.ano_lectivo, c.nome_curso, dt.titulo, dt.nome_completo
    LIMIT 1
");

if (!$resTurma || $resTurma->num_rows === 0) {
    json_out(['ok' => false, 'msg' => 'Turma nao encontrada.']);
}

$turma = $resTurma->fetch_assoc();

$formandos = [];
$resFormandos = $conn->query("
    SELECT id, nome_completo, codigo_formando
    FROM formandos
    WHERE turma_id = $turmaId
    ORDER BY nome_completo ASC
");
if ($resFormandos) {
    while ($row = $resFormandos->fetch_assoc()) {
        $formandos[] = $row;
    }
}

json_out([
    'ok' => true,
    'turma' => $turma,
    'formandos' => $formandos,
]);
