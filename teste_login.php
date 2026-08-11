<?php
require_once 'config/db.php';

$email = 'admin@iicaeg';
$senha_digitada = '123456';

$res = $conn->query("SELECT * FROM usuarios WHERE email = '$email'");

if ($res->num_rows === 0) {
    echo "ERRO: O email '$email' não existe na tabela usuarios!<br>";
} else {
    $user = $res->fetch_assoc();
    $hash_banco = $user['senha'];
    
    echo "Email encontrado!<br>";
    echo "Hash no Banco: " . $hash_banco . " (Tamanho: " . strlen($hash_banco) . " caracteres)<br>";
    
    if (password_verify($senha_digitada, $hash_banco)) {
        echo "SUCESSO: A senha '$senha_digitada' é válida para este hash.";
    } else {
        echo "FALHA: O password_verify retornou FALSE. O hash não corresponde à senha.";
    }
}
?>