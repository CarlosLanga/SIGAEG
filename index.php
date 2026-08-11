<?php
require_once __DIR__ . '/config/init.php';

if (isset($_SESSION['usuario_id'])) {
    switch ((int)$_SESSION['nivel_acesso']) {
        case 1: header("Location: pages/admin/dashboard.php"); exit;
        case 2: header("Location: pages/formador/dashboard.php"); exit;
        case 3: header("Location: pages/formando/dashboard.php"); exit;
        case 4: header("Location: pages/encarregado/dashboard.php"); exit;
        default: header("Location: pages/formando/dashboard.php"); exit;
    }
}

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? ''
];

$activeForm = $_SESSION['active_form'] ?? 'login';

unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form']);

function showError($error) {
    return !empty($error) ? "<p class='error-message'>$error</p>" : '';
}

function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAEG | Sistema TIC's</title>
    <link rel="shortcut icon" href="assets\img\favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/fonts.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- <link rel="stylesheet" href="assets/fontawesome/css/all.min.css"> -->
</head>
<body class="<?= ($_COOKIE['iicaeg_tema'] ?? 'light') === 'dark' ? 'dark' : '' ?>">
    
<div class="main-wrapper">
    <div class="side-image"></div>

    <div class="form-container">
        <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
            <form>
                <h2>Iniciar Sessão</h2>
                <p class="descr">SISTEMA INFORMÁTICO DE GESTÃO ACADÉMICA ARMANDO EMÍLIO GUEBUZA</p>
                <?= showError($errors['login']); ?>
                <input type="email" name="email" placeholder="Email" required>
                <div class="input-group password-toggle">
                    <input type="password" name="password" id="login-password" placeholder="Senha" required>
                    <button type="button" class="toggle-pass" aria-label="Mostrar senha">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                <div class="form-options">
                    <label class="remember-label">
                        <input type="checkbox" name="remember" value="1">
                        <span>Lembrar de mim</span>
                    </label>
                </div>
                <button type="submit" name="login">Entrar</button>
                <p>Não tem uma conta? <a href="#" onclick="showForm('register-form')">Criar agora</a></p>
            </form>
        </div>

        <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
            <form>
                <h2>Cadastrar</h2>
                <p class="descr">SISTEMA INFORMÁTICO DE GESTÃO ACADÉMICA ARMANDO EMÍLIO GUEBUZA</p>
                <?= showError($errors['register']); ?>
                <input type="text" name="name" placeholder="Nome Completo" required>

                <input type="email" name="email" placeholder="Email" required>

                <div class="input-group info-hint">
                    <input type="text" name="token_acesso" id="token_acesso" placeholder="Código de convite" required>
                    <span class="hint-icon" data-tooltip="Código fornecido pelo administrador">
                        <i class="fa-solid fa-circle-question"></i>
                    </span>
                </div>

                <div class="input-group password-toggle">
                    <input type="password" name="password" id="register-password" placeholder="Senha" required>
                    <button type="button" class="toggle-pass" aria-label="Mostrar senha">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                
                <button type="submit" name="register">Cadastrar</button>
                <p>Já tem uma conta? <a href="#" onclick="showForm('login-form')">Iniciar Sessão</a></p> 
            </form>
        </div>

    </div>
</div>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> Desenvolvido por <a href="https://github.com/CarlosLanga" target="_blank">Carlos Langa</a></p>
</footer>

    <script src="assets\js\jQuery.js"></script>
    <script src="assets\js\script.js"></script>
</body>
</html>