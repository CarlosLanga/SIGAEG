<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload): void {
    echo json_encode($payload);
    exit;
}

if (!$conn) {
    json_out(['ok' => false, 'mensagem' => 'Erro de ligação à base de dados.']);
}

$turma_id = (int)($_GET['turma_id'] ?? 0);
$modulo_id = (int)($_GET['modulo_id'] ?? 0);
$avaliacao_id = (int)($_GET['avaliacao_id'] ?? 0);
if ($turma_id <= 0 || $modulo_id <= 0 || $avaliacao_id <= 0) {
    json_out(['ok' => false, 'mensagem' => 'Parâmetros inválidos.']);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$avaliacaoScope = $nivel === 2 ? "
      AND EXISTS (
          SELECT 1
          FROM formador_modulo fm
          INNER JOIN formadores f ON f.id = fm.formador_id
          WHERE fm.turma_id = avaliacoes.turma_id
            AND fm.modulo_id = avaliacoes.modulo_id
            AND f.usuario_id = $usuario_id
      )
" : "";
$formadorJoin = $nivel === 2 ? "INNER JOIN formadores f ON f.id = fm.formador_id" : "";
$formadorWhere = $nivel === 2 ? "AND f.usuario_id = $usuario_id" : "";

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

$hasHora = false;
$col = $conn->query("SHOW COLUMNS FROM avaliacoes LIKE 'hora_avaliacao'");
if ($col && $col->num_rows > 0) {
    $hasHora = true;
}

$horaSelect = $hasHora ? ", hora_avaliacao" : "";
$resAval = $conn->query("
    SELECT titulo, data_avaliacao $horaSelect, turma_id, modulo_id
    FROM avaliacoes
    WHERE id = $avaliacao_id
    $avaliacaoScope
    LIMIT 1
");
if (!$resAval || $resAval->num_rows === 0) {
    json_out(['ok' => false, 'mensagem' => 'Avaliação não encontrada.']);
}
$avaliacao = $resAval->fetch_assoc();
if ((int)$avaliacao['turma_id'] !== $turma_id || (int)$avaliacao['modulo_id'] !== $modulo_id) {
    json_out(['ok' => false, 'mensagem' => 'Avaliação inválida para a turma seleccionada.']);
}

$resFm = $conn->query("
    SELECT fm.id, fm.data_inicio, fm.data_fim
    FROM formador_modulo fm
    $formadorJoin
    WHERE fm.turma_id = $turma_id AND fm.modulo_id = $modulo_id
    $formadorWhere
    ORDER BY fm.id DESC
    LIMIT 1
");
if (!$resFm || $resFm->num_rows === 0) {
    json_out(['ok' => false, 'mensagem' => 'Módulo não encontrado para esta turma.']);
}
$fm = $resFm->fetch_assoc();
$formador_modulo_id = (int)$fm['id'];
$data_inicio = $fm['data_inicio'] ?? null;
$data_fim = $fm['data_fim'] ?? null;
$vigencia_terminada = $data_fim ? (date('Y-m-d') > $data_fim) : false;

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

$resultados = [];
$resResultados = $conn->query("
    SELECT formando_id, nota_obtida, resultado, observacao
    FROM avaliacoes_resultados
    WHERE avaliacao_id = $avaliacao_id
");
if ($resResultados) {
    while ($row = $resResultados->fetch_assoc()) {
        $resultados[(int)$row['formando_id']] = $row;
    }
}

$permanentes = [];
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

if ($vigencia_terminada) {
    foreach ($formandos as $f) {
        $fid = (int)$f['id'];
        $row = $resultados[$fid] ?? null;
        if ($row && $row['nota_obtida'] !== null) {
            continue;
        }
        $notaZero = 0;
        $conn->query("
            INSERT INTO avaliacoes_resultados (avaliacao_id, formando_id, nota_obtida, resultado, observacao)
            VALUES ($avaliacao_id, $fid, $notaZero, 'NA', NULL)
            ON DUPLICATE KEY UPDATE nota_obtida = $notaZero, resultado = 'NA'
        ");
    }

    $resultados = [];
    $resResultados = $conn->query("
        SELECT formando_id, nota_obtida, resultado, observacao
        FROM avaliacoes_resultados
        WHERE avaliacao_id = $avaliacao_id
    ");
    if ($resResultados) {
        while ($row = $resResultados->fetch_assoc()) {
            $resultados[(int)$row['formando_id']] = $row;
        }
    }
}

$estado = 'rascunho';
$resEstado = $conn->query("SELECT estado FROM avaliacoes_resultados_status WHERE avaliacao_id = $avaliacao_id LIMIT 1");
if ($resEstado && $resEstado->num_rows > 0) {
    $estadoRow = $resEstado->fetch_assoc();
    $estado = $estadoRow['estado'] ?? 'rascunho';
}

$rows = [];
$stats = ['total' => 0, 'alcancados' => 0, 'nao_alcancados' => 0];
foreach ($formandos as $f) {
    $fid = (int)$f['id'];
    $row = $resultados[$fid] ?? null;
    $nota = $row['nota_obtida'] ?? null;
    $resultado = $row['resultado'] ?? 'SE';
    $observacao = $row['observacao'] ?? '';

    if ($nota !== null) {
        $notaNum = (float)$nota;
        if ($notaNum >= 80) {
            $stats['alcancados']++;
            $resultado = 'A';
        } else {
            $stats['nao_alcancados']++;
            $resultado = 'NA';
        }
    }

    $stats['total']++;

    $rows[] = [
        'formando_id' => $fid,
        'nome_completo' => $f['nome_completo'],
        'codigo_formando' => $f['codigo_formando'],
        'nota_obtida' => $nota,
        'resultado' => $resultado,
        'observacao' => $observacao,
        'situacao' => $permanentes[$fid] ?? ''
    ];
}

json_out([
    'ok' => true,
    'avaliacao' => [
        'titulo' => $avaliacao['titulo'] ?? '',
        'data' => $avaliacao['data_avaliacao'] ?? '',
        'hora' => $hasHora ? ($avaliacao['hora_avaliacao'] ?? '') : ''
    ],
    'formador_modulo_id' => $formador_modulo_id,
    'vigencia_inicio' => $data_inicio,
    'vigencia_fim' => $data_fim,
    'vigencia_terminada' => $vigencia_terminada,
    'rows' => $rows,
    'stats' => $stats,
    'estado' => $estado
]);
