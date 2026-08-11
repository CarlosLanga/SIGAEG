<?php
require_once __DIR__ . '/../../config/init.php';

require_once __DIR__ . '/../../includes/auth_admin_formador.php';

$page_css = [
    'forms.css',
    'tables.css',
    'modules/breadcrumbs.css',
    'modules/cards.css',
    'pages/presencas_ver.css'
];

$page_js = [
    'modules/notifications.js',
    'modules/table-manager.js',
    'pages/presencas_ver.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Gerir Presenças</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/formador/dashboard.php'],
                ['label' => 'Presenças', 'url' => null],
                ['label' => 'Gerir Presenças', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card presencas-ver"
            data-base-url="<?= BASE_URL ?>"
            data-list-url="<?= BASE_URL ?>api/presencas_listar.php"
            data-turmas-url="<?= BASE_URL ?>api/turmas_select.php"
            data-detalhe-url="<?= BASE_URL ?>api/presencas_detalhe.php"
            data-publicar-url="<?= BASE_URL ?>api/presencas_publicar.php"
            data-delete-url="<?= BASE_URL ?>api/presencas_delete.php"
            data-imprimir-url="<?= BASE_URL ?>api/presencas_imprimir.php"
            data-edit-url="<?= BASE_URL ?>pages/formador/formador_presencas.php"
        >
            <h2 class="section-title">Listagem de Presenças</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="table-toolbar">
                <div class="toolbar-left">
                    <label class="filter-field">
                        <span>Turma</span>
                        <div class="select-wrap">
                            <select id="filtro_turma">
                                <option value="all">Todas as turmas</option>
                            </select>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </label>

                    <label class="filter-field">
                        <span>Pesquisar</span>
                        <input type="text" id="pesquisa_presenca">
                    </label>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="tabela_presencas_ver">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Turma</th>
                            <th>Módulo</th>
                            <th>Marcado por</th>
                            <th>Data de marcação</th>
                            <th>Aulas</th>
                            <th>Estado</th>
                            <th>Acção</th>
                        </tr>
                    </thead>
                    <tbody id="lista_presencas_ver">
                        <tr>
                            <td colspan="8" class="empty-row">Nenhuma presença encontrada</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="table-footer table-footer-modern">
                <div class="table-footer-meta">
                    <label class="table-page-size">
                        <span>Linhas por página</span>
                        <div class="select-wrap">
                            <select id="page_size">
                                <option value="10">10</option>
                                <option value="20" selected>20</option>
                                <option value="50">50</option>
                            </select>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </label>
                    <div class="table-info" id="table_info">0-0 de 0</div>
                </div>
                <div class="table-pagination table-pagination-modern" id="table_pagination">
                    <button class="btn btn-outline btn-table table-nav-btn" id="btn_prev" type="button" aria-label="Página anterior">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <div class="table-page-numbers" id="table_page_numbers"></div>
                    <button class="btn btn-outline btn-table table-nav-btn" id="btn_next" type="button" aria-label="Próxima página">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </section>

        <div class="modal" id="modal_presenca_ver">
            <div class="modal-content modal-presenca-content">
                <div class="modal-header">
                    <h2>Detalhe de Presenças</h2>
                    <button type="button" class="btn btn-outline" id="btn_fechar_presenca_ver">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="presenca-meta" id="presenca_meta"></div>

                <div class="summary-cards" id="presenca_cards"></div>

                <div class="table-wrap">
                    <table class="data-table" id="tabela_presencas_modal">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nome</th>
                                <th>Código</th>
                                <th>Situação</th>
                                <th>Observação</th>
                            </tr>
                        </thead>
                        <tbody id="lista_presencas_modal">
                            <tr>
                                <td colspan="5" class="empty-row">Nenhum registo encontrado</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
