<?php
require_once __DIR__ . '/../../config/init.php';

if ($_SESSION['nivel_acesso'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_css = [
    'modules/breadcrumbs.css',
    'pages/logs.css'
];

$page_js = [
    'modules/notifications.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

prune_logs_if_needed(30);
$errors = tail_log_lines(__DIR__ . '/../../logs/app_errors.log', 300);
$actions = tail_log_lines(__DIR__ . '/../../logs/app_actions.log', 300);
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Logs do Sistema</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Administrador', 'url' => null],
                ['label' => 'Logs do Sistema', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card log-card">
            <h2 class="section-title">Logs de Erros</h2>
            <div class="log-box">
                <pre><?php echo htmlspecialchars(implode(PHP_EOL, $errors)); ?></pre>
            </div>
        </section>

        <section class="card log-card">
            <h2 class="section-title">Logs de Ações</h2>
            <div class="log-box">
                <pre><?php echo htmlspecialchars(implode(PHP_EOL, $actions)); ?></pre>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
