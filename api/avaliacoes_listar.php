<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload): void {
    echo json_encode($payload);
    exit;
}

if (!$conn) {
    json_out(['ok' => false, 'rows' => []]);
}

$turma_id = (int)($_GET['turma_id'] ?? 0);
$modulo_id = (int)($_GET['modulo_id'] ?? 0);
if ($turma_id <= 0 || $modulo_id <= 0) {
    json_out(['ok' => false, 'rows' => []]);
}
$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$scopeWhere = $nivel === 2 ? "
      AND EXISTS (
          SELECT 1
          FROM formador_modulo fm
          INNER JOIN formadores f ON f.id = fm.formador_id
          WHERE fm.turma_id = a.turma_id
            AND fm.modulo_id = a.modulo_id
            AND f.usuario_id = $usuario_id
      )
" : "";

$hasHora = false;
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
$col = $conn->query("SHOW COLUMNS FROM avaliacoes LIKE 'hora_avaliacao'");
if ($col && $col->num_rows > 0) {
    $hasHora = true;
}

$horaSelect = $hasHora ? ", a.hora_avaliacao" : "";
$sql = "
    SELECT a.id, a.titulo, a.data_avaliacao $horaSelect,
           COALESCE(rs.estado, 'rascunho') AS estado_resultado
    FROM avaliacoes a
    LEFT JOIN avaliacoes_resultados_status rs ON rs.avaliacao_id = a.id
    WHERE a.turma_id = $turma_id AND a.modulo_id = $modulo_id
    $scopeWhere
    ORDER BY a.data_avaliacao ASC, a.id ASC
";

$rows = [];
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}

json_out(['ok' => true, 'rows' => $rows, 'hasHora' => $hasHora]);
