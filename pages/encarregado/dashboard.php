<?php
require_once __DIR__ . '/../../config/init.php';

if ($_SESSION['nivel_acesso'] != 4 && $_SESSION['nivel_acesso'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$dashboard_title = 'Painel do Encarregado';

$page_css = ['pages/dashboard.css'];
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
