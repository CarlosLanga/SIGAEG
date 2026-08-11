<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../config/db.php';

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
    'modules/masks.js',
    'modules/forms.js',
    'modules/validation.js',
    'modules/codegen.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$cursos = [];
if ($conn) {
    $res = $conn->query("SELECT id, nome_curso FROM cursos ORDER BY nome_curso ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) $cursos[] = $row;
    }
}
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Adicionar Formador</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Formadores', 'url' => null],
                ['label' => 'Adicionar Formador', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card">
            <h2 class="section-title">Preencher dados do formador</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <form
                action="<?= BASE_URL ?>api/formador_add.php"
                method="POST"
                data-ajax="true"
                data-validate="true"
                data-entity="formador"
                data-entity-title="Formador"
                data-entity-label="formador"
                data-codegen-url="<?= BASE_URL ?>api/gerar_codigo.php"
                data-base-url="<?= BASE_URL ?>"
            >
                <div class="form-grid">
                    <label class="form-field">
                        <span>Nome completo</span>
                        <input type="text" name="nome_completo" required>
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
                        <input type="text" name="codigo_formador" required placeholder="Ex: 10020xxxxxx">
                    </label>

                    <label class="form-field">
                        <span>Contacto</span>
                        <input
                            type="text"
                            name="telefone"
                            data-input-mask="mz-contact"
                            data-mask-message="Informe o contacto no formato +258(XX)XXX-XXXX."
                            inputmode="numeric"
                            maxlength="16"
                            placeholder="+258(XX)XXX-XXXX"
                            autocomplete="off"
                        >
                    </label>

                    <label class="form-field">
                        <span>Título</span>
                        <select name="titulo">
                            <option value="">Seleccione o título</option>
                            <option value="Dr.">Dr.</option>
                            <option value="Eng.">Eng.</option>
                            <option value="MSc">MSc</option>
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Especialidade</span>
                        <input type="text" name="especialidade" placeholder="Ex: Redes / Programação">
                    </label>

                    <div class="form-field full">
                        <span>Qualificação</span>
                        <div class="radio-group">
                            <?php foreach ($cursos as $c): ?>
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
                            <input type="email" name="email" data-codegen-email required>
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
                    <button type="reset" class="btn btn-outline">Limpar</button>
                    <button type="submit" class="btn">Adicionar</button>
                </div>
            </form>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>


