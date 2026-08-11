<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/vendor/autoload.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Parâmetro inválido");
}

if (!$conn) {
    die("Erro de ligação");
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$scopeWhere = $nivel === 2 ? "AND f.usuario_id = $usuario_id" : "";

 $res = $conn->query("
    SELECT
        pp.id,
        pp.data_aula,
        pp.dia_semana,
        t.nome_turma,
        tr.nome_turno,
        c.nome_curso,
        fm.formador_id,
        f.nome_completo AS formador_nome,
        f.titulo AS formador_titulo
    FROM presencas_plano pp
    LEFT JOIN turmas t ON t.id = pp.turma_id
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    LEFT JOIN cursos c ON c.id = t.curso_id
    LEFT JOIN formador_modulo fm ON fm.id = pp.formador_modulo_id
    LEFT JOIN formadores f ON f.id = fm.formador_id
    WHERE pp.id = $id
    $scopeWhere
    LIMIT 1
");

if (!$res || $res->num_rows === 0) {
    die("Registo não encontrado");
}

$data = $res->fetch_assoc();

$slots = [];
$resSlots = $conn->query("SELECT slot_codigo FROM presencas_intervalo WHERE plano_id = $id ORDER BY slot_codigo ASC");
if ($resSlots) {
    while ($row = $resSlots->fetch_assoc()) {
        $slots[] = $row['slot_codigo'];
    }
}

$slotOrderDiurno = [
    '07:00-07:45',
    '07:45-08:30',
    '08:35-09:20',
    '09:20-10:05',
    '10:10-10:55',
    '11:00-11:45',
    '12:05-12:50',
    '12:50-13:35',
    '13:40-14:25',
    '14:25-15:10',
    '15:10-15:55',
    '15:55-16:40',
];

$slotOrderNocturno = [
    '17:00-17:45',
    '17:45-18:30',
    '18:35-19:20',
    '19:20-20:05',
    '20:10-20:55',
    '21:00-21:45',
];

$turnoLower = mb_strtolower(trim((string)($data['nome_turno'] ?? '')), 'UTF-8');
$isNocturno = strpos($turnoLower, 'nocturno') !== false;
$slotOrder = $isNocturno ? $slotOrderNocturno : $slotOrderDiurno;
$slotIndex = [];
foreach ($slotOrder as $idx => $code) {
    $slotIndex[$code] = $idx + 1;
}

$aulasNums = [];
foreach ($slots as $code) {
    if (isset($slotIndex[$code])) {
        $aulasNums[] = $slotIndex[$code];
    }
}
sort($aulasNums);
$aulasLabel = $aulasNums ? implode(', ', $aulasNums) : '-';

$rows = [];
$resReg = $conn->query("
    SELECT f.nome_completo, f.codigo_formando, pr.situacao, pr.observacao
    FROM presencas_registo pr
    INNER JOIN formandos f ON f.id = pr.formando_id
    WHERE pr.plano_id = $id
    ORDER BY f.nome_completo ASC
");
if ($resReg) {
    while ($row = $resReg->fetch_assoc()) {
        $rows[] = $row;
    }
}

function esc($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function turnoSigla(?string $nomeTurno): string
{
    if (!$nomeTurno) return '';
    return stripos($nomeTurno, 'diurno') !== false ? 'CD' : 'CN';
}

function situacaoSigla(string $situacao): string
{
    if ($situacao === 'Presente') return 'P';
    if ($situacao === 'Ausente') return 'F';
    if ($situacao === 'WD') return 'WD';
    if ($situacao === 'D') return 'D';
    return '-';
}

$formadorNome = trim(($data['formador_titulo'] ?? '') . ' ' . ($data['formador_nome'] ?? ''));
$siglaTurno = turnoSigla($data['nome_turno'] ?? '');

$logoPath = __DIR__ . '/../assets/img/images.jpg';

$dataFormatada = '-';
if (!empty($data['data_aula'])) {
    $parts = explode('-', $data['data_aula']);
    if (count($parts) === 3) {
        $dataFormatada = $parts[2] . '/' . $parts[1] . '/' . $parts[0];
    }
}

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: "Times New Roman", serif; font-size: 12px; }
    .header { text-align:center; line-height:1.4; }
    .header img { width: 80px; margin-bottom: 6px; }
    .title { font-weight: bold; text-decoration: underline; margin: 12px 0; }
    .subtitle { font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #000; padding: 4px; text-align: center; }
    th { background: #f2f2f2; }
    .meta-table {
        width: 100%;
        border-collapse: collapse;
        margin: 8px auto 0;
        table-layout: fixed;
    }

    .meta-table td {
        padding: 2px 4px;
        font-size: 12px;
        border: none;
        vertical-align: middle;
    }

    .meta-left   { width: 30%; text-align: left; }
    .meta-center { width: 30%; text-align: center; }
    .meta-right  { width: 30%; text-align: right; }

    .legend { margin-top: 8px; font-size: 11px; }
    .footer { position: fixed; bottom: 20px; right: 30px; font-size: 10px; }
</style>
</head>
<body>
    <div class="header">
        <img src="' . $logoPath . '" />
        <div>REPÚBLICA DE MOÇAMBIQUE</div>
        <div>GOVERNO DO DISTRITO DE BOANE</div>
        <div>SERVIÇO DISTRITAL DE EDUCAÇÃO, JUVENTUDE E TECNOLOGIA</div>
        <div>INSTITUTO INDUSTRIAL E DE COMPUTAÇÃO ARMANDO EMÍLIO GUEBUZA</div>
        <div class="subtitle">DEPARTAMENTO DE TIC</div>
        <div class="title">Lista de Presenças</div>
        <div class="subtitle">Qualificação: ' . esc($data['nome_curso'] ?? '-') . '</div>
        <div class="subtitle">Turma: ' . esc($data['nome_turma'] ?? '-') . ($siglaTurno ? ' - ' . esc($siglaTurno) : '') . '</div>
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-left">
                <strong>Formador:</strong> ' . esc($formadorNome ?: '-') . '
            </td>
            <td class="meta-center">
                <strong>Data:</strong> ' . esc($dataFormatada) . '
            </td>
            <td class="meta-right">
                <strong>Aulas:</strong> ' . esc($aulasLabel) . '
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Nº</th>
                <th>Nome do Formando</th>
                <th>Código</th>
                <th>Situação</th>
                <th>Observação</th>
            </tr>
        </thead>
        <tbody>';

$i = 1;
foreach ($rows as $r) {
    $html .= '<tr>
        <td>' . str_pad($i++, 2, "0", STR_PAD_LEFT) . '</td>
        <td style="text-align:left;">' . esc($r['nome_completo']) . '</td>
        <td>' . esc($r['codigo_formando']) . '</td>
        <td>' . esc(situacaoSigla($r['situacao'] ?? '')) . '</td>
        <td style="text-align:left;">' . esc($r['observacao'] ?? '') . '</td>
    </tr>';
}

$html .= '
        </tbody>
    </table>

    <div class="legend">
        <strong>Descrição:</strong>
        P - Presente, F - Falta/Ausente, WD - Desistência formal, D - Desistiu
    </div>
    <div class="footer">1</div>
</body>
</html>';

$mpdf = new \Mpdf\Mpdf([
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 10,
    'margin_bottom' => 10,
]);
$mpdf->WriteHTML($html);
$mpdf->Output('presencas.pdf', 'I');
