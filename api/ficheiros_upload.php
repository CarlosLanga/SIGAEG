<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload): void
{
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'message' => 'Método inválido.']);
}

if (!$conn) {
    json_out(['ok' => false, 'message' => 'Erro de ligação à base de dados.']);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

if (!in_array($nivel, [1, 2], true) || $usuarioId <= 0) {
    json_out(['ok' => false, 'message' => 'Sem permissão.']);
}

$categoria = trim((string)($_POST['categoria'] ?? ''));
$titulo = trim((string)($_POST['titulo'] ?? ''));
$descricao = trim((string)($_POST['descricao'] ?? ''));
$turmaId = (int)($_POST['turma_id'] ?? 0);
$id = (int)($_POST['id'] ?? 0);

if ($nivel === 2) {
    $categoria = 'turma';
}

if (!in_array($categoria, ['geral', 'turma'], true)) {
    json_out(['ok' => false, 'message' => 'Categoria inválida.']);
}

if ($titulo === '') {
    json_out(['ok' => false, 'message' => 'Informe o título do ficheiro.']);
}

if ($categoria === 'turma' && $turmaId <= 0) {
    json_out(['ok' => false, 'message' => 'Seleccione a turma.']);
}

if ($nivel === 2 && $turmaId > 0) {
    $resPerm = $conn->query("
        SELECT t.id
        FROM turmas t
        INNER JOIN formador_modulo fm ON fm.turma_id = t.id
        INNER JOIN formadores f ON f.id = fm.formador_id
        WHERE t.id = $turmaId AND f.usuario_id = $usuarioId
        LIMIT 1
    ");
    if (!$resPerm || $resPerm->num_rows === 0) {
        json_out(['ok' => false, 'message' => 'A turma seleccionada não está associada ao formador.']);
    }
}

if ($id > 0) {
    $resCheck = $conn->query("SELECT * FROM ficheiros WHERE id = $id");
    if (!$resCheck || $resCheck->num_rows === 0) {
        json_out(['ok' => false, 'message' => 'Ficheiro não encontrado.']);
    }
    $fileInfo = $resCheck->fetch_assoc();
    if ($nivel === 2 && (int)$fileInfo['criado_por'] !== $usuarioId) {
        json_out(['ok' => false, 'message' => 'Não pode editar este ficheiro.']);
    }
}

$hasNewFile = isset($_FILES['ficheiro']) && $_FILES['ficheiro']['error'] === UPLOAD_ERR_OK;

if (!$hasNewFile && $id === 0) {
    json_out(['ok' => false, 'message' => 'Seleccione um ficheiro para publicar.']);
}

if ($hasNewFile) {
    $file = $_FILES['ficheiro'];
    $maxSize = 20 * 1024 * 1024;
    if ((int)$file['size'] > $maxSize) {
        json_out(['ok' => false, 'message' => 'O ficheiro não pode exceder 20MB.']);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
    if (!in_array($ext, $allowed, true)) {
        json_out(['ok' => false, 'message' => 'Formato de ficheiro não permitido.']);
    }

    $dirUpload = __DIR__ . '/../assets/ficheiros/';
    if (!is_dir($dirUpload)) {
        @mkdir($dirUpload, 0775, true);
    }

    $safeName = 'ficheiro_' . $usuarioId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destino = $dirUpload . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        json_out(['ok' => false, 'message' => 'Falha ao guardar o ficheiro.']);
    }
    
    $nomeOriginalEsc = $conn->real_escape_string((string)$file['name']);
    $caminhoEsc = $conn->real_escape_string('assets/ficheiros/' . $safeName);
    $mimeEsc = $conn->real_escape_string((string)($file['type'] ?? 'application/octet-stream'));
    $tamanho = (int)$file['size'];
}

$categoriaEsc = $conn->real_escape_string($categoria);
$tituloEsc = $conn->real_escape_string($titulo);
$descricaoSql = $descricao !== '' ? "'" . $conn->real_escape_string($descricao) . "'" : "NULL";
$turmaSql = $categoria === 'turma' ? (string)$turmaId : "NULL";

if ($id > 0) {
    $updates = [
        "categoria = '$categoriaEsc'",
        "titulo = '$tituloEsc'",
        "descricao = $descricaoSql",
        "turma_id = $turmaSql"
    ];
    if ($hasNewFile) {
        $updates[] = "nome_original = '$nomeOriginalEsc'";
        $updates[] = "caminho = '$caminhoEsc'";
        $updates[] = "mime_type = '$mimeEsc'";
        $updates[] = "tamanho = $tamanho";
    }
    
    $sql = "UPDATE ficheiros SET " . implode(', ', $updates) . " WHERE id = $id";
    if (!$conn->query($sql)) {
        if ($hasNewFile) @unlink($destino);
        log_erro('ficheiros_upload_update', $conn->error);
        json_out(['ok' => false, 'message' => 'Erro ao atualizar o ficheiro.']);
    }
    
    if ($hasNewFile && !empty($fileInfo['caminho'])) {
        $oldFile = __DIR__ . '/../' . $fileInfo['caminho'];
        if (file_exists($oldFile)) @unlink($oldFile);
    }
    
    log_acao('ficheiros_update', "Ficheiro atualizado: $titulo");
} else {
    $sql = "
        INSERT INTO ficheiros (
            categoria, titulo, descricao, nome_original, caminho, mime_type,
            tamanho, turma_id, criado_por
        ) VALUES (
            '$categoriaEsc', '$tituloEsc', $descricaoSql, '$nomeOriginalEsc', '$caminhoEsc', '$mimeEsc',
            $tamanho, $turmaSql, $usuarioId
        )
    ";

    if (!$conn->query($sql)) {
        if ($hasNewFile) @unlink($destino);
        log_erro('ficheiros_upload', $conn->error);
        json_out(['ok' => false, 'message' => 'Erro ao registar o ficheiro.']);
    }

    log_acao('ficheiros_upload', "Ficheiro publicado: $titulo");

    // --- Lógica de Notificações ---
    $notifTitulo = 'Novo ficheiro adicionado';
    $notifMensagem = 'Novo arquivo adicionado: ' . $tituloEsc;
    $notifLink = BASE_URL . 'pages/ficheiros.php';
    
    $destinatarios = [];
    
    if ($categoria === 'geral') {
        $resUsers = $conn->query("SELECT id FROM usuarios WHERE status = 1 AND id != $usuarioId");
        if ($resUsers) {
            while ($u = $resUsers->fetch_assoc()) {
                $destinatarios[] = (int)$u['id'];
            }
        }
    } else if ($categoria === 'turma' && $turmaId > 0) {
        $resFormandos = $conn->query("SELECT usuario_id FROM formandos WHERE turma_id = $turmaId AND usuario_id IS NOT NULL AND usuario_id != $usuarioId");
        if ($resFormandos) {
            while ($f = $resFormandos->fetch_assoc()) {
                $destinatarios[] = (int)$f['usuario_id'];
            }
        }
        
        $resFormadores = $conn->query("
            SELECT DISTINCT f.usuario_id 
            FROM formador_modulo fm 
            INNER JOIN formadores f ON f.id = fm.formador_id 
            WHERE fm.turma_id = $turmaId AND f.usuario_id IS NOT NULL AND f.usuario_id != $usuarioId
        ");
        if ($resFormadores) {
            while ($f = $resFormadores->fetch_assoc()) {
                $destinatarios[] = (int)$f['usuario_id'];
            }
        }
    }
    
    if (!empty($destinatarios)) {
        $destinatarios = array_unique($destinatarios);
        $values = [];
        foreach ($destinatarios as $destId) {
            $values[] = "($destId, '$notifTitulo', '$notifMensagem', 'ficheiro', '$notifLink')";
        }
        
        $chunks = array_chunk($values, 100);
        foreach ($chunks as $chunk) {
            $sqlNotif = "INSERT INTO notificacoes (usuario_id, titulo, mensagem, tipo, link) VALUES " . implode(',', $chunk);
            $conn->query($sqlNotif);
        }
    }
}
json_out(['ok' => true]);
