<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_css = [
    'modules/breadcrumbs.css',
    'pages/notificacoes.css'
];

$page_js = [];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$usuarioId = (int)$_SESSION['usuario_id'];

// Marcar todas como lidas (segurança extra, mas também via AJAX ao carregar ou via endpoint check)
$conn->query("UPDATE notificacoes SET lida = 1 WHERE usuario_id = $usuarioId AND lida = 0");

// Buscar notificações
$notificacoes = [];
$res = $conn->query("SELECT * FROM notificacoes WHERE usuario_id = $usuarioId ORDER BY data_criacao DESC LIMIT 100");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $notificacoes[] = $row;
    }
}
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Notificações</h1>
            <?php
            $nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
            $dashUrl = BASE_URL . 'pages/admin/dashboard.php';
            if ($nivel === 2) $dashUrl = BASE_URL . 'pages/formador/dashboard.php';
            elseif ($nivel === 3) $dashUrl = BASE_URL . 'pages/formando/dashboard.php';
            elseif ($nivel === 4) $dashUrl = BASE_URL . 'pages/encarregado/dashboard.php';
            
            $breadcrumbs = [
                ['label' => 'Início', 'url' => $dashUrl],
                ['label' => 'Notificações', 'url' => null]
            ];
            require __DIR__ . '/../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="notificacoes-page">
            <div class="notificacoes-container card">
                <?php if (empty($notificacoes)): ?>
                    <div class="notificacoes-empty">
                        <i class="fa-regular fa-bell-slash"></i>
                        <p>Não tem nenhuma notificação de momento.</p>
                    </div>
                <?php else: ?>
                    <ul class="notificacoes-list">
                        <?php foreach ($notificacoes as $notif): ?>
                            <li class="notif-item <?= $notif['lida'] == 0 ? 'unread' : '' ?>">
                                <div class="notif-icon">
                                    <?php if ($notif['tipo'] === 'ficheiro'): ?>
                                        <i class="fa-solid fa-file-arrow-down" style="color: var(--accent);"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-bell" style="color: var(--accent);"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="notif-content">
                                    <h3><?= htmlspecialchars($notif['titulo']) ?></h3>
                                    <p><?= htmlspecialchars($notif['mensagem']) ?></p>
                                    <span class="notif-time"><?= date('d/m/Y H:i', strtotime($notif['data_criacao'])) ?></span>
                                    <?php 
                                        if (!empty($notif['link'])): 
                                            $link = $notif['link'];
                                            if ($notif['tipo'] === 'ficheiro' && in_array($nivel, [3, 4], true)) {
                                                $link = str_replace('pages/ficheiros.php', 'pages/formando/formando_ficheiros.php', $link);
                                            }
                                    ?>
                                        <a href="<?= htmlspecialchars($link) ?>" class="notif-link-btn">Ver Detalhes</a>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div>

<script>
// Forçar reset do badge na topbar ao entrar nesta página
$(document).ready(function() {
    $('#badge-desktop').hide();
    $('#badge-mobile').hide();
    $.post('<?= BASE_URL ?>api/notificacoes_read.php');
});
</script>
