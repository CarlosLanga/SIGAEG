<?php
if (!isset($dashboard_title)) {
    $dashboard_title = 'Dashboard';
}

if (!isset($MENU) || !is_array($MENU)) {
    $menuPath = __DIR__ . '/../../includes/menu.php';
    if (file_exists($menuPath)) {
        require_once $menuPath;
    }
}

$show_dashboard_stats = !empty($show_dashboard_stats);
$is_formando_dashboard = (int)($_SESSION['nivel_acesso'] ?? 0) === 3
    && ($dashboard_title ?? '') === 'Painel do Formando';
$is_formador_dashboard = (int)($_SESSION['nivel_acesso'] ?? 0) === 2
    && ($dashboard_title ?? '') === 'Painel do Formador';
$is_encarregado_dashboard = (int)($_SESSION['nivel_acesso'] ?? 0) === 4
    && ($dashboard_title ?? '') === 'Painel do Encarregado';
$is_dashboard_hero = $is_formando_dashboard || $is_formador_dashboard;
$formando_academico = null;
$formador_info = null;
$educandos_encarregado = [];

require_once __DIR__ . '/../../config/db.php';

$eventos_proximos = [];
$anuncios_novidades = [];
if (!empty($conn)) {
    $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
    if ($is_formando_dashboard && $usuarioId > 0) {
        $stmt = $conn->prepare(
            "SELECT f.nome_completo, f.codigo_formando, t.nome_turma, tr.nome_turno,
                    f.certificado_vocacional, c.nome_curso, f.ano_ingresso, f.ano_conclusao
             FROM formandos f
             LEFT JOIN cursos c ON c.id = f.curso_id
             LEFT JOIN turmas t ON t.id = f.turma_id
             LEFT JOIN turnos tr ON tr.id = f.turno_id
             WHERE f.usuario_id = ?
             LIMIT 1"
        );
        if ($stmt) {
            $stmt->bind_param('i', $usuarioId);
            if ($stmt->execute()) {
                $stmt->bind_result(
                    $nomeDb,
                    $codigoDb,
                    $turmaDb,
                    $turnoDb,
                    $certificadoDb,
                    $cursoDb,
                    $anoIngressoDb,
                    $anoConclusaoDb
                );
                if ($stmt->fetch()) {
                    $formando_academico = [
                        'nome' => $nomeDb ?: '—',
                        'codigo' => $codigoDb ?: '—',
                        'turma' => $turmaDb ?: '—',
                        'turno' => $turnoDb ?: '—',
                        'certificado' => $certificadoDb ?: '—',
                        'curso' => $cursoDb ?: '—',
                        'ano_ingresso' => $anoIngressoDb ?: '—',
                        'ano_conclusao' => $anoConclusaoDb ?: '—',
                    ];
                }
            }
            $stmt->close();
        }
    }

    if ($is_formador_dashboard && $usuarioId > 0) {
        $stmt = $conn->prepare(
            "SELECT f.nome_completo, f.codigo_formador, f.titulo, f.sexo, f.telefone,
                    f.especialidade, GROUP_CONCAT(DISTINCT c.nome_curso ORDER BY c.nome_curso SEPARATOR ', ') AS cursos
             FROM formadores f
             LEFT JOIN formador_curso fc ON fc.formador_id = f.id
             LEFT JOIN cursos c ON c.id = fc.curso_id
             WHERE f.usuario_id = ?
             GROUP BY f.id
             LIMIT 1"
        );
        if ($stmt) {
            $stmt->bind_param('i', $usuarioId);
            if ($stmt->execute()) {
                $stmt->bind_result(
                    $formadorNomeDb,
                    $formadorCodigoDb,
                    $formadorTituloDb,
                    $formadorSexoDb,
                    $formadorTelefoneDb,
                    $formadorEspecialidadeDb,
                    $formadorCursosDb
                );
                if ($stmt->fetch()) {
                    $formador_info = [
                        'nome' => $formadorNomeDb ?: '—',
                        'codigo' => $formadorCodigoDb ?: '—',
                        'titulo' => $formadorTituloDb ?: '—',
                        'sexo' => $formadorSexoDb ?: '—',
                        'telefone' => $formadorTelefoneDb ?: '',
                        'especialidade' => $formadorEspecialidadeDb ?: '—',
                        'cursos' => $formadorCursosDb ?: '—',
                    ];
                }
            }
            $stmt->close();
        }
    }

    if ($is_encarregado_dashboard && $usuarioId > 0) {
        $stmt = $conn->prepare(
            "SELECT f.id, f.nome_completo, f.codigo_formando, t.nome_turma, tr.nome_turno,
                    c.nome_curso, f.ano_ingresso, f.ano_conclusao, f.certificado_vocacional
             FROM formandos f
             INNER JOIN encarregado_formando ef ON ef.formando_id = f.id
             INNER JOIN encarregados e ON e.id = ef.encarregado_id
             LEFT JOIN turmas t ON t.id = f.turma_id
             LEFT JOIN turnos tr ON tr.id = f.turno_id
             LEFT JOIN cursos c ON c.id = f.curso_id
             WHERE e.usuario_id = ?
             ORDER BY f.nome_completo ASC"
        );
        if ($stmt) {
            $stmt->bind_param('i', $usuarioId);
            if ($stmt->execute()) {
                $stmt->bind_result(
                    $educandoIdDb,
                    $educandoNomeDb,
                    $educandoCodigoDb,
                    $educandoTurmaDb,
                    $educandoTurnoDb,
                    $educandoCursoDb,
                    $educandoAnoIngressoDb,
                    $educandoAnoConclusaoDb,
                    $educandoCertificadoDb
                );
                while ($stmt->fetch()) {
                    $educandos_encarregado[] = [
                        'id' => $educandoIdDb,
                        'nome' => $educandoNomeDb ?: '—',
                        'codigo' => $educandoCodigoDb ?: '—',
                        'turma' => $educandoTurmaDb ?: '—',
                        'turno' => $educandoTurnoDb ?: '—',
                        'curso' => $educandoCursoDb ?: '—',
                        'ano_ingresso' => $educandoAnoIngressoDb ?: '—',
                        'ano_conclusao' => $educandoAnoConclusaoDb ?: '—',
                        'certificado' => $educandoCertificadoDb ?: '—',
                    ];
                }
            }
            $stmt->close();
        }
    }

    $nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
    $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
    $hoje = date('Y-m-d');

    $publicoFilter = '';
    switch ($nivel) {
        case 1:
            $publicoFilter = '1 = 1';
            break;
        case 2:
            $formadorId = getFormadorId($conn, $usuarioId);
            $publicoFilter = "a.publico_alvo IN ('todos','formadores')";
            if ($formadorId > 0) {
                $publicoFilter = "(
                    a.publico_alvo IN ('todos','formadores')
                    OR (a.publico_alvo = 'turma' AND a.turma_id IN (
                        SELECT turma_id FROM formador_modulo WHERE formador_id = $formadorId
                    ))
                )";
            }
            break;
        case 3:
            $turmaId = 0;
            $stmt = $conn->prepare('SELECT turma_id FROM formandos WHERE usuario_id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $usuarioId);
                $stmt->execute();
                $stmt->bind_result($turmaId);
                $stmt->fetch();
                $stmt->close();
            }
            $publicoFilter = "a.publico_alvo IN ('todos','formandos')";
            if ($turmaId > 0) {
                $publicoFilter = "(
                    a.publico_alvo IN ('todos','formandos')
                    OR (a.publico_alvo = 'turma' AND a.turma_id = $turmaId)
                )";
            }
            break;
        case 4:
            $turmaIds = [];
            $res = $conn->query("SELECT DISTINCT f.turma_id FROM formandos f
                INNER JOIN encarregado_formando ef ON ef.formando_id = f.id
                INNER JOIN encarregados e ON e.id = ef.encarregado_id
                WHERE e.usuario_id = $usuarioId
                  AND f.turma_id IS NOT NULL");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $turmaIds[] = (int)$row['turma_id'];
                }
            }
            $publicoFilter = "a.publico_alvo IN ('todos','encarregados')";
            if (!empty($turmaIds)) {
                $turmaList = implode(',', array_unique($turmaIds));
                $publicoFilter = "(
                    a.publico_alvo IN ('todos','encarregados')
                    OR (a.publico_alvo = 'turma' AND a.turma_id IN ($turmaList))
                )";
            }
            break;
        default:
            $publicoFilter = "a.publico_alvo IN ('todos')";
            break;
    }

    $sql = "
        SELECT
            a.titulo,
            a.evento_data_inicio,
            a.evento_data_fim,
            COALESCE(t.nome_turma, '') AS nome_turma,
            a.publico_alvo
        FROM anuncios a
        LEFT JOIN turmas t ON t.id = a.turma_id
        WHERE a.prioridade = 'evento'
          AND (
                a.evento_data_inicio >= '$hoje'
                OR (
                    a.evento_data_inicio <= '$hoje'
                    AND a.evento_data_fim >= '$hoje'
                )
          )
          AND (a.data_expiracao IS NULL OR a.data_expiracao >= '$hoje')
          AND $publicoFilter
        ORDER BY a.evento_data_inicio ASC, a.id DESC
        LIMIT 5
    ";

    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $eventos_proximos[] = $row;
        }
    }

    $sqlNovidades = "
        SELECT
            a.titulo,
            a.prioridade,
            a.publico_alvo,
            COALESCE(t.nome_turma, '') AS nome_turma,
            a.descricao
        FROM anuncios a
        LEFT JOIN turmas t ON t.id = a.turma_id
        WHERE a.prioridade <> 'evento'
          AND (a.data_expiracao IS NULL OR a.data_expiracao >= '$hoje')
          AND $publicoFilter
        ORDER BY a.data_publicacao DESC, a.id DESC
        LIMIT 5
    ";

    $resNovidades = $conn->query($sqlNovidades);
    if ($resNovidades) {
        while ($row = $resNovidades->fetch_assoc()) {
            $anuncios_novidades[] = $row;
        }
    }
}

