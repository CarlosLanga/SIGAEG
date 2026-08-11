<?php
require_once __DIR__ . '/../../config/init.php';

require_once __DIR__ . '/../../includes/auth_admin_formador.php';

$page_css = [
    'forms.css',
    'tables.css',
    'modules/breadcrumbs.css',
    'pages/formandos_gerir.css',
    'pages/formador_modulos.css'
];

$page_js = [
    'modules/notifications.js',
    'modules/table-manager.js',
    'pages/formador_modulos.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Meus Módulos</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/formador/dashboard.php'],
                ['label' => 'Ensino', 'url' => null],
                ['label' => 'Módulos', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card formador-modulos-page"
            data-base-url="<?= BASE_URL ?>"
            data-list-url="<?= BASE_URL ?>api/formador_modulos_listar.php"
            data-detail-url="<?= BASE_URL ?>api/formador_modulo_detalhe.php"
        >
            <h2 class="section-title">Módulos atribuídos</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="table-toolbar">
                <div class="toolbar-left">
                    <label class="filter-field">
                        <span>Pesquisar</span>
                        <input type="text" id="pesquisa_modulo">
                    </label>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="tabela_modulos_formador">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Módulo</th>
                            <th>Turma</th>
                            <th>Formador</th>
                            <th>Estado</th>
                            <th>Acção</th>
                        </tr>
                    </thead>
                    <tbody id="lista_modulos_formador">
                        <tr>
                            <td colspan="6" class="empty-row">Nenhum módulo encontrado</td>
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

        <section class="detail-panel" id="painel_detalhe_modulo" style="display: none;">
            <div class="page-header">
                <h1>Detalhes do Módulo</h1>
                <nav class="breadcrumbs" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="<?= BASE_URL ?>pages/formador/dashboard.php"><i class="fa-solid fa-home"></i> Início</a></li>
                        <li><span>Ensino</span></li>
                        <li><a href="#voltar" id="breadcrumb_voltar_modulos">Meus Módulos</a></li>
                        <li><span>Detalhes do Módulo</span></li>
                    </ol>
                </nav>
            </div>

            <div class="modulo-detail-stack">
                <div class="card detail-profile-card">
                    <h2 class="detail-name modulo-detail-title" id="detalhe_modulo_titulo">—</h2>
                    <div class="detail-info-rows modulo-info-grid">
                        <div class="detail-row">
                            <div class="detail-row-icon"><i class="fa-solid fa-book"></i></div>
                            <span class="detail-row-label">Nome</span>
                            <span class="detail-row-value" id="detalhe_modulo_nome">—</span>
                        </div>
                        <div class="detail-row">
                            <div class="detail-row-icon"><i class="fa-solid fa-users-rectangle"></i></div>
                            <span class="detail-row-label">Turma</span>
                            <span class="detail-row-value" id="detalhe_modulo_turma">—</span>
                        </div>
                        <div class="detail-row">
                            <div class="detail-row-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                            <span class="detail-row-label">Formador</span>
                            <span class="detail-row-value" id="detalhe_modulo_formador">—</span>
                        </div>
                        <div class="detail-row">
                            <div class="detail-row-icon"><i class="fa-solid fa-tag"></i></div>
                            <span class="detail-row-label">Tipo</span>
                            <span class="detail-row-value" id="detalhe_modulo_tipo">—</span>
                        </div>
                        <div class="detail-row">
                            <div class="detail-row-icon"><i class="fa-solid fa-calendar-plus"></i></div>
                            <span class="detail-row-label">Data de início</span>
                            <span class="detail-row-value" id="detalhe_modulo_inicio">—</span>
                        </div>
                        <div class="detail-row">
                            <div class="detail-row-icon"><i class="fa-solid fa-calendar-check"></i></div>
                            <span class="detail-row-label">Data de conclusão</span>
                            <span class="detail-row-value" id="detalhe_modulo_fim">—</span>
                        </div>
                        <div class="detail-row">
                            <div class="detail-row-icon"><i class="fa-solid fa-signal"></i></div>
                            <span class="detail-row-label">Estado</span>
                            <span class="detail-row-value" id="detalhe_modulo_estado">—</span>
                        </div>
                    </div>
                </div>

                <div class="card detail-section-card">
                    <div class="modulo-section-head">
                        <h3 class="detail-section-title">Estatísticas por Formando</h3>
                        <span class="modulo-eval-meta" id="detalhe_avaliacoes_meta">—</span>
                    </div>

                    <div class="table-toolbar modulo-detail-toolbar">
                        <div class="toolbar-left">
                            <label class="filter-field">
                                <span>Pesquisar</span>
                                <input type="text" id="pesquisa_formandos_modulo">
                            </label>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="data-table" id="tabela_formandos_modulo">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nome</th>
                                    <th>Código</th>
                                    <th>Progresso</th>
                                    <th>Resultado</th>
                                </tr>
                            </thead>
                            <tbody id="lista_formandos_modulo">
                                <tr>
                                    <td colspan="5" class="empty-row">Nenhum formando encontrado</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <button type="button" class="detail-fab-back" id="btn_voltar_modulos_floating" title="Voltar para lista">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
