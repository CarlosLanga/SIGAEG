<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload): void {
    echo json_encode($payload);
    exit;
}

if (!$conn) {
    json_out(['disponivel' => false, 'mensagem' => 'Erro de ligação à base de dados.']);
}

$turma_id = (int)($_GET['turma_id'] ?? 0);
$modulo_id = (int)($_GET['modulo_id'] ?? 0);
if ($turma_id <= 0 || $modulo_id <= 0) {
    json_out(['disponivel' => false, 'mensagem' => 'Parâmetros inválidos.']);
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

$conn->query("
    CREATE TABLE IF NOT EXISTS avaliacoes_resultados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        avaliacao_id INT NOT NULL,
        formando_id INT NOT NULL,
        nota_obtida DECIMAL(5,2) NULL,
        resultado ENUM('A','NA','SE') NOT NULL DEFAULT 'SE',
        observacao VARCHAR(255) DEFAULT NULL,
        actualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_avaliacao_formando (avaliacao_id, formando_id),
        KEY idx_avaliacao (avaliacao_id),
        KEY idx_formando (formando_id),
        CONSTRAINT fk_avaliacoes_resultados_avaliacao FOREIGN KEY (avaliacao_id)
            REFERENCES avaliacoes(id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_avaliacoes_resultados_formando FOREIGN KEY (formando_id)
            REFERENCES formandos(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$conn->query("
    CREATE TABLE IF NOT EXISTS avaliacoes_resultados_status (
        id INT AUTO_INCREMENT PRIMARY KEY,
        avaliacao_id INT NOT NULL,
        estado ENUM('rascunho','publicado') NOT NULL DEFAULT 'rascunho',
        publicado_em DATETIME DEFAULT NULL,
        actualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_avaliacao_estado (avaliacao_id),
        CONSTRAINT fk_avaliacoes_resultados_status_avaliacao FOREIGN KEY (avaliacao_id)
            REFERENCES avaliacoes(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

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
    json_out(['disponivel' => false, 'mensagem' => 'Pauta ainda não disponível.']);
}

foreach ($avaliacoes as $a) {
    if (($a['estado_resultado'] ?? 'rascunho') !== 'publicado') {
        json_out(['disponivel' => false, 'mensagem' => 'Pauta ainda não disponível.']);
    }
}

usort($avaliacoes, function ($a, $b) {
    preg_match('/(\d+)/', $a['titulo'], $ma);
    preg_match('/(\d+)/', $b['titulo'], $mb);
    $na = isset($ma[1]) ? (int)$ma[1] : 0;
    $nb = isset($mb[1]) ? (int)$mb[1] : 0;
    return $na <=> $nb;
});

// Cabeçalho
$cab = [
    'turma' => '',
    'modulo' => '',
    'formador' => ''
];

$resCab = $conn->query("
    SELECT t.nome_turma, m.nome_modulo, f.nome_completo
    FROM formador_modulo fm
    INNER JOIN turmas t ON t.id = fm.turma_id
    INNER JOIN modulos m ON m.id = fm.modulo_id
    INNER JOIN formadores f ON f.id = fm.formador_id
    $formadorJoin
    WHERE fm.turma_id = $turma_id AND fm.modulo_id = $modulo_id
    $formadorWhere
    ORDER BY fm.id DESC
    LIMIT 1
");
if ($resCab && $resCab->num_rows > 0) {
    $cabRow = $resCab->fetch_assoc();
    $cab['turma'] = $cabRow['nome_turma'] ?? '';
    $cab['modulo'] = $cabRow['nome_modulo'] ?? '';
    $cab['formador'] = $cabRow['nome_completo'] ?? '';
}

// Vigência
$data_fim = null;
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
    $data_fim = $fm['data_fim'] ?? null;
}
$vigencia_terminada = $data_fim ? (date('Y-m-d') > $data_fim) : false;

// Formandos
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
        SELECT pr.formando_id, pr.situacao, pp.data_aula
        FROM presencas_registo pr
        INNER JOIN presencas_plano pp ON pp.id = pr.plano_id
        WHERE pp.turma_id = $turma_id AND pp.formador_modulo_id = $formador_modulo_id
        ORDER BY pp.data_aula DESC, pr.id DESC
    ");
    if ($resPerm) {
        $lastStatus = [];
        while ($row = $resPerm->fetch_assoc()) {
            $fid = (int)$row['formando_id'];
            if (!isset($lastStatus[$fid])) {
                $lastStatus[$fid] = $row['situacao'];
            }
        }
        foreach ($lastStatus as $fid => $situ) {
            if (in_array($situ, ['WD', 'D'], true)) {
                $permanentes[$fid] = $situ;
            }
        }
    }
}

$avaliacaoIds = array_map(function ($a) { return (int)$a['id']; }, $avaliacoes);
$idsList = implode(',', $avaliacaoIds);
$resultados = [];
if ($idsList !== '') {
    $resRes = $conn->query("
        SELECT avaliacao_id, formando_id, nota_obtida, resultado
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
                    'nota_obtida' => 0,
                    'resultado' => 'NA'
                ];
            }
        }
    }
}

$as_list = array_map(function ($a) {
    return ['id' => (int)$a['id'], 'titulo' => $a['titulo']];
}, $avaliacoes);

$rows = [];
foreach ($formandos as $f) {
    $fid = (int)$f['id'];
    $as_values = [];
    $temNA = false;
    $temA = false;

    foreach ($avaliacaoIds as $aid) {
        $row = $resultados[$fid][$aid] ?? null;
        if (!$row || $row['nota_obtida'] === null) {
            $as_values[] = '';
            continue;
        }
        $nota = (float)$row['nota_obtida'];
        $val = $nota >= 80 ? 'A' : 'NA';
        $as_values[] = $val;
        if ($val === 'NA') $temNA = true;
        if ($val === 'A') $temA = true;
    }

    $resultadoFinal = '';
    if (isset($permanentes[$fid])) {
        $resultadoFinal = $permanentes[$fid];
    } else if ($temNA) {
        $resultadoFinal = 'NA';
    } else if ($temA && !in_array('', $as_values, true)) {
        $resultadoFinal = 'A';
    }

    $rows[] = [
        'nome' => $f['nome_completo'],
        'codigo' => $f['codigo_formando'],
        'as' => $as_values,
        'resultado' => $resultadoFinal
    ];
}

json_out([
    'disponivel' => true,
    'cabecalho' => $cab,
    'as_list' => $as_list,
    'rows' => $rows
]);
