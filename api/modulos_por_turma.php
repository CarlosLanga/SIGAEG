<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$turma_id = (int)($_GET['turma_id'] ?? 0);
$curso_id = (int)($_GET['curso_id'] ?? 0);
$tipo_modulo = $_GET['tipo_modulo'] ?? '';

if (!$conn || !$turma_id || !$curso_id) {
    echo json_encode([]);
    exit;
}

$tipoFiltro = '';
if ($tipo_modulo === 'generico' || $tipo_modulo === 'vocacional') {
    $tipoFiltro = " AND m.tipo_modulo = '" . mysqli_real_escape_string($conn, $tipo_modulo) . "'";
}

$sql = "
SELECT
    m.id,
    m.sigla_modulo,
    m.codigo_modulo,
    m.nome_modulo,
    m.tipo_modulo,
    CASE 
        WHEN fm.id IS NULL THEN 0
        ELSE 1
    END AS ja_registado
FROM modulos m
LEFT JOIN formador_modulo fm 
    ON fm.modulo_id = m.id AND fm.turma_id = $turma_id
WHERE m.curso_id = $curso_id
$tipoFiltro
ORDER BY m.codigo_modulo ASC
";

$res = $conn->query($sql);
$rows = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}

echo json_encode($rows);