function format_evento_periodo(?string $inicio, ?string $fim): string
{
    if (!$inicio) {
        return '';
    }

    $inicioDt = date_create($inicio);
    $fimDt = $fim ? date_create($fim) : null;
    if (!$inicioDt) {
        return '';
    }

    $inicioLabel = $inicioDt->format('d/m/Y');
    if ($fimDt && $fimDt > $inicioDt) {
        $fimLabel = $fimDt->format('d/m/Y');
        return "$inicioLabel – $fimLabel";
    }

    return $inicioLabel;
}

function resumo_anuncio(string $html, int $max = 120): string
{
    $text = trim(strip_tags($html));
    if (mb_strlen($text, 'UTF-8') <= $max) {
        return $text;
    }
    return mb_substr($text, 0, $max - 1, 'UTF-8') . '…';
}

if (!isset($searchItems)) {

    $nivel = (int)($_SESSION['nivel_acesso'] ?? 0);
    $menuKey = match ($nivel) {
        1 => 'admin',
        2 => 'formador',
        3 => 'formando',
        4 => 'encarregado',
        default => 'formando',
    };
    $searchItems = [];

    $dashIcon = $MENU['dashboard']['icon'] ?? 'fa-gauge-high';
    $dashUrl  = BASE_URL . 'index.php';
    if (isset($MENU['dashboard']['url'])) {
        $dashUrl = is_callable($MENU['dashboard']['url']) ? $MENU['dashboard']['url']() : $MENU['dashboard']['url'];
    }

    $searchItems[] = [
        'label' => 'Dashboard',
        'icon'  => $dashIcon,
        'url'   => $dashUrl,
        'category' => 'Principal',
    ];


    if (isset($MENU[$menuKey]['items'])) {
        foreach ($MENU[$menuKey]['items'] as $group) {
            if (!empty($group['children']) && is_array($group['children'])) {
                foreach ($group['children'] as $child) {
                    $searchItems[] = [
                        'label' => $child['label'],
                        'icon' => $child['icon'],
                        'url' => $child['url'],
                        'category' => $group['label'],
                    ];
                }
            } elseif (isset($group['url'])) {
                $searchItems[] = [
                    'label' => $group['label'],
                    'icon' => $group['icon'],
                    'url' => $group['url'],
                    'category' => $group['label'],
                ];
            }
        }
    }
}

