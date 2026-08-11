<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

function esc_sql(mysqli $conn, string $value): string
{
    return $conn->real_escape_string($value);
}

function ensure_trabalho_submissoes(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS trabalho_submissoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            trabalho_id INT NOT NULL,
            formando_id INT NOT NULL,
            ficheiro_nome VARCHAR(255) DEFAULT NULL,
            ficheiro_caminho VARCHAR(255) DEFAULT NULL,
            comentario TEXT DEFAULT NULL,
            estado ENUM('submetido','revisto') NOT NULL DEFAULT 'submetido',
            submetido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_trabalho_formando (trabalho_id, formando_id),
            KEY idx_trabalho (trabalho_id),
            KEY idx_formando (formando_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function ensure_avaliacoes_resultados(mysqli $conn): void
{
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
            KEY idx_formando (formando_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

if (!$conn) {
    json_out(['ok' => false, 'msg' => 'Erro de conexao.']);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? $_SESSION['nivel_acesso_id'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
if (!in_array($nivel, [3, 4], true) || $usuario_id <= 0) {
    json_out(['ok' => false, 'msg' => 'Sem permissao.']);
}

ensure_avaliacoes_resultados($conn);

$action = (string)($_GET['action'] ?? $_POST['action'] ?? 'context');
$educando_id = (int)($_GET['educando_id'] ?? $_POST['educando_id'] ?? 0);

function educandos_do_usuario(mysqli $conn, int $nivel, int $usuario_id): array
{
    if ($nivel === 3) {
        $res = $conn->query("
            SELECT f.id, f.nome_completo, f.codigo_formando, f.turma_id
            FROM formandos f
            WHERE f.usuario_id = $usuario_id
            LIMIT 1
        ");
    } else {
        $res = $conn->query("
            SELECT f.id, f.nome_completo, f.codigo_formando, f.turma_id
            FROM formandos f
            INNER JOIN encarregado_formando ef ON ef.formando_id = f.id
            INNER JOIN encarregados e ON e.id = ef.encarregado_id
            WHERE e.usuario_id = $usuario_id
            ORDER BY ef.principal DESC, f.nome_completo ASC
        ");
    }

    $rows = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

$educandos = educandos_do_usuario($conn, $nivel, $usuario_id);
if (!$educandos) {
    if ($action === 'context') {
        json_out(['ok' => true, 'educandos' => [], 'formando' => null, 'turmas' => []]);
    }
    json_out(['ok' => false, 'msg' => 'Nenhum formando associado.']);
}

$formando = $educandos[0];
if ($educando_id > 0) {
    foreach ($educandos as $cand) {
        if ((int)$cand['id'] === $educando_id) {
            $formando = $cand;
            break;
        }
    }
}
$formando_id = (int)$formando['id'];

function turmas_acessiveis(mysqli $conn, int $formando_id): array
{
    $ids = [];
    $res = $conn->query("SELECT turma_id FROM formandos WHERE id = $formando_id AND turma_id IS NOT NULL");
    if ($res && $row = $res->fetch_assoc()) {
        $ids[(int)$row['turma_id']] = true;
    }

    $res = $conn->query("
        SELECT DISTINCT pp.turma_id
        FROM presencas_plano pp
        INNER JOIN presencas_registo pr ON pr.plano_id = pp.id
        WHERE pr.formando_id = $formando_id
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $ids[(int)$row['turma_id']] = true;
        }
    }

    $res = $conn->query("
        SELECT DISTINCT a.turma_id
        FROM avaliacoes a
        INNER JOIN avaliacoes_resultados ar ON ar.avaliacao_id = a.id
        WHERE ar.formando_id = $formando_id
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $ids[(int)$row['turma_id']] = true;
        }
    }

    $ids = array_keys(array_filter($ids));
    if (!$ids) return [];

    $currentTurma = 0;
    $resCurrent = $conn->query("SELECT turma_id FROM formandos WHERE id = $formando_id LIMIT 1");
    if ($resCurrent && $r = $resCurrent->fetch_assoc()) {
        $currentTurma = (int)($r['turma_id'] ?? 0);
    }

    $idSql = implode(',', array_map('intval', $ids));
    $rows = [];
    $resTurmas = $conn->query("
        SELECT t.id, t.nome_turma, t.certificado_vocacional, t.ano_lectivo,
               c.nome_curso, tr.nome_turno,
               CASE WHEN t.id = $currentTurma THEN 1 ELSE 0 END AS actual
        FROM turmas t
        LEFT JOIN cursos c ON c.id = t.curso_id
        LEFT JOIN turnos tr ON tr.id = t.turno_id
        WHERE t.id IN ($idSql)
        ORDER BY actual DESC, t.ano_lectivo DESC, t.nome_turma ASC
    ");
    if ($resTurmas) {
        while ($row = $resTurmas->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function turma_permitida(mysqli $conn, int $formando_id, int $turma_id): bool
{
    foreach (turmas_acessiveis($conn, $formando_id) as $turma) {
        if ((int)$turma['id'] === $turma_id) return true;
    }
    return false;
}

function normalizar_estado_modulo(array $row): string
{
    $estado = strtolower(trim((string)($row['estado'] ?? '')));
    $inicio = (string)($row['data_inicio'] ?? '');
    $fim = (string)($row['data_fim'] ?? '');
    $hoje = date('Y-m-d');

    if ($estado === 'concluido' || ($fim !== '' && $fim < $hoje)) {
        return 'concluido';
    }
    if (in_array($estado, ['em_curso', 'em curso', 'em_vigencia', 'vigente'], true)) {
        return 'em_curso';
    }
    if ($inicio !== '' && $fim !== '' && $inicio <= $hoje && $fim >= $hoje) {
        return 'em_curso';
    }
    return 'por_iniciar';
}

function rows_modulos(mysqli $conn, int $formando_id, int $turma_id): array
{
    $rows = [];
    $res = $conn->query("
        SELECT fm.id AS formador_modulo_id, fm.turma_id, fm.modulo_id, fm.data_inicio, fm.data_fim, fm.estado,
               m.sigla_modulo, m.nome_modulo, m.tipo_modulo,
               t.nome_turma,
               TRIM(CONCAT(COALESCE(fr.titulo, ''), ' ', COALESCE(fr.nome_completo, ''))) AS formador_nome,
               (SELECT COUNT(*) FROM avaliacoes a WHERE a.turma_id = fm.turma_id AND a.modulo_id = fm.modulo_id) AS total_avaliacoes,
               (SELECT COUNT(*)
                  FROM avaliacoes a
                  INNER JOIN avaliacoes_resultados ar ON ar.avaliacao_id = a.id
                 WHERE a.turma_id = fm.turma_id AND a.modulo_id = fm.modulo_id
                   AND ar.formando_id = $formando_id AND ar.resultado = 'A') AS positivas,
               (SELECT COUNT(*)
                  FROM avaliacoes a
                  INNER JOIN avaliacoes_resultados ar ON ar.avaliacao_id = a.id
                 WHERE a.turma_id = fm.turma_id AND a.modulo_id = fm.modulo_id
                   AND ar.formando_id = $formando_id AND ar.resultado IN ('A','NA')) AS lancadas,
               (SELECT pr.situacao
                  FROM presencas_registo pr
                  INNER JOIN presencas_plano pp ON pp.id = pr.plano_id
                 WHERE pr.formando_id = $formando_id
                   AND pp.turma_id = fm.turma_id
                   AND pp.formador_modulo_id = fm.id
                   AND pr.situacao IN ('D','WD')
                 ORDER BY pp.data_aula DESC, pr.id DESC
                 LIMIT 1) AS situacao_final
        FROM formador_modulo fm
        INNER JOIN modulos m ON m.id = fm.modulo_id
        INNER JOIN turmas t ON t.id = fm.turma_id
        LEFT JOIN formadores fr ON fr.id = fm.formador_id
        WHERE fm.turma_id = $turma_id
          AND UPPER(COALESCE(m.sigla_modulo, m.codigo_modulo, '')) <> 'RT'
        ORDER BY fm.data_inicio ASC, m.sigla_modulo ASC
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $total = (int)$row['total_avaliacoes'];
            $positivas = (int)$row['positivas'];
            $lancadas = (int)$row['lancadas'];
            $estadoModulo = normalizar_estado_modulo($row);
            $situacaoFinal = strtoupper((string)($row['situacao_final'] ?? ''));
            $row['progresso'] = $total > 0 ? (int)round(($positivas / $total) * 100) : 0;
            $row['estado_modulo'] = $estadoModulo;
            if ($estadoModulo !== 'concluido') {
                $row['resultado'] = '-';
            } elseif (in_array($situacaoFinal, ['D', 'WD'], true)) {
                $row['resultado'] = $situacaoFinal;
                $row['progresso'] = 0;
            } elseif ($total <= 0 || $lancadas < $total) {
                $row['resultado'] = 'NA';
            } else {
                $row['resultado'] = $positivas === $total ? 'A' : 'NA';
            }
            $rows[] = $row;
        }
    }
    return $rows;
}

$turmas = turmas_acessiveis($conn, $formando_id);
$turma_id = (int)($_GET['turma_id'] ?? $_POST['turma_id'] ?? ($turmas[0]['id'] ?? 0));

if ($action !== 'context' && $turma_id > 0 && !turma_permitida($conn, $formando_id, $turma_id)) {
    json_out(['ok' => false, 'msg' => 'Turma nao permitida.']);
}

if ($action === 'context') {
    json_out(['ok' => true, 'educandos' => $educandos, 'formando' => $formando, 'turmas' => $turmas]);
}

if ($action === 'turmas') {
    json_out(['ok' => true, 'rows' => $turmas, 'formando' => $formando]);
}

if ($action === 'turma_detail') {
    $resTurma = $conn->query("
        SELECT t.id, t.nome_turma, t.certificado_vocacional, t.ano_lectivo, c.nome_curso, tr.nome_turno,
               TRIM(CONCAT(COALESCE(dt.titulo, ''), ' ', COALESCE(dt.nome_completo, ''))) AS director_turma
        FROM turmas t
        LEFT JOIN cursos c ON c.id = t.curso_id
        LEFT JOIN turnos tr ON tr.id = t.turno_id
        LEFT JOIN formadores dt ON dt.id = t.dt_id
        WHERE t.id = $turma_id
        LIMIT 1
    ");
    $turma = $resTurma ? $resTurma->fetch_assoc() : null;
    $formandos = [];
    $resFormandos = $conn->query("
        SELECT id, nome_completo, codigo_formando, sexo
        FROM formandos
        WHERE turma_id = $turma_id
        ORDER BY nome_completo ASC
    ");
    if ($resFormandos) {
        while ($row = $resFormandos->fetch_assoc()) $formandos[] = $row;
    }
    json_out(['ok' => true, 'turma' => $turma, 'formandos' => $formandos]);
}

if ($action === 'horarios') {
    $rows = [];
    if ($turma_id > 0) {
        $res = $conn->query("
            SELECT hp.id, hp.turma_id, hp.semestre, hp.bloco, hp.publicado, hp.publicado_em, hp.actualizado_em,
                   t.nome_turma, t.ano_lectivo, tr.nome_turno
            FROM horarios_plano hp
            INNER JOIN turmas t ON t.id = hp.turma_id
            LEFT JOIN turnos tr ON tr.id = t.turno_id
            WHERE hp.turma_id = $turma_id
            ORDER BY COALESCE(hp.publicado_em, hp.actualizado_em) DESC, hp.id DESC
        ");
        if ($res) {
            while ($row = $res->fetch_assoc()) $rows[] = $row;
        }
    }
    json_out(['ok' => true, 'rows' => $rows, 'turmas' => $turmas]);
}

if ($action === 'horario_current') {
    $row = null;
    $res = $conn->query("
        SELECT hp.id, hp.turma_id, hp.semestre, hp.bloco
        FROM horarios_plano hp
        WHERE hp.turma_id = $turma_id
        ORDER BY hp.publicado DESC, COALESCE(hp.publicado_em, hp.actualizado_em) DESC, hp.id DESC
        LIMIT 1
    ");
    if ($res && $res->num_rows > 0) $row = $res->fetch_assoc();
    json_out(['ok' => true, 'plano' => $row, 'turmas' => $turmas]);
}

if ($action === 'modulos') {
    json_out(['ok' => true, 'rows' => rows_modulos($conn, $formando_id, $turma_id), 'turmas' => $turmas]);
}

if ($action === 'modulo_detail') {
    $formador_modulo_id = (int)($_GET['formador_modulo_id'] ?? 0);
    $rows = rows_modulos($conn, $formando_id, $turma_id);
    $modulo = null;
    foreach ($rows as $row) {
        if ((int)$row['formador_modulo_id'] === $formador_modulo_id) $modulo = $row;
    }

    $avaliacoes = [];
    $presencas = [];
    $presencasResumo = ['total' => 0, 'presente' => 0, 'ausente' => 0, 'd' => 0, 'wd' => 0];
    $trabalhosModulo = [];
    if ($modulo) {
        ensure_trabalho_submissoes($conn);
        $modulo_id = (int)$modulo['modulo_id'];

        $res = $conn->query("
            SELECT a.id, a.titulo, a.data_avaliacao, ar.nota_obtida, COALESCE(ar.resultado, '-') AS resultado, ar.observacao
            FROM avaliacoes a
            LEFT JOIN avaliacoes_resultados ar ON ar.avaliacao_id = a.id AND ar.formando_id = $formando_id
            WHERE a.turma_id = $turma_id AND a.modulo_id = $modulo_id
            ORDER BY a.data_avaliacao ASC, a.id ASC
        ");
        if ($res) while ($r = $res->fetch_assoc()) $avaliacoes[] = $r;

        $resPres = $conn->query("
            SELECT pp.data_aula, pp.dia_semana, pr.situacao, pr.observacao,
                   GROUP_CONCAT(pi.slot_codigo ORDER BY pi.slot_codigo SEPARATOR ', ') AS aulas
            FROM presencas_registo pr
            INNER JOIN presencas_plano pp ON pp.id = pr.plano_id
            LEFT JOIN presencas_intervalo pi ON pi.plano_id = pp.id
            WHERE pr.formando_id = $formando_id
              AND pp.turma_id = $turma_id
              AND pp.formador_modulo_id = $formador_modulo_id
            GROUP BY pr.id
            ORDER BY pp.data_aula DESC, pp.id DESC
        ");
        if ($resPres) {
            while ($r = $resPres->fetch_assoc()) {
                $situacao = strtoupper(trim((string)($r['situacao'] ?? '')));
                $presencasResumo['total']++;
                if (in_array($situacao, ['P', 'PRESENTE'], true)) $presencasResumo['presente']++;
                elseif ($situacao === 'D') $presencasResumo['d']++;
                elseif ($situacao === 'WD') $presencasResumo['wd']++;
                else $presencasResumo['ausente']++;
                $presencas[] = $r;
            }
        }

        $resTrab = $conn->query("
            SELECT tr.id, tr.titulo, tr.tipo, tr.data_publicacao, tr.data_entrega, tr.estado,
                   ts.id AS submissao_id, ts.ficheiro_nome, ts.submetido_em, ts.estado AS estado_submissao
            FROM trabalhos tr
            LEFT JOIN trabalho_submissoes ts ON ts.trabalho_id = tr.id AND ts.formando_id = $formando_id
            WHERE tr.turma_id = $turma_id AND tr.modulo_id = $modulo_id
            ORDER BY tr.data_entrega ASC, tr.id ASC
        ");
        if ($resTrab) while ($r = $resTrab->fetch_assoc()) $trabalhosModulo[] = $r;
    }

    $formandoModulo = null;
    if ($modulo) {
        $formandoModulo = [
            'id' => $formando_id,
            'nome_completo' => $formando['nome_completo'] ?? '',
            'codigo_formando' => $formando['codigo_formando'] ?? '',
            'progresso' => $modulo['progresso'] ?? 0,
            'resultado' => $modulo['resultado'] ?? '-',
        ];
    }

    json_out([
        'ok' => (bool)$modulo,
        'modulo' => $modulo,
        'formando' => $formandoModulo,
        'avaliacoes' => $avaliacoes,
        'presencas' => $presencas,
        'presencas_resumo' => $presencasResumo,
        'trabalhos' => $trabalhosModulo,
    ]);
}
if ($action === 'frequencias') {
    $rows = [];
    $res = $conn->query("
        SELECT pp.data_aula, pp.dia_semana, pp.estado, m.sigla_modulo, m.nome_modulo, pr.situacao, pr.observacao,
               GROUP_CONCAT(pi.slot_codigo ORDER BY pi.slot_codigo SEPARATOR ', ') AS aulas
        FROM presencas_registo pr
        INNER JOIN presencas_plano pp ON pp.id = pr.plano_id
        LEFT JOIN presencas_intervalo pi ON pi.plano_id = pp.id
        LEFT JOIN formador_modulo fm ON fm.id = pp.formador_modulo_id
        LEFT JOIN modulos m ON m.id = fm.modulo_id
        WHERE pr.formando_id = $formando_id AND pp.turma_id = $turma_id
        GROUP BY pr.id
        ORDER BY pp.data_aula DESC, pp.id DESC
    ");
    if ($res) while ($row = $res->fetch_assoc()) $rows[] = $row;
    json_out(['ok' => true, 'rows' => $rows, 'turmas' => $turmas]);
}

if ($action === 'avaliacoes_options') {
    $modulos = rows_modulos($conn, $formando_id, $turma_id);
    $modulo_id = (int)($_GET['modulo_id'] ?? ($modulos[0]['modulo_id'] ?? 0));
    $avaliacoes = [];
    if ($modulo_id > 0) {
        $res = $conn->query("
            SELECT id, titulo, data_avaliacao
            FROM avaliacoes
            WHERE turma_id = $turma_id AND modulo_id = $modulo_id
            ORDER BY data_avaliacao ASC, id ASC
        ");
        if ($res) while ($row = $res->fetch_assoc()) $avaliacoes[] = $row;
    }
    json_out(['ok' => true, 'turmas' => $turmas, 'modulos' => $modulos, 'avaliacoes' => $avaliacoes]);
}

if ($action === 'avaliacao_resultado') {
    $avaliacao_id = (int)($_GET['avaliacao_id'] ?? 0);
    $res = $conn->query("
        SELECT a.id, a.titulo, a.data_avaliacao, m.sigla_modulo, m.nome_modulo,
               ar.nota_obtida, ar.resultado, ar.observacao
        FROM avaliacoes a
        INNER JOIN modulos m ON m.id = a.modulo_id
        LEFT JOIN avaliacoes_resultados ar ON ar.avaliacao_id = a.id AND ar.formando_id = $formando_id
        WHERE a.id = $avaliacao_id AND a.turma_id = $turma_id
        LIMIT 1
    ");
    json_out(['ok' => $res && $res->num_rows > 0, 'data' => $res && $res->num_rows > 0 ? $res->fetch_assoc() : null]);
}

if ($action === 'trabalhos') {
    ensure_trabalho_submissoes($conn);
    $modulo_id = (int)($_GET['modulo_id'] ?? 0);
    $whereModulo = $modulo_id > 0 ? "AND tr.modulo_id = $modulo_id" : "";
    $rows = [];
    $res = $conn->query("
        SELECT tr.id, tr.titulo, tr.tipo, tr.data_publicacao, tr.data_entrega, tr.estado,
               m.sigla_modulo, m.nome_modulo,
               ts.id AS submissao_id, ts.ficheiro_nome, ts.submetido_em, ts.estado AS estado_submissao
        FROM trabalhos tr
        INNER JOIN modulos m ON m.id = tr.modulo_id
        LEFT JOIN trabalho_submissoes ts ON ts.trabalho_id = tr.id AND ts.formando_id = $formando_id
        WHERE tr.turma_id = $turma_id $whereModulo
        ORDER BY tr.data_entrega ASC, tr.id ASC
    ");
    if ($res) while ($row = $res->fetch_assoc()) $rows[] = $row;
    json_out(['ok' => true, 'rows' => $rows, 'turmas' => $turmas, 'modulos' => rows_modulos($conn, $formando_id, $turma_id)]);
}

if ($action === 'trabalho_detail') {
    ensure_trabalho_submissoes($conn);
    $id = (int)($_GET['id'] ?? 0);
    $res = $conn->query("
        SELECT tr.*, m.sigla_modulo, m.nome_modulo, ts.ficheiro_nome, ts.ficheiro_caminho, ts.comentario, ts.submetido_em
        FROM trabalhos tr
        INNER JOIN modulos m ON m.id = tr.modulo_id
        LEFT JOIN trabalho_submissoes ts ON ts.trabalho_id = tr.id AND ts.formando_id = $formando_id
        WHERE tr.id = $id AND tr.turma_id = $turma_id
        LIMIT 1
    ");
    json_out(['ok' => $res && $res->num_rows > 0, 'data' => $res && $res->num_rows > 0 ? $res->fetch_assoc() : null]);
}

if ($action === 'trabalho_submit') {
    if ($nivel !== 3) {
        json_out(['ok' => false, 'msg' => 'So o formando pode submeter trabalhos.']);
    }
    ensure_trabalho_submissoes($conn);
    $id = (int)($_POST['trabalho_id'] ?? 0);
    $comentario = esc_sql($conn, trim((string)($_POST['comentario'] ?? '')));
    $resTrab = $conn->query("SELECT id FROM trabalhos WHERE id = $id AND turma_id = $turma_id LIMIT 1");
    if (!$resTrab || $resTrab->num_rows === 0) json_out(['ok' => false, 'msg' => 'Trabalho invalido.']);
    if (empty($_FILES['ficheiro']['name'])) json_out(['ok' => false, 'msg' => 'Seleccione o ficheiro.']);

    $dir = __DIR__ . '/../assets/trabalhos_submissoes';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $original = basename((string)$_FILES['ficheiro']['name']);
    $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $original);
    $fileName = 'trabalho_' . $id . '_formando_' . $formando_id . '_' . time() . '_' . $safeName;
    $target = $dir . '/' . $fileName;
    if (!move_uploaded_file($_FILES['ficheiro']['tmp_name'], $target)) {
        json_out(['ok' => false, 'msg' => 'Erro ao guardar ficheiro.']);
    }
    $path = 'assets/trabalhos_submissoes/' . $fileName;
    $originalEsc = esc_sql($conn, $original);
    $pathEsc = esc_sql($conn, $path);
    $conn->query("
        INSERT INTO trabalho_submissoes (trabalho_id, formando_id, ficheiro_nome, ficheiro_caminho, comentario)
        VALUES ($id, $formando_id, '$originalEsc', '$pathEsc', " . ($comentario !== '' ? "'$comentario'" : "NULL") . ")
        ON DUPLICATE KEY UPDATE ficheiro_nome = '$originalEsc', ficheiro_caminho = '$pathEsc', comentario = VALUES(comentario), submetido_em = NOW(), estado = 'submetido'
    ");
    json_out(['ok' => true]);
}

if ($action === 'ficheiros') {
    $turmaIds = array_map(fn($t) => (int)$t['id'], $turmas);
    $idSql = $turmaIds ? implode(',', $turmaIds) : '0';
    $rows = [];
    $res = $conn->query("
        SELECT fi.id, fi.categoria, fi.titulo, fi.descricao, fi.nome_original, fi.caminho, fi.tamanho,
               fi.downloads, fi.data_upload, fi.turma_id, t.nome_turma, tr.nome_turno
        FROM ficheiros fi
        LEFT JOIN turmas t ON t.id = fi.turma_id
        LEFT JOIN turnos tr ON tr.id = t.turno_id
        WHERE fi.categoria = 'geral'
           OR (fi.categoria = 'turma' AND fi.turma_id IN ($idSql))
        ORDER BY fi.categoria ASC, fi.data_upload DESC
    ");
    if ($res) while ($row = $res->fetch_assoc()) $rows[] = $row;
    json_out(['ok' => true, 'rows' => $rows]);
}

json_out(['ok' => false, 'msg' => 'Accao invalida.']);
