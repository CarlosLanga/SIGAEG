<?php
require_once __DIR__ . '/../../config/init.php';

if ($_SESSION['nivel_acesso'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_css = [
    'forms.css',
    'modules/breadcrumbs.css',
    'tables.css',
    'pages/horario_adicionar.css',
    'pages/horarios_gerir.css'
];

$page_js = [
    'modules/notifications.js',
    'modules/table-manager.js',
    'pages/horarios_gerir.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Gerir Horários</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Horários', 'url' => null],
                ['label' => 'Gerir Horários', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card horario-gestao"
            data-base-url="<?= BASE_URL ?>"
            data-list-url="<?= BASE_URL ?>api/horarios_gerir_listar.php"
            data-turmas-url="<?= BASE_URL ?>api/turmas_select.php"
            data-preview-url="<?= BASE_URL ?>api/horario_grade_preview.php"
            data-publicar-url="<?= BASE_URL ?>api/horario_publicar.php"
            data-delete-url="<?= BASE_URL ?>api/horario_delete.php"
            data-anos-url="<?= BASE_URL ?>api/anos_lectivos.php">
            <h2 class="section-title">Lista de Horários</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="table-toolbar">
                <div class="toolbar-left">
                    <label class="filter-field">
                        <span>Turma</span>
                        <div class="select-wrap">
                            <select id="filtro_turma">
                                <option value="all">Todas as turmas</option>
                            </select>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </label>

                    <label class="filter-field">
                        <span>Pesquisar</span>
                        <input type="text" id="pesquisa_horario">
                    </label>
                </div>
                <div class="toolbar-right">
                    <button class="btn btn-outline btn-table" id="btn_imprimir_global">
                        <i class="fa-solid fa-print"></i>
                        <span class="btn-text">Imprimir</span>
                    </button>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="tabela_horarios">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Turma</th>
                            <th>Director de Turma</th>
                            <th>Semestre</th>
                            <th>Bloco</th>
                            <th>Data de Modificação</th>
                            <th>Estado</th>
                            <th>Acções</th>
                        </tr>
                    </thead>
                    <tbody id="lista_horarios">
                        <tr>
                            <td colspan="8" class="empty-row">Nenhum horário encontrado</td>
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

        <div id="modal_confirmar_remocao_horario" class="confirm-modal-overlay" style="display:none;">
            <div class="confirm-modal-card" role="dialog" aria-modal="true" aria-labelledby="confirm_horario_title">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <h3 id="confirm_horario_title">Remover horário?</h3>
                <p>Tem a certeza que deseja remover este horário? Esta acção é irreversível.</p>
                <div class="confirm-modal-actions">
                    <button type="button" class="btn" id="btn_cancelar_remocao_horario">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btn_confirmar_remocao_horario">Remover</button>
                </div>
            </div>
        </div>

        <div class="modal" id="modal_ver_horario">
            <div class="modal-content modal-view-content">
                <div class="modal-header">
                    <h2>Horário da turma</h2>
                    <button type="button" class="btn btn-outline" id="btn_fechar_ver_horario">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="horario-meta" id="horario_meta"></div>

                <div class="horario-grade-wrap">
                    <div class="horario-grade-scroll">
                        <table class="horario-grade-table" id="horario_preview_table"></table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal" id="modal_imprimir_global">
            <div class="modal-content modal-print-content">
                <div class="modal-header">
                    <h2>Imprimir horários</h2>
                    <button type="button" class="btn btn-outline" id="btn_fechar_imprimir_global">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <p class="slot-info">
                    Seleccione o ano lectivo, semestre e bloco para imprimir.
                </p>

                <div class="form-grid print-grid">
                    <div class="form-field">
                        <span>Ano lectivo</span>
                        <div id="print_anos" class="radio-group"></div>
                    </div>

                    <div class="form-field">
                        <span>Semestre</span>
                        <div class="radio-group">
                            <label class="radio-item">
                                <input type="radio" name="print_semestre" value="1"> I semestre </label>
                            <label class="radio-item">
                                <input type="radio" name="print_semestre" value="2"> II semestre</label>
                        </div>
                    </div>

                    <div class="form-field">
                        <span>Bloco</span>
                        <div class="radio-group">
                            <label class="radio-item">
                                <input type="radio" name="print_bloco" value="1"> 1º bloco</label>
                            <label class="radio-item">
                                <input type="radio" name="print_bloco" value="2"> 2º bloco</label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" id="btn_cancelar_imprimir_global">Voltar</button>
                    <button type="button" class="btn" id="btn_confirmar_imprimir_global">Imprimir</button>
                </div>
            </div>
        </div>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>