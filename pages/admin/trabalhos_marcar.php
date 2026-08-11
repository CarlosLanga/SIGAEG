<?php
require_once __DIR__ . '/../../config/init.php';

require_once __DIR__ . '/../../includes/auth_admin_formador.php';

$page_css = [
    'forms.css',
    'tables.css',
    'modules/breadcrumbs.css',
    'pages/trabalhos_marcar.css'
];

$page_js = [
    'modules/notifications.js',
    'pages/trabalhos_marcar.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Marcar Trabalhos</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Trabalhos', 'url' => null],
                ['label' => 'Marcar Trabalhos', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card trabalhos-page"
            data-base-url="<?= BASE_URL ?>"
            data-turmas-url="<?= BASE_URL ?>api/turmas_select.php"
            data-modulos-url="<?= BASE_URL ?>api/presencas_modulos.php"
            data-listar-url="<?= BASE_URL ?>api/trabalhos_listar.php"
            data-save-url="<?= BASE_URL ?>api/trabalhos_save.php"
            data-delete-url="<?= BASE_URL ?>api/trabalhos_delete.php"
            data-detail-url="<?= BASE_URL ?>api/trabalho_detalhe.php"
        >
            <h2 class="section-title">Dados do trabalho</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="form-grid">
                <label class="form-field form-field-third">
                    <span>Turma</span>
                    <select id="trabalho_turma">
                        <option value="">Seleccione a turma</option>
                    </select>
                </label>

                <label class="form-field form-field-third">
                    <span>Módulo</span>
                    <select id="trabalho_modulo">
                        <option value="">Seleccione o módulo</option>
                    </select>
                </label>

                <label class="form-field form-field-third">
                    <span>Tipo</span>
                    <select id="trabalho_tipo">
                        <option value="individual">Individual</option>
                        <option value="grupo">Grupo</option>
                        <option value="pratico">Prático</option>
                        <option value="projecto">Projecto</option>
                    </select>
                </label>

                <div id="trabalho_info" class="trabalho-info form-field-full"></div>

                <label class="form-field form-field-wide">
                    <span>Título</span>
                    <input type="text" id="trabalho_titulo" maxlength="180" placeholder="Ex.: Trabalho prático 1">
                </label>

                <label class="form-field">
                    <span>Data de publicação</span>
                    <input type="date" id="trabalho_publicacao">
                </label>

                <label class="form-field">
                    <span>Prazo de entrega</span>
                    <input type="date" id="trabalho_entrega">
                </label>

                <label class="form-field">
                    <span>Nota</span>
                    <div class="nota-input">
                        <input type="number" id="trabalho_pontuacao" min="0" max="100" step="0.01" placeholder="Sem nota">
                        <span>%</span>
                    </div>
                </label>

                <label class="form-field">
                    <span>Estado</span>
                    <select id="trabalho_estado">
                        <option value="rascunho">Rascunho</option>
                        <option value="publicado">Publicado</option>
                        <option value="encerrado">Encerrado</option>
                    </select>
                </label>

                <label class="form-field form-field-full">
                    <span>Descrição</span>
                    <textarea id="trabalho_descricao" rows="5" placeholder="Detalhes do que os formandos devem fazer."></textarea>
                </label>
            </div>

            <div class="trabalho-acoes">
                <button type="button" class="trabalho-btn trabalho-btn-secondary" id="trabalho_limpar">
                    <i class="fa-solid fa-eraser"></i>
                    <span>Limpar campos</span>
                </button>
                <button type="button" class="trabalho-btn trabalho-btn-primary" id="trabalho_adicionar">
                    <i class="fa-solid fa-plus"></i>
                    <span>Marcar trabalho</span>
                </button>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="trabalhos_tabela">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Publicação</th>
                            <th>Prazo</th>
                            <th>Estado</th>
                            <th>Acções</th>
                        </tr>
                    </thead>
                    <tbody id="trabalhos_lista">
                        <tr>
                            <td colspan="7" class="empty-row">Nenhum trabalho marcado ainda</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
