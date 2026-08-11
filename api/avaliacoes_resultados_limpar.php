<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload): void {
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'message' => 'Método inválido.']);
}

if (!$conn) {
    json_out(['ok' => false, 'message' => 'Erro de ligação à base de dados.']);
}

$avaliacao_id = (int)($_POST['avaliacao_id'] ?? 0);
if ($avaliacao_id <= 0) {
    json_out(['ok' => false, 'message' => 'Avaliação inválida.']);
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$scopeWhere = $nivel === 2 ? "AND f.usuario_id = $usuario_id" : "";

$resPermissao = $conn->query("
    SELECT a.id
    FROM avaliacoes a
    LEFT JOIN formador_modulo fm ON fm.turma_id = a.turma_id AND fm.modulo_id = a.modulo_id
    LEFT JOIN formadores f ON f.id = fm.formador_id
    WHERE a.id = $avaliacao_id
    $scopeWhere
    LIMIT 1
");

if (!$resPermissao || $resPermissao->num_rows === 0) {
    json_out(['ok' => false, 'message' => 'Sem permissao para limpar estes resultados.']);
}

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

$conn->query("UPDATE avaliacoes_resultados SET nota_obtida = NULL, resultado = 'SE', observacao = NULL WHERE avaliacao_id = $avaliacao_id");

$conn->query("
    INSERT INTO avaliacoes_resultados_status (avaliacao_id, estado)
    VALUES ($avaliacao_id, 'rascunho')
    ON DUPLICATE KEY UPDATE estado = 'rascunho', publicado_em = NULL
");

json_out(['ok' => true]);
