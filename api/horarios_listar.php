<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode([]);
    exit;
}

$turma_id = (int)($_GET['turma_id'] ?? 0);
if (!$turma_id) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT
            t.nome_turma,
            tr.nome_turno,
            m.sigla_modulo,
            f.nome_completo,
            f.titulo,
            h.dia_semana,
            h.hora_inicio,
            h.hora_fim,
            h.sala
        FROM horarios h
        LEFT JOIN turmas t ON t.id = h.turma_id
        LEFT JOIN turnos tr ON tr.id = t.turno_id
        LEFT JOIN modulos m ON m.id = h.modulo_id
        LEFT JOIN formadores f ON f.id = h.formador_id
        WHERE h.turma_id = $turma_id
        ORDER BY FIELD(h.dia_semana,'Segunda','Terça','Quarta','Quinta','Sexta','Sábado'),
                 h.hora_inicio ASC";

$res = $conn->query($sql);
$rows = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $turnoSigla = '';
        if (!empty($row['nome_turno'])) {
            $turnoSigla = stripos($row['nome_turno'], 'diurno') !== false ? 'CD' : 'CN';
        }
        $formador = trim(($row['titulo'] ?? '') . ' ' . ($row['nome_completo'] ?? ''));
        $rows[] = [
            'turma' => $row['nome_turma'] . ($turnoSigla ? ' - ' . $turnoSigla : ''),
            'modulo' => $row['sigla_modulo'],
            'formador' => $formador !== '' ? $formador : $row['nome_completo'],
            'dia_semana' => $row['dia_semana'],
            'hora_inicio' => $row['hora_inicio'],
            'hora_fim' => $row['hora_fim'],
            'sala' => $row['sala']
        ];
    }
}

echo json_encode($rows);









