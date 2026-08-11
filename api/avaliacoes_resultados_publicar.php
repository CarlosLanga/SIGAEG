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
    json_out(['ok' => false, 'message' => 'Sem permissao para publicar estes resultados.']);
}

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

$conn->query("
    INSERT INTO avaliacoes_resultados_status (avaliacao_id, estado, publicado_em)
    VALUES ($avaliacao_id, 'publicado', NOW())
    ON DUPLICATE KEY UPDATE estado = 'publicado', publicado_em = NOW()
");

json_out(['ok' => true]);
