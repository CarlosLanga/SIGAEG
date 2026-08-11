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
    'modules/masks.js',
    'modules/forms.js',
    'modules/codegen.js',
    'modules/validation.js',
    'modules/table-manager.js',
    'pages/formadores_gerir.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Gerir Formadores</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Formadores', 'url' => null],
                ['label' => 'Gerir Formadores', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card"
            data-list-url="<?= BASE_URL ?>api/formadores_gerir_listar.php"
            data-base-url="<?= BASE_URL ?>">
            <h2 class="section-title">Lista de Formadores</h2>

            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="table-toolbar">
                <div class="toolbar-left">
                    <label class="filter-field">
                        <span>Pesquisar</span>
                        <input type="text" id="pesquisa_formador">
                    </label>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="tabela_formadores">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Sexo</th>
                            <th>Código</th>
                            <th>Turmas</th>
                            <th>Módulos</th>
                            <th>Estado</th>
                            <th>Acção</th>
                        </tr>
                    </thead>
                    <tbody id="lista_formadores">
                        <tr>
                            <td colspan="8" class="empty-row">Nenhum formador encontrado</td>
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

        <div id="modal_confirmar_remocao_formador" class="confirm-modal-overlay" style="display:none;">
            <div class="confirm-modal-card" role="dialog" aria-modal="true" aria-labelledby="confirm_formador_title">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <h3 id="confirm_formador_title">Remover formador?</h3>
                <p>Tem a certeza que deseja remover este formador? Esta acção é irreversível.</p>
                <div class="confirm-modal-actions">
                    <button type="button" class="btn" id="btn_cancelar_remocao_formador">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btn_confirmar_remocao_formador">Remover</button>
                </div>
            </div>
        </div>

        <!-- Modal de editar -->
        <div class="modal" id="modal_editar_formador">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Editar Formador</h2>
                    <button type="button" class="btn btn-outline btn-icon" id="btn_fechar_editar_formador">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="info-panels">
                    <div class="info-panel">
                        <h3>
                            Turmas
                            <span class="hint">?
                                <span class="hint-tooltip">
                                    <div class="legend-row">
                                        <span class="badge badge-dt">DT</span>
                                        <span>Director de Turma</span>
                                    </div>
                                    <div class="legend-row">
                                        <span class="badge badge-docente">Formador</span>
                                        <span>Turma a leccionar</span>
                                    </div>
                                </span>
                            </span>
                        </h3>
                        <div class="badge-list" id="formador_turmas_badges"></div>
                    </div>

                    <div class="info-panel">
                        <h3>
                            Módulos
                            <span class="hint">?
                                <span class="hint-tooltip">
                                    <div class="legend-row">
                                        <span class="badge badge-mod-done">Concluído</span>
                                        <span>Módulo findado</span>
                                    </div>
                                    <div class="legend-row">
                                        <span class="badge badge-mod-active">Em vigência</span>
                                        <span>Módulo a decorrer</span>
                                    </div>
                                    <div class="legend-row">
                                        <span class="badge badge-mod-start">Por iniciar</span>
                                        <span>Módulo pendente</span>
                                    </div>
                                </span>
                            </span>
                        </h3>
                        <div class="badge-list" id="formador_modulos_badges"></div>
                    </div>
                </div>

                <form
                    id="form_editar_formador"
                    action="<?= BASE_URL ?>api/formador_update.php"
                    method="POST"
                    data-ajax="true"
                    data-validate="true"
                    data-entity="formador"
                    data-entity-title="Formador"
                    data-entity-label="formador"
                    data-success-msg="Formador actualizado com êxito!"
                    data-error-msg="Erro ao actualizar formador."
                    data-codegen-url="<?= BASE_URL ?>api/gerar_codigo.php"
                    data-base-url="<?= BASE_URL ?>">
                    <input type="hidden" name="id" id="formador_id">

                    <div class="form-grid">
                        <label class="form-field">
                            <span>Nome completo</span>
                            <input type="text" name="nome_completo" id="formador_nome" required>
                        </label>

                        <div class="form-field">
                            <span>Sexo</span>
                            <div class="radio-group">
                                <label><input type="radio" name="sexo" value="Masculino" required> Masculino</label>
                                <label><input type="radio" name="sexo" value="Feminino" required> Feminino</label>
                            </div>
                        </div>

                        <label class="form-field">
                            <span>Código do formador</span>
                            <input type="text" name="codigo_formador" id="codigo_formador" required>
                        </label>

                        <label class="form-field">
                            <span>Contacto</span>
                            <input
                                type="text"
                                name="telefone"
                                id="telefone"
                                data-input-mask="mz-contact"
                                data-mask-message="Informe o contacto no formato +258(XX)XXX-XXXX."
                                inputmode="numeric"
                                maxlength="16"
                                placeholder="+258(XX)XXX-XXXX"
                                autocomplete="off">
                        </label>

                        <label class="form-field">
                            <span>Tí­tulo</span>
                            <select name="titulo" id="titulo">
                                <option value="">Seleccione o tí­tulo</option>
                                <option value="Dr.">Dr.</option>
                                <option value="Eng.">Eng.</option>
                                <option value="MSc">MSc</option>
                            </select>
                        </label>

                        <label class="form-field">
                            <span>Especialidade</span>
                            <input type="text" name="especialidade" id="especialidade">
                        </label>

                        <div class="form-field full">
                            <span>Qualificação</span>
                            <div class="radio-group" id="cursos_checkboxes">
                                <?php
                                require_once __DIR__ . '/../../config/db.php';
                                $cursos = [];
                                if ($conn) {
                                    $res = $conn->query("SELECT id, nome_curso FROM cursos ORDER BY nome_curso ASC");
                                    if ($res) {
                                        while ($row = $res->fetch_assoc()) $cursos[] = $row;
                                    }
                                }
                                foreach ($cursos as $c): ?>
                                    <label>
                                        <input type="checkbox" name="cursos[]" value="<?= $c['id'] ?>">
                                        <?= $c['nome_curso'] ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <label class="form-field">
                            <span>Email</span>
                            <div class="input-group">
                                <input type="email" name="email" id="email" data-codegen-email required>
                                <button type="button" class="btn btn-codegen" title="Gerar código" data-codegen-btn>
                                    <i class="fa-solid fa-rotate"></i>
                                    <span class="btn-text">Gerar código</span>
                                </button>
                            </div>
                            <small class="field-msg" data-codegen-msg></small>
                        </label>

                        <input type="hidden" name="nivel_destinado" value="2">

                        <label class="form-field">
                            <span>Código de convite</span>
                            <input type="text" name="codigo_convite" data-codegen-output readonly>
                        </label>

                        <input type="hidden" name="codigo_gerado" data-codegen-hidden>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" id="btn_cancelar_editar_formador">Voltar</button>
                        <button type="submit" class="btn">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>