<?php
require_once __DIR__ . '/../../config/init.php';

if ($_SESSION['nivel_acesso'] != 3 && $_SESSION['nivel_acesso'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$dashboard_title = 'Painel do Formando';

$page_css = [
    'forms.css',
    'pages/horario_adicionar.css',
    'pages/horarios_gerir.css',
    'pages/formandos_gerir.css',
    'pages/dashboard.css'
];
$page_js = ['pages/dashboard.js'];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>

    <main class="content-body" id="dynamic-content">
        <?php require_once __DIR__ . '/../../includes/components/dashboard-shell.php'; ?>
    </main>

<?php
require_once __DIR__ . '/../../includes/footer.php';
