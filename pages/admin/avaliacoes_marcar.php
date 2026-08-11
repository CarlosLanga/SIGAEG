<?php 
require_once __DIR__ . '/../../config/init.php';

if ($_SESSION['nivel_acesso'] != 1) { 
    header("Location: " . BASE_URL . "index.php"); 
    exit; 
}

$page_css = [
    'forms.css',
    'tables.css',
    'modules/breadcrumbs.css',
    'pages/avaliacoes_marcar.css'
];

$page_js = [
    'modules/notifications.js',
    'pages/avaliacoes_marcar.js'
];

require_once __DIR__ . '/../../includes/header.php'; 
require_once __DIR__ . '/../../includes/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Marcar Avaliações</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Avaliações', 'url' => null],
                ['label' => 'Marcar Avaliações', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card avaliacoes-page"
            data-base-url="<?= BASE_URL ?>"
            data-turmas-url="<?= BASE_URL ?>api/turmas_select.php"
            data-modulos-url="<?= BASE_URL ?>api/presencas_modulos.php"
            data-listar-url="<?= BASE_URL ?>api/avaliacoes_listar.php"
            data-save-url="<?= BASE_URL ?>api/avaliacoes_save.php"
            data-delete-url="<?= BASE_URL ?>api/avaliacoes_delete.php"
            data-resultados-url="<?= BASE_URL ?>pages/admin/avaliacoes_resultados.php"
        >
            <h2 class="section-title">Dados da avaliação sumativa</h2>
            <div id="form-message" class="form-message" style="display: none;"></div>

            <div class="form-grid">
                <label class="form-field">
                    <span>Turma</span>
                    <select id="avaliacao_turma">
                        <option value="">Seleccione a turma</option>
                    </select>
                </label>

                <label class="form-field">
                    <span>Módulo</span>
                    <select id="avaliacao_modulo">
                        <option value="">Seleccione o módulo</option>
                    </select>
                    <small class="form-note"><i class="fa fa-solid fa-info-circle"></i> As avaliações devem estar dentro da vigência do módulo seleccionado.</small>
                </label>

                <label class="form-field">
                    <span>Avaliação sumativa</span>
                    <select id="avaliacao_titulo">
                        <option value="">Seleccione a avaliação</option>
                        <option value="Avaliação Sumativa 1">Avaliação Sumativa 1</option>
                        <option value="Avaliação Sumativa 2">Avaliação Sumativa 2</option>
                        <option value="Avaliação Sumativa 3">Avaliação Sumativa 3</option>
                        <option value="Avaliação Sumativa 4">Avaliação Sumativa 4</option>
                        <option value="Avaliação Sumativa 5">Avaliação Sumativa 5</option>
                        <option value="Avaliação Sumativa 6">Avaliação Sumativa 6</option>
                        <option value="Avaliação Sumativa 7">Avaliação Sumativa 7</option>
                        <option value="Avaliação Sumativa 8">Avaliação Sumativa 8</option>
                        <option value="Avaliação Sumativa 9">Avaliação Sumativa 9</option>
                        <option value="Avaliação Sumativa 10">Avaliação Sumativa 10</option>
                    </select>
                </label>

                <label class="form-field">
                    <span>Data</span>
                    <input type="date" id="avaliacao_data">
                </label>

                <label class="form-field">
                    <span>Hora</span>
                    <input type="time" id="avaliacao_hora">
                </label>
            </div>

            <div id="avaliacao_info" class="avaliacao-info"></div>

            <div class="avaliacao-acoes">
                <button type="button" class="btn btn-outline" id="avaliacao_limpar">
                    <i class="fa-solid fa-eraser"></i>
                    <span>Limpar campos</span>
                </button>
                <button type="button" class="btn" id="avaliacao_adicionar">
                    <i class="fa-solid fa-plus"></i>
                    <span>Registar avaliação</span>
                </button>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="avaliacoes_tabela">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Título</th>
                            <th>Data</th>
                            <th>Hora</th>
                            <th>Situação</th>
                            <th>Acções</th>
                        </tr>
                    </thead>
                    <tbody id="avaliacoes_lista">
                        <tr>
                            <td colspan="6" class="empty-row">Nenhuma avaliação registada ainda</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
