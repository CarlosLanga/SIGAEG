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
    json_out(['ok' => false, 'categories' => []]);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

if (!isset($_SESSION['usuario_id'])) {
    json_out(['ok' => false, 'categories' => []]);
}

$turmaJoin = "LEFT JOIN turmas t ON t.id = fi.turma_id LEFT JOIN turnos tu ON tu.id = t.turno_id";
$baseSelect = "
    SELECT
        fi.id,
        fi.categoria,
        fi.titulo,
        fi.descricao,
        fi.nome_original,
        fi.caminho,
        fi.mime_type,
        fi.tamanho,
        fi.downloads,
        fi.data_upload,
        fi.turma_id,
        t.nome_turma,
        tu.nome_turno
    FROM ficheiros fi
    $turmaJoin
";

$generalRows = [];
$turmaRows = [];
$meusRows = [];

$res = $conn->query($baseSelect . " WHERE fi.categoria = 'geral' ORDER BY fi.data_upload DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $generalRows[] = $row;
    }
}

if ($nivel === 1) {
    $sqlTurma = $baseSelect . " WHERE fi.categoria = 'turma' ORDER BY t.nome_turma ASC, fi.data_upload DESC";
} elseif ($nivel === 2) {
    $sqlTurma = $baseSelect . "
        WHERE fi.categoria = 'turma'
          AND EXISTS (
              SELECT 1
              FROM formador_modulo fm
              INNER JOIN formadores f ON f.id = fm.formador_id
              WHERE fm.turma_id = fi.turma_id
                AND f.usuario_id = $usuarioId
          )
        ORDER BY t.nome_turma ASC, fi.data_upload DESC
    ";
} else {
    $sqlTurma = $baseSelect . " WHERE 1 = 0";
}

$res = $conn->query($sqlTurma);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $turmaRows[] = $row;
    }
}

$res = $conn->query($baseSelect . " WHERE fi.criado_por = $usuarioId ORDER BY fi.data_upload DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $meusRows[] = $row;
    }
}

json_out([
    'ok' => true,
    'can_upload' => in_array($nivel, [1, 2], true),
    'categories' => [
        ['key' => 'geral', 'label' => 'Geral', 'rows' => $generalRows],
        ['key' => 'turma', 'label' => $nivel === 2 ? 'Turmas' : 'Ficheiros de Turma', 'rows' => $turmaRows],
        ['key' => 'meus', 'label' => 'Meus Ficheiros', 'rows' => $meusRows],
    ]
]);
