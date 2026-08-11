<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
$perfilTitulo = 'Perfil do Utilizador';
if ($nivel === 1) $perfilTitulo = 'Perfil de Administrador';
if ($nivel === 2) $perfilTitulo = 'Perfil de Formador';
if ($nivel === 3) $perfilTitulo = 'Perfil de Formando';
if ($nivel === 4) $perfilTitulo = 'Perfil de Encarregado';

$nivelBadge = 'Utilizador';
if ($nivel === 1) $nivelBadge = 'Administrador';
if ($nivel === 2) $nivelBadge = 'Formador';
if ($nivel === 3) $nivelBadge = 'Formando';
if ($nivel === 4) $nivelBadge = 'Encarregado de Educação';

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Utilizador';
$usuarioFoto = 'default.png';
$usuarioEmail = '';
$usuarioContacto = '';
$usuarioCriadoEm = '';
$codigoPessoal = '';
$isDirectorTurma = false;
$adminContacto = '';
$formandoInfo = [
    'nome' => '—',
    'codigo' => '—',
    'documento' => '—',
    'sexo' => '—',
    'contacto' => '',
    'ano_ingresso' => '—',
    'ano_conclusao' => '—',
    'certificado' => '—',
    'qualificacao' => '—',
    'turno' => '—',
    'turma' => '—',
    'id' => 0
];
$encarregadoInfo = [
    'tem' => false,
    'nome' => '',
    'email' => '',
    'parentesco' => '',
    'contacto' => '',
    'estado' => ''
];
$formadorInfo = [
    'nome' => '—',
    'codigo' => '—',
    'sexo' => '—',
    'titulo' => '—',
    'contacto' => '',
    'especialidade' => '—',
    'qualificacao' => '—',
    'id' => 0
];
$encarregadoPerfil = [
    'nome' => '—',
    'contacto' => ''
];

