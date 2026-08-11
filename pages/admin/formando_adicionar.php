<?php 
require_once __DIR__ . '/../../config/init.php';

if ($_SESSION['nivel_acesso'] != 1) { 
    header("Location: " . BASE_URL . "index.php"); 
    exit; 
}

$page_css = [
    'forms.css',
    'modules/breadcrumbs.css',
    'pages/formando_adicionar.css'
];

$page_js = [
    'modules/notifications.js',
    'modules/masks.js',
    'modules/forms.js',
    'modules/encarregado.js',
    'modules/codegen.js',
    'modules/validation.js',
    'pages/formando_adicionar.js'
];


require_once __DIR__ . '/../../includes/header.php'; 
require_once __DIR__ . '/../../includes/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Adicionar Formando</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Formandos', 'url' => null],
                ['label' => 'Adicionar Formando', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card">
            <h2 class="section-title">Preencher dados do formando</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <form 
                action="<?= BASE_URL ?>api/formando_add.php" 
                method="POST" 
                data-ajax="true" 
                data-validate="true" 
                data-entity="formando" 
                data-codegen-url="<?= BASE_URL ?>api/gerar_codigo.php" data-turmas-url="<?= BASE_URL ?>api/buscar_turmas.php"
                data-base-url="<?= BASE_URL ?>"
                >
                <div class="form-grid">
                    <label class="form-field">
                        <span>Nome completo</span>
                        <input type="text" name="nome_completo" required>
                    </label>

                    <label class="form-field">
                        <span>Número de documento</span>
                        <input type="text" name="numero_documento" required>
                    </label>


                    <div class="form-field">
                        <span>Sexo</span>
                        <div class="radio-group">
                            <label><input type="radio" name="sexo" value="Masculino" required> Masculino</label>
                            <label><input type="radio" name="sexo" value="Feminino" required> Feminino</label>
                        </div>
                    </div>

                    <label class="form-field">
                        <span>Data de nascimento</span>
                        <input
                            type="text"
                            name="data_nascimento"
                            data-input-mask="date"
                            data-mask-message="Informe a data de nascimento no formato dd.mm.aaaa."
                            inputmode="numeric"
                            maxlength="10"
                            placeholder="dd.mm.aaaa"
                            autocomplete="off"
                        >
                    </label>

                    <label class="form-field">
                        <span>Contacto</span>
                        <input
                            type="text"
                            name="contacto"
                            data-input-mask="mz-contact"
                            data-mask-message="Informe o contacto no formato +258(XX)XXX-XXXX."
                            inputmode="numeric"
                            maxlength="16"
                            placeholder="+258(XX)XXX-XXXX"
                            autocomplete="off"
                        >
                    </label>

                    <label class="form-field">
                        <span>Código do formando</span>
                        <input
                            type="text"
                            name="codigo_formando"
                            data-input-mask="formando-code"
                            data-mask-message="Informe o cÃ³digo do formando no formato 100XXXXXX."
                            inputmode="numeric"
                            maxlength="9"
                            placeholder="100XXXXXX"
                            autocomplete="off"
                            required
                        >
                    </label>

                    <label class="form-field">
                        <span>Ano de ingresso</span>
                        <input type="number" required name="ano_ingresso" min="2000" max="2100" placeholder="Ex: 2024">
                    </label>

                    <label class="form-field">
                        <span>Ano de conclusão</span>
                        <input type="number" required name="ano_conclusao" min="2000" max="2100" placeholder="Ex: 2026">
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
                        <span>Turma</span>
                        <select name="turma_id" id="turma_id" required>
                            <option value="">Seleccione a turma</option>
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Email</span>
                        <div class="input-group">
                            <input type="email" name="email" data-codegen-email required>
                            <button type="button" class="btn btn-codegen" title="Gerar código" data-codegen-btn>
                                <i class="fa-solid fa-rotate"></i>
                                <span class="btn-text">Gerar código</span>
                            </button>
                        </div>
                        <small class="field-msg" id="email_msg" data-codegen-msg></small>
                    </label>

                    <input type="hidden" name="nivel_destinado" value="3">

                    <label class="form-field">
                        <span>Código de convite</span>
                        <input type="text" name="codigo_convite" id="codigo_convite" data-codegen-output readonly>
                    </label>

                    <input type="hidden" name="codigo_gerado" id="codigo_gerado" data-codegen-hidden>
                </div>

                <h2 class="section-title section-divider">Encarregado de Educação (opcional)</h2>

                <div class="form-grid">
                    <label class="form-field">
                        <span>Email do encarregado</span>
                        <div class="input-group">
                            <input type="email" name="encarregado_email" data-enc-email>
                            <button type="button" class="btn btn-codegen" data-enc-btn title="Gerar código">
                                <i class="fa-solid fa-rotate"></i>
                                <span class="btn-text">Gerar código</span>
                            </button>
                        </div>
                        <small class="field-msg" data-enc-msg></small>
                    </label>

                    <label class="form-field">
                        <span>Código de convite</span>
                        <input type="text" name="encarregado_codigo" data-enc-codigo readonly>
                    </label>

                    <input type="hidden" name="encarregado_id" data-enc-id>

                    <label class="form-field">
                        <span>Nome completo</span>
                        <input type="text" name="encarregado_nome" data-enc-nome>
                    </label>

                    <label class="form-field">
                        <span>Grau de parentesco</span>
                        <select name="encarregado_tipo" data-enc-grau>
                            <option value="">Seleccione o grau de parentesco</option>
                            <option value="Pai">Pai</option>
                            <option value="Mãe">Mãe</option>
                            <option value="Tutor">Representante legal</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Contacto</span>
                        <input
                            type="text"
                            name="encarregado_contacto"
                            data-enc-contacto
                            data-input-mask="mz-contact"
                            data-mask-message="Informe o contacto no formato +258(XX)XXX-XXXX."
                            inputmode="numeric"
                            maxlength="16"
                            placeholder="+258(XX)XXX-XXXX"
                            autocomplete="off"
                        >
                    </label>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-outline">Limpar</button>
                    <button type="submit" class="btn" name="salvar_formando">Adicionar</button>
                </div>
            </form>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
