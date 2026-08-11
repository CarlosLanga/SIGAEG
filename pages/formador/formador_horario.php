<?php
require_once __DIR__ . '/../../config/init.php';

require_once __DIR__ . '/../../includes/auth_admin_formador.php';

$page_css = [
    'forms.css',
    'tables.css',
    'modules/breadcrumbs.css',
    'pages/horario_adicionar.css',
    'pages/horarios_gerir.css',
    'pages/formador_horario.css'
];

$page_js = [
    'modules/notifications.js',
    'modules/table-manager.js',
    'pages/formador_horario.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Horários</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/formador/dashboard.php'],
                ['label' => 'Ensino', 'url' => null],
                ['label' => 'Horários', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card formador-horarios-page"
            data-base-url="<?= BASE_URL ?>"
            data-list-url="<?= BASE_URL ?>api/formador_horarios_listar.php"
            data-detail-url="<?= BASE_URL ?>api/formador_horario_detalhe.php"
            data-print-url="<?= BASE_URL ?>api/horario_grade_print.php"
        >
            <h2 class="section-title">Horários das turmas</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="table-toolbar">
                <div class="toolbar-left">
                    <label class="filter-field">
                        <span>Pesquisar</span>
                        <input type="text" id="pesquisa_horario">
                    </label>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="tabela_horarios_formador">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Turma</th>
                            <th>Bloco</th>
                            <th>Semestre</th>
                            <th>Última actualização</th>
                            <th>Acção</th>
                        </tr>
                    </thead>
                    <tbody id="lista_horarios_formador">
                        <tr>
                            <td colspan="6" class="empty-row">Nenhum horário encontrado</td>
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

        <section class="detail-panel" id="painel_detalhe_horario" style="display: none;">
            <div class="page-header">
                <h1>Detalhes de Horário</h1>
                <nav class="breadcrumbs" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="<?= BASE_URL ?>pages/formador/dashboard.php"><i class="fa-solid fa-home"></i> Início</a></li>
                        <li><span>Ensino</span></li>
                        <li><a href="#voltar" id="breadcrumb_voltar_horarios">Horários</a></li>
                        <li><span>Detalhes de Horário</span></li>
                    </ol>
                </nav>
            </div>

            <div class="horario-detail-stack">
                <div class="card detail-profile-card">
                    <h2 class="detail-name horario-detail-title" id="detalhe_horario_titulo">—</h2>
                    <div class="horario-meta horario-detail-meta" id="detalhe_horario_meta"></div>
                </div>

                <div class="card detail-section-card">
                    <div class="horario-detail-head">
                        <h3 class="detail-section-title">Grelha do Horário</h3>
                        <button type="button" class="btn btn-outline btn-table" id="btn_imprimir_horario_detalhe">
                            <i class="fa-solid fa-print"></i>
                            <span class="btn-text">Imprimir</span>
                        </button>
                    </div>

                    <div class="horario-grade-wrap">
                        <div class="horario-grade-scroll">
                            <table class="horario-grade-table" id="detalhe_horario_grid"></table>
                        </div>
                    </div>

                    <div class="horario-resumo-wrap" id="detalhe_horario_resumo_wrap">
                        <h3>Descrições</h3>
                        <div class="horario-resumo-conteudo" id="detalhe_horario_resumo"></div>
                    </div>
                </div>
            </div>

            <button type="button" class="detail-fab-back" id="btn_voltar_horarios_floating" title="Voltar para lista">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
