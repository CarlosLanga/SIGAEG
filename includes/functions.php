<?php
declare(strict_types=1);
function getCargo($nivel) {
    $cargos = [
        1 => 'Administrador',
        2 => 'Formador',
        3 => 'Formando',
        4 => 'Encarregado de Educação'
    ];
    return $cargos[$nivel] ?? 'Visitante';
}

// Visa saudar de acordo com o horário e mostrar apenas o primeiro nome do usuário (aquando do preenchimento no cadastro)
function primeiroNome(string $fallback = 'Visitante'):string {
    if(empty($_SESSION['usuario_nome'])) {
        return $fallback;
    }

    $nome = trim($_SESSION['usuario_nome']);
        $partes = explode(' ', $nome);
        return ucfirst(mb_strtolower($partes[0], 'UTF-8'));
}

function saudacaoPorHorario(): string {
    $hora = (int) date('H');

    if ($hora >= 5 && $hora < 12) {
        return 'Bom dia';
    } else if ($hora >= 12 && $hora < 18) {
        return 'Boa tarde';
    } else {
        return 'Boa noite';
    }
}

function getInitials(string $name = ''): string {
    $name = trim($name);
    if ($name === '') {
        return 'U';
    }

    $parts = preg_split('/\s+/', $name);
    $first = strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'));
    $last = count($parts) > 1 ? strtoupper(mb_substr(end($parts), 0, 1, 'UTF-8')) : $first;
    return $first . $last;
}

// Logs de erros
function log_erro($origem, $mensagem) {
    prune_logs_if_needed(30);
    $linha = date('Y-m-d H:i:s') . " | " . $origem . " | " . $mensagem . PHP_EOL;
    error_log($linha, 3, __DIR__ . '/../logs/app_errors.log');
}

// Logs de acoes
function log_acao($origem, $mensagem) {
    prune_logs_if_needed(30);
    $usuario = $_SESSION['usuario_nome'] ?? 'Desconhecido';
    $linha = date('Y-m-d H:i:s') . " | " . $origem . " | " . $usuario . " | " . $mensagem . PHP_EOL;
    error_log($linha, 3, __DIR__ . '/../logs/app_actions.log');
}

function prune_logs_if_needed(int $days = 30): void {
    $stampFile = __DIR__ . '/../logs/.log_cleanup';
    $now = time();
    if (file_exists($stampFile)) {
        $last = (int)@file_get_contents($stampFile);
        if ($last > 0 && ($now - $last) < 86400) {
            return;
        }
    }

    $cutoff = strtotime("-{$days} days");
    $logs = [
        __DIR__ . '/../logs/app_errors.log',
        __DIR__ . '/../logs/app_actions.log'
    ];

    foreach ($logs as $path) {
        prune_log_file($path, $cutoff);
    }

    @file_put_contents($stampFile, (string)$now);
}

function prune_log_file(string $path, int $cutoff): void {
    if (!file_exists($path)) {
        return;
    }

    $lines = @file($path, FILE_IGNORE_NEW_LINES);
    if (!$lines) {
        return;
    }

    $kept = [];
    foreach ($lines as $line) {
        $datePart = substr($line, 0, 19);
        $ts = strtotime($datePart);
        if ($ts === false) {
            $kept[] = $line;
            continue;
        }
        if ($ts >= $cutoff) {
            $kept[] = $line;
        }
    }

    @file_put_contents($path, implode(PHP_EOL, $kept) . PHP_EOL);
}

function tail_log_lines(string $path, int $max = 200): array {
    if (!file_exists($path)) {
        return [];
    }
    $lines = @file($path, FILE_IGNORE_NEW_LINES);
    if (!$lines) {
        return [];
    }
    if (count($lines) <= $max) {
        return $lines;
    }
    return array_slice($lines, -$max);
}

function getFormadorId(mysqli $conn, int $usuarioId): int {
    if ($usuarioId <= 0) {
        return 0;
    }

    $stmt = $conn->prepare('SELECT id FROM formadores WHERE usuario_id = ? LIMIT 1');
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    return (int)($row['id'] ?? 0);
}

