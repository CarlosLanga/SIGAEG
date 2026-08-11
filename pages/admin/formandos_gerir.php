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
    'pages/horarios_gerir.css',
    'pages/formandos_gerir.css'
];

$page_js = [
    'modules/notifications.js',
    'modules/masks.js',
    'modules/codegen.js',
    'modules/encarregado.js',
    'modules/table-manager.js',
    'html2canvas.min.js',
    'pages/formandos_gerir.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Gerir Formandos</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Formandos', 'url' => null],
                ['label' => 'Gerir Formandos', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card"
            data-list-url="<?= BASE_URL ?>api/formandos_listar.php"
            data-turmas-url="<?= BASE_URL ?>api/turmas_listar.php"
            data-base-url="<?= BASE_URL ?>">

            <h2 class="section-title">Informações de Formando</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <?php
            $filter_id = 'filtro_turma';
            $filter_label = 'Turma';
            $filter_all = 'Todas as turmas';
            $search_id = 'pesquisa_formando';
            $print_id = 'btn_imprimir';
            $print_text = 'Imprimir';
            require __DIR__ . '/../../includes/components/table-toolbar.php';
            ?>

            <div class="table-wrap">
                <table class="data-table" id="tabela_formandos">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Sexo</th>
                            <th>Código</th>
                            <th>Turma</th>
                            <th>Data de registo</th>
                            <th>Estado</th>
                            <th>Acção</th>
                        </tr>
                    </thead>
                    <tbody id="lista_formandos">
                        <tr>
                            <td colspan="8" class="empty-row">Nenhum formando encontrado</td>
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

        <section class="detail-panel" id="painel_detalhe_formando" style="display: none;">
            <div class="page-header">
                <h1>Detalhes do Formando</h1>
                <?php
                $breadcrumbs_detalhe = [
                    ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                    ['label' => 'Formandos', 'url' => null],
                    ['label' => 'Gerir Formandos', 'url' => '#voltar'],
                    ['label' => 'Detalhes do Formando', 'url' => null],
                ];
                ?>
                <nav class="breadcrumbs" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="<?= BASE_URL ?>pages/admin/dashboard.php"><i class="fa-solid fa-home"></i> Início</a></li>
                        <li><span>Formandos</span></li>
                        <li><a href="#voltar" id="breadcrumb_voltar">Gerir Formandos</a></li>
                        <li><span>Detalhes do Formando</span></li>
                    </ol>
                </nav>
            </div>

            <div class="detail-layout">
                <div class="detail-col-left">
                    <div class="card detail-profile-card">
                        <div class="detail-profile-center">
                            <div class="detail-avatar-large" id="detalhe_avatar">
                                <span class="avatar-initials" id="detalhe_iniciais">--</span>
                            </div>
                            <h2 class="detail-name" id="detalhe_nome">—</h2>
                            <div class="detail-tags">
                                <span class="badge badge-formando" id="detalhe_codigo_badge">
                                    <i class="fa-solid fa-id-badge"></i> <span>—</span>
                                </span>
                                <span class="badge" id="detalhe_turma_badge">—</span>
                                <span class="badge" id="detalhe_estado_badge">—</span>
                            </div>
                        </div>

                        <div class="detail-info-rows">
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-venus-mars"></i></div>
                                <span class="detail-row-label">Sexo</span>
                                <span class="detail-row-value" id="detalhe_sexo">—</span>
                            </div>
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-calendar"></i></div>
                                <span class="detail-row-label">Data de nascimento</span>
                                <span class="detail-row-value" id="detalhe_data_nascimento">—</span>
                            </div>
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-phone"></i></div>
                                <span class="detail-row-label">Contacto</span>
                                <span class="detail-row-value" id="detalhe_contacto">—</span>
                            </div>
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-envelope"></i></div>
                                <span class="detail-row-label">Email</span>
                                <span class="detail-row-value" id="detalhe_email">—</span>
                            </div>
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-id-card"></i></div>
                                <span class="detail-row-label">Nº de documento</span>
                                <span class="detail-row-value" id="detalhe_documento">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="card detail-section-card">
                        <h3 class="detail-section-title">Encarregado de Educação</h3>

                        <div class="detail-enc-list" id="detalhe_encarregado_grid">
                            <div class="detail-enc-row">
                                <span class="detail-enc-rel" id="detalhe_enc_parentesco">—</span>
                                <span class="detail-enc-name" id="detalhe_enc_nome">—</span>
                                <span class="detail-enc-contact" id="detalhe_enc_contacto">—</span>
                            </div>
                            <div class="detail-enc-row">
                                <span class="detail-enc-rel">Email</span>
                                <span class="detail-enc-name" id="detalhe_enc_email" style="grid-column: 2 / -1;">—</span>
                            </div>
                        </div>

                        <p class="detail-empty-note" id="detalhe_enc_vazio" style="display: none;">
                            <i class="fa-solid fa-circle-info"></i> Nenhum encarregado associado a este formando.
                        </p>
                    </div>
                </div>
                <div class="detail-col-right">
                    <div class="card detail-section-card">
                        <h3 class="detail-section-title">Informações Académicas</h3>

                        <div class="detail-info-rows">
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-barcode"></i></div>
                                <span class="detail-row-label">Código do formando</span>
                                <span class="detail-row-value detail-value-mono" id="detalhe_codigo">—</span>
                            </div>
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-users-rectangle"></i></div>
                                <span class="detail-row-label">Turma</span>
                                <span class="detail-row-value" id="detalhe_turma">—</span>
                            </div>
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-certificate"></i></div>
                                <span class="detail-row-label">Certificado Vocacional</span>
                                <span class="detail-row-value" id="detalhe_cv">—</span>
                            </div>
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-right-to-bracket"></i></div>
                                <span class="detail-row-label">Ano de ingresso</span>
                                <span class="detail-row-value" id="detalhe_ano_ingresso">—</span>
                            </div>
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></div>
                                <span class="detail-row-label">Ano de conclusão</span>
                                <span class="detail-row-value" id="detalhe_ano_conclusao">—</span>
                            </div>
                            <div class="detail-row">
                                <div class="detail-row-icon"><i class="fa-solid fa-clock"></i></div>
                                <span class="detail-row-label">Data de registo</span>
                                <span class="detail-row-value" id="detalhe_data_registo">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="card detail-section-card detail-horario-card">
                        <h3 class="detail-section-title">Horário de Hoje</h3>
                        <div class="detail-horario-meta" id="detalhe_horario_meta">A carregar horário...</div>
                        <div class="detail-horario-list" id="detalhe_horario_list"></div>
                        <div class="detail-horario-actions" id="detalhe_horario_actions">
                            <button type="button" class="btn-text" id="btn_horario_toggle_all" style="display:none;">Mostrar tudo</button>
                            <button type="button" class="btn-text" id="btn_ver_horario_turma" disabled>Ver horário</button>
                        </div>
                    </div>

                    <div class="card detail-section-card detail-frequencia-card">
                        <div class="detail-frequencia-header">
                            <h3 class="detail-section-title">Balanço de Módulos</h3>
                            <div class="select-wrap select-wrap-small">
                                <select id="filtro_cv_balanco">
                                    <option value="">A carregar CVs...</option>
                                </select>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                        </div>

                        <div class="frequencia-layout">
                            <div class="frequencia-gauge-wrap">
                                <div class="frequencia-gauge">
                                    <svg viewBox="0 0 100 50" class="gauge-svg">
                                        <path class="gauge-bg" d="M 10 50 A 40 40 0 0 1 90 50" fill="none" stroke-width="12" stroke-linecap="round"></path>
                                        <path class="gauge-fill" id="freq_gauge_path" d="M 10 50 A 40 40 0 0 1 90 50" fill="none" stroke-width="12" stroke-linecap="round" stroke-dasharray="125.6" stroke-dashoffset="125.6"></path>
                                    </svg>
                                    <div class="gauge-content">
                                        <div class="gauge-value"><span id="freq_media_val">0</span>%</div>
                                        <div class="gauge-label">Média Geral</div>
                                    </div>
                                </div>
                                <div class="frequencia-desc" id="freq_desc_text">
                                    A carregar dados de frequência...
                                </div>
                            </div>

                            <div class="frequencia-bars-wrap" id="balanco_modulos_container">
                                <!-- Bars will be generated via JS -->
                            </div>
                            <div class="detail-balanco-actions" style="margin-top:12px; text-align:right;">
                                <button type="button" class="btn-text" id="btn_balanco_ver_tudo" style="display:none;">Ver tudo</button>
                            </div>
                        </div>
                    </div>

                    <div class="card detail-section-card detail-actions-card">
                        <h3 class="detail-section-title">Acções</h3>
                        <div class="detail-actions-grid">
                            <button type="button" class="detail-action-btn detail-action-btn-download" id="btn_baixar_cartao" title="Baixar Cartão de Formando">
                                <i class="fa-solid fa-id-card"></i>
                                <span>Baixar Cartão</span>
                            </button>
                            <button type="button" class="detail-action-btn detail-action-btn-edit" id="btn_editar_detalhe" title="Editar formando">
                                <i class="fa-solid fa-pen"></i>
                                <span>Editar Formando</span>
                            </button>
                            <button type="button" class="detail-action-btn detail-action-btn-delete" id="btn_eliminar_detalhe" title="Eliminar formando">
                                <i class="fa-solid fa-trash-can"></i>
                                <span>Eliminar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- cartao de formando -->
            <button type="button" class="detail-fab-back" id="btn_voltar_floating" title="Voltar para lista">
                <i class="fa-solid fa-arrow-left"></i>
            </button>

            <div id="cartao_template_wrapper" style="position: absolute; left: -9999px; top: -9999px;">
                <div id="cartao_formando" class="id-card">
                    <img src="<?= BASE_URL ?>assets/img/images.jpg" class="id-card-bg" alt="background">

                    <div class="id-card-header">
                        <img src="<?= BASE_URL ?>assets/img/Emblem_of_Mozambique.svg.png" class="id-card-emblem" alt="Emblema">
                        <div class="id-card-header-text">
                            <h4>REPÚBLICA DE MOÇAMBIQUE</h4>
                            <h4>GOVERNO DO DISTRITO DE BOANE</h4>
                            <h4>SERVIÇO DISTRITAL DE EDUCAÇÃO, JUVENTUDE E TECNOLOGIA</h4>
                            <h4 class="institute-name">INSTITUTO INDUSTRIAL E DE COMPUTAÇÃO ARMANDO EMÍLIO GUEBUZA</h4>
                        </div>
                    </div>

                    <div class="id-card-title">CARTÃO DE FORMANDO Nº <span id="cartao_codigo">____</span> / <span id="cartao_ano_ingresso">____</span></div>

                    <div class="id-card-body">
                        <div class="id-card-photo-wrapper">
                            <div class="id-card-photo" id="cartao_foto">
                                <!-- placeholder -->
                            </div>
                        </div>

                        <div class="id-card-info">
                            <div class="id-card-field full-width">
                                <span class="id-card-label">Nome:</span>
                                <span class="id-card-value" id="cartao_nome"></span>
                            </div>

                            <div class="id-card-row">
                                <div class="id-card-field">
                                    <span class="id-card-label">Ano Lectivo:</span>
                                    <span class="id-card-value" id="cartao_ano_lectivo"></span>
                                </div>
                                <div class="id-card-field">
                                    <span class="id-card-label">CV:</span>
                                    <span class="id-card-value" id="cartao_cv"></span>
                                </div>
                                <div class="id-card-field">
                                    <span class="id-card-label">Turma:</span>
                                    <span class="id-card-value" id="cartao_turma"></span>
                                </div>
                            </div>

                            <div class="id-card-row">
                                <div class="id-card-field">
                                    <span class="id-card-label">Nº:</span>
                                    <span class="id-card-value" id="cartao_numero_estudante"></span>
                                </div>
                                <div class="id-card-field flex-grow">
                                    <span class="id-card-label">Curso:</span>
                                    <span class="id-card-value" id="cartao_curso"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="id-card-signature-area">
                        <div class="id-card-signature">
                            <div class="signature-title">O Director</div>
                            <div class="signature-name">Gino Basílio</div>
                            <div class="signature-line"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div id="modal_confirmar_remocao_formando" class="anuncio-modal-overlay" style="display:none;">
            <div class="anuncio-confirm-card" role="dialog" aria-modal="true" aria-labelledby="confirm_formando_title">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <h3 id="confirm_formando_title">Remover formando?</h3>
                <p>Tem a certeza que deseja remover este formando? Esta acção é irreversível.</p>
                <div class="anuncio-confirm-actions">
                    <button type="button" class="btn" id="btn_cancelar_remocao_formando">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btn_confirmar_remocao_formando">Remover</button>
                </div>
            </div>
        </div>

        <!-- Modal de editar -->
        <div class="modal" id="modal_editar_formando">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Editar Formando</h2>
                    <button type="button" class="btn btn-outline" id="btn_fechar_editar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div id="form-message-editar" class="form-message" style="display: none;"></div>

                <form
                    id="form_editar_formando"
                    action="<?= BASE_URL ?>api/formando_update.php"
                    method="POST"
                    data-turmas-url="<?= BASE_URL ?>api/buscar_turmas.php"
                    data-base-url="<?= BASE_URL ?>"
                    data-codegen-url="<?= BASE_URL ?>api/gerar_codigo.php"
                    data-enc-allow-edit="1">
                    <input type="hidden" name="formando_id" id="formando_id">

                    <div class="form-grid">
                        <label class="form-field">
                            <span>Nome completo</span>
                            <input type="text" name="nome_completo" id="editar_nome" required>
                        </label>

                        <label class="form-field">
                            <span>Número de documento</span>
                            <input type="text" name="numero_documento" id="editar_documento" required>
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
                                id="editar_data_nascimento"
                                data-input-mask="date"
                                data-mask-message="Informe a data de nascimento no formato dd.mm.aaaa."
                                inputmode="numeric"
                                maxlength="10"
                                placeholder="dd.mm.aaaa"
                                autocomplete="off">
                        </label>

                        <label class="form-field">
                            <span>Contacto</span>
                            <input
                                type="text"
                                name="contacto"
                                id="editar_contacto"
                                data-input-mask="mz-contact"
                                data-mask-message="Informe o contacto no formato +258(XX)XXX-XXXX."
                                inputmode="numeric"
                                maxlength="16"
                                placeholder="+258(XX)XXX-XXXX"
                                autocomplete="off">
                        </label>

                        <label class="form-field">
                            <span>Código do formando</span>
                            <input
                                type="text"
                                name="codigo_formando"
                                id="editar_codigo_formando"
                                data-input-mask="formando-code"
                                data-mask-message="Informe o cÃ³digo do formando no formato 100XXXXXX."
                                inputmode="numeric"
                                maxlength="9"
                                placeholder="100XXXXXX"
                                autocomplete="off"
                                required>
                        </label>

                        <label class="form-field">
                            <span>Ano de ingresso</span>
                            <input type="number" name="ano_ingresso" id="editar_ano_ingresso" min="2000" max="2100">
                        </label>

                        <label class="form-field">
                            <span>Ano de conclusão</span>
                            <input type="number" name="ano_conclusao" id="editar_ano_conclusao" min="2000" max="2100">
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
                                <input type="email" name="email" id="editar_email" required data-codegen-email>
                                <button type="button" class="btn btn-codegen" title="Gerar código" data-codegen-btn>
                                    <i class="fa-solid fa-rotate"></i>
                                    <span class="btn-text">Gerar código</span>
                                </button>
                            </div>
                            <small class="field-msg" data-codegen-msg></small>
                        </label>

                        <input type="hidden" name="nivel_destinado" value="3">

                        <label class="form-field">
                            <span>Código de convite</span>
                            <input type="text" name="codigo_convite" id="editar_codigo_convite" data-codegen-output readonly>
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
                                autocomplete="off">
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" id="btn_cancelar_editar">Voltar</button>
                        <button type="submit" class="btn" name="actualizar_formando">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Modal de Horário -->
        <div class="modal" id="modal_horario_turma">
            <div class="modal-content modal-view-content">
                <div class="modal-header">
                    <h2>Horário da Turma</h2>
                    <button type="button" class="btn btn-outline" id="btn_fechar_horario">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="horario-meta" id="detalhe_horario_meta_modal"></div>
                <div class="horario-grade-wrap">
                    <div class="horario-grade-scroll">
                        <table class="horario-grade-table" id="detalhe_horario_grid"></table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>