<?php

if (!in_array((int)($_SESSION['nivel_acesso'] ?? 0), [1, 2], true)) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
