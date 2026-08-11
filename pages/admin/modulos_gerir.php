<?php
require_once __DIR__ . '/../../config/init.php';

if ($_SESSION['nivel_acesso'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_css = [
    'forms.css',
    'modules/breadcrumbs.css',
    'tables.css'
];

$page_js = [
    'modules/notifications.js',
    'modules/forms.js',
    'modules/validation.js',
    'modules/table-manager.js',
    'pages/modulos_gerir.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Gerir Módulos</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Módulos', 'url' => null],
                ['label' => 'Gerir Módulos', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card"
            data-list-url="<?= BASE_URL ?>api/modulos_gerir_listar.php"
            data-turmas-url="<?= BASE_URL ?>api/turmas_listar.php"
            data-base-url="<?= BASE_URL ?>">
            <h2 class="section-title">Lista de Módulos</h2>

            <div id="form-message" class="form-message" style="display: none;"></div>

            <?php
            $filter_id = 'filtro_turma';
            $filter_label = 'Turma';
            $filter_all = 'Todas as turmas';
            $search_id = 'pesquisa_modulo';
            $print_id = 'btn_imprimir';
            $print_text = 'Imprimir';
            require __DIR__ . '/../../includes/components/table-toolbar.php';
            ?>

            <div class="table-toolbar">
                <div class="toolbar-left">
                    <label class="filter-field">
                        <span>Tipo</span>
                        <div class="select-wrap">
                            <select id="filtro_tipo_modulo">
                                <option value="">Todos os tipos</option>
                                <option value="generico">Genérico</option>
                                <option value="vocacional">Vocacional</option>
                            </select>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </label>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="tabela_modulos">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Módulo</th>
                            <th>Tipo</th>
                            <th>Turma</th>
                            <th>Formador</th>
                            <th>Estado</th>
                            <th>Acção</th>
                        </tr>
                    </thead>
                    <tbody id="lista_modulos">
                        <tr>
                            <td colspan="7" class="empty-row">Nenhum módulo encontrado</td>
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

        <div id="modal_confirmar_remocao_modulo" class="confirm-modal-overlay" style="display:none;">
            <div class="confirm-modal-card" role="dialog" aria-modal="true" aria-labelledby="confirm_modulo_title">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <h3 id="confirm_modulo_title">Remover módulo?</h3>
                <p>Tem a certeza que deseja remover este módulo? Esta acção é irreversível.</p>
                <div class="confirm-modal-actions">
                    <button type="button" class="btn" id="btn_cancelar_remocao_modulo">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btn_confirmar_remocao_modulo">Remover</button>
                </div>
            </div>
        </div>

        <!-- MOdal editar -->
        <div class="modal" id="modal_editar_modulo">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Editar Módulo</h2>
                    <button type="button" class="btn btn-outline" id="btn_fechar_editar_modulo">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form
                    id="form_editar_modulo"
                    action="<?= BASE_URL ?>api/modulo_update.php"
                    method="POST"
                    data-ajax="true"
                    data-validate="true"
                    data-entity="módulo"
                    data-entity-title="Módulo"
                    data-entity-label="módulo"
                    data-success-msg="Módulo actualizado com êxito!"
                    data-existing-msg="Este módulo já está registado na turma."
                    data-error-msg="Erro ao actualizar módulo."
                    data-base-url="<?= BASE_URL ?>">
                    <input type="hidden" name="fm_id" id="fm_id">
                    <input type="hidden" name="turma_id" id="turma_id_hidden">
                    <input type="hidden" name="modulo_id" id="modulo_id_hidden">

                    <div class="form-grid">
                        <label class="form-field">
                            <span>Turma</span>
                            <select name="turma_id_disabled" id="turma_id" disabled>
                                <option value="">Seleccione a turma</option>
                            </select>
                        </label>

                        <label class="form-field">
                            <span>Módulo</span>
                            <select name="modulo_id_disabled" id="modulo_id" disabled>
                                <option value="">Seleccione um módulo</option>
                            </select>
                        </label>

                        <label class="form-field">
                            <span>Tipo</span>
                            <input type="text" id="tipo_modulo" readonly>
                        </label>

                        <label class="form-field">
                            <span>Nome</span>
                            <input type="text" id="modulo_info" readonly>
                        </label>

                        <label class="form-field">
                            <span>Formador</span>
                            <select name="formador_id" id="formador_id" required>
                                <option value="">Seleccione um formador</option>
                            </select>
                        </label>

                        <label class="form-field">
                            <span>Data de início</span>
                            <input type="date" name="data_inicio" id="data_inicio" required>
                        </label>

                        <label class="form-field">
                            <span>Data de conclusão</span>
                            <input type="date" name="data_fim" id="data_fim" required>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" id="btn_cancelar_editar_modulo">Voltar</button>
                        <button type="submit" class="btn">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>