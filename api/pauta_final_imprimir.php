<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/vendor/autoload.php';

if (!$conn) {
    die('Erro de ligacao a base de dados.');
}

$turma_id = (int)($_GET['turma_id'] ?? 0);
$modulo_id = (int)($_GET['modulo_id'] ?? 0);
if ($turma_id <= 0 || $modulo_id <= 0) {
    die('Parametros invalidos.');
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$scopeWhere = $nivel === 2 ? "
      AND EXISTS (
          SELECT 1
          FROM formador_modulo fm_scope
          INNER JOIN formadores f_scope ON f_scope.id = fm_scope.formador_id
          WHERE fm_scope.turma_id = a.turma_id
            AND fm_scope.modulo_id = a.modulo_id
            AND f_scope.usuario_id = $usuario_id
      )
" : "";
$formadorJoin = $nivel === 2 ? "INNER JOIN formadores f_scope ON f_scope.id = fm.formador_id" : "";
$formadorWhere = $nivel === 2 ? "AND f_scope.usuario_id = $usuario_id" : "";

function esc($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function turno_sigla(?string $nomeTurno): string
{
    if (!$nomeTurno) return '';
    return stripos($nomeTurno, 'diurno') !== false ? 'CD' : 'CN';
}

function data_pt(?string $data, string $sep = '.'): string
{
    if (!$data || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        return '-';
    }
    [$y, $m, $d] = explode('-', $data);
    return $d . $sep . $m . $sep . $y;
}

$avaliacoes = [];
$resAval = $conn->query("
    SELECT a.id, a.titulo, COALESCE(rs.estado, 'rascunho') AS estado_resultado
    FROM avaliacoes a
    LEFT JOIN avaliacoes_resultados_status rs ON rs.avaliacao_id = a.id
    WHERE a.turma_id = $turma_id
      AND a.modulo_id = $modulo_id
      AND (a.titulo LIKE 'Avaliação Sumativa %' OR a.titulo LIKE 'Avaliacao Sumativa %')
      $scopeWhere
    ORDER BY a.id ASC
");
if ($resAval) {
    while ($row = $resAval->fetch_assoc()) {
        $avaliacoes[] = $row;
    }
}

if (!$avaliacoes) {
    die('Pauta ainda nao disponivel. Nenhuma avaliacao sumativa encontrada.');
}

foreach ($avaliacoes as $a) {
    if (($a['estado_resultado'] ?? 'rascunho') !== 'publicado') {
        die('Pauta ainda nao disponivel. Existem avaliacoes nao realizadas/publicadas.');
    }
}

usort($avaliacoes, function ($a, $b) {
    preg_match('/(\d+)/', $a['titulo'] ?? '', $ma);
    preg_match('/(\d+)/', $b['titulo'] ?? '', $mb);
    $na = isset($ma[1]) ? (int)$ma[1] : 0;
    $nb = isset($mb[1]) ? (int)$mb[1] : 0;
    return $na <=> $nb;
});

$avaliacaoIds = array_map(function ($a) {
    return (int)$a['id'];
}, $avaliacoes);
$idsList = implode(',', $avaliacaoIds);

$cab = [
    'nome_turma' => '',
    'nome_turno' => '',
    'nome_modulo' => '',
    'codigo_modulo' => '',
    'nome_formador' => '',
    'nome_curso' => '',
    'codigo_curso' => '',
    'data_fim' => ''
];

$resCab = $conn->query("
    SELECT 
        t.nome_turma,
        tr.nome_turno,
        m.nome_modulo,
        COALESCE(m.codigo, '') AS codigo_modulo,
        f.nome_completo AS nome_formador,
        c.nome_curso,
        COALESCE(c.codigo, '') AS codigo_curso,
        fm.data_fim
    FROM formador_modulo fm
    INNER JOIN turmas t ON t.id = fm.turma_id
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    INNER JOIN modulos m ON m.id = fm.modulo_id
    INNER JOIN formadores f ON f.id = fm.formador_id
    $formadorJoin
    LEFT JOIN cursos c ON c.id = t.curso_id
    WHERE fm.turma_id = $turma_id
      AND fm.modulo_id = $modulo_id
      $formadorWhere
    ORDER BY fm.id DESC
    LIMIT 1
");

if ($resCab && $resCab->num_rows > 0) {
    $cab = array_merge($cab, $resCab->fetch_assoc());
}

$formador_modulo_id = 0;
$resFm = $conn->query("
    SELECT fm.id, fm.data_fim
    FROM formador_modulo fm
    $formadorJoin
    WHERE fm.turma_id = $turma_id AND fm.modulo_id = $modulo_id
    $formadorWhere
    ORDER BY fm.id DESC
    LIMIT 1
");
if ($resFm && $resFm->num_rows > 0) {
    $fm = $resFm->fetch_assoc();
    $formador_modulo_id = (int)$fm['id'];
    $cab['data_fim'] = $fm['data_fim'] ?? $cab['data_fim'];
}

$vigencia_terminada = !empty($cab['data_fim']) && (date('Y-m-d') > $cab['data_fim']);

$formandos = [];
$resFormandos = $conn->query("
    SELECT id, nome_completo, codigo_formando
    FROM formandos
    WHERE turma_id = $turma_id
    ORDER BY nome_completo ASC
");
if ($resFormandos) {
    while ($row = $resFormandos->fetch_assoc()) {
        $formandos[] = $row;
    }
}

$permanentes = [];
if ($formador_modulo_id > 0) {
    $resPerm = $conn->query("
        SELECT pr.formando_id, pr.situacao
        FROM presencas_registo pr
        INNER JOIN presencas_plano pp ON pp.id = pr.plano_id
        WHERE pp.turma_id = $turma_id
          AND pp.formador_modulo_id = $formador_modulo_id
        ORDER BY pp.data_aula DESC, pr.id DESC
    ");
    if ($resPerm) {
        $lastStatus = [];
        while ($row = $resPerm->fetch_assoc()) {
            $fid = (int)$row['formando_id'];
            if (!isset($lastStatus[$fid])) {
                $lastStatus[$fid] = (string)$row['situacao'];
            }
        }
        foreach ($lastStatus as $fid => $situ) {
            if (in_array($situ, ['WD', 'D'], true)) {
                $permanentes[$fid] = $situ;
            }
        }
    }
}


$resultados = [];
if ($idsList !== '') {
    $resRes = $conn->query("
        SELECT avaliacao_id, formando_id, nota_obtida
        FROM avaliacoes_resultados
        WHERE avaliacao_id IN ($idsList)
    ");
    if ($resRes) {
        while ($row = $resRes->fetch_assoc()) {
            $aid = (int)$row['avaliacao_id'];
            $fid = (int)$row['formando_id'];
            $resultados[$fid][$aid] = $row;
        }
    }
}


if ($vigencia_terminada && $idsList !== '') {
    foreach ($formandos as $f) {
        $fid = (int)$f['id'];
        foreach ($avaliacaoIds as $aid) {
            if (!isset($resultados[$fid][$aid]) || $resultados[$fid][$aid]['nota_obtida'] === null) {
                $conn->query("
                    INSERT INTO avaliacoes_resultados (avaliacao_id, formando_id, nota_obtida, resultado, observacao)
                    VALUES ($aid, $fid, 0, 'NA', NULL)
                    ON DUPLICATE KEY UPDATE nota_obtida = 0, resultado = 'NA'
                ");
                $resultados[$fid][$aid] = [
                    'avaliacao_id' => $aid,
                    'formando_id' => $fid,
                    'nota_obtida' => 0
                ];
            }
        }
    }
}

$rows = [];
foreach ($formandos as $f) {
    $fid = (int)$f['id'];
    $temNA = false;
    $temA = false;
    $temVazio = false;

    foreach ($avaliacaoIds as $aid) {
        $row = $resultados[$fid][$aid] ?? null;
        if (!$row || $row['nota_obtida'] === null || $row['nota_obtida'] === '') {
            $temVazio = true;
            continue;
        }
        $nota = (float)$row['nota_obtida'];
        if ($nota >= 80) {
            $temA = true;
        } else {
            $temNA = true;
        }
    }

    $resultadoFinal = '';
    if (isset($permanentes[$fid])) {
        $resultadoFinal = $permanentes[$fid];
    } elseif ($temNA) {
        $resultadoFinal = 'NA';
    } elseif ($temA && !$temVazio) {
        $resultadoFinal = 'A';
    }

    $rows[] = [
        'nome' => (string)$f['nome_completo'],
        'codigo' => (string)($f['codigo_formando'] ?? ''),
        'resultado' => $resultadoFinal
    ];
}

$rowsMin = 22;
while (count($rows) < $rowsMin) {
    $rows[] = ['nome' => '', 'codigo' => '', 'resultado' => ''];
}

$turnoSigla = turno_sigla($cab['nome_turno'] ?? '');
$anoFim = '';
if (!empty($cab['data_fim']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $cab['data_fim'])) {
    $anoFim = substr($cab['data_fim'], 0, 4);
}

$identTurma = trim((string)$cab['nome_turma']);
if ($turnoSigla !== '') {
    $identTurma .= '-' . $turnoSigla;
}
if ($anoFim !== '') {
    $identTurma .= '-' . $anoFim;
}

$logoPath = __DIR__ . '/../assets/img/images.jpg';
$dataFimFmt = data_pt($cab['data_fim'] ?? null, '.');

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: "Times New Roman", serif; font-size: 11.5px; color: #000; }
    .page { width: 100%; }
    .header { text-align: center; margin-bottom: 8px; line-height: 1.25; }
    .header img { width: 62px; margin-bottom: 4px; }
    .header .line { font-size: 11px; font-weight: bold; text-transform: uppercase; }
    .header .title { margin-top: 10px; font-size: 13px; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    td, th { border: 1px solid #000; padding: 3px 6px; vertical-align: top; }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    .small { font-size: 11px; }
    .shade { background: #f1f1f1; }
    .h20 { height: 20px; }
    .h24 { height: 24px; }
    .h32 { height: 32px; }
    .head-row td { font-size: 12px; font-weight: bold; text-align: center; vertical-align: middle; background: #f1f1f1; }
    .candidate-table td { vertical-align: middle; }
    .module-cell { min-height: 42px; padding-bottom: 14px; }
    .candidate-row td { height: 18px; padding-top: 1px; padding-bottom: 1px; }
    .candidate-row td:first-child,
    .candidate-row td:nth-child(2),
    .candidate-row td:nth-child(3) { white-space: nowrap; }
    .sign-cell { height: 18px; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <img src="' . $logoPath . '" />
        <div class="line">REPÚBLICA DE MOÇAMBIQUE</div>
        <div class="line">GOVERNO DO DISTRITO DE BOANE</div>
        <div class="line">SERVIÇO DISTRITAL DE EDUCAÇÃO, JUVENTUDE E TECNOLOGIA</div>
        <div class="line">INSTITUTO INDUSTRIAL E DE COMPUTAÇÃO ARMANDO EMÍLIO GUEBUZA</div>
        <div class="line">DEPARTAMENTO DE TECNOLOGIAS DE INFORMAÇÃO E COMUNICAÇÃO</div>
        <div class="title">Pauta Final de Módulo</div>
    </div>

    <table>
        <colgroup>
            <col style="width: 18%;">
            <col style="width: 43%;">
            <col style="width: 16%;">
            <col style="width: 23%;">
        </colgroup>
        <tr>
            <td class="bold center shade">Nome do centro</td>
            <td>Instituto Industrial e de Computação Armando Emílio Guebuza</td>
            <td class="bold shade">Número do Centro</td>
            <td class="bold center">1004</td>
        </tr>
        <tr>
            <td class="bold center shade">Título da Qualificação</td>
            <td>' . esc($cab['nome_curso'] ?? '-') . '</td>
            <td class="bold shade">Código da Qualificação</td>
            <td class="bold center">' . esc($cab['codigo_curso'] ?? '-') . '</td>
        </tr>
        <tr>
            <td colspan="2" class="shade"><span class="bold">Identificador da turma/grupo do Centro (opcional) pré-impresso</span></td>
            <td colspan="2" class="bold center">' . esc($identTurma !== '' ? $identTurma : '-') . '</td>
        </tr>
        <tr>
            <td colspan="2" class="shade module-cell"><span class="bold">Título do Módulo:</span><br>' . esc($cab['nome_modulo'] ?? '-') . '</td>
            <td colspan="2" class="shade module-cell"><span class="bold">Código do Módulo:</span><br>' . esc($cab['codigo_modulo'] ?? '-') . '</td>
        </tr>
    </table>

    <table class="candidate-table">
        <colgroup>
            <col style="width: 47%;">
            <col style="width: 17%;">
            <col style="width: 18%;">
            <col style="width: 18%;">
        </colgroup>
        <tr class="head-row">
            <td>Nome do Candidato</td>
            <td>Número do Candidato</td>
            <td>Resultado A/NA/WD ou D</td>
            <td>Assinatura do VE</td>
        </tr>';

foreach ($rows as $row) {
    $html .= '
        <tr class="candidate-row">
            <td>' . esc($row['nome']) . '</td>
            <td class="center">' . esc($row['codigo']) . '</td>
            <td class="center bold">' . esc($row['resultado']) . '</td>
            <td></td>
        </tr>';
}

$html .= '
    </table>

    <table>
        <colgroup>
            <col style="width: 33.33%;">
            <col style="width: 33.33%;">
            <col style="width: 33.34%;">
        </colgroup>
        <tr>
            <td colspan="3" class="center bold">Para uso pelos avaliadores:</td>
        </tr>
        <tr class="h24">
            <td class="bold shade">Nome do Avaliador</td>
            <td>' . esc($cab['nome_formador']) . '</td>
            <td class="bold shade">Data:</td>
        </tr>
        <tr class="h24">
            <td class="bold shade">Assinatura:</td>
            <td></td>
            <td class="center bold">' . esc($dataFimFmt) . '</td>
        </tr>
        <tr class="h24">
            <td class="bold shade">Nome do Verificador Interno</td>
            <td></td>
            <td></td>
        </tr>
        <tr class="h24">
            <td class="bold shade">Assinatura</td>
            <td></td>
            <td></td>
        </tr>
        <tr class="h24">
            <td class="bold shade">Assinatura do Verificador Externo</td>
            <td></td>
            <td></td>
        </tr>
    </table>
</div>
</body>
</html>';

$mpdf = new \Mpdf\Mpdf([
    'format' => 'A4',
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 8,
    'margin_bottom' => 8
]);
$mpdf->WriteHTML($html);
$mpdf->Output('pauta_final.pdf', 'I');
