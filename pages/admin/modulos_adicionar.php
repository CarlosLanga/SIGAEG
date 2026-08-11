<?php
require_once __DIR__ . '/../../config/init.php';

if ($_SESSION['nivel_acesso'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_css = [
    'forms.css',
    'modules/breadcrumbs.css'
];

$page_js = [
    'modules/notifications.js',
    'modules/forms.js',
    'modules/validation.js',
    'pages/modulos_adicionar.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Iniciar Módulo</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Módulos', 'url' => null],
                ['label' => 'Iniciar Módulo', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card">
            <h2 class="section-title">Dados do módulo</h2>
            <div id="form-message" class="form-message" style="display:none;"></div>

            <form
                action="<?= BASE_URL ?>api/modulo_iniciar.php"
                method="POST"
                data-ajax="true"
                data-validate="true"
                data-entity="módulo"
                data-entity-title="Módulo"
                data-entity-label="módulo"
                data-success-msg="Módulo iniciado com êxito!"
                data-existing-msg="Este módulo já está registado na turma."
                data-error-msg="Erro ao iniciar módulo."
                data-base-url="<?= BASE_URL ?>"
            >
                <div class="form-grid">
                    <label class="form-field">
                        <span>Turma</span>
                        <select name="turma_id" id="turma_id" required>
                            <option value="">Seleccione a turma</option>
                        </select>
                        <small class="field-msg" id="turma_hint"></small>
                    </label>

                    <label class="form-field">
                        <span>Tipo de módulo</span>
                        <select name="tipo_modulo" id="tipo_modulo" required>
                            <option value="">Seleccione o tipo</option>
                            <option value="generico">Genérico</option>
                            <option value="vocacional">Vocacional</option>
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Módulo</span>
                        <select name="modulo_id" id="modulo_id" required>
                            <option value="">Seleccione um módulo</option>
                        </select>
                        <small class="field-msg" style="color: var(--text-muted);"><i class="fa-solid fa-info-circle"></i> Módulos desabilitados já estão registados na turma.</small>
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
                    <button type="reset" class="btn btn-outline">Limpar</button>
                    <button type="submit" class="btn">Iniciar</button>
                </div>
            </form>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
