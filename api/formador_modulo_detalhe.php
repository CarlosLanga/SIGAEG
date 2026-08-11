<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

if (!$conn) {
    json_out(['ok' => false, 'msg' => 'Erro de conexao.']);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
if (!in_array($nivel, [1, 2], true)) {
    json_out(['ok' => false, 'msg' => 'Sem permissao.']);
}

$formador_modulo_id = (int)($_GET['formador_modulo_id'] ?? 0);
if ($formador_modulo_id <= 0) {
    json_out(['ok' => false, 'msg' => 'Parametros invalidos.']);
}

$scopeWhere = $nivel === 2 ? "AND fr.usuario_id = $usuario_id" : "";
$resModulo = $conn->query("
    SELECT
        fm.id,
        fm.modulo_id,
        fm.turma_id,
        fm.data_inicio,
        fm.data_fim,
        m.sigla_modulo,
        m.nome_modulo,
        COALESCE(NULLIF(m.tipo_modulo, ''), 'generico') AS tipo_modulo,
        t.nome_turma,
        tr.nome_turno,
        TRIM(CONCAT(COALESCE(fr.titulo, ''), ' ', COALESCE(fr.nome_completo, ''))) AS formador_nome,
        CASE
            WHEN fm.data_inicio IS NOT NULL AND fm.data_inicio > CURDATE() THEN 'por_iniciar'
            WHEN fm.data_fim IS NOT NULL AND fm.data_fim < CURDATE() THEN 'concluido'
            ELSE 'em_vigencia'
        END AS estado
    FROM formador_modulo fm
    INNER JOIN modulos m ON m.id = fm.modulo_id
    INNER JOIN turmas t ON t.id = fm.turma_id
    LEFT JOIN turnos tr ON tr.id = t.turno_id
    INNER JOIN formadores fr ON fr.id = fm.formador_id
    WHERE fm.id = $formador_modulo_id
      AND UPPER(COALESCE(m.sigla_modulo, '')) <> 'RT'
      $scopeWhere
    LIMIT 1
");

if (!$resModulo || $resModulo->num_rows === 0) {
    json_out(['ok' => false, 'msg' => 'Modulo nao encontrado.']);
}
$modulo = $resModulo->fetch_assoc();
$turma_id = (int)$modulo['turma_id'];
$modulo_id = (int)$modulo['modulo_id'];

$avaliacoes = [];
$resAvaliacoes = $conn->query("
    SELECT a.id, a.titulo
    FROM avaliacoes a
    INNER JOIN avaliacoes_resultados_status rs ON rs.avaliacao_id = a.id AND rs.estado = 'publicado'
    WHERE a.turma_id = $turma_id
      AND a.modulo_id = $modulo_id
      AND (a.titulo LIKE 'Avaliação Sumativa %' OR a.titulo LIKE 'Avaliacao Sumativa %')
    ORDER BY a.id ASC
");
if ($resAvaliacoes) {
    while ($row = $resAvaliacoes->fetch_assoc()) {
        $avaliacoes[] = $row;
    }
}
$avaliacaoIds = array_map(static fn($row) => (int)$row['id'], $avaliacoes);
$totalAvaliacoes = count($avaliacaoIds);
$idsList = $avaliacaoIds ? implode(',', $avaliacaoIds) : '';

$resultados = [];
if ($idsList !== '') {
    $resResultados = $conn->query("
        SELECT avaliacao_id, formando_id, nota_obtida, resultado
        FROM avaliacoes_resultados
        WHERE avaliacao_id IN ($idsList)
    ");
    if ($resResultados) {
        while ($row = $resResultados->fetch_assoc()) {
            $resultados[(int)$row['formando_id']][(int)$row['avaliacao_id']] = $row;
        }
    }
}

$permanentes = [];
$resPerm = $conn->query("
    SELECT pr.formando_id, pr.situacao
    FROM presencas_registo pr
    INNER JOIN presencas_plano pp ON pp.id = pr.plano_id
    WHERE pp.turma_id = $turma_id
      AND pp.formador_modulo_id = $formador_modulo_id
    ORDER BY pp.data_aula DESC, pr.id DESC
");
if ($resPerm) {
    while ($row = $resPerm->fetch_assoc()) {
        $fid = (int)$row['formando_id'];
        if (!isset($permanentes[$fid]) && in_array($row['situacao'], ['WD', 'D'], true)) {
            $permanentes[$fid] = $row['situacao'];
        }
    }
}

$formandos = [];
$resFormandos = $conn->query("
    SELECT id, nome_completo, codigo_formando
    FROM formandos
    WHERE turma_id = $turma_id
    ORDER BY nome_completo ASC
");
if ($resFormandos) {
    while ($f = $resFormandos->fetch_assoc()) {
        $fid = (int)$f['id'];
        $positivas = 0;
        $respondidas = 0;
        $resultadoFinal = '-';

        if (isset($permanentes[$fid])) {
            $resultadoFinal = $permanentes[$fid];
        } elseif ($totalAvaliacoes > 0) {
            foreach ($avaliacaoIds as $aid) {
                $row = $resultados[$fid][$aid] ?? null;
                if (!$row || $row['nota_obtida'] === null || $row['nota_obtida'] === '') {
                    continue;
                }
                $respondidas++;
                $nota = (float)$row['nota_obtida'];
                if ($nota >= 80) {
                    $positivas++;
                }
            }

            if ($respondidas === $totalAvaliacoes) {
                $resultadoFinal = $positivas === $totalAvaliacoes ? 'A' : 'NA';
            }
        }

        $progress = $totalAvaliacoes > 0 ? (int)round(($positivas / $totalAvaliacoes) * 100) : 0;
        if (in_array($resultadoFinal, ['WD', 'D'], true)) {
            $progress = 0;
        }

        $formandos[] = [
            'id' => $fid,
            'nome_completo' => $f['nome_completo'],
            'codigo_formando' => $f['codigo_formando'],
            'progresso' => $progress,
            'resultado' => $resultadoFinal,
            'avaliacoes_total' => $totalAvaliacoes,
            'avaliacoes_respondidas' => $respondidas,
            'avaliacoes_positivas' => $positivas,
        ];
    }
}

json_out([
    'ok' => true,
    'modulo' => $modulo,
    'avaliacoes_total' => $totalAvaliacoes,
    'formandos' => $formandos
]);
