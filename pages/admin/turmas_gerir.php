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
    'pages/turmas_gerir.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Gerir Turmas</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Turmas', 'url' => null],
                ['label' => 'Gerir Turmas', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card"
            data-list-url="<?= BASE_URL ?>api/turmas_gerir_listar.php"
            data-base-url="<?= BASE_URL ?>"
        >
            <h2 class="section-title">Informações de Turma</h2>
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
                <table class="data-table" id="tabela_turmas">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Turma</th>
                            <th>Turno</th>
                            <th>Certificado Vocacional</th>
                            <th>Nº de formandos</th>
                            <th>Director de Turma</th>
                            <th>Ano lectivo</th>
                            <th>Acção</th>
                        </tr>
                    </thead>
                    <tbody id="lista_turmas">
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

        <div id="modal_confirmar_remocao_turma" class="confirm-modal-overlay" style="display:none;">
            <div class="confirm-modal-card" role="dialog" aria-modal="true" aria-labelledby="confirm_turma_title">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <h3 id="confirm_turma_title">Remover turma?</h3>
                <p>Tem a certeza que deseja remover esta turma? Esta acção é irreversível.</p>
                <div class="confirm-modal-actions">
                    <button type="button" class="btn" id="btn_cancelar_remocao_turma">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btn_confirmar_remocao_turma">Remover</button>
                </div>
            </div>
        </div>

        <!-- Ok vamos fazer isso. Só que a minha regra é. Eu vou guardar modulos predifinidos na base de dados manualmente no php my admin. Então o propósito dessa página é Iniciar Módulo. Deve ter esses campos. Turma (vai listar todas as turmas e - sigla turno). Dependendo da turma seleccionada, (que possui a uma qualificação/curso) vai mostrar um campo select com label Módulo da qualificação daquela turma (que tem as siglas do módulo que estão na BD), e do lado um campo maior não editável e disabled (que dependendo da sigla do do módulo seleccionado vai mostrar pegando da BD o código do módulo e hifen (-) o nome em extenso do módulo). A seguir, um select que mostra escolhe os formador da qualificação daquela turma. Depois disso um campo para Data de Início e outro campo para data de conclusão. Então ao adicionar o módulo -->
        <!-- MODAL EDITAR TURMA -->
        <div class="modal" id="modal_editar_turma">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Editar Turma</h2>
                    <button type="button" class="btn btn-outline" id="btn_fechar_editar_turma">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div id="form-message" class="form-message" style="display: none;"></div>

                <form
                    id="form_editar_turma"
                    action="<?= BASE_URL ?>api/turma_update.php"
                    method="POST"
                    data-ajax="true"
                    data-validate="true"
                    data-entity="turma"
                    data-entity-title="Turma"
                    data-entity-label="turma"
                    data-success-msg="Turma actualizada com êxito!"
                    data-existing-msg="Turma já existente!"
                    data-error-msg="Erro ao actualizar turma."
                    data-base-url="<?= BASE_URL ?>"
                >
                    <input type="hidden" name="turma_id" id="turma_id">

                    <div class="form-grid">
                        <label class="form-field">
                            <span>Ano lectivo</span>
                            <input type="number" name="ano_lectivo" id="ano_lectivo" required placeholder="Ex: 2025" min="2000" max="2100">
                        </label>

                        <label class="form-field">
                            <span>Certificado Vocacional</span>
                            <select name="certificado_vocacional" id="certificado_vocacional" required>
                                <option value="">Seleccione um CV</option>
                                <option value="CV3">CV3</option>
                                <option value="CV4">CV4</option>
                                <option value="CV5">CV5</option>
                            </select>
                        </label>

                        <label class="form-field">
                            <span>Qualificação</span>
                            <select name="curso_id" id="curso_id" required>
                                <option value="">Seleccione uma qualificação</option>
                            </select>
                        </label>

                        <label class="form-field">
                            <span>Turno</span>
                            <select name="turno_id" id="turno_id" required>
                                <option value="">Seleccione o turno</option>
                                <option value="1">Curso Diurno</option>
                                <option value="2">Curso Nocturno</option>
                            </select>
                        </label>

                        <label class="form-field">
                            <span>Director de turma</span>
                            <select name="dt_id" id="formador_id" required>
                                <option value="">Seleccione um formador</option>
                            </select>
                        </label>

                        <label class="form-field">
                            <span>Secção</span>
                            <input type="text" name="seccao" id="seccao" maxlength="1" required placeholder="Ex: A">
                        </label>

                        <label class="form-field">
                            <span>Nome da turma</span>
                            <input type="text" name="nome_turma" id="nome_turma" readonly>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" id="btn_cancelar_editar_turma">Voltar</button>
                        <button type="submit" class="btn">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
