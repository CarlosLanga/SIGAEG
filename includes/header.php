<?php

if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAEG | Sistema TIC's</title>
    <link rel="shortcut icon" href="<?= BASE_URL ?>assets\img\favicon.ico" type="image/x-icon">
    <!-- Fontawesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Fontawesome local (ao usar só preciso adicionar BASE_URL (trecho php) no início de href="") -->
    <!-- <link rel="stylesheet" href="assets/fontawesome/css/all.min.css"> -->

    <!-- CDN fonte poppins -->
    <!-- <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet"> -->

    <!-- CDN fonte noto sans -->
    <!-- <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"> -->

    <!-- Fontes locais -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/fonts.css">
    
    <!-- Bootstrap icons local -->
    <!-- <link rel="stylesheet" href="<?= BASE_URL ?>assets\bootstrap-icons-1.13.1\bootstrap-icons.css"> -->

    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"> -->

    <!-- Chart JS -->
    <script src="<?= BASE_URL ?>lib/chartjs/vendor/chart.umd.min.js"></script>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/layout.css">
    <?php
    $page_css_list = [];
    if (!empty($page_css)) {
        $page_css_list = is_array($page_css) ? $page_css : [$page_css];
    }
    foreach ($page_css_list as $css_file):
    ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/<?= $css_file ?>">
    <?php endforeach; ?>
</head>
<body data-base-url="<?= BASE_URL ?>" class="<?= ($_COOKIE['iicaeg_tema'] ?? $_SESSION['tema'] ?? 'light') === 'dark' ? 'dark' : '' ?>">
