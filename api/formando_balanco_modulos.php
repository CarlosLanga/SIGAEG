<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['ok' => false, 'msg' => 'Database error']);
    exit;
}

$id = isset($_GET['formando_id']) ? (int)$_GET['formando_id'] : 0;
if (!$id) {
    echo json_encode(['ok' => false, 'msg' => 'Formando ID missing']);
    exit;
}

// Info do formand
$sqlFormando = "SELECT f.curso_id, f.turma_id, f.certificado_vocacional, c.nome_curso 
                FROM formandos f 
                LEFT JOIN cursos c ON c.id = f.curso_id
                WHERE f.id = $id LIMIT 1";
$resFormando = $conn->query($sqlFormando);
if (!$resFormando || $resFormando->num_rows === 0) {
    echo json_encode(['ok' => false, 'msg' => 'Formando not found']);
    exit;
}
$formando = $resFormando->fetch_assoc();
$curso_id = (int)$formando['curso_id'];
$turma_id = (int)$formando['turma_id'];
$cv = $formando['certificado_vocacional'] ?: 'CV Desconhecido';
$nome_curso = $formando['nome_curso'] ?: 'Curso Desconhecido';

$sigla_curso = $nome_curso;
if (preg_match('/\((.*?)\)/', $nome_curso, $matches)) {
    $sigla_curso = trim($matches[1]);
} else {
    $words = explode(' ', $nome_curso);
    $sigla_curso = '';
    foreach ($words as $w) {
        if (strlen($w) > 2) $sigla_curso .= strtoupper(substr($w, 0, 1));
    }
    if (!$sigla_curso) $sigla_curso = $nome_curso;
}

$cvs_disponiveis = [
    ['id' => $curso_id, 'label' => "$cv - $sigla_curso", 'selected' => true]
];

$sqlModulos = "SELECT id, nome_modulo, sigla_modulo, tipo_modulo 
               FROM modulos 
               WHERE curso_id = $curso_id AND (sigla_modulo IS NULL OR sigla_modulo != 'RT')";
$resModulos = $conn->query($sqlModulos);
$modulos = [];
$modulos_ids = [];
if ($resModulos) {
    while ($row = $resModulos->fetch_assoc()) {
        $modulos[$row['id']] = [
            'id' => $row['id'],
            'nome' => $row['nome_modulo'],
            'sigla' => $row['sigla_modulo'] ?: substr($row['nome_modulo'], 0, 4),
            'estado' => 'Por iniciar',
            'data_inicio' => null,
            'data_fim' => null,
            'reprovado' => false,
            'progress' => 0
        ];
        $modulos_ids[] = $row['id'];
    }
}

if (!empty($modulos_ids)) {
    $ids_str = implode(',', $modulos_ids);
    $sqlFm = "SELECT modulo_id, data_inicio, data_fim, estado 
              FROM formador_modulo 
              WHERE turma_id = $turma_id AND modulo_id IN ($ids_str)";
    $resFm = $conn->query($sqlFm);
    if ($resFm) {
        while ($row = $resFm->fetch_assoc()) {
            $mid = $row['modulo_id'];
            if (isset($modulos[$mid])) {
                $modulos[$mid]['estado'] = $row['estado'];
                $modulos[$mid]['data_inicio'] = $row['data_inicio'];
                $modulos[$mid]['data_fim'] = $row['data_fim'];
            }
        }
    }

    $sqlNotas = "SELECT DISTINCT a.modulo_id 
                 FROM notas n
                 JOIN avaliacoes a ON n.avaliacao_id = a.id
                 WHERE n.formando_id = $id AND n.resultado IN ('NA', 'D', 'WD') AND a.modulo_id IN ($ids_str)";
    $resNotas = $conn->query($sqlNotas);
    if ($resNotas) {
        while ($row = $resNotas->fetch_assoc()) {
            $mid = $row['modulo_id'];
            if (isset($modulos[$mid])) {
                $modulos[$mid]['reprovado'] = true;
            }
        }
    }
}

$modulos_list = [];
$hoje = new DateTime();
$hoje->setTime(0, 0, 0);

$stats = [
    'total' => count($modulos),
    'concluidos' => 0,
    'em_curso' => 0,
    'por_iniciar' => 0,
    'reprovados' => 0,
    'progress_sum' => 0
];

foreach ($modulos as $mid => $m) {
    $progresso = 0;
    
    if ($m['reprovado']) {
        $progresso = 0;
        $stats['reprovados']++;
    } else {
        if ($m['estado'] === 'Concluído') {
            $progresso = 100;
            $stats['concluidos']++;
        } elseif ($m['estado'] === 'Por iniciar') {
            $progresso = 0;
            $stats['por_iniciar']++;
        } elseif ($m['estado'] === 'Em vigência') {
            $stats['em_curso']++;
            if ($m['data_inicio'] && $m['data_fim']) {
                $inicio = new DateTime($m['data_inicio']);
                $fim = new DateTime($m['data_fim']);
                
                if ($fim <= $inicio) {
                    $progresso = 0;
                } else {
                    if ($hoje >= $fim) {
                        $progresso = 100;
                    } elseif ($hoje <= $inicio) {
                        $progresso = 0;
                    } else {
                        $total_days = $inicio->diff($fim)->days;
                        $passed_days = $inicio->diff($hoje)->days;
                        $progresso = round(($passed_days / $total_days) * 100);
                    }
                }
            } else {
                $progresso = 50; 
            }
        }
    }
    
    $m['progress'] = max(0, min(100, $progresso));
    if (!$m['reprovado']) {
        $stats['progress_sum'] += $m['progress'];
    }
    
    $modulos_list[] = $m;
}

usort($modulos_list, function($a, $b) {
    if ($a['reprovado'] && !$b['reprovado']) return -1;
    if (!$a['reprovado'] && $b['reprovado']) return 1;
    
    $order = ['Em vigência' => 1, 'Concluído' => 2, 'Por iniciar' => 3];
    $oa = $order[$a['estado']] ?? 99;
    $ob = $order[$b['estado']] ?? 99;
    if ($oa === $ob) {
        return strcmp($a['sigla'], $b['sigla']);
    }
    return $oa - $ob;
});

$media_geral = 0;
if ($stats['total'] > 0) {
    $media_geral = round($stats['progress_sum'] / $stats['total']);
}

echo json_encode([
    'ok' => true,
    'cvs' => $cvs_disponiveis,
    'modulos' => $modulos_list,
    'stats' => $stats,
    'media_geral' => $media_geral
]);
