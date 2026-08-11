<?php
require_once __DIR__ . '/../../config/init.php';

require_once __DIR__ . '/../../includes/auth_admin_formador.php';

$page_css = [
    'forms.css',
    'tables.css',
    'modules/breadcrumbs.css',
    'pages/horario_adicionar.css',
    'pages/horarios_gerir.css',
    'pages/formandos_gerir.css',
    'pages/formador_turmas.css'
];

$page_js = [
    'modules/notifications.js',
    'modules/table-manager.js',
    'pages/formador_turmas.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Minhas Turmas</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/formador/dashboard.php'],
                ['label' => 'Ensino', 'url' => null],
                ['label' => 'Turmas', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card formador-turmas-page"
            data-base-url="<?= BASE_URL ?>"
            data-list-url="<?= BASE_URL ?>api/formador_turmas_listar.php"
            data-detail-url="<?= BASE_URL ?>api/formador_turma_detalhe.php"
            data-print-url="<?= BASE_URL ?>api/formador_turma_formandos_imprimir.php"
            data-horario-url="<?= BASE_URL ?>api/horario_grade_preview.php"
        >
            <h2 class="section-title">Turmas atribuídas</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="table-toolbar">
                <div class="toolbar-left">
                    <label class="filter-field">
                        <span>Pesquisar</span>
                        <input type="text" id="pesquisa_turma">
                    </label>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="tabela_turmas_formador">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Turma</th>
                            <th>Turno</th>
                            <th>Certificado Vocacional</th>
                            <th>Nº de formandos</th>
                            <th>Director de Turma</th>
                            <th>Ano lectivo</th>
                            <th>Acções</th>
                        </tr>
                    </thead>
                    <tbody id="lista_turmas_formador">
                        <tr>
                            <td colspan="8" class="empty-row">Nenhuma turma encontrada</td>
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

        <section class="detail-panel" id="painel_detalhe_turma" style="display: none;">
            <div class="page-header">
                <h1>Detalhes da Turma</h1>
                <nav class="breadcrumbs" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="<?= BASE_URL ?>pages/formador/dashboard.php"><i class="fa-solid fa-home"></i> Início</a></li>
                        <li><span>Ensino</span></li>
                        <li><a href="#voltar" id="breadcrumb_voltar_turmas">Minhas Turmas</a></li>
                        <li><span>Detalhes da Turma</span></li>
                    </ol>
                </nav>
            </div>

            <div class="turma-detail-stack">
                <div class="detail-col-left">
                    <div class="card detail-profile-card">
                        <h2 class="detail-name turma-detail-title" id="detalhe_turma_nome">—</h2>

                        <div class="detail-info-rows">
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-clock"></i></div>
                                <span class="detail-row-label">Turno</span>
                                <span class="detail-row-value" id="detalhe_turma_turno">—</span>
                            </div>
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-certificate"></i></div>
                                <span class="detail-row-label">Certificado Vocacional</span>
                                <span class="detail-row-value" id="detalhe_cv_valor">—</span>
                            </div>
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-book"></i></div>
                                <span class="detail-row-label">Qualificação</span>
                                <span class="detail-row-value" id="detalhe_curso">—</span>
                            </div>
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-calendar"></i></div>
                                <span class="detail-row-label">Ano lectivo</span>
                                <span class="detail-row-value" id="detalhe_ano">—</span>
                            </div>
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-users"></i></div>
                                <span class="detail-row-label">Formandos</span>
                                <span class="detail-row-value" id="detalhe_total_formandos">—</span>
                            </div>
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-user-tie"></i></div>
                                <span class="detail-row-label">Director de Turma</span>
                                <span class="detail-row-value" id="detalhe_director">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="card detail-section-card detail-horario-card">
                        <h3 class="detail-section-title">Horário de Hoje</h3>
                        <div class="detail-horario-meta" id="detalhe_horario_meta">A processar horário...</div>
                        <div class="detail-horario-list" id="detalhe_horario_list"></div>
                        <div class="detail-horario-actions">
                            <button type="button" class="btn-text" id="btn_ver_horario_turma" disabled>Ver horário</button>
                        </div>
                    </div>
                </div>

                <div class="detail-col-right">
                    <div class="card detail-section-card turma-formandos-card">
                        <div class="turma-section-head">
                            <h3 class="detail-section-title">Formandos</h3>
                            <button type="button" class="btn btn-outline btn-table" id="btn_imprimir_formandos">
                                <i class="fa-solid fa-print"></i>
                                <span class="btn-text-label">Imprimir</span>
                            </button>
                        </div>

                        <div class="table-toolbar turma-detail-toolbar">
                            <div class="toolbar-left">
                                <label class="filter-field">
                                    <span>Pesquisar</span>
                                    <input type="text" id="pesquisa_formandos_turma">
                                </label>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table class="data-table" id="tabela_formandos_turma">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nome</th>
                                        <th>Sexo</th>
                                        <th>Código</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="lista_formandos_turma">
                                    <tr>
                                        <td colspan="5" class="empty-row">Nenhum formando encontrado</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="detail-fab-back" id="btn_voltar_turmas_floating" title="Voltar para lista">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
        </section>

        <div class="modal" id="modal_horario_turma">
            <div class="modal-content modal-view-content">
                <div class="modal-header">
                    <h2>Horário da Turma</h2>
                    <button type="button" class="btn btn-outline" id="btn_fechar_horario">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="horario-meta" id="detalhe_horario_meta_modal"></div>
                <div class="horario-grade-wrap">
                    <div class="horario-grade-scroll">
                        <table class="horario-grade-table" id="detalhe_horario_grid"></table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
