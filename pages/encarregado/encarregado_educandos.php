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
            <h1>Meus Educandos</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Inicio', 'url' => BASE_URL . 'pages/encarregado/dashboard.php'],
                ['label' => 'Educandos', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Turma</th>
                            <th>Curso</th>
                            <th>Estado</th>
                            <th>Acao</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Mateus N.</td>
                            <td>ASR15A</td>
                            <td>Redes</td>
                            <td><span class="status status-active">Ativo</span></td>
                            <td><button class="btn btn-outline btn-table">Ver</button></td>
                        </tr>
                        <tr>
                            <td>Carla M.</td>
                            <td>ASR15B</td>
                            <td>Redes</td>
                            <td><span class="status status-started">Em curso</span></td>
                            <td><button class="btn btn-outline btn-table">Ver</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