$layoutClass = $show_dashboard_stats ? 'dashboard-layout' : 'dashboard-layout dashboard-layout--no-content';
if ($is_formando_dashboard) {
    $layoutClass .= ' dashboard-layout--formando';
}
?>
<script>
    window.__IICAEG_MENU = <?= json_encode($searchItems, JSON_UNESCAPED_UNICODE) ?>;
</script>

<div class="<?= htmlspecialchars($layoutClass) ?>">

    <div class="dashboard-intro">
        <div class="dashboard-intro-copy">
            <h1><?= htmlspecialchars($dashboard_title) ?></h1>
            <p><?= saudacaoPorHorario() ?>,
                <span class="user-name">
                    <a href="<?= BASE_URL ?>pages/perfil.php" style="color: var(--accent); text-decoration: none; font-weight: 600;">
                        <?= htmlspecialchars(primeiroNome()) ?></a>!
                </span>
            </p>
        </div>
        <?php if ($is_formando_dashboard): ?>
            <img class="dashboard-student-art" src="<?= BASE_URL ?>assets/img/student.webp" alt="" aria-hidden="true">
        <?php endif; ?>
    </div>

    <div class="dashboard-search-section">
        <div class="dash-search-container" id="dash-search-container">
            <div class="dash-search-box">
                <input type="text" id="dash-search-input" placeholder="Pesquisar" autocomplete="off">
                <button type="button" class="dash-search-action" id="dash-search-action" aria-label="Pesquisar">
                    <i class="fa-solid fa-magnifying-glass" id="dash-search-action-icon"></i>
                </button>
                <div class="dash-search-results" id="dash-search-results"></div>
            </div>
        </div>
        <div class="search-overlay" id="search-overlay"></div>
    </div>

    <?php if ($is_formador_dashboard): ?>
        <?php
        $formador = $formador_info ?: [
            'nome' => '—',
            'codigo' => '—',
            'titulo' => '—',
            'sexo' => '—',
            'telefone' => '',
            'especialidade' => '—',
            'cursos' => '—',
        ];
        ?>
        <section class="dashboard-profile-card">
            <div class="dashboard-academic-head">
                <div>
                    <span class="dashboard-academic-kicker">Visão Geral</span>
                    <h2>Informações do Formador</h2>
                </div>
                <span class="dashboard-academic-code"><?= htmlspecialchars($formador['codigo']) ?></span>
            </div>
            <div class="dashboard-academic-grid">
                <div class="dashboard-academic-item">
                    <span>Nome</span>
                    <strong><?= htmlspecialchars($formador['nome']) ?></strong>
                </div>
                <div class="dashboard-academic-item">
                    <span>Título</span>
                    <strong><?= htmlspecialchars($formador['titulo']) ?></strong>
                </div>
                <div class="dashboard-academic-item">
                    <span>Especialidade</span>
                    <strong><?= htmlspecialchars($formador['especialidade']) ?></strong>
                </div>
                <div class="dashboard-academic-item dashboard-academic-item--wide">
                    <span>Cursos associados</span>
                    <strong><?= htmlspecialchars($formador['cursos']) ?></strong>
                </div>
                <div class="dashboard-academic-item">
                    <span>Telefone</span>
                    <strong><?= htmlspecialchars($formador['telefone'] ?: '—') ?></strong>
                </div>
                <div class="dashboard-academic-item">
                    <span>Sexo</span>
                    <strong><?= htmlspecialchars($formador['sexo']) ?></strong>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($is_formando_dashboard): ?>
        <?php
        $academico = $formando_academico ?: [
            'nome' => '—',
            'codigo' => '—',
            'turma' => '—',
            'turno' => '—',
            'certificado' => '—',
            'curso' => '—',
            'ano_ingresso' => '—',
            'ano_conclusao' => '—',
        ];
        ?>
        <section class="dashboard-academic-card">
            <div class="dashboard-academic-head">
                <div>
                    <span class="dashboard-academic-kicker">Visão Geral</span>
                    <h2>Informações Académicas</h2>
                </div>
                <span class="dashboard-academic-code"><?= htmlspecialchars($academico['codigo']) ?></span>
            </div>
            <div class="dashboard-academic-grid">
                <div class="dashboard-academic-item">
                    <span>Nome</span>
                    <strong><?= htmlspecialchars($academico['nome']) ?></strong>
                </div>
                <div class="dashboard-academic-item">
                    <span>Turma</span>
                    <strong><?= htmlspecialchars($academico['turma']) ?></strong>
                </div>
                <div class="dashboard-academic-item">
                    <span>Turno</span>
                    <strong><?= htmlspecialchars($academico['turno']) ?></strong>
                </div>
                <div class="dashboard-academic-item">
                    <span>Certificado vocacional</span>
                    <strong><?= htmlspecialchars($academico['certificado']) ?></strong>
                </div>
                <div class="dashboard-academic-item dashboard-academic-item--wide">
                    <span>Qualificação</span>
                    <strong><?= htmlspecialchars($academico['curso']) ?></strong>
                </div>
                <div class="dashboard-academic-item">
                    <span>Ano de ingresso</span>
                    <strong><?= htmlspecialchars($academico['ano_ingresso']) ?></strong>
                </div>
                <div class="dashboard-academic-item">
                    <span>Ano de conclusão</span>
                    <strong><?= htmlspecialchars($academico['ano_conclusao']) ?></strong>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <aside class="dashboard-sidebar">
        <div class="dash-sidebar-section">
            <div class="dash-sidebar-header">
                <i class="fa-regular fa-file-lines"></i>
                <h2>Novidades</h2>
            </div>
            <div class="dash-sidebar-body" id="dash-novidades">
                <?php if (empty($anuncios_novidades)): ?>
                    <p class="dash-sidebar-empty">Não tem novidades.</p>
                <?php else: ?>
                    <?php foreach ($anuncios_novidades as $anuncio): ?>
                        <article class="anuncio-card priority-<?= htmlspecialchars($anuncio['prioridade'] ?: 'normal') ?>">
                            <div class="anuncio-body">
                                <h3><?= htmlspecialchars($anuncio['titulo']) ?></h3>
                                <div class="anuncio-content"><?= htmlspecialchars(resumo_anuncio($anuncio['descricao'])) ?></div>
                                <?php if (!empty($anuncio['nome_turma'])): ?>
                                    <div class="anuncio-event-dates">Turma <?= htmlspecialchars($anuncio['nome_turma']) ?></div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="#" class="dash-sidebar-link">Ver mais</a>
            </div>
        </div>

        <div class="dash-sidebar-section">
            <div class="dash-sidebar-header">
                <i class="fa-regular fa-calendar"></i>
                <h2>Próximos Eventos</h2>
            </div>
            <div class="dash-sidebar-body" id="dash-eventos">
                <?php if (empty($eventos_proximos)): ?>
                    <p class="dash-sidebar-empty">Não tem eventos próximos.</p>
                <?php else: ?>
                    <?php foreach ($eventos_proximos as $evento): ?>
                        <article class="anuncio-card priority-evento">
                            <div class="anuncio-body">
                                <h3><?= htmlspecialchars($evento['titulo']) ?></h3>
                                <div class="anuncio-event-dates">
                                    <i class="fa-solid fa-calendar-days"></i>
                                    <span><?= htmlspecialchars(format_evento_periodo($evento['evento_data_inicio'], $evento['evento_data_fim'])) ?></span>
                                </div>
                                <?php if (!empty($evento['nome_turma'])): ?>
                                    <div class="anuncio-content">Turma <?= htmlspecialchars($evento['nome_turma']) ?></div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="#" class="dash-sidebar-link">Ver calendário</a>
            </div>
        </div>
        <?php if ($is_formando_dashboard): ?>
            <div class="dash-sidebar-section dash-horario-section" id="dash_formando_horario"
                data-horario-url="<?= BASE_URL ?>api/formando_horario_hoje.php"
                data-grade-url="<?= BASE_URL ?>api/horario_grade_preview.php">
                <div class="dash-sidebar-header">
                    <i class="fa-regular fa-clock"></i>
                    <h2>Meu Horário</h2>
                </div>
                <div class="dash-sidebar-body">
                    <div class="detail-horario-meta" id="detalhe_horario_meta">A carregar horário...</div>
                    <div class="detail-horario-list" id="detalhe_horario_list"></div>
                    <div class="detail-horario-actions" id="detalhe_horario_actions">
                        <button type="button" class="btn-text" id="btn_horario_toggle_all" style="display:none;">Mostrar tudo</button>
                        <button type="button" class="btn-text" id="btn_ver_horario_turma" disabled>Ver horário</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </aside>

    <?php if ($is_encarregado_dashboard && !empty($educandos_encarregado)): ?>
        <section class="dashboard-educandos-card dashboard-content-card">
            <div class="dashboard-academic-head">
                <div>
                    <h2>Educandos</h2>
                </div>
            </div>
            <p class="dashboard-educandos-description">Seleccione o educando a exibir informações.</p>
            <div class="dashboard-educandos-table-wrap">
                <table class="dashboard-educandos-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Código</th>
                            <th>Opção</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($educandos_encarregado as $index => $educando): ?>
                            <tr
                                data-id="<?= (int)$educando['id'] ?>"
                                data-nome="<?= htmlspecialchars($educando['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                data-codigo="<?= htmlspecialchars($educando['codigo'], ENT_QUOTES, 'UTF-8') ?>"
                                data-turma="<?= htmlspecialchars($educando['turma'], ENT_QUOTES, 'UTF-8') ?>"
                                data-turno="<?= htmlspecialchars($educando['turno'], ENT_QUOTES, 'UTF-8') ?>"
                                data-curso="<?= htmlspecialchars($educando['curso'], ENT_QUOTES, 'UTF-8') ?>"
                                data-certificado="<?= htmlspecialchars($educando['certificado'], ENT_QUOTES, 'UTF-8') ?>"
                                data-ano-ingresso="<?= htmlspecialchars($educando['ano_ingresso'], ENT_QUOTES, 'UTF-8') ?>"
                                data-ano-conclusao="<?= htmlspecialchars($educando['ano_conclusao'], ENT_QUOTES, 'UTF-8') ?>">
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($educando['nome']) ?></td>
                                <td><?= htmlspecialchars($educando['codigo']) ?></td>
                                <td>
                                    <label class="dashboard-radio">
                                        <input type="radio" name="educando_selection" value="<?= (int)$educando['id'] ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="dashboard-educando-details" id="dashboard-educando-details">
                <?php $educando_selecionado = $educandos_encarregado[0] ?? null; ?>
                <div class="dashboard-academic-head">
                    <div>
                        <span class="dashboard-academic-kicker">Visão Geral</span>
                        <h2 data-educando-field="nome"><?= htmlspecialchars($educando_selecionado['nome'] ?? '—') ?></h2>
                    </div>
                    <span class="dashboard-academic-code" data-educando-field="codigo"><?= htmlspecialchars($educando_selecionado['codigo'] ?? '—') ?></span>
                </div>
                <div class="dashboard-academic-grid">
                    <div class="dashboard-academic-item">
                        <span>Turma</span>
                        <strong data-educando-field="turma"><?= htmlspecialchars($educando_selecionado['turma'] ?? '—') ?></strong>
                    </div>
                    <div class="dashboard-academic-item">
                        <span>Turno</span>
                        <strong data-educando-field="turno"><?= htmlspecialchars($educando_selecionado['turno'] ?? '—') ?></strong>
                    </div>
                    <div class="dashboard-academic-item">
                        <span>Certificado vocacional</span>
                        <strong data-educando-field="certificado"><?= htmlspecialchars($educando_selecionado['certificado'] ?? '—') ?></strong>
                    </div>
                    <div class="dashboard-academic-item dashboard-academic-item--wide">
                        <span>Qualificação</span>
                        <strong data-educando-field="curso"><?= htmlspecialchars($educando_selecionado['curso'] ?? '—') ?></strong>
                    </div>
                    <div class="dashboard-academic-item">
                        <span>Ano de ingresso</span>
                        <strong data-educando-field="ano-ingresso"><?= htmlspecialchars($educando_selecionado['ano_ingresso'] ?? '—') ?></strong>
                    </div>
                    <div class="dashboard-academic-item">
                        <span>Ano de conclusão</span>
                        <strong data-educando-field="ano-conclusao"><?= htmlspecialchars($educando_selecionado['ano_conclusao'] ?? '—') ?></strong>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($show_dashboard_stats): ?>
        <div class="dashboard-content">
            <?php require_once __DIR__ . '/dashboard-admin-stats.php'; ?>
        </div>
    <?php endif; ?>

</div>

<?php if ($is_formando_dashboard): ?>
    <div class="modal" id="modal_horario_turma">
        <div class="modal-content modal-view-content">
            <div class="modal-header">
                <h2>Meu Horário</h2>
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
<?php endif; ?>