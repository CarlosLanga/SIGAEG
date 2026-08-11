<?php
require_once __DIR__ . '/../../config/init.php';

require_once __DIR__ . '/../../includes/auth_admin_formador.php';

$page_css = [
    'forms.css',
    'tables.css',
    'modules/breadcrumbs.css',
    'pages/trabalhos_gerir.css'
];

$page_js = [
    'modules/notifications.js',
    'pages/trabalhos_gerir.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header trabalhos-list-header">
            <h1>Gerir Trabalhos</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Trabalhos', 'url' => null],
                ['label' => 'Gerir Trabalhos', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card trabalhos-gerir-page"
            data-base-url="<?= BASE_URL ?>"
            data-turmas-url="<?= BASE_URL ?>api/turmas_select.php"
            data-modulos-url="<?= BASE_URL ?>api/presencas_modulos.php"
            data-list-url="<?= BASE_URL ?>api/trabalhos_gerir_listar.php"
            data-detail-url="<?= BASE_URL ?>api/trabalho_detalhe.php"
            data-delete-url="<?= BASE_URL ?>api/trabalhos_delete.php"
        >
            <h2 class="section-title">Filtros</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="trabalhos-filter-grid">
                <label class="form-field">
                    <span>Turma</span>
                    <select id="filtro_turma_trabalho">
                        <option value="">Seleccione a turma</option>
                    </select>
                </label>

                <label class="form-field">
                    <span>Módulo</span>
                    <select id="filtro_modulo_trabalho">
                        <option value="">Seleccione o módulo</option>
                    </select>
                </label>

                <div class="form-field trabalhos-filter-action">
                    <span>&nbsp;</span>
                    <button type="button" class="trabalho-btn trabalho-btn-primary" id="btn_ver_trabalhos">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>Ver trabalhos</span>
                    </button>
                </div>
            </div>

            <div class="trabalhos-table-tools" id="trabalhos_table_tools" style="display: none;">
                <label class="form-field trabalhos-search">
                    <span>Pesquisar</span>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" id="pesquisa_trabalho" placeholder="Pesquisar por título, tipo ou estado">
                    </div>
                </label>

                <a href="<?= BASE_URL ?>pages/admin/trabalhos_marcar.php" class="trabalho-btn trabalho-btn-primary trabalhos-novo-btn">
                    <i class="fa-solid fa-plus"></i>
                    <span>Novo trabalho</span>
                </a>
            </div>

            <div class="table-wrap trabalhos-table-wrap" id="trabalhos_table_wrap" style="display: none;">
                <table class="data-table" id="tabela_trabalhos">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Título</th>
                            <th>Turma</th>
                            <th>Módulo</th>
                            <th>Tipo</th>
                            <th>Nota</th>
                            <th>Publicação</th>
                            <th>Prazo</th>
                            <th>Estado</th>
                            <th>Acções</th>
                        </tr>
                    </thead>
                    <tbody id="lista_trabalhos">
                        <tr>
                            <td colspan="10" class="empty-row">Seleccione a turma e o módulo para ver os trabalhos</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="detail-panel trabalhos-detail-panel" id="painel_detalhe_trabalho" style="display: none;">
            <div class="page-header">
                <h1>Detalhes do Trabalho</h1>
                <nav class="breadcrumbs" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="<?= BASE_URL ?>pages/admin/dashboard.php"><i class="fa-solid fa-home"></i> Início</a></li>
                        <li><span>Trabalhos</span></li>
                        <li><a href="#voltar" id="breadcrumb_voltar_trabalhos">Gerir Trabalhos</a></li>
                        <li><span>Detalhes do Trabalho</span></li>
                    </ol>
                </nav>
            </div>

            <div class="trabalho-detail-actions-top">
                <button type="button" class="trabalho-btn trabalho-btn-secondary" id="btn_submeter_trabalho_admin">
                    <i class="fa-solid fa-upload"></i>
                    <span>Submeter trabalho</span>
                </button>
                <button type="button" class="trabalho-btn trabalho-btn-primary" id="btn_editar_trabalho_detalhe">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Editar</span>
                </button>
            </div>

            <div class="trabalho-detail-layout">
                <section class="card trabalho-detail-main">
                    <div class="trabalho-detail-heading">
                        <span class="badge-estado" id="detalhe_estado">-</span>
                        <h2 id="detalhe_titulo">-</h2>
                        <p id="detalhe_meta">-</p>
                    </div>

                    <div class="trabalho-detail-description">
                        <h3>Descrição / enunciado</h3>
                        <p id="detalhe_descricao">Sem descrição registada.</p>
                    </div>

                    <div class="trabalho-detail-info-grid">
                        <div>
                            <span>Turma</span>
                            <strong id="detalhe_turma">-</strong>
                        </div>
                        <div>
                            <span>Módulo</span>
                            <strong id="detalhe_modulo">-</strong>
                        </div>
                        <div>
                            <span>Duração do módulo</span>
                            <strong id="detalhe_duracao">-</strong>
                        </div>
                        <div>
                            <span>Formador</span>
                            <strong id="detalhe_formador">-</strong>
                        </div>
                    </div>
                </section>

                <aside class="trabalho-detail-side">
                    <div class="card trabalho-summary-card">
                        <span class="summary-label">Submissões</span>
                        <strong id="card_submissoes">0</strong>
                    </div>
                    <div class="card trabalho-summary-card">
                        <span class="summary-label">Ficheiros</span>
                        <strong id="card_ficheiros">0</strong>
                    </div>
                    <div class="card trabalho-summary-card">
                        <span class="summary-label">Nota</span>
                        <strong id="card_nota">Sem nota</strong>
                    </div>
                </aside>
            </div>

            <section class="card trabalho-submissoes-card">
                <div class="section-row-title">
                    <h2 class="section-title">Trabalhos submetidos</h2>
                    <button type="button" class="trabalho-btn trabalho-btn-secondary" id="btn_ver_ficheiros" disabled>
                        <i class="fa-solid fa-folder-open"></i>
                        <span>Ver ficheiros</span>
                    </button>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Formando</th>
                                <th>Data de submissão</th>
                                <th>Estado</th>
                                <th>Ficheiro</th>
                                <th>Nota</th>
                            </tr>
                        </thead>
                        <tbody id="lista_submissoes_trabalho">
                            <tr>
                                <td colspan="6" class="empty-row">Ainda não há submissões registadas para este trabalho</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <button type="button" class="trabalho-fab-back" id="btn_voltar_trabalhos_floating" title="Voltar para lista">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
