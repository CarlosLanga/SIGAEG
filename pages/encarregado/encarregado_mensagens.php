<?php
require_once __DIR__ . '/../../config/init.php';

if ($_SESSION['nivel_acesso'] != 4 && $_SESSION['nivel_acesso'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_css = [
    'forms.css',
    'tables.css',
    'modules/breadcrumbs.css',
    'pages/encarregado_app.css'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Mensagens</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/encarregado/dashboard.php'],
                ['label' => 'Mensagens', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card">
            <div class="form-grid">
                <label class="form-field">
                    <span>Para</span>
                    <input type="text" placeholder="Admin ou Formador">
                </label>
                <label class="form-field">
                    <span>Assunto</span>
                    <input type="text" placeholder="Assunto">
                </label>
                <label class="form-field full">
                    <span>Mensagem</span>
                    <textarea rows="4" placeholder="Escreva aqui..."></textarea>
                </label>
            </div>
            <div class="form-actions">
                <button class="btn">Enviar</button>
            </div>
        </section>

        <section class="card">
            <h3>Caixa de Entrada</h3>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Remetente</th>
                            <th>Assunto</th>
                            <th>Data</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Admin</td>
                            <td>Relatorio mensal</td>
                            <td>2026-02-18</td>
                            <td><span class="status status-started">Nao lida</span></td>
                        </tr>
                        <tr>
                            <td>Formador</td>
                            <td>Faltas do educando</td>
                            <td>2026-02-10</td>
                            <td><span class="status status-done">Lida</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