if (!empty($conn) && $usuarioId > 0) {
    $stmt = $conn->prepare("SELECT nome_completo, email, foto, data_criacao FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $usuarioId);
    if ($stmt->execute()) {
        $stmt->bind_result($nomeDb, $emailDb, $fotoDb, $dataCriacaoDb);
        if ($stmt->fetch()) {
            $usuarioNome = $nomeDb ?: $usuarioNome;
            $usuarioEmail = $emailDb ?: '';
            $usuarioFoto = $fotoDb ?: $usuarioFoto;
            $usuarioCriadoEm = $dataCriacaoDb ?: '';
        }
    }
    $stmt->close();
}

if (!empty($conn) && $usuarioId > 0 && $nivel === 1) {
    $temCol = $conn->query("SHOW COLUMNS FROM administradores LIKE 'contacto'");
    if ($temCol && $temCol->num_rows > 0) {
        $stmt = $conn->prepare("SELECT contacto FROM administradores WHERE usuario_id = ? LIMIT 1");
        $stmt->bind_param("i", $usuarioId);
        if ($stmt->execute()) {
            $stmt->bind_result($contactoDb);
            if ($stmt->fetch()) {
                $adminContacto = $contactoDb ?: '';
            }
        }
        $stmt->close();
    }
}

if (!empty($conn) && !empty($usuarioEmail)) {
    $stmt = $conn->prepare("SELECT codigo_acesso FROM codigos_autorizados WHERE email_dono = ? AND nivel_destinado = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("si", $usuarioEmail, $nivel);
    if ($stmt->execute()) {
        $stmt->bind_result($codigoDb);
        if ($stmt->fetch()) {
            $codigoPessoal = $codigoDb ?: '';
        }
    }
    $stmt->close();
}

if (!empty($conn) && $usuarioId > 0) {
    $sqlContacto = '';
    if ($nivel === 2) {
        $sqlContacto = "SELECT telefone FROM formadores WHERE usuario_id = ? LIMIT 1";
    } elseif ($nivel === 3) {
        $sqlContacto = "SELECT contacto FROM formandos WHERE usuario_id = ? LIMIT 1";
    } elseif ($nivel === 4) {
        $sqlContacto = "SELECT contacto FROM encarregados WHERE usuario_id = ? LIMIT 1";
    }

    if ($sqlContacto) {
        $stmt = $conn->prepare($sqlContacto);
        $stmt->bind_param("i", $usuarioId);
        if ($stmt->execute()) {
            $stmt->bind_result($contactoDb);
            if ($stmt->fetch()) {
                $usuarioContacto = $contactoDb ?: '';
            }
        }
        $stmt->close();
    }
}

if (!empty($conn) && $usuarioId > 0 && $nivel === 2) {
    $formadorId = 0;
    $stmt = $conn->prepare("
        SELECT f.id, f.nome_completo, f.codigo_formador, f.sexo, f.titulo, f.telefone, f.especialidade,
               GROUP_CONCAT(c.nome_curso ORDER BY c.nome_curso SEPARATOR ', ')
        FROM formadores f
        LEFT JOIN formador_curso fc ON fc.formador_id = f.id
        LEFT JOIN cursos c ON c.id = fc.curso_id
        WHERE f.usuario_id = ?
        GROUP BY f.id
        LIMIT 1
    ");
    $stmt->bind_param("i", $usuarioId);
    if ($stmt->execute()) {
        $stmt->bind_result(
            $formadorDb,
            $formadorNomeDb,
            $formadorCodigoDb,
            $formadorSexoDb,
            $formadorTituloDb,
            $formadorTelDb,
            $formadorEspDb,
            $formadorCursosDb
        );
        if ($stmt->fetch()) {
            $formadorId = (int)$formadorDb;
            $formadorInfo['id'] = $formadorId;
            $formadorInfo['nome'] = $formadorNomeDb ?: '—';
            $formadorInfo['codigo'] = $formadorCodigoDb ?: '—';
            $formadorInfo['sexo'] = $formadorSexoDb ?: '—';
            $formadorInfo['titulo'] = $formadorTituloDb ?: '—';
            $formadorInfo['contacto'] = $formadorTelDb ?: '';
            $formadorInfo['especialidade'] = $formadorEspDb ?: '—';
            $formadorInfo['qualificacao'] = $formadorCursosDb ?: '—';
        }
    }
    $stmt->close();

    if ($formadorId > 0) {
        $res = $conn->query("SELECT id FROM turmas WHERE dt_id = $formadorId LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $isDirectorTurma = true;
        }
    }
}

if (!empty($conn) && $usuarioId > 0 && $nivel === 4) {
    $stmt = $conn->prepare("SELECT nome_completo, contacto FROM encarregados WHERE usuario_id = ? LIMIT 1");
    $stmt->bind_param("i", $usuarioId);
    if ($stmt->execute()) {
        $stmt->bind_result($encNomeDb, $encContactoDb);
        if ($stmt->fetch()) {
            $encarregadoPerfil['nome'] = $encNomeDb ?: '—';
            $encarregadoPerfil['contacto'] = $encContactoDb ?: '';
        }
    }
    $stmt->close();
}

if (!empty($conn) && $usuarioId > 0 && $nivel === 3) {
    $stmt = $conn->prepare("
        SELECT f.id, f.nome_completo, f.codigo_formando, f.numero_documento, f.sexo, f.contacto,
               f.ano_ingresso, f.ano_conclusao, f.certificado_vocacional,
               c.nome_curso, t.nome_turma, tr.nome_turno
        FROM formandos f
        LEFT JOIN cursos c ON c.id = f.curso_id
        LEFT JOIN turmas t ON t.id = f.turma_id
        LEFT JOIN turnos tr ON tr.id = f.turno_id
        WHERE f.usuario_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $usuarioId);
    if ($stmt->execute()) {
        $stmt->bind_result(
            $formandoIdDb,
            $formandoNomeDb,
            $formandoCodigoDb,
            $formandoDocDb,
            $formandoSexoDb,
            $formandoContactoDb,
            $formandoAnoIngDb,
            $formandoAnoConDb,
            $formandoCvDb,
            $formandoCursoDb,
            $formandoTurmaDb,
            $formandoTurnoDb
        );
        if ($stmt->fetch()) {
            $formandoInfo['id'] = (int)$formandoIdDb;
            $formandoInfo['nome'] = $formandoNomeDb ?: '—';
            $formandoInfo['codigo'] = $formandoCodigoDb ?: '—';
            $formandoInfo['documento'] = $formandoDocDb ?: '—';
            $formandoInfo['sexo'] = $formandoSexoDb ?: '—';
            $formandoInfo['contacto'] = $formandoContactoDb ?: '';
            $formandoInfo['ano_ingresso'] = $formandoAnoIngDb ?: '—';
            $formandoInfo['ano_conclusao'] = $formandoAnoConDb ?: '—';
            $formandoInfo['certificado'] = $formandoCvDb ?: '—';
            $formandoInfo['qualificacao'] = $formandoCursoDb ?: '—';
            $formandoInfo['turno'] = $formandoTurnoDb ?: '—';
            $formandoInfo['turma'] = $formandoTurmaDb ?: '—';
        }
    }
    $stmt->close();

    if ($formandoInfo['id'] > 0) {
        $stmt = $conn->prepare("
            SELECT e.nome_completo, e.email, e.parentesco, e.contacto, ca.estado
            FROM encarregado_formando ef
            INNER JOIN encarregados e ON e.id = ef.encarregado_id
            LEFT JOIN codigos_autorizados ca
                ON ca.email_dono = e.email AND ca.nivel_destinado = 4
            WHERE ef.formando_id = ?
            ORDER BY ef.principal DESC, ef.id DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $formandoInfo['id']);
        if ($stmt->execute()) {
            $stmt->bind_result($encNomeDb, $encEmailDb, $encParentescoDb, $encContactoDb, $encEstadoDb);
            if ($stmt->fetch()) {
                $estadoEnc = ($encEstadoDb === 'utilizado') ? 'Activo' : 'Inactivo';
                $encarregadoInfo = [
                    'tem' => true,
                    'nome' => $encNomeDb ?: '—',
                    'email' => $encEmailDb ?: '—',
                    'parentesco' => $encParentescoDb ?: '—',
                    'contacto' => $encContactoDb ?: '—',
                    'estado' => $estadoEnc
                ];
            }
        }
        $stmt->close();
    }
}

$dataCriacaoFormatada = $usuarioCriadoEm ? date('d/m/Y', strtotime($usuarioCriadoEm)) : '—';

$page_css = [
    'forms.css',
    'modules/breadcrumbs.css',
    'pages/perfil.css'
];

$page_js = [
    'modules/notifications.js',
    'modules/masks.js',
    'pages/perfil.js'
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/topbar.php'; ?>
    <main class="content-body">
        <div class="page-header">
            <h1>Meu Perfil</h1>
            <?php
            $breadcrumbs = [
                ['label' => 'Início', 'url' => BASE_URL . 'pages/admin/dashboard.php'],
                ['label' => 'Meu Perfil', 'url' => null],
            ];
            require __DIR__ . '/../includes/components/breadcrumbs.php';
            ?>
        </div>

        <section class="card perfil-card">
            <div class="tabs" id="perfil_tabs">
                <button class="tab-btn is-active" data-tab="visao_geral">Visão Geral</button>
                <button class="tab-btn" data-tab="perfil_tipo"><?= htmlspecialchars($perfilTitulo) ?></button>
                <button class="tab-btn" data-tab="perfil_pessoal">Perfil Pessoal</button>
                <span class="tab-indicator"></span>
            </div>

            <div class="tab-panels">
                <section class="tab-panel is-active" id="visao_geral">
                    <h2 class="section-title">Visão Geral</h2>
                    <div class="perfil-overview">
                        <button type="button" class="perfil-photo" data-tab-target="perfil_pessoal" aria-label="Abrir perfil pessoal">
                            <?php
                            $fotoSegura = htmlspecialchars($usuarioFoto);
                            $nomeSegura = htmlspecialchars($usuarioNome);
                            if (!empty($usuarioFoto) && $usuarioFoto !== 'default.png'): ?>
                                <img src="<?= BASE_URL ?>assets/img/<?= $fotoSegura ?>" alt="<?= $nomeSegura ?>" class="perfil-photo-img" data-photo-role="overview">
                            <?php else: ?>
                                <span class="perfil-iniciais" data-photo-role="overview-iniciais"><?= htmlspecialchars(getInitials($usuarioNome)) ?></span>
                            <?php endif; ?>
                        </button>

                        <div class="perfil-info">
                            <div class="perfil-topo">
                                <button type="button" class="perfil-nome" data-tab-target="perfil_pessoal">
                                    <?= $nomeSegura ?>
                                </button>
                                <div class="perfil-badges">
                                    <span class="nivel-badge"><?= htmlspecialchars($nivelBadge) ?></span>
                                    <?php if ($isDirectorTurma): ?>
                                        <span class="nivel-badge badge-director">Director de Turma</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="perfil-inst">
                                <span class="inst-full">Instituto Industrial e de Computação Armando Emílio Guebuza (10040)</span>
                                <span class="inst-short">IICAEG (10040)</span>
                            </p>
                            <div class="perfil-codigo">
                                <span class="perfil-label">Número pessoal</span>
                                <strong><?= htmlspecialchars($codigoPessoal ?: '—') ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="perfil-info-card">
                        <button type="button" class="perfil-section-title" data-tab-target="perfil_pessoal">
                            Informações de utilizador
                        </button>
                        <div class="perfil-info-grid">
                            <div class="info-item">
                                <span class="info-label"><i class="fa-solid fa-envelope"></i> Email</span>
                                <span class="info-value"><?= htmlspecialchars($usuarioEmail ?: '—') ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><i class="fa-solid fa-phone"></i> Contacto</span>
                                <?php if (!empty($usuarioContacto)): ?>
                                    <span class="info-value"><?= htmlspecialchars($usuarioContacto) ?></span>
                                <?php else: ?>
                                    <button type="button" class="info-link" data-tab-target="perfil_pessoal">Adicionar contacto</button>
                                <?php endif; ?>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><i class="fa-solid fa-calendar-days"></i> Data de criação</span>
                                <span class="info-value"><?= htmlspecialchars($dataCriacaoFormatada) ?></span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="tab-panel" id="perfil_tipo">
                    <h2 class="section-title"><?= htmlspecialchars($perfilTitulo) ?></h2>
                    <div id="form-message" class="form-message" style="display: none;"></div>
                    <?php if ($nivel === 1): ?>
                        <div class="perfil-details-card">
                            <div class="perfil-details-grid">
                                <div class="perfil-detail perfil-detail-contacto">
                                    <span class="detail-label">Contacto</span>
                                    <form class="perfil-contacto-form" data-update-url="<?= BASE_URL ?>api/admin_contacto_update.php" data-success="Contacto actualizado com êxito.">
                                        <input type="text" value="<?= htmlspecialchars($adminContacto) ?>" data-input-mask="mz-contact" data-mask-message="Informe o contacto no formato +258(XX)XXX-XXXX." inputmode="numeric" maxlength="16" placeholder="+258(XX)XXX-XXXX" autocomplete="off">
                                        <button type="submit" class="btn-contacto">
                                            <i class="fa-solid fa-floppy-disk"></i>
                                            <span>Guardar contacto</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($nivel === 2): ?>
                        <div class="perfil-details-card">
                            <div class="perfil-details-grid">
                                <div class="perfil-detail">
                                    <span class="detail-label">Nome do formador</span>
                                    <span class="detail-value"><?= htmlspecialchars($formadorInfo['nome']) ?></span>
                                </div>
                                <div class="perfil-detail">
                                    <span class="detail-label">Código do formador</span>
                                    <span class="detail-value"><?= htmlspecialchars($formadorInfo['codigo']) ?></span>
                                </div>
                                <div class="perfil-detail">
                                    <span class="detail-label">Sexo</span>
                                    <span class="detail-value"><?= htmlspecialchars($formadorInfo['sexo']) ?></span>
                                </div>
                                <div class="perfil-detail perfil-detail-contacto">
                                    <span class="detail-label">Contacto</span>
                                    <form class="perfil-contacto-form" data-update-url="<?= BASE_URL ?>api/formador_contacto_update.php" data-success="Contacto actualizado com êxito.">
                                        <input type="text" value="<?= htmlspecialchars($formadorInfo['contacto']) ?>" data-input-mask="mz-contact" data-mask-message="Informe o contacto no formato +258(XX)XXX-XXXX." inputmode="numeric" maxlength="16" placeholder="+258(XX)XXX-XXXX" autocomplete="off">
                                        <button type="submit" class="btn-contacto">
                                            <i class="fa-solid fa-floppy-disk"></i>
                                            <span>Guardar contacto</span>
                                        </button>
                                    </form>
                                </div>
                                <div class="perfil-detail">
                                    <span class="detail-label">Título</span>
                                    <span class="detail-value"><?= htmlspecialchars($formadorInfo['titulo']) ?></span>
                                </div>
                                <div class="perfil-detail">
                                    <span class="detail-label">Especialidade</span>
                                    <span class="detail-value"><?= htmlspecialchars($formadorInfo['especialidade']) ?></span>
                                </div>
                                <div class="perfil-detail">
                                    <span class="detail-label">Qualificação</span>
                                    <span class="detail-value"><?= htmlspecialchars($formadorInfo['qualificacao']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($nivel === 3): ?>
                        <div class="perfil-details-card">
                            <div id="form-message" class="form-message" style="display: none;"></div>
                            <div class="perfil-details-grid">
                                <div class="perfil-detail">
                                    <span class="detail-label">Nome do formando</span>
                                    <span class="detail-value"><?= htmlspecialchars($formandoInfo['nome']) ?></span>
                                </div>
                                <div class="perfil-detail">
                                    <span class="detail-label">Código do formando</span>
                                    <span class="detail-value"><?= htmlspecialchars($formandoInfo['codigo']) ?></span>
                                </div>
                                <div class="perfil-detail">
                                    <span class="detail-label">Número de documento</span>
                                    <span class="detail-value"><?= htmlspecialchars($formandoInfo['documento']) ?></span>
                                </div>
                                <div class="perfil-detail">
                                    <span class="detail-label">Sexo</span>
                                    <span class="detail-value"><?= htmlspecialchars($formandoInfo['sexo']) ?></span>
                                </div>
                                <div class="perfil-detail perfil-detail-contacto">
                                    <span class="detail-label">Contacto</span>
                                    <form class="perfil-contacto-form" data-update-url="<?= BASE_URL ?>api/formando_contacto_update.php" data-success="Contacto actualizado com êxito.">
                                        <input type="text" value="<?= htmlspecialchars($formandoInfo['contacto']) ?>" data-input-mask="mz-contact" data-mask-message="Informe o contacto no formato +258(XX)XXX-XXXX." inputmode="numeric" maxlength="16" placeholder="+258(XX)XXX-XXXX" autocomplete="off">
                                        <button type="submit" class="btn-contacto">
                                            <i class="fa-solid fa-floppy-disk"></i>
                                            <span>Guardar contacto</span>
                                        </button>
                                    </form>
                                </div>
                                <div class="perfil-detail">
                                    <span class="detail-label">Ano de ingresso</span>
                                    <span class="detail-value"><?= htmlspecialchars($formandoInfo['ano_ingresso']) ?></span>
                                </div>
                                <div class="perfil-detail">
                                    <span class="detail-label">Ano de conclusão</span>
                                    <span class="detail-value"><?= htmlspecialchars($formandoInfo['ano_conclusao']) ?></span>
                                </div>
                                <div class="perfil-detail">
                                    <span class="detail-label">Certificado vocacional</span>
                                    <span class="detail-value"><?= htmlspecialchars($formandoInfo['certificado']) ?></span>
                                </div>
                                <div class="perfil-detail">
                                    <span class="detail-label">Qualificação</span>
                                    <span class="detail-value"><?= htmlspecialchars($formandoInfo['qualificacao']) ?></span>
                                </div>
                                <div class="perfil-detail">
                                    <span class="detail-label">Turno</span>
                                    <span class="detail-value"><?= htmlspecialchars($formandoInfo['turno']) ?></span>
                                </div>
                                <div class="perfil-detail">
                                    <span class="detail-label">Turma</span>
                                    <span class="detail-value"><?= htmlspecialchars($formandoInfo['turma']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="perfil-encarregado-card">
                            <div class="perfil-encarregado-title">Informações de encarregado de educação</div>
                            <?php if (!$encarregadoInfo['tem']): ?>
                                <p class="perfil-encarregado-empty">Não possui encarregado registado.</p>
                            <?php else: ?>
                                <div class="perfil-details-grid">
                                    <div class="perfil-detail">
                                        <span class="detail-label">Nome</span>
                                        <span class="detail-value"><?= htmlspecialchars($encarregadoInfo['nome']) ?></span>
                                    </div>
                                    <div class="perfil-detail">
                                        <span class="detail-label">Email</span>
                                        <span class="detail-value"><?= htmlspecialchars($encarregadoInfo['email']) ?></span>
                                    </div>
                                    <div class="perfil-detail">
                                        <span class="detail-label">Grau de parentesco</span>
                                        <span class="detail-value"><?= htmlspecialchars($encarregadoInfo['parentesco']) ?></span>
                                    </div>
                                    <div class="perfil-detail">
                                        <span class="detail-label">Contacto</span>
                                        <span class="detail-value"><?= htmlspecialchars($encarregadoInfo['contacto']) ?></span>
                                    </div>
                                    <div class="perfil-detail">
                                        <span class="detail-label">Estado</span>
                                        <span class="detail-value"><?= htmlspecialchars($encarregadoInfo['estado']) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <small class="perfil-note">
                            <i class="fa-solid fa-info-circle"></i>
                            Algumas informações só podem ser alteradas pelo administrador. Entre em contacto com ele se for necessário corrigir essas informações.
                        </small>
                    <?php elseif ($nivel === 4): ?>
                        <div class="perfil-details-card">
                            <div class="perfil-details-grid">
                                <div class="perfil-detail">
                                    <span class="detail-label">Nome completo</span>
                                    <span class="detail-value"><?= htmlspecialchars($encarregadoPerfil['nome']) ?></span>
                                </div>
                                <div class="perfil-detail perfil-detail-contacto">
                                    <span class="detail-label">Contacto</span>
                                    <form class="perfil-contacto-form" data-update-url="<?= BASE_URL ?>api/encarregado_contacto_update.php" data-success="Contacto actualizado com êxito.">
                                        <input type="text" value="<?= htmlspecialchars($encarregadoPerfil['contacto']) ?>" data-input-mask="mz-contact" data-mask-message="Informe o contacto no formato +258(XX)XXX-XXXX." inputmode="numeric" maxlength="16" placeholder="+258(XX)XXX-XXXX" autocomplete="off">
                                        <button type="submit" class="btn-contacto">
                                            <i class="fa-solid fa-floppy-disk"></i>
                                            <span>Guardar contacto</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <small class="perfil-note">
                            <i class="fa-solid fa-info-circle"></i>
                            Algumas informações só podem ser alteradas pelo administrador. Entre em contacto com ele se for necessário corrigir essas informações.
                        </small>
                    <?php else: ?>
                        <p class="tab-placeholder">Conteúdo em construção.</p>
                    <?php endif; ?>
                </section>

                <section class="tab-panel" id="perfil_pessoal">
                    <h2 class="section-title">Perfil Pessoal</h2>
                    <div class="perfil-photo-card" data-update-url="<?= BASE_URL ?>api/perfil_foto_update.php" data-initials="<?= htmlspecialchars(getInitials($usuarioNome)) ?>" data-has-photo="<?= (!empty($usuarioFoto) && $usuarioFoto !== 'default.png') ? '1' : '0' ?>">
                        <div class="perfil-section-title perfil-section-title--static">
                            Alterar informações de utilizador
                        </div>
                        <div id="form-message" class="form-message" style="display: none;"></div>
                        <div class="perfil-photo-body">
                            <div class="photo-preview" id="perfil_photo_preview">
                                <?php if (!empty($usuarioFoto) && $usuarioFoto !== 'default.png'): ?>
                                    <img src="<?= BASE_URL ?>assets/img/<?= $fotoSegura ?>" alt="<?= $nomeSegura ?>" class="perfil-photo-img" data-photo-role="personal">
                                <?php else: ?>
                                    <span class="perfil-iniciais" data-photo-role="personal-iniciais"><?= htmlspecialchars(getInitials($usuarioNome)) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="photo-actions">
                                <input type="file" id="perfil_foto_input" accept="image/png,image/jpeg" hidden>
                                <button type="button" class="btn btn-outline" id="perfil_foto_pick">
                                    <i class="fa-solid fa-camera"></i>
                                    <span>Escolher foto</span>
                                </button>
                                <button type="button" class="btn" id="perfil_foto_save" disabled>
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span>Guardar</span>
                                </button>
                                <small class="photo-note">JPG ou PNG até 2MB.</small>
                            </div>
                        </div>
                        <form
                            id="perfil_form"
                            class="perfil-form"
                            data-update-url="<?= BASE_URL ?>api/perfil_update.php"
                            data-delete-url="<?= BASE_URL ?>api/perfil_delete.php"
                            data-base-url="<?= BASE_URL ?>"
                        >
                            <div class="form-grid perfil-form-grid">
                                <label class="form-field">
                                    <span>Nome completo</span>
                                    <input type="text" id="perfil_nome" name="nome_completo" value="<?= $nomeSegura ?>" required>
                                </label>

                                <div class="form-field perfil-static-field">
                                    <span>Email</span>
                                    <div class="perfil-static-value"><?= htmlspecialchars($usuarioEmail ?: '—') ?></div>
                                </div>

                                <div class="form-field perfil-static-field">
                                    <span>Privilégio</span>
                                    <div class="perfil-static-value"><?= htmlspecialchars($nivelBadge) ?></div>
                                </div>

                            </div>

                            <div class="perfil-pass">
                                <button type="button" class="btn-pass" id="perfil_toggle_pass">
                                    <i class="fa-solid fa-key"></i>
                                    <span>Alterar senha</span>
                                </button>

                                <div class="perfil-pass-fields" id="perfil_pass_fields" style="display: none;">
                                    <div class="form-grid perfil-form-grid">
                                        <label class="form-field">
                                            <span>Senha actual</span>
                                            <div class="password-toggle">
                                                <input type="password" id="perfil_pass_atual" name="senha_actual" placeholder="Digite a senha actual">
                                                <button type="button" class="toggle-pass"><i class="fa-solid fa-eye"></i></button>
                                            </div>
                                        </label>

                                        <label class="form-field">
                                            <span>Nova senha</span>
                                            <div class="password-toggle">
                                                <input type="password" id="perfil_pass_nova" name="senha_nova" placeholder="Nova senha">
                                                <button type="button" class="toggle-pass"><i class="fa-solid fa-eye"></i></button>
                                            </div>
                                        </label>

                                        <label class="form-field">
                                            <span>Confirmar senha</span>
                                            <div class="password-toggle">
                                                <input type="password" id="perfil_pass_confirmar" name="senha_confirmar" placeholder="Repita a nova senha">
                                                <button type="button" class="toggle-pass"><i class="fa-solid fa-eye"></i></button>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="perfil-actions">
                                <button type="submit" class="btn perfil-btn" id="perfil_save" disabled>
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    <span>Guardar</span>
                                </button>
                                <button type="button" class="btn btn-outline perfil-btn" id="perfil_delete">
                                    <i class="fa-solid fa-trash"></i>
                                    <span>Remover conta</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <small class="perfil-note">
                        <i class="fa-solid fa-info-circle"></i>
                        Algumas informações só podem ser alteradas pelo administrador. Entre em contacto com ele se for necessário corrigir essas informações.
                    </small>
                </section>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div>
