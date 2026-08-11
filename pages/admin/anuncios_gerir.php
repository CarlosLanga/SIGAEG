<?php
require_once __DIR__ . '/../../config/init.php';

require_once __DIR__ . '/../../includes/auth_admin_formador.php';

$isFormador = ((int)($_SESSION['nivel_acesso'] ?? 0) === 2);
$dashUrl = $isFormador
    ? BASE_URL . 'pages/formador/dashboard.php'
    : BASE_URL . 'pages/admin/dashboard.php';
$criarUrl = $isFormador
    ? BASE_URL . 'pages/formador/formador_anuncios_criar.php'
    : BASE_URL . 'pages/admin/anuncios_criar.php';

$page_css = [
    'forms.css',
    'tables.css',
    'modules/breadcrumbs.css',
    'pages/anuncios_gerir.css'
];

$page_js = [
    'modules/notifications.js',
    'pages/anuncios_gerir.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Gerir Anúncios</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => $dashUrl],
                ['label' => 'Anúncios', 'url' => null],
                ['label' => 'Gerir Anúncios', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card anuncios-gerir-page"
            data-base-url="<?= BASE_URL ?>"
            data-list-url="<?= BASE_URL ?>api/anuncios_gerir_listar.php"
            data-delete-url="<?= BASE_URL ?>api/anuncios_delete.php"
            data-formador-mode="<?= $isFormador ? '1' : '0' ?>">

            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="anuncios-table-tools">
                <label class="form-field anuncios-search">
                    <span>Pesquisar</span>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" id="pesquisa_anuncio" placeholder="Pesquisar por título, público ou turma">
                    </div>
                </label>

                <label class="form-field anuncios-filter-prioridade">
                    <span>Prioridade</span>
                    <select id="filtro_prioridade">
                        <option value="">Todas</option>
                        <option value="normal">Normal</option>
                        <option value="importante">Importante</option>
                        <option value="evento">Evento</option>
                    </select>
                </label>

                <a href="<?= $criarUrl ?>" class="btn anuncios-novo-btn">
                    <i class="fa-solid fa-plus"></i>
                    <span>Novo anúncio</span>
                </a>
            </div>

            <div class="table-wrap anuncios-table-wrap">
                <table class="data-table" id="tabela_anuncios">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Título</th>
                            <th>Prioridade</th>
                            <th>Público-Alvo</th>
                            <?php if (!$isFormador): ?><th>Autor</th><?php endif; ?>
                            <th>Publicação</th>
                            <th>Expiração</th>
                            <th>Anexo</th>
                            <th>Acções</th>
                        </tr>
                    </thead>
                    <tbody id="lista_anuncios">
                        <tr>
                            <td colspan="9" class="empty-row">A carregar anúncios...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="anuncio-modal-overlay" id="anuncio_modal" style="display: none;">
            <div class="anuncio-modal-card">
                <button type="button" class="anuncio-modal-close" id="anuncio_modal_close" aria-label="Fechar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="anuncio-modal-badges">
                    <span class="anuncio-badge" id="modal_badge_prioridade"></span>
                    <span class="anuncio-badge badge-alvo" id="modal_badge_alvo"></span>
                </div>
                <h2 id="modal_titulo">-</h2>
                <p class="anuncio-modal-meta" id="modal_meta">-</p>
                <div class="anuncio-modal-event" id="modal_event" style="display:none;">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span id="modal_event_text"></span>
                </div>
                <div class="anuncio-modal-content" id="modal_content"></div>
                <a class="anuncio-modal-anexo" id="modal_anexo" style="display:none;" target="_blank" rel="noopener">
                    <i class="fa-solid fa-paperclip"></i>
                    <span id="modal_anexo_nome"></span>
                </a>
            </div>
        </div>

        <div class="anuncio-confirm-overlay" id="anuncio_confirm" style="display: none;">
            <div class="anuncio-confirm-card">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <h3>Remover anúncio</h3>
                <p id="anuncio_confirm_text">Tem a certeza que deseja remover este anúncio? Esta acção é irreversível.</p>
                <div class="anuncio-confirm-actions">
                    <button type="button" class="btn" id="anuncio_confirm_cancel">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="anuncio_confirm_ok">Remover</button>
                </div>
            </div>
        </div>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
