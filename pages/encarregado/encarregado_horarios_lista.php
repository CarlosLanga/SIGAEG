<?php
require_once __DIR__ . '/../../config/init.php';
if ((int)($_SESSION['nivel_acesso'] ?? 0) !== 4) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}
$portalTitle = 'Lista de Horários';
$portalMode = 'horarios';
$page_css = ['forms.css', 'tables.css', 'modules/breadcrumbs.css', 'pages/horario_adicionar.css', 'pages/horarios_gerir.css', 'pages/encarregado_app.css', 'pages/formando_portal.css'];
$page_js = ['pages/formando_portal.js'];
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require __DIR__ . '/../../includes/components/formando-portal-page.php';
