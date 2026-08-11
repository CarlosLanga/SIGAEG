<?php
require_once __DIR__ . '/../../config/init.php';

if ($_SESSION['nivel_acesso'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_css = [
    'forms.css',
    'modules/breadcrumbs.css',
    'pages/turma_criar.css'
];

$page_js = [
    'modules/notifications.js',
    'modules/forms.js',
    'pages/turma_criar.js',
    'modules/validation.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Criar Turma</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Turmas', 'url' => null],
                ['label' => 'Criar Turma', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card">
            <h2 class="section-title">Preencher dados da turma</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <form
                action="<?= BASE_URL ?>api/turma_add.php"
                method="POST"
                data-ajax="true"
                data-validate="true"
                data-entity="turma"
                data-entity-title="Turma"
                data-entity-label="turma"
                data-success-msg="Turma criada com êxito!"
                data-existing-msg="Turma já existente!"
                data-error-msg="Erro ao criar turma."
                data-base-url="<?= BASE_URL ?>"
            >
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
                        <select name="formador_id" id="formador_id" required>
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
                    <button type="reset" class="btn btn-outline">Limpar</button>
                    <button type="submit" class="btn">Criar turma</button>
                </div>
            </form>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
