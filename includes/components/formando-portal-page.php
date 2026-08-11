<?php
$portalTitle = $portalTitle ?? 'Portal do Formando';
$portalMode = $portalMode ?? 'turmas';
$portalRole = (int)($_SESSION['nivel_acesso'] ?? 0) === 4 ? 'encarregado' : 'formando';
$portalDashboard = $portalRole === 'encarregado'
    ? BASE_URL . 'pages/encarregado/dashboard.php'
    : BASE_URL . 'pages/formando/dashboard.php';
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1><?= htmlspecialchars($portalTitle) ?></h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => $portalDashboard],
                ['label' => $portalTitle, 'url' => null],
            ];
            require __DIR__ . '/breadcrumbs.php';
            ?>
        </div>

        <section class="card formando-portal"
            data-mode="<?= htmlspecialchars($portalMode) ?>"
            data-role="<?= htmlspecialchars($portalRole) ?>"
            data-api-url="<?= BASE_URL ?>api/formando_portal.php"
            data-grade-url="<?= BASE_URL ?>api/horario_grade_preview.php"
            data-download-url="<?= BASE_URL ?>api/ficheiros_download.php">
            <div id="form-message" class="form-message" style="display:none;"></div>

            <?php if ($portalRole === 'encarregado'): ?>
                <div class="filters-row portal-educando-row">
                    <label class="form-field">
                        <span>Educando</span>
                        <select id="portal_educando"></select>
                    </label>
                </div>
            <?php endif; ?>

            <div class="portal-toolbar" id="portal_toolbar"></div>
            <div class="portal-content" id="portal_content">
                <div class="portal-empty"><i class="fa-solid fa-spinner fa-spin"></i> A carregar...</div>
            </div>

            <div class="modal" id="portal_modal">
                <div class="modal-content modal-view-content">
                    <div class="modal-header">
                        <h2 id="portal_modal_title">Detalhes</h2>
                        <button type="button" class="btn btn-outline" id="portal_modal_close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div id="portal_modal_body"></div>
                </div>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../footer.php'; ?>
</div>