function formadorTemAcessoTurma(mysqli $conn, int $formadorId, int $turmaId): bool {
    if ($formadorId <= 0 || $turmaId <= 0) {
        return false;
    }

    $sql = "
        SELECT 1
        FROM turmas t
        WHERE t.id = ?
          AND (
            t.dt_id = ?
            OR EXISTS (
                SELECT 1 FROM formador_modulo fm
                WHERE fm.turma_id = t.id AND fm.formador_id = ?
            )
          )
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('iii', $turmaId, $formadorId, $formadorId);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok = (bool)($res && $res->fetch_assoc());
    $stmt->close();

    return $ok;
}

function formadorTemModuloNaTurma(mysqli $conn, int $formadorId, int $turmaId, int $moduloId): bool {
    if ($formadorId <= 0 || $turmaId <= 0 || $moduloId <= 0) {
        return false;
    }

    $stmt = $conn->prepare(
        'SELECT 1 FROM formador_modulo WHERE formador_id = ? AND turma_id = ? AND modulo_id = ? LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('iii', $formadorId, $turmaId, $moduloId);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok = (bool)($res && $res->fetch_assoc());
    $stmt->close();

    return $ok;
}

function getFormandoId(mysqli $conn, int $usuarioId): int
{
    if ($usuarioId <= 0) {
        return 0;
    }

    $stmt = $conn->prepare('SELECT id FROM formandos WHERE usuario_id = ? LIMIT 1');
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    return (int)($row['id'] ?? 0);
}

function formandoTurmaAtualId(mysqli $conn, int $formandoId): int
{
    if ($formandoId <= 0) {
        return 0;
    }

    $stmt = $conn->prepare('SELECT turma_id FROM formandos WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $formandoId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    return (int)($row['turma_id'] ?? 0);
}

function formandoTurmaIdsAcessiveis(mysqli $conn, int $formandoId): array
{
    if ($formandoId <= 0) {
        return [];
    }

    $ids = [];

    $res = $conn->query("SELECT turma_id FROM formandos WHERE id = $formandoId AND turma_id IS NOT NULL LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $ids[(int)$row['turma_id']] = true;
    }

    $res = $conn->query("
        SELECT DISTINCT pp.turma_id
        FROM presencas_plano pp
        INNER JOIN presencas_registo pr ON pr.plano_id = pp.id
        WHERE pr.formando_id = $formandoId
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
        WHERE ar.formando_id = $formandoId
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $ids[(int)$row['turma_id']] = true;
        }
    }

    return array_map('intval', array_keys(array_filter($ids)));
}

function formandoPodeAcederTurma(mysqli $conn, int $formandoId, int $turmaId): bool
{
    if ($formandoId <= 0 || $turmaId <= 0) {
        return false;
    }

    return in_array($turmaId, formandoTurmaIdsAcessiveis($conn, $formandoId), true);
}

function ensure_anuncios_modulo_schema(mysqli $conn): bool {
    static $checked = false;

    if ($checked) {
        return true;
    }

    $result = $conn->query("SHOW COLUMNS FROM anuncios LIKE 'modulo_id'");
    if (!$result) {
        return false;
    }

    if ($result->num_rows === 0) {
        if (!$conn->query(
            'ALTER TABLE anuncios ADD COLUMN modulo_id INT NULL DEFAULT NULL AFTER turma_id'
        )) {
            return false;
        }
        $conn->query('ALTER TABLE anuncios ADD KEY idx_anuncios_modulo (modulo_id)');
    }

    $checked = true;
    return true;
}

function ensure_horarios_plano_schema(mysqli $conn): bool {
    static $checked = false;

    if ($checked) {
        return true;
    }

    $requiredColumns = [
        'publicado' => "ALTER TABLE horarios_plano ADD COLUMN publicado TINYINT(1) NOT NULL DEFAULT 0 AFTER bloco",
        'publicado_em' => "ALTER TABLE horarios_plano ADD COLUMN publicado_em DATETIME NULL DEFAULT NULL AFTER actualizado_em",
    ];

    foreach ($requiredColumns as $column => $sql) {
        $columnEsc = mysqli_real_escape_string($conn, $column);
        $result = $conn->query("SHOW COLUMNS FROM horarios_plano LIKE '$columnEsc'");
        if (!$result) {
            return false;
        }

        if ($result->num_rows === 0 && !$conn->query($sql)) {
            return false;
        }
    }

    $checked = true;
    return true;
}

?>
