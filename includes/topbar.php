<header class="topbar">

    <div class="top-left">
        <!-- VerSão Desktop -->
        <div class="desktop-controls">
            <button id="toggle-sidebar" title="Menu"><i class="fa-solid fa-bars-staggered"></i></button>

            <button id="btn-profile" title="Meu Perfil"><i class="fa-solid fa-circle-user"></i></button>

            <button id="btn-fullscreen" title="Tela Cheia"><i class="fa-solid fa-maximize"></i></button>

            <button class="toggle-theme" title="Alternar Tema"><i class="fa-solid fa-moon"></i></button>

            <a href="<?= BASE_URL ?>pages/notificacoes.php" class="topbar-btn notif-btn" title="Notificações">
                <i class="fa-solid fa-bell"></i>
                <span class="notif-badge" id="badge-desktop" style="display: none;">0</span>
            </a>
        </div>

        <!-- Versão Telemóvel/Mobile -->
        <div class="mobile-controls">
            <button id="toggle-menu-mobile" class="hamburguer" title="Menú" aria-label="Alternar Menú">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <button class="toggle-theme" title="Alternar Tema"><i class="fa-solid fa-moon"></i></button>
        </div>
    </div>

    <div class="top-center">
        <div class="brand">
            <img class="brand-logo" src="<?= BASE_URL ?>assets/img/brand_logo.webp" alt="IICAEG" data-light-logo="<?= BASE_URL ?>assets/img/brand_logo.webp" data-dark-logo="<?= BASE_URL ?>assets/img/brand_logo_dark.webp">
        </div>
    </div>

    <div class="top-right">
        <?php
        $avatarName = $_SESSION['usuario_nome'] ?? '';
        $avatarInitials = getInitials($avatarName);
        $avatarPhoto = $_SESSION['usuario_foto'] ?? '';
        $hasPhoto = !empty($avatarPhoto) && $avatarPhoto !== 'default.png';
        ?>
        <!-- Desktop Versão -->
        <a href="<?= BASE_URL ?>api/logout.php" class="logout-btn desktop-logout">
            <i class="fa-solid fa-power-off"></i> <span>Terminar Sessão</span>
        </a>

        <!-- Versão SmartPhone -->
        <button class="notif-wrapper" id="btn-profile-mobile" title="Perfil" aria-label="Abrir Perfil">
            <?php if ($hasPhoto): ?>
                <img src="<?= BASE_URL ?>assets/img/<?= htmlspecialchars($avatarPhoto) ?>" alt="Perfil">
            <?php else: ?>
                <span class="avatar-initials"><?= htmlspecialchars($avatarInitials) ?></span>
            <?php endif; ?>
            <span class="notif-badge" id="badge-mobile" style="display: none;">0</span>
        </button>
    </div>
</header>

<div class="global-notif-banner" id="global-notif-banner" style="display: none;">
    <a href="<?= BASE_URL ?>pages/notificacoes.php" class="notif-banner-content">
        <i class="fa-solid fa-circle-info"></i>
        <span id="global-notif-text">Nova notificação recebida!</span>
    </a>
    <button type="button" class="notif-banner-close" id="global-notif-close" aria-label="Fechar notificação">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>

<div class="mobile-profile-panel" id="mobile-profile">
    <div class="profile-header">
        <a href="<?= BASE_URL ?>pages/perfil.php">
            <?php if ($hasPhoto): ?>
                <img src="<?= BASE_URL ?>assets/img/<?= htmlspecialchars($avatarPhoto) ?>">
            <?php else: ?>
                <span class="avatar-initials avatar-lg"><?= htmlspecialchars($avatarInitials) ?></span>
            <?php endif; ?>
        </a>
        <div>
            <p class="p-name">
                <a href="<?= BASE_URL ?>pages/perfil.php"><?= $_SESSION['usuario_nome'] ?></a>
            </p>
            <p class="p-role"><?= getCargo($_SESSION['nivel_acesso']) ?></p>
        </div>
    </div>

    <a href="<?= BASE_URL ?>pages/notificacoes.php" class="notif-link" id="mobile-notif-text">
        Notificações
    </a>

    <a href="<?= BASE_URL ?>api/logout.php" class="logout-btn mobile-logout">
        <i class="fa-solid fa-power-off"></i> Terminar Sessão
    </a>
</div>

<div class="mobile-backdrop" id="mobile-backdrop"></div>