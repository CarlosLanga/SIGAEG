<?php
declare(strict_types=1);

session_start();

// Logica de inicio de sessao via cookie "O famoso lembre de mim (aquela musica do muchacho kkk)
if (!isset($_SESSION['usuario_id']) && isset($_COOKIE['iicaeg_remember'])) {
    require_once __DIR__ . '/db.php';
    $token = $conn->real_escape_string($_COOKIE['iicaeg_remember']);
    $res = $conn->query("SELECT * FROM usuarios WHERE remember_token = '$token' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['nome_completo'];
        $_SESSION['nivel_acesso'] = $user['nivel_acesso_id'];
        $_SESSION['usuario_foto'] = $user['foto'] ?? 'default.png';
        $_SESSION['tema'] = $user['tema'] ?? 'light';
        $_SESSION['sidebar_estado'] = $user['sidebar_estado'] ?? 'expandida';
    }
}

date_default_timezone_set('Africa/Johannesburg');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/functions.php';

function handle_app_error(string $origem, string $mensagem, int $code = 404): void {
    log_erro($origem, $mensagem);
    if (!headers_sent()) {
        http_response_code($code);
    }
    include __DIR__ . '/../pages/404.php';
    exit;
}

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    handle_app_error('erro', $message . " | $file:$line", 404);
});

set_exception_handler(function ($ex) {
    handle_app_error('excecao', $ex->getMessage() . " | " . $ex->getFile() . ":" . $ex->getLine(), 404);
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        handle_app_error('fatal', $error['message'] . " | " . $error['file'] . ":" . $error['line'], 404);
    }
});

?>
