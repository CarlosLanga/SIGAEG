<?php
require_once __DIR__ . '/../config/init.php';

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
if (!in_array($nivel, [1, 2], true)) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_css = [
    'forms.css',
    'modules/breadcrumbs.css',
    'pages/ficheiros.css'
];

$page_js = [
    'modules/notifications.js',
    'pages/ficheiros.js'
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Ficheiros</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . ($nivel === 1 ? 'pages/admin/dashboard.php' : 'pages/formador/dashboard.php')],
                ['label' => 'Ficheiros', 'url' => null],
            ];
            require __DIR__ . '/../includes/components/breadcrumbs.php';
            ?>
        </div>

        <div id="modal_confirmar_remocao_ficheiro" class="confirm-modal-overlay" style="display:none;">
            <div class="confirm-modal-card" role="dialog" aria-modal="true" aria-labelledby="confirm_ficheiro_title">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <h3 id="confirm_ficheiro_title">Remover ficheiro?</h3>
                <p>Tem a certeza que deseja remover este ficheiro? Esta acção é irreversível.</p>
                <div class="confirm-modal-actions">
                    <button type="button" class="btn" id="btn_cancelar_remocao_ficheiro">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btn_confirmar_remocao_ficheiro">Remover</button>
                </div>
            </div>
        </div>

        <section class="ficheiros-page"
            data-base-url="<?= BASE_URL ?>"
            data-nivel="<?= $nivel ?>"
            data-list-url="<?= BASE_URL ?>api/ficheiros_listar.php"
            data-upload-url="<?= BASE_URL ?>api/ficheiros_upload.php"
            data-turmas-url="<?= BASE_URL ?>api/ficheiros_turmas.php"
            data-download-url="<?= BASE_URL ?>api/ficheiros_download.php"
        >
            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="ficheiros-toolbar">
                <div class="ficheiros-search">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" id="ficheiros_search_input" placeholder="Pesquisar ficheiros...">
                </div>
                <?php if (in_array($nivel, [1, 2], true)): ?>
                <button type="button" class="ficheiro-btn ficheiro-btn-primary" id="btn_abrir_modal_ficheiro">
                    <i class="fa-solid fa-upload"></i>
                    <span>Adicionar ficheiro</span>
                </button>
                <?php endif; ?>
            </div>

            <div class="ficheiros-categorias" id="ficheiros_categorias">
                <div class="ficheiros-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>A carregar ficheiros...</span>
                </div>
            </div>

            <div class="ficheiros-modal" id="modal_ficheiro" aria-hidden="true">
                <div class="ficheiros-modal-backdrop" data-close-modal></div>
                <div class="ficheiros-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modal_ficheiro_titulo">
                    <div class="ficheiros-modal-header">
                        <h2 id="modal_ficheiro_titulo">Adicionar ficheiro</h2>
                        <button type="button" class="ficheiro-icon-btn" data-close-modal aria-label="Fechar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form id="form_ficheiro" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="ficheiro_id" value="">
                        <?php if ($nivel === 1): ?>
                        <label class="form-field">
                            <span>Categoria</span>
                            <select name="categoria" id="ficheiro_categoria" required>
                                <option value="">Seleccione a categoria</option>
                                <option value="geral">Ficheiro Geral</option>
                                <option value="turma">Ficheiro de Turma</option>
                            </select>
                        </label>
                        <?php else: ?>
                        <input type="hidden" name="categoria" value="turma">
                        <?php endif; ?>

                        <label class="form-field" id="campo_turma_ficheiro" style="<?= $nivel === 1 ? 'display:none;' : '' ?>">
                            <span><?= $nivel === 1 ? 'Turma' : 'Turma que lecciona' ?></span>
                            <select name="turma_id" id="ficheiro_turma">
                                <option value="">Seleccione a turma</option>
                            </select>
                        </label>

                        <label class="form-field">
                            <span>Título</span>
                            <input type="text" name="titulo" id="ficheiro_titulo" maxlength="150" required>
                        </label>

                        <label class="form-field">
                            <span>Descrição</span>
                            <textarea name="descricao" id="ficheiro_descricao" rows="4" placeholder="Pequena descrição do ficheiro"></textarea>
                        </label>

                        <label class="ficheiro-upload-box">
                            <input type="file" name="ficheiro" id="ficheiro_upload" required>
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <strong id="ficheiro_upload_label">Seleccionar ficheiro</strong>
                            <span>PDF, Office, imagens, ZIP ou TXT até 20MB</span>
                        </label>

                        <div class="ficheiros-modal-actions">
                            <button type="button" class="ficheiro-btn ficheiro-btn-secondary" data-close-modal>Cancelar</button>
                            <button type="submit" class="ficheiro-btn ficheiro-btn-primary" id="btn_publicar_ficheiro">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>Publicar</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div>
