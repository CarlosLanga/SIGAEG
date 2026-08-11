<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SESSION['nivel_acesso'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_css = ['forms.css', 'modules/breadcrumbs.css'];
$page_js = ['modules/notifications.js', 'modules/masks.js', 'modules/forms.js', 'modules/validation.js', 'modules/codegen.js'];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Adicionar Administrador</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Inicio', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Utilizadores', 'url' => BASE_URL . 'pages/admin/usuarios_gerir.php'],
                ['label' => 'Adicionar Administrador', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card">
            <h2 class="section-title">Preencher dados do administrador</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <form action="<?= BASE_URL ?>api/administrador_add.php" method="POST" data-ajax="true" data-validate="true" data-entity="administrador" data-entity-title="Administrador" data-entity-label="administrador" data-codegen-url="<?= BASE_URL ?>api/gerar_codigo.php" data-success-msg="Administrador adicionado com sucesso!" data-error-msg="Erro ao adicionar administrador." data-existing-msg="Administrador ja existente!">
                <div class="form-grid">
                    <label class="form-field">
                        <span>Nome completo</span>
                        <input type="text" name="nome_completo" required>
                    </label>

                    <label class="form-field">
                        <span>Contacto</span>
                        <input type="text" name="contacto" data-input-mask="mz-contact" inputmode="numeric" maxlength="16" placeholder="+258(XX)XXX-XXXX" autocomplete="off">
                    </label>

                    <label class="form-field">
                        <span>Email</span>
                        <div class="input-group">
                            <input type="email" name="email" data-codegen-email required>
                            <button type="button" class="btn btn-codegen" title="Gerar codigo" data-codegen-btn>
                                <i class="fa-solid fa-rotate"></i>
                                <span class="btn-text">Gerar codigo</span>
                            </button>
                        </div>
                        <small class="field-msg" data-codegen-msg></small>
                    </label>

                    <input type="hidden" name="nivel_destinado" value="1">

                    <label class="form-field">
                        <span>Codigo de convite</span>
                        <input type="text" name="codigo_gerado" data-codegen-output readonly required>
                        <input type="hidden" data-codegen-hidden>
                    </label>
                </div>

                <div class="form-actions">
                    <a href="<?= BASE_URL ?>pages/admin/usuarios_gerir.php" class="btn btn-outline">Cancelar</a>
                    <button type="submit" class="btn"><i class="fa-solid fa-save"></i> Guardar</button>
                </div>
            </form>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>