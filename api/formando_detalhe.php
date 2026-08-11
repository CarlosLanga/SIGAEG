<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['ok' => false]);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    echo json_encode(['ok' => false]);
    exit;
}

$sql = "SELECT 
            f.*,
            t.nome_turma,
            tr.nome_turno,
            c.nome_curso,
            ca.codigo_acesso AS codigo_convite
        FROM formandos f
        LEFT JOIN turmas t ON t.id = f.turma_id
        LEFT JOIN turnos tr ON tr.id = f.turno_id
        LEFT JOIN cursos c ON c.id = f.curso_id
        LEFT JOIN codigos_autorizados ca 
            ON ca.email_dono = f.email AND ca.nivel_destinado = 3
        WHERE f.id = $id
        LIMIT 1";

$res = $conn->query($sql);
if (!$res || $res->num_rows === 0) {
    echo json_encode(['ok' => false]);
    exit;
}

$data = $res->fetch_assoc();

$enc = [
    'id' => '',
    'email' => '',
    'nome_completo' => '',
    'parentesco' => '',
    'contacto' => '',
    'codigo_convite' => ''
];

$sqlEnc = "SELECT e.id, e.email, e.nome_completo, e.parentesco, e.contacto, ca.codigo_acesso AS codigo_convite
           FROM encarregado_formando ef
           JOIN encarregados e ON e.id = ef.encarregado_id
           LEFT JOIN codigos_autorizados ca ON ca.email_dono = e.email AND ca.nivel_destinado = 4
           WHERE ef.formando_id = $id
           ORDER BY ef.principal DESC
           LIMIT 1";

$resEnc = $conn->query($sqlEnc);
if ($resEnc && $resEnc->num_rows > 0) {
    $enc = $resEnc->fetch_assoc();
}

echo json_encode([
    'ok' => true,
    'data' => $data,
    'encarregado' => $enc
]);
