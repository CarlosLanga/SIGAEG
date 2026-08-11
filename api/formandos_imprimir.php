<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/vendor/autoload.php';

$turma_id = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
if (!$turma_id) {
    die("Turma inválida");
}

$sql = "SELECT f.nome_completo, f.codigo_formando, f.sexo, f.data_nascimento, t.nome_turma, c.nome_curso
        FROM formandos f
        LEFT JOIN turmas t ON t.id = f.turma_id
        LEFT JOIN cursos c ON c.id = t.curso_id
        WHERE f.turma_id = $turma_id
        ORDER BY f.nome_completo ASC";
$res = $conn->query($sql);

$rows = [];
$nomeTurma = "";
$nomeCurso = "";
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $nomeTurma = $row['nome_turma'];
        $nomeCurso = $row['nome_curso'];
        $rows[] = $row;
    }
}

function esc($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function calc_idade(?string $data): string
{
    if (!$data || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        return '-';
    }
    try {
        $nasc = new DateTime($data);
        $hoje = new DateTime();
        return (string)$hoje->diff($nasc)->y;
    } catch (Throwable $e) {
        return '-';
    }
}

$logoPath = __DIR__ . '/../assets/img/images.jpg';

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
        <div class="title">Proposta de Lista de Formandos</div>
        <div class="subtitle">Qualificação: ' . esc($nomeCurso) . '</div>
        <div class="subtitle">Turma: ' . esc($nomeTurma) . '</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nº</th>
                <th>Nome do Formando</th>
                <th>Código</th>
                <th>Sexo</th>
                <th>Idade</th>
                <th>Observações</th>
            </tr>
        </thead>
        <tbody>';

$i = 1;
foreach ($rows as $r) {
    $sexo = ($r['sexo'] === 'Masculino') ? 'M' : 'F';
    $idade = calc_idade($r['data_nascimento'] ?? null);
    $html .= '<tr>
        <td>' . str_pad($i++, 2, "0", STR_PAD_LEFT) . '</td>
        <td style="text-align:left;">' . esc($r['nome_completo']) . '</td>
        <td>' . esc($r['codigo_formando']) . '</td>
        <td>' . esc($sexo) . '</td>
        <td>' . esc($idade) . '</td>
        <td></td>
    </tr>';
}

$html .= '
        </tbody>
    </table>
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
$mpdf->Output("formandos$nomeTurma.pdf", 'I');
