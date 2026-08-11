<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';
if (!$conn) {
    log_erro('entrar_cadastrar', 'Falha de conexão com a base de dados');
    echo "Não foi possível conectar à base de dados. Tente novamente!";
    exit;
}


if (isset($_POST['register']) || isset($_GET['register'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $token = mysqli_real_escape_string($conn, $_POST['token_acesso']); # Código de Convite/Acesso
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql_token = "SELECT nivel_destinado FROM codigos_autorizados
                WHERE codigo_acesso = '$token'
                AND email_dono = '$email'
                AND estado = 'disponivel'";
    
    $res_token = $conn->query($sql_token);
    if ($res_token->num_rows == 0) {
        log_erro('entrar_cadastrar', "Código inválido para email: $email");
        echo "Código de convite inválido.";
        exit;
    }


    $dados_token = $res_token->fetch_assoc();
    $nivel = $dados_token['nivel_destinado'];

    switch ((int)$nivel) {
        case 1:
            $tabela_perfil = 'administradores';
            break;
        case 2:
            $tabela_perfil = 'formadores';
            break;
        case 3:
            $tabela_perfil = 'formandos';
            break;
        case 4:
            $tabela_perfil = 'encarregados';
            break;
        default:
            echo "Ní­vel de acesso inválido.";
            exit;
    }

    $res_perfil = $conn->query("SELECT id, usuario_id FROM $tabela_perfil WHERE email = '$email' LIMIT 1");
    if ($res_perfil->num_rows == 0) {
        log_erro('entrar_cadastrar', "Email não pré-cadastrado: $email");
        echo "Este email não foi pré-cadastrado pelo administrador.";
        exit;
    }
    $perfil = $res_perfil->fetch_assoc();

    $res_email = $conn->query("SELECT id FROM usuarios
                                WHERE email = '$email'");
    if ($res_email->num_rows > 0) {
        log_erro('entrar_cadastrar', "Email já associado a um user: $email");
        echo "Este email já está associado a um utilizador.";
        exit;
    }
 

    $sql_ins = "INSERT INTO usuarios (nome_completo, email, senha, nivel_acesso_id) VALUES ('$name', '$email', '$password', '$nivel')";
    if ($conn->query($sql_ins)) {
        $novo_id = $conn->insert_id;
        $conn->query("UPDATE $tabela_perfil SET usuario_id = $novo_id WHERE id = {$perfil['id']}");
        $conn->query("UPDATE codigos_autorizados SET estado = 'utilizado' WHERE codigo_acesso = '$token'");
        echo "sucesso_cadastrado";
        exit;
    }

    log_erro('entrar_cadastrar', $conn->error);
    echo "Erro ao processar cadastro no servidor.";
    exit;
}


// Login
if (isset($_POST['login']) || isset($_GET['login'])) {
    $email_login = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $senha_digitada = $_POST['password'];

    $res = $conn->query("SELECT * FROM usuarios WHERE email = '$email_login'");
    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        if (password_verify($senha_digitada, $user['senha'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nome'] = $user['nome_completo'];
            $_SESSION['nivel_acesso'] = $user['nivel_acesso_id'];
            $_SESSION['usuario_foto'] = $user['foto'] ?? 'default.png';
            $_SESSION['tema'] = $user['tema'] ?? 'light';
            $_SESSION['sidebar_estado'] = $user['sidebar_estado'] ?? 'expandida';

            setcookie('iicaeg_tema', $_SESSION['tema'], time() + (86400 * 365), "/");
            setcookie('iicaeg_sidebar', $_SESSION['sidebar_estado'], time() + (86400 * 365), "/");

            // Logica de lembra de mim - os cookies, toma biscoitos kkkkkk
            if (isset($_POST['remember']) && $_POST['remember'] == '1') {
                $token = bin2hex(random_bytes(32));
                $conn->query("UPDATE usuarios SET remember_token = '$token' WHERE id = {$user['id']}");
                setcookie('iicaeg_remember', $token, time() + (86400 * 30), "/");
            } else {
                if (isset($_COOKIE['iicaeg_remember'])) {
                    setcookie('iicaeg_remember', '', time() - 3600, "/");
                    $conn->query("UPDATE usuarios SET remember_token = NULL WHERE id = {$user['id']}");
                }
            }

            switch ((int)$user['nivel_acesso_id']) {
                case 1: 
                    echo "sucesso_admin"; 
                    break;
                case 2: 
                    echo "sucesso_formador"; 
                    break;
                case 3: 
                    echo "sucesso_formando"; 
                    break;
                case 4: 
                    echo "sucesso_encarregado"; break;
                default: 
                    echo "sucesso_user"; 
                    break;
            }
            exit;
        }
    }
    log_erro('entrar_cadastrar', "Falha no login: $email_login");
    echo "Email ou senha incorrectos.";
    exit;
}

?>
