<?php
require_once __DIR__ . '/../../config/init.php';

if ($_SESSION['nivel_acesso'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_css = [
    'forms.css',
    'tables.css',
    'modules/breadcrumbs.css',
    'modules/cards.css',
    'pages/presencas_marcar.css'
];

$page_js = [
    'modules/notifications.js',
    'pages/presencas_marcar.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Marcar Presenças</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Presenças', 'url' => null],
                ['label' => 'Marcar Presenças', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card presencas-page"
            data-base-url="<?= BASE_URL ?>"
            data-turmas-url="<?= BASE_URL ?>api/turmas_select.php"
            data-modulos-url="<?= BASE_URL ?>api/presencas_modulos.php"
            data-contexto-url="<?= BASE_URL ?>api/presencas_contexto.php"
            data-save-url="<?= BASE_URL ?>api/presencas_save.php"
            data-publicar-url="<?= BASE_URL ?>api/presencas_publicar.php"
        >
            <h2 class="section-title">Dados de presença</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="form-grid">
                <label class="form-field">
                    <span>Turma</span>
                    <select id="presenca_turma">
                        <option value="">Seleccione a turma</option>
                    </select>
                </label>

                <label class="form-field">
                    <span>Módulo</span>
                    <select id="presenca_modulo">
                        <option value="">Seleccione o módulo</option>
                    </select>
                    <small class="form-note"><i class="fa fa-solid fa-info-circle"></i> Apenas módulos em vigência estão habilitados.</small>
                </label>

                <label class="form-field">
                    <span>Data</span>
                    <input type="date" id="presenca_data">
                </label>
            </div>

            <div id="presenca_info" class="presenca-info"></div>

            <div id="presenca_cards" class="summary-cards" style="display:none;">
                <div class="summary-card card-formandos">
                    <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="card-info">
                        <h3>Total de formandos</h3>
                        <p class="summary-value" id="card_total">0</p>
                    </div>
                </div>
                <div class="summary-card card-formadores">
                    <div class="card-icon"><i class="fa-solid fa-user-check"></i></div>
                    <div class="card-info">
                        <h3>Presenças</h3>
                        <p class="summary-value" id="card_presentes">0</p>
                    </div>
                </div>
                <div class="summary-card card-turmas">
                    <div class="card-icon"><i class="fa-solid fa-user-xmark"></i></div>
                    <div class="card-info">
                        <h3>Ausências</h3>
                        <p class="summary-value" id="card_ausentes">0</p>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="card-icon card-wd"><i class="fa-solid fa-user-minus"></i></div>
                    <div class="card-info">
                        <h3>WD</h3>
                        <p class="summary-value" id="card_wd">0</p>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="card-icon card-d"><i class="fa-solid fa-user-slash"></i></div>
                    <div class="card-info">
                        <h3>D</h3>
                        <p class="summary-value" id="card_d">0</p>
                    </div>
                </div>
            </div>

            <div id="presenca_slots" class="slots-wrap" style="display:none;">
                <h3 class="section-title">Aulas do horário</h3>
                <div id="slots_lista" class="slots-grid"></div>
            </div>

            <div id="presenca_acoes" class="presenca-acoes" style="display:none;">
                <div class="mass-select">
                    <label class="form-field">
                        <span>Marcar em massa</span>
                        <select id="select_massa">
                            <option value="">Seleccione</option>
                            <option value="Presente">Presente</option>
                            <option value="Ausente">Ausente</option>
                            <option value="WD">WD (Withdrew)</option>
                            <option value="D">D (Desistiu)</option>
                        </select>
                    </label>
                    <button type="button" class="btn btn-outline btn-table" id="btn_aplicar_massa">
                        <i class="fa-solid fa-check"></i>
                        <span class="btn-text">Aplicar</span>
                    </button>
                </div>
                <div class="acoes-direita">
                    <button type="button" class="btn btn-outline btn-table" id="btn_todos_presentes">
                        <i class="fa-solid fa-user-check"></i>
                        <span class="btn-text">Todos presentes</span>
                    </button>
                    <button type="button" class="btn btn-outline btn-table" id="btn_limpar_presencas">
                        <i class="fa-solid fa-eraser"></i>
                        <span class="btn-text">Limpar</span>
                    </button>
                </div>
            </div>

            <div id="presenca_tabela_wrap" class="table-wrap" style="display:none;">
                <table class="data-table" id="tabela_presencas">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Código</th>
                            <th>Situação</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody id="lista_presencas">
                        <tr>
                            <td colspan="5" class="empty-row">Nenhum formando encontrado</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="presenca_footer" class="presenca-footer" style="display:none;">
                <div class="status-badge" id="presenca_estado">Rascunho</div>
                <button type="button" class="btn" id="btn_publicar_presencas">Publicar</button>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
