<?php
require_once __DIR__ . '/../../config/init.php';

require_once __DIR__ . '/../../includes/auth_admin_formador.php';

$page_css = [
    'forms.css',
    'tables.css',
    'modules/breadcrumbs.css',
    'pages/pauta_final.css'
];

$page_js = [
    'modules/notifications.js',
    'pages/pauta_final.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Pauta Final</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/formador/dashboard.php'],
                ['label' => 'Avaliações', 'url' => null],
                ['label' => 'Pauta Final', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card pauta-page"
            data-base-url="<?= BASE_URL ?>"
            data-turmas-url="<?= BASE_URL ?>api/turmas_select.php"
            data-modulos-url="<?= BASE_URL ?>api/presencas_modulos.php"
            data-contexto-url="<?= BASE_URL ?>api/pauta_final_contexto.php"
            data-imprimir-url="<?= BASE_URL ?>api/pauta_final_imprimir.php"
        >
            <h2 class="section-title">Dados de Pauta</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="form-grid">
                <label class="form-field">
                    <span>Turma</span>
                    <select id="pauta_turma">
                        <option value="">Seleccione a turma</option>
                    </select>
                </label>

                <label class="form-field">
                    <span>Módulo</span>
                    <select id="pauta_modulo">
                        <option value="">Seleccione o módulo</option>
                    </select>
                </label>

                <div class="form-field pauta-acao">
                    <span>&nbsp;</span>
                    <button type="button" class="btn-contacto" id="pauta_gerar">
                        <i class="fa-solid fa-clipboard-check"></i>
                        <span>Gerar pauta</span>
                    </button>
                </div>
            </div>

            <div id="pauta_info" class="pauta-info"></div>
            <div id="pauta_empty_state" class="pauta-empty-state" style="display:none;">
                <div class="pauta-empty-illustration" id="pauta_empty_illustration"></div>
                <h3 class="pauta-empty-title">Pauta ainda não disponível</h3>
                <p class="pauta-empty-text" id="pauta_empty_text">
                    Esta pauta só fica disponível quando todas as avaliações sumativas do módulo estiverem realizadas.
                </p>
            </div>

            <template id="pauta_empty_light_tpl">
                <?php readfile(__DIR__ . '/../../includes/components/illustrations/pauta_indisponivel_light.fragment.html'); ?>
            </template>

            <template id="pauta_empty_dark_tpl">
                <?php readfile(__DIR__ . '/../../includes/components/illustrations/pauta_indisponivel_dark.fragment.html'); ?>
            </template>

            <div id="pauta_header" class="pauta-header" style="display:none;">
                <div>
                    <div class="pauta-title">Pauta Final</div>
                    <div class="pauta-meta" id="pauta_meta"></div>
                </div>
                <button type="button" class="btn btn-outline btn-table" id="pauta_imprimir">
                    <i class="fa-solid fa-print"></i>
                    <span class="btn-text">Imprimir</span>
                </button>
            </div>

            <div id="pauta_tabela_wrap" class="table-wrap" style="display:none;">
                <table class="data-table" id="pauta_tabela">
                    <thead id="pauta_head"></thead>
                    <tbody id="pauta_body">
                        <tr>
                            <td colspan="5" class="empty-row">Nenhuma pauta disponível</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
