<?php
require_once __DIR__ . '/../../config/init.php';

if ($_SESSION['nivel_acesso'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_css = [
    'forms.css',
    'modules/breadcrumbs.css',
    'pages/horario_adicionar.css'
];

$page_js = [
    'modules/notifications.js',
    'pages/horario_adicionar.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Adicionar Horário</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Horários', 'url' => null],
                ['label' => 'Adicionar Horário', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card horario-page"
            data-base-url="<?= BASE_URL ?>"
            data-turmas-url="<?= BASE_URL ?>api/turmas_select.php"
            data-grade-get-url="<?= BASE_URL ?>api/horario_grade_get.php"
            data-grade-set-url="<?= BASE_URL ?>api/horario_grade_set.php"
            data-grade-remove-url="<?= BASE_URL ?>api/horario_grade_remove.php"
        >
            <h2 class="section-title">Plano de horário da turma</h2>
            <div id="form-message" class="form-message" style="display:none;"></div>

            <div class="form-grid horario-filtros">
                <label class="form-field">
                    <span>Turma</span>
                    <select id="horario_turma_id">
                        <option value="">Seleccione a turma</option>
                    </select>
                </label>

                <label class="form-field">
                    <span>Semestre</span>
                    <select id="horario_semestre">
                        <option value="">Seleccione o semestre</option>
                        <option value="1">I semestre</option>
                        <option value="2">II semestre</option>
                    </select>
                </label>

                <label class="form-field">
                    <span>Bloco</span>
                    <select id="horario_bloco">
                        <option value="">Seleccione o bloco</option>
                        <option value="1">1º bloco</option>
                        <option value="2">2º bloco</option>
                    </select>
                </label>
            </div>

            <div id="horario_grade_wrap" class="horario-grade-wrap" style="display:none;">
                <div class="horario-grade-scroll">
                    <table class="horario-grade-table" id="horario_grade_table"></table>
                </div>
            </div>

            <div id="horario_resumo_wrap" class="horario-resumo-wrap" style="display:none;">
                <h3 class="section-title">Descrição dos módulos</h3>
                <div id="horario_resumo_conteudo" class="horario-resumo-conteudo"></div>
            </div>
        </section>

        <div class="modal" id="modal_horario_slot">
            <div class="modal-content modal-slot-content">
                <div class="modal-header">
                    <h2>Seleccionar módulo</h2>
                    <button type="button" class="btn btn-icon" id="btn_fechar_modal_slot" aria-label="Fechar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <p class="slot-info">
                    Módulos desabilitados já estão concluídos ou em vigência.
                </p>

                <div class="slot-group">
                    <h3>Módulos Genéricos</h3>
                    <div id="slot_modulos_genericos" class="slot-badges"></div>
                </div>

                <div class="slot-group">
                    <h3>Módulos Vocacionais</h3>
                    <div id="slot_modulos_vocacionais" class="slot-badges"></div>
                </div>

                <div class="slot-group">
                    <h3>Outros Módulos</h3>
                    <div id="slot_modulos_outros" class="slot-badges"></div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" id="btn_remover_slot">Remover deste horário</button>
                    <button type="button" class="btn btn-outline" id="btn_cancelar_modal_slot">Fechar</button>
                </div>
            </div>
        </div>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
