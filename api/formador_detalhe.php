<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$conn || !$id) {
    echo json_encode(['ok' => false]);
    exit;
}

$res = $conn->query("SELECT id, titulo, nome_completo, sexo, codigo_formador, email, telefone, especialidade, usuario_id FROM formadores WHERE id = $id LIMIT 1");
if (!$res || $res->num_rows === 0) {
    echo json_encode(['ok' => false]);
    exit;
}
$data = $res->fetch_assoc();

$codigo_convite = '';
$resCod = $conn->query("SELECT codigo_acesso FROM codigos_autorizados 
                        WHERE email_dono = '{$data['email']}' AND nivel_destinado = 2 
                        ORDER BY id DESC LIMIT 1");
if ($resCod && $resCod->num_rows > 0) {
    $codigo_convite = $resCod->fetch_assoc()['codigo_acesso'];
}
$data['codigo_convite'] = $codigo_convite;


$cursos = [];
$resCursos = $conn->query("SELECT curso_id FROM formador_curso WHERE formador_id = $id");
if ($resCursos) {
    while ($row = $resCursos->fetch_assoc()) {
        $cursos[] = (int)$row['curso_id'];
    }
}

$turmas = [];
$sqlTurmas = "
    SELECT 
        t.id,
        t.nome_turma,
        tr.nome_turno,
        CASE WHEN t.dt_id = $id THEN 'dt' ELSE 'docente' END AS papel
    FROM turmas t
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    LEFT JOIN formador_modulo fm ON fm.turma_id = t.id AND fm.formador_id = $id
    WHERE t.dt_id = $id OR fm.formador_id = $id
    GROUP BY t.id
    ORDER BY t.nome_turma ASC
";
$resTurmas = $conn->query($sqlTurmas);
if ($resTurmas) {
    while ($row = $resTurmas->fetch_assoc()) {
        $turmas[] = $row;
    }
}

$modulos = [];
$sqlModulos = "
    SELECT 
        m.sigla_modulo,
        fm.data_inicio,
        fm.data_fim,
        CASE
            WHEN fm.data_inicio IS NULL OR fm.data_fim IS NULL THEN 'Por iniciar'
            WHEN CURDATE() < fm.data_inicio THEN 'Por iniciar'
            WHEN CURDATE() > fm.data_fim THEN 'Concluído'
            ELSE 'Em vigência'
        END AS estado
    FROM formador_modulo fm
    INNER JOIN modulos m ON m.id = fm.modulo_id
    WHERE fm.formador_id = $id
    ORDER BY m.sigla_modulo ASC
";
$resModulos = $conn->query($sqlModulos);
if ($resModulos) {
    while ($row = $resModulos->fetch_assoc()) {
        $modulos[] = $row;
    }
}

echo json_encode([
    'ok' => true,
    'data' => $data,
    'cursos' => $cursos,
    'turmas' => $turmas,
    'modulos' => $modulos
]);
