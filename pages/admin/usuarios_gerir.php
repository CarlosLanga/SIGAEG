<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../config/db.php';

if (($_SESSION['nivel_acesso'] ?? 0) != 1) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$page_css = [
    'forms.css',
    'modules/breadcrumbs.css',
    'tables.css',
    'pages/usuarios_gerir.css',
];

$page_js = [
    'modules/table-manager.js',
    'pages/usuarios_gerir.js',
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

function esc($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatarData(?string $data, bool $comHora = false): string
{
    if (!$data || $data === '0000-00-00' || $data === '0000-00-00 00:00:00') {
        return '-';
    }

    $timestamp = strtotime($data);
    if (!$timestamp) {
        return '-';
    }

    return $comHora ? date('d/m/Y H:i', $timestamp) : date('d/m/Y', $timestamp);
}

function obterBadgePrivilegio($nivelId, ?string $nivelNome): string
{
    $nivelId = (int)$nivelId;
    $rotulo = $nivelNome ?: 'Sem nivel';

    switch ($nivelId) {
        case 1:
            $class = 'role-admin';
            $icon = 'fa-user-shield';
            break;
        case 2:
            $class = 'role-formador';
            $icon = 'fa-chalkboard-user';
            break;
        case 3:
            $class = 'role-formando';
            $icon = 'fa-user-graduate';
            break;
        case 4:
            $class = 'role-encarregado';
            $icon = 'fa-users';
            break;
        default:
            $class = 'role-default';
            $icon = 'fa-user';
            break;
    }

    return '<span class="role-badge ' . $class . '"><i class="fa-solid ' . $icon . '"></i><span>' . esc($rotulo) . '</span></span>';
}

$usuarios = [];
$erro = null;

if (!$conn) {
    $erro = 'Nao foi possivel ligar a base de dados.';
} else {
    $sql = "
        SELECT *
        FROM (
            SELECT
                u.id,
                COALESCE(NULLIF(u.nome_completo, ''), c.email_dono) AS nome_exibicao,
                COALESCE(NULLIF(u.email, ''), c.email_dono) AS email,
                COALESCE(NULLIF(u.foto, ''), 'default.png') AS foto,
                u.status AS usuario_status,
                COALESCE(u.nivel_acesso_id, c.nivel_destinado) AS nivel_id,
                n.nivel AS nivel_nome,
                c.codigo_acesso,
                COALESCE(NULLIF(f.contacto, ''), NULLIF(form.telefone, ''), NULLIF(enc.contacto, '')) AS contacto,
                u.data_criacao,
                1 AS is_cadastrado
            FROM codigos_autorizados c
            LEFT JOIN usuarios u ON u.email = c.email_dono
            LEFT JOIN niveis_acesso n ON n.id = COALESCE(u.nivel_acesso_id, c.nivel_destinado)
            LEFT JOIN formandos f ON f.usuario_id = u.id
            LEFT JOIN formadores form ON form.usuario_id = u.id
            LEFT JOIN encarregados enc ON enc.usuario_id = u.id

            UNION

            SELECT
                u.id,
                u.nome_completo AS nome_exibicao,
                u.email,
                COALESCE(NULLIF(u.foto, ''), 'default.png') AS foto,
                u.status AS usuario_status,
                u.nivel_acesso_id AS nivel_id,
                n.nivel AS nivel_nome,
                NULL AS codigo_acesso,
                COALESCE(NULLIF(f.contacto, ''), NULLIF(form.telefone, ''), NULLIF(enc.contacto, '')) AS contacto,
                u.data_criacao,
                1 AS is_cadastrado
            FROM usuarios u
            LEFT JOIN niveis_acesso n ON n.id = u.nivel_acesso_id
            LEFT JOIN formandos f ON f.usuario_id = u.id
            LEFT JOIN formadores form ON form.usuario_id = u.id
            LEFT JOIN encarregados enc ON enc.usuario_id = u.id
            WHERE u.email NOT IN (SELECT email_dono FROM codigos_autorizados)
        ) AS lista_usuarios
        ORDER BY nome_exibicao ASC
    ";

    $resultado = $conn->query($sql);
    if ($resultado) {
        while ($row = $resultado->fetch_assoc()) {
            $isCadastrado = !empty($row['id']);
            $statusAtivo = $isCadastrado && (int)$row['usuario_status'] === 1;

            $usuarios[] = [
                'id' => $row['id'],
                'nome_exibicao' => $row['nome_exibicao'] ?: 'Utilizador sem nome',
                'email' => $row['email'] ?: '-',
                'foto' => $row['foto'] ?: 'default.png',
                'iniciais' => getInitials($row['nome_exibicao'] ?: 'Utilizador'),
                'nivel_id' => $row['nivel_id'],
                'nivel_nome' => $row['nivel_nome'] ?: 'Sem nivel',
                'codigo_acesso' => $row['codigo_acesso'] ?: '',
                'contacto' => $row['contacto'] ?: '-',
                'is_cadastrado' => $isCadastrado,
                'status_rotulo' => $statusAtivo ? 'Activo' : 'Inactivo',
                'status_classe' => $statusAtivo ? 'user-status-active' : 'user-status-inactive',
                'cadastro_rotulo' => $isCadastrado ? 'Cadastrado' : 'Pendente',
                'data_cadastro' => formatarData($row['data_criacao'] ?? null),
                'ultima_sessao' => '-',
            ];
        }
    } else {
        $erro = 'Erro ao carregar utilizadores.';
    }
}
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>

    <main class="content-body">
        <div class="page-header">
            <h1>Utilizadores</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Utilizadores', 'url' => null],
            ];
            require __DIR__ . '/../../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card form-card">
            <div class="usuarios-header">
                <div>
                    <h2 class="section-title">Lista de Utilizadores</h2>
                    <p class="usuarios-subtitle">Todos os utilizadores activos e inactivos no sistema.</p>
                </div>

                <button type="button" class="btn" id="btn-add-user">
                    <i class="fa-solid fa-user-plus"></i>
                    <span class="btn-text">Novo utilizador</span>
                </button>
            </div>

            <div class="modal" id="modal_novo_utilizador">
                <div class="modal-content usuarios-choice-modal">
                    <div class="modal-header">
                        <div>
                            <h2>Novo utilizador</h2>
                            <p>Escolha o tipo de perfil que pretende adicionar.</p>
                        </div>
                        <button type="button" class="btn btn-outline" id="btn_fechar_novo_utilizador" aria-label="Fechar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="usuarios-choice-grid">
                        <a class="usuarios-choice-card" href="<?= BASE_URL ?>pages/admin/administrador_adicionar.php">
                            <span class="usuarios-choice-icon role-admin"><i class="fa-solid fa-user-shield"></i></span>
                            <strong>Administrador</strong>
                            <small>Criar conta administrativa.</small>
                        </a>
                        <a class="usuarios-choice-card" href="<?= BASE_URL ?>pages/admin/formador_adicionar.php">
                            <span class="usuarios-choice-icon role-formador"><i class="fa-solid fa-user-tie"></i></span>
                            <strong>Formador</strong>
                            <small>Adicionar docente/formador.</small>
                        </a>
                        <a class="usuarios-choice-card" href="<?= BASE_URL ?>pages/admin/formando_adicionar.php">
                            <span class="usuarios-choice-icon role-formando"><i class="fa-solid fa-user-graduate"></i></span>
                            <strong>Formando</strong>
                            <small>Registar novo formando.</small>
                        </a>
                        <a class="usuarios-choice-card" href="<?= BASE_URL ?>pages/admin/encarregado_adicionar.php">
                            <span class="usuarios-choice-icon role-encarregado"><i class="fa-solid fa-user-group"></i></span>
                            <strong>Encarregado de Educacao</strong>
                            <small>Pre-cadastrar encarregado.</small>
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($erro): ?>
                <div class="form-message error"><?= esc($erro) ?></div>
            <?php endif; ?>

            <div class="table-toolbar usuarios-toolbar">
                <div class="toolbar-left">
                    <label class="filter-field usuarios-search-field">
                        <span>Pesquisar</span>
                        <div class="usuarios-search-wrap">
                            <i class="fa-solid fa-search"></i>
                            <input type="text" id="search-users" placeholder="Pesquisar por nome, email, contacto ou token">
                        </div>
                    </label>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="tabela_usuarios">
                    <thead>
                        <tr>
                            <th>Utilizador</th>
                            <th>Privilegio</th>
                            <th>Email</th>
                            <th>Contacto</th>
                            <th>Token</th>
                            <th>Estado</th>
                            <th>Ultima sessao</th>
                            <th>Data de cadastro</th>
                            <th>Accoes</th>
                        </tr>
                    </thead>
                    <tbody id="lista_usuarios">
                        <?php if (!$usuarios): ?>
                            <tr>
                                <td colspan="9" class="empty-row">Nenhum utilizador encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $utilizador): ?>
                                <?php
                                $avatar = trim((string)$utilizador['foto']) !== '' ? $utilizador['foto'] : 'default.png';
                                $avatarUrl = BASE_URL . 'assets/img/' . ltrim($avatar, '/');
                                $subtitulo = $utilizador['is_cadastrado']
                                    ? 'ID #' . (int)$utilizador['id']
                                    : 'Registo pendente';
                                $temFoto = $utilizador['is_cadastrado']
                                    && $avatar !== 'default.png';
                                ?>
                                <tr class="usuario-row">
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar">
                                                <?php if (!$utilizador['is_cadastrado']): ?>
                                                    <span class="user-avatar-icon" aria-hidden="true">
                                                        <i class="fa-solid fa-user-slash"></i>
                                                    </span>
                                                <?php elseif ($temFoto): ?>
                                                    <img src="<?= esc($avatarUrl) ?>" alt="Foto de perfil de <?= esc($utilizador['nome_exibicao']) ?>">
                                                <?php else: ?>
                                                    <span class="avatar-initials"><?= esc($utilizador['iniciais']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-meta">
                                                <span class="user-name"><?= esc($utilizador['nome_exibicao']) ?></span>
                                                <span class="user-subtitle"><?= esc($subtitulo) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= obterBadgePrivilegio($utilizador['nivel_id'], $utilizador['nivel_nome']) ?></td>
                                    <td><span class="cell-text"><?= esc($utilizador['email']) ?></span></td>
                                    <td><span class="cell-text"><?= esc($utilizador['contacto']) ?></span></td>
                                    <td>
                                        <?php if ($utilizador['codigo_acesso'] !== ''): ?>
                                            <span class="invite-code"><?= esc($utilizador['codigo_acesso']) ?></span>
                                        <?php else: ?>
                                            <span class="cell-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="status-stack">
                                            <span class="status-chip <?= esc($utilizador['status_classe']) ?>">
                                                <i class="fa-solid fa-circle status-indicator"></i>
                                                <span><?= esc($utilizador['status_rotulo']) ?></span>
                                            </span>
                                            <span class="status-subtext"><?= esc($utilizador['cadastro_rotulo']) ?></span>
                                        </div>
                                    </td>
                                    <td><span class="cell-muted"><?= esc($utilizador['ultima_sessao']) ?></span></td>
                                    <td><span class="cell-text"><?= esc($utilizador['data_cadastro']) ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button
                                                type="button"
                                                class="btn btn-outline btn-action btn-promote"
                                                title="Promover utilizador"
                                                data-id="<?= esc($utilizador['id']) ?>"
                                                data-email="<?= esc($utilizador['email']) ?>"
                                            >
                                                <i class="fa-solid fa-level-up-alt"></i>
                                                <span>Promover</span>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-outline btn-action btn-delete"
                                                title="Eliminar utilizador"
                                                data-id="<?= esc($utilizador['id']) ?>"
                                                data-email="<?= esc($utilizador['email']) ?>"
                                            >
                                                <i class="fa-solid fa-trash-can"></i>
                                                <span>Eliminar</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-footer table-footer-modern">
                <div class="table-footer-meta">
                    <label class="table-page-size">
                        <span>Linhas por pagina</span>
                        <div class="select-wrap">
                            <select id="page_size">
                                <option value="5" selected>5</option>
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </label>

                    <div class="table-info" id="table_info">0-0 de <?= count($usuarios) ?></div>
                </div>

                <div class="table-pagination table-pagination-modern" id="table_pagination">
                    <button class="btn btn-outline btn-table table-nav-btn" id="btn_prev" type="button" aria-label="Pagina anterior">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <div class="table-page-numbers" id="table_page_numbers"></div>

                    <button class="btn btn-outline btn-table table-nav-btn" id="btn_next" type="button" aria-label="Proxima pagina">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </section>
    </main>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</div>
