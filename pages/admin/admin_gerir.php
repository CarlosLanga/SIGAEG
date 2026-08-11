<?php 
require_once __DIR__ . '/../../config/init.php';

if ($_SESSION['nivel_acesso'] != 1) { 
    header("Location: " . BASE_URL . "index.php"); 
    exit; 
}

require_once __DIR__ . '/../../includes/header.php'; 

require_once __DIR__ . '/../../includes/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <h1>Página: <?= basename($_SERVER['PHP_SELF']) ?></h1>
        <p>Estrutura carregada com sucesso. Pronto para desenvolver o conteúdo.</p>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>