<?php
require_once __DIR__ . '/../../config/init.php';

if (!in_array((int)($_SESSION['nivel_acesso'] ?? 0), [1, 3], true)) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$page_css = [
    'forms.css',
    'tables.css',
    'modules/breadcrumbs.css',
    'pages/formandos_gerir.css',
    'pages/formador_turmas.css',
    'pages/formando_turmas.css',
];

$page_js = [
    'modules/notifications.js',
    'modules/table-manager.js',
    'pages/formando_turmas.js',
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Turmas</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/formando/dashboard.php'],
                ['label' => 'Turmas', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card formando-turmas-page"
            data-list-url="<?= BASE_URL ?>api/formando_turmas_listar.php"
            data-detail-url="<?= BASE_URL ?>api/formando_turma_detalhe.php">
            <h2 class="section-title">Minhas turmas</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="table-toolbar">
                <div class="toolbar-left">
                    <label class="filter-field">
                        <span>Pesquisar</span>
                        <input type="text" id="pesquisa_turma" placeholder="Turma, turno, ano lectivo...">
                    </label>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="tabela_turmas_formando">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Turma</th>
                            <th>Turno</th>
                            <th>Certificado Vocacional</th>
                            <th>Director de Turma</th>
                            <th>Ano lectivo</th>
                            <th>Acções</th>
                        </tr>
                    </thead>
                    <tbody id="lista_turmas_formando">
                        <tr>
                            <td colspan="7" class="empty-row">Nenhuma turma encontrada</td>
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
                <h1 id="detalhe_titulo_pagina">—</h1>
                <nav class="breadcrumbs" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="<?= BASE_URL ?>pages/formando/dashboard.php"><i class="fa-solid fa-home"></i> Início</a></li>
                        <li><a href="#voltar" id="breadcrumb_voltar_turmas">Turmas</a></li>
                        <li><span>Detalhes da Turma</span></li>
                    </ol>
                </nav>
            </div>

            <div class="turma-detail-stack">
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

                <div class="card detail-section-card turma-formandos-card">
                    <h3 class="detail-section-title">Formandos</h3>

                    <div class="table-wrap">
                        <table class="data-table" id="tabela_formandos_turma">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nome</th>
                                    <th>Código</th>
                                </tr>
                            </thead>
                            <tbody id="lista_formandos_turma">
                                <tr>
                                    <td colspan="3" class="empty-row">Nenhum formando encontrado</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <button type="button" class="detail-fab-back" id="btn_voltar_turmas_floating" title="Voltar para lista">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
