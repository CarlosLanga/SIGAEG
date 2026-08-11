<?php 
require_once __DIR__ . '/../../config/init.php';

require_once __DIR__ . '/../../includes/auth_admin_formador.php';

$page_css = [
    'forms.css',
    'tables.css',
    'modules/breadcrumbs.css',
    'modules/cards.css',
    'pages/avaliacoes_resultados.css'
];

$page_js = [
    'modules/notifications.js',
    'pages/avaliacoes_resultados.js'
];

require_once __DIR__ . '/../../includes/header.php'; 
require_once __DIR__ . '/../../includes/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Resultados das Avaliações</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Avaliações', 'url' => null],
                ['label' => 'Resultados', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card resultados-page"
            data-base-url="<?= BASE_URL ?>"
            data-turmas-url="<?= BASE_URL ?>api/turmas_select.php"
            data-modulos-url="<?= BASE_URL ?>api/presencas_modulos.php"
            data-avaliacoes-url="<?= BASE_URL ?>api/avaliacoes_listar.php"
            data-contexto-url="<?= BASE_URL ?>api/avaliacoes_resultados_contexto.php"
            data-save-url="<?= BASE_URL ?>api/avaliacoes_resultados_save.php"
            data-publicar-url="<?= BASE_URL ?>api/avaliacoes_resultados_publicar.php"
            data-limpar-url="<?= BASE_URL ?>api/avaliacoes_resultados_limpar.php"
        >
            <h2 class="section-title">Dados de Avaliação</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="form-grid">
                <label class="form-field">
                    <span>Turma</span>
                    <select id="resultado_turma">
                        <option value="">Seleccione a turma</option>
                    </select>
                </label>

                <label class="form-field">
                    <span>Módulo</span>
                    <select id="resultado_modulo">
                        <option value="">Seleccione o módulo</option>
                    </select>
                </label>

                <label class="form-field">
                    <span>Avaliação</span>
                    <select id="resultado_avaliacao">
                        <option value="">Seleccione a avaliação</option>
                    </select>
                </label>
            </div>

            <div id="resultado_info" class="resultado-info"></div>

            <div id="resultado_cards" class="summary-cards resultados-cards" style="display:none;">
                <div class="summary-card card-formandos">
                    <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="card-info">
                        <h3>Total de formandos</h3>
                        <p class="summary-value" id="card_total">0</p>
                    </div>
                </div>
                <div class="summary-card card-formadores">
                    <div class="card-icon"><i class="fa-solid fa-check"></i></div>
                    <div class="card-info">
                        <h3>Alcançados</h3>
                        <p class="summary-value" id="card_alcancados">0</p>
                    </div>
                </div>
                <div class="summary-card card-turmas">
                    <div class="card-icon"><i class="fa-solid fa-xmark"></i></div>
                    <div class="card-info">
                        <h3>Não alcançados</h3>
                        <p class="summary-value" id="card_nao_alcancados">0</p>
                    </div>
                </div>
            </div>

            <div id="resultado_tabela_wrap" class="table-wrap" style="display:none;">
                <table class="data-table" id="tabela_resultados">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Código</th>
                            <th>Nota</th>
                            <th>Resultado</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody id="lista_resultados">
                        <tr>
                            <td colspan="6" class="empty-row">Nenhum resultado disponível</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="resultado_footer" class="resultado-footer" style="display:none;">
                <div class="status-badge" id="resultado_estado">Rascunho</div>
                <div class="resultado-acoes">
                    <button type="button" class="btn btn-outline btn-table" id="btn_limpar_resultados">
                        <i class="fa-solid fa-eraser"></i>
                        <span class="btn-text">Limpar</span>
                    </button>
                    <button type="button" class="btn btn-table" id="btn_publicar_resultados">
                        <i class="fa-solid fa-check"></i>
                        <span class="btn-text">Publicar</span>
                    </button>
                </div>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
