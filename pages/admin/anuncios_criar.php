<?php
require_once __DIR__ . '/../../config/init.php';

require_once __DIR__ . '/../../includes/auth_admin_formador.php';

$isFormador = ((int)($_SESSION['nivel_acesso'] ?? 0) === 2);
$dashUrl = match ((int)($_SESSION['nivel_acesso'] ?? 0)) {
    1 => BASE_URL . 'pages/admin/dashboard.php',
    2 => BASE_URL . 'pages/formador/dashboard.php',
    default => BASE_URL . 'pages/admin/dashboard.php',
};
$anunciosGerirUrl = $isFormador
    ? BASE_URL . 'pages/formador/formador_anuncios_gerir.php'
    : BASE_URL . 'pages/admin/anuncios_gerir.php';
$turmasApiUrl = $isFormador
    ? BASE_URL . 'api/anuncios_turmas_formador.php'
    : BASE_URL . 'api/turmas_select.php';

$page_css = [
    'forms.css',
    'modules/breadcrumbs.css',
    'pages/anuncios.css',
    'lib/quill.snow.css',
    'lib/easepick.css'
];

$page_js = [
    'modules/notifications.js',
    'lib/quill.min.js',
    'lib/easepick.umd.min.js',
    'pages/anuncios_criar.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Fazer Anúncio</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => $dashUrl],
                ['label' => 'Anúncios', 'url' => $anunciosGerirUrl],
                ['label' => 'Fazer Anúncio', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="anuncios-creator-page"
            data-save-url="<?= BASE_URL ?>api/anuncios_save.php"
            data-turmas-url="<?= $turmasApiUrl ?>"
            data-modulos-url="<?= BASE_URL ?>api/anuncios_modulos_formador.php"
            data-formador-mode="<?= $isFormador ? '1' : '0' ?>"
            data-author-name="<?= htmlspecialchars($_SESSION['usuario_nome']) ?>"
            data-author-initials="<?= htmlspecialchars(getInitials($_SESSION['usuario_nome'])) ?>">

            <div class="anuncio-form-panel card">
                <h2 class="section-title">Detalhes do Anúncio</h2>
                <form id="form_anuncio" enctype="multipart/form-data">
                    <label class="form-field">
                        <span>Título do Anúncio</span>
                        <input type="text" name="titulo" id="anuncio_titulo" maxlength="100" required placeholder="Ex: Aviso importante de manutenção">
                    </label>

                    <div class="form-grid">
                        <label class="form-field">
                            <span>Prioridade</span>
                            <select name="prioridade" id="anuncio_prioridade">
                                <option value="normal">Normal (Azul)</option>
                                <option value="importante">Importante (Vermelho)</option>
                                <option value="evento">Evento (Verde)</option>
                            </select>
                        </label>

                        <div class="form-field" id="evento_duracao_wrapper" style="display:none; position: relative;">
                            <span>Duração do Evento</span>
                            <button type="button" class="evento-duracao-btn" id="btn_selecionar_duracao">
                                <i class="fa-solid fa-calendar-days"></i>
                                <span id="duracao_label">Seleccionar data(s)</span>
                            </button>
                            <input type="text" id="easepick_input" style="position: absolute; left: 0; bottom: 0; width: 1px; height: 1px; opacity: 0; border: none;" readonly tabindex="-1">
                            <input type="hidden" name="evento_data_inicio" id="evento_data_inicio">
                            <input type="hidden" name="evento_data_fim" id="evento_data_fim">
                        </div>

                        <label class="form-field">
                            <span>Data de Expiração (opcional)</span>
                            <input type="date" name="data_expiracao" id="anuncio_expiracao">
                        </label>

                        <?php if ($isFormador): ?>
                            <input type="hidden" name="publico_alvo" id="anuncio_publico" value="turma">

                            <label class="form-field" id="anuncio_turma_wrapper">
                                <span>Turma Destinada</span>
                                <select name="turma_id" id="anuncio_turma" required>
                                    <option value="">A carregar turmas...</option>
                                </select>
                            </label>

                            <label class="form-field" id="anuncio_modulo_wrapper" style="display:none;">
                                <span>Módulo (opcional)</span>
                                <select name="modulo_id" id="anuncio_modulo">
                                    <option value="">Todos os módulos da turma</option>
                                </select>
                            </label>
                        <?php else: ?>
                            <label class="form-field" id="anuncio_publico_wrapper">
                                <span>Público-Alvo</span>
                                <select name="publico_alvo" id="anuncio_publico">
                                    <option value="todos">Todos</option>
                                    <option value="formadores">Apenas Formadores</option>
                                    <option value="formandos">Apenas Formandos</option>
                                    <option value="encarregados">Apenas Encarregados</option>
                                    <option value="turma">Turma Específica</option>
                                </select>
                            </label>

                            <label class="form-field" id="anuncio_turma_wrapper" style="display:none;">
                                <span>Seleccione a Turma</span>
                                <select name="turma_id" id="anuncio_turma">
                                    <option value="">A carregar turmas...</option>
                                </select>
                            </label>
                        <?php endif; ?>
                    </div>

                    <div class="quill-wrapper">
                        <span>Conteúdo do Anúncio</span>
                        <div id="quill_editor"></div>
                        <input type="hidden" name="descricao" id="anuncio_descricao">
                    </div>

                    <div class="form-field anexo-field">
                        <span>Anexo (opcional)</span>
                        <label class="anexo-upload-area" id="anexo_upload_area">
                            <input type="file" name="anexo" id="anuncio_anexo" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.jpg,.jpeg,.png">
                            <div class="anexo-placeholder" id="anexo_placeholder">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>Clique para seleccionar um ficheiro</span>
                                <small>PDF, DOC, XLS, ZIP, JPG, PNG (máx. 10MB)</small>
                            </div>
                            <div class="anexo-selected" id="anexo_selected" style="display:none;">
                                <i class="fa-solid fa-file-lines"></i>
                                <span id="anexo_nome_display"></span>
                                <button type="button" class="anexo-remove-btn" id="anexo_remove" title="Remover anexo">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn btn-outline" id="btn_reset">Limpar</button>
                        <button type="submit" class="btn" id="btn_publish">
                            <i class="fa-solid fa-paper-plane"></i> Publicar
                        </button>
                    </div>
                </form>
            </div>

            <div class="anuncio-preview-panel">
                <div class="preview-header-title">Pré-visualização</div>

                <div id="preview_empty" class="empty-preview">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <p>Pré-visualize aqui o anúncio</p>
                </div>

                <div id="preview_card" class="anuncio-card" style="display: none;">
                    <div class="anuncio-header">
                        <div class="anuncio-badges">
                            <span id="preview_badge_prioridade" class="anuncio-badge" style="display:none;"></span>
                            <span id="preview_badge_alvo" class="anuncio-badge badge-alvo">Todos</span>
                        </div>
                    </div>
                    <div class="anuncio-body">
                        <h3 id="preview_title">Sem título</h3>
                        <div id="preview_event_dates" class="anuncio-event-dates" style="display:none;">
                            <i class="fa-solid fa-calendar-days"></i>
                            <span id="preview_event_dates_text"></span>
                        </div>
                        <div id="preview_content" class="anuncio-content"></div>
                        <div id="preview_attachment" class="anuncio-attachment" style="display:none;">
                            <i class="fa-solid fa-paperclip"></i>
                            <span id="preview_attachment_name">ficheiro.pdf</span>
                        </div>
                    </div>
                </div>

            </div>

        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
