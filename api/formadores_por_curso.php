<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$curso_id = (int)($_GET['curso_id'] ?? 0);

if (!$conn || !$curso_id) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT f.id, f.titulo, f.nome_completo,
               CONCAT(IFNULL(NULLIF(f.titulo, ''), ''), 
                      CASE WHEN f.titulo IS NULL OR f.titulo = '' THEN '' ELSE ' ' END,
                      f.nome_completo) AS nome_formatado
        FROM formadores f
        INNER JOIN formador_curso fc ON fc.formador_id = f.id
        WHERE fc.curso_id = $curso_id
        ORDER BY f.nome_completo ASC";

$res = $conn->query($sql);
$rows = [];
if ($res) {
    while ($row = $res->fetch_assoc()) $rows[] = $row;
}

echo json_encode($rows);
