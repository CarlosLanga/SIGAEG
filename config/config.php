<?php
define('BASE_URL', 'http://localhost/iicaeg_sistema/'); //Mudar se o caminho do pc for diferente


// opção 2 --- imparcial BASE_URL dinamica
/*
$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

$host = $_SERVER['HTTP_HOST'];

$caminhoProjecto = '/iicaegsistema';

define('BASE_URL', $protocolo . '://' . $host . $caminhoProjecto . '/');
*/

// ------ opção 1 - teste
// define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/iicaeg_sistema/');
?>