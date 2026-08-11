<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
if (!in_array($nivel, [1, 2], true)) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

if (!$conn) {
    echo "Erro de conexao.";
    exit;
}

$turma_id = (int)($_GET['turma_id'] ?? 0);
$semestre = (int)($_GET['semestre'] ?? 0);
$bloco = (int)($_GET['bloco'] ?? 0);

if ($turma_id <= 0 || !in_array($semestre, [1, 2], true) || !in_array($bloco, [1, 2], true)) {
    echo "Parametros invalidos.";
    exit;
}

$scopeJoin = $nivel === 2 ? "
    INNER JOIN formador_modulo fm_scope ON fm_scope.turma_id = t.id
    INNER JOIN formadores f_scope ON f_scope.id = fm_scope.formador_id
" : "";
$scopeWhere = $nivel === 2 ? "AND f_scope.usuario_id = $usuario_id" : "";

$turmaRes = $conn->query("
    SELECT t.nome_turma, tr.nome_turno
    FROM turmas t
    $scopeJoin
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    WHERE t.id = $turma_id
    $scopeWhere
    LIMIT 1
");
if (!$turmaRes || $turmaRes->num_rows === 0) {
    echo "Turma nao encontrada.";
    exit;
}

$turma = $turmaRes->fetch_assoc();
$nome_turma = $turma['nome_turma'] ?? '';
$nome_turno = $turma['nome_turno'] ?? '';

$resPlano = $conn->query("
    SELECT id
    FROM horarios_plano
    WHERE turma_id = $turma_id AND semestre = $semestre AND bloco = $bloco
    LIMIT 1
");

$plano_id = 0;
if ($resPlano && $resPlano->num_rows > 0) {
    $plano_id = (int)$resPlano->fetch_assoc()['id'];
}

$cells = [];
if ($plano_id > 0) {
    $resCells = $conn->query("
        SELECT c.dia_semana, c.slot_codigo, m.sigla_modulo
        FROM horarios_celula c
        INNER JOIN formador_modulo fm ON fm.id = c.formador_modulo_id
        INNER JOIN modulos m ON m.id = fm.modulo_id
        WHERE c.plano_id = $plano_id
    ");
    if ($resCells) {
        while ($row = $resCells->fetch_assoc()) {
            $key = $row['dia_semana'] . '__' . $row['slot_codigo'];
            $cells[$key] = $row['sigla_modulo'];
        }
    }
}

$turnoLower = mb_strtolower(trim((string)$nome_turno), 'UTF-8');
$isNocturno = strpos($turnoLower, 'nocturno') !== false;

$slots = $isNocturno ? [
    ['code' => '17:00-17:45', 'label' => '17:00 - 17:45'],
    ['code' => '17:45-18:30', 'label' => '17:45 - 18:30'],
    ['code' => '18:35-19:20', 'label' => '18:35 - 19:20'],
    ['code' => '19:20-20:05', 'label' => '19:20 - 20:05'],
    ['code' => '20:10-20:55', 'label' => '20:10 - 20:55'],
    ['code' => '21:00-21:45', 'label' => '21:00 - 21:45'],
] : [
    ['code' => '07:00-07:45', 'label' => '07:00 - 07:45'],
    ['code' => '07:45-08:30', 'label' => '07:45 - 08:30'],
    ['code' => '08:35-09:20', 'label' => '08:35 - 09:20'],
    ['code' => '09:20-10:05', 'label' => '09:20 - 10:05'],
    ['code' => '10:10-10:55', 'label' => '10:10 - 10:55'],
    ['code' => '11:00-11:45', 'label' => '11:00 - 11:45'],
    ['code' => '12:05-12:50', 'label' => '12:05 - 12:50'],
    ['code' => '12:50-13:35', 'label' => '12:50 - 13:35'],
    ['code' => '13:40-14:25', 'label' => '13:40 - 14:25'],
    ['code' => '14:25-15:10', 'label' => '14:25 - 15:10'],
    ['code' => '15:10-15:55', 'label' => '15:10 - 15:55'],
    ['code' => '15:55-16:40', 'label' => '15:55 - 16:40'],
];

$days = [
    ['key' => 'seg', 'label' => '2ª Feira'],
    ['key' => 'ter', 'label' => '3ª Feira'],
    ['key' => 'qua', 'label' => '4ª Feira'],
    ['key' => 'qui', 'label' => '5ª Feira'],
    ['key' => 'sex', 'label' => '6ª Feira'],
];

function esc($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$html = '
<style>
body { font-family: Arial, sans-serif; color: #222; }
h1 { font-size: 18px; margin: 0 0 4px; }
.meta { font-size: 12px; margin-bottom: 12px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #777; padding: 6px; text-align: center; font-size: 11px; }
th { background: #f0f0f0; }
.hour { width: 110px; font-weight: 700; }
</style>
';

$html .= '<h1>Horário - ' . esc($nome_turma) . '</h1>';
$html .= '<div class="meta">Semestre ' . esc($semestre) . ' | Bloco ' . esc($bloco) . ' | ' . esc($nome_turno) . '</div>';

$html .= '<table><thead><tr><th class="hour">Horas</th>';
foreach ($days as $d) {
    $html .= '<th>' . esc($d['label']) . '</th>';
}
$html .= '</tr></thead><tbody>';

foreach ($slots as $slot) {
    $html .= '<tr><td class="hour">' . esc($slot['label']) . '</td>';
    foreach ($days as $d) {
        $key = $d['key'] . '__' . $slot['code'];
        $sigla = $cells[$key] ?? '';
        $html .= '<td>' . esc($sigla) . '</td>';
    }
    $html .= '</tr>';
}

$html .= '</tbody></table>';

require_once __DIR__ . '/../lib/vendor/autoload.php';
$mpdf = new \Mpdf\Mpdf([
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 10,
    'margin_bottom' => 10,
]);
$mpdf->WriteHTML($html);
$mpdf->Output('horario.pdf', 'I');
