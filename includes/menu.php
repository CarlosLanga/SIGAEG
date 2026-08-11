<?php

$MENU = [

    'dashboard' => [
        'label' => 'Dashboard',
        'icon'  => 'fa-gauge-high',
        'url'   => function () {
            switch ((int)$_SESSION['nivel_acesso']) {
                case 1:
                    return BASE_URL . 'pages/admin/dashboard.php';
                case 2:
                    return BASE_URL . 'pages/formador/dashboard.php';
                case 3:
                    return BASE_URL . 'pages/formando/dashboard.php';
                case 4:
                    return BASE_URL . 'pages/encarregado/dashboard.php';
                default:
                    return BASE_URL . 'pages/formando/dashboard.php';
            }
        }
    ],

    'admin' => [
        'only' => 1,
        'items' => [
            [
                'label' => 'Formandos',
                'icon'  => 'fa-user-graduate',
                'children' => [
                    ['label' => 'Adicionar Formando', 'icon' => 'fa-user-plus', 'url' => BASE_URL . 'pages/admin/formando_adicionar.php'],
                    ['label' => 'Gerir Formandos', 'icon' => 'fa-users-gear', 'url' => BASE_URL . 'pages/admin/formandos_gerir.php'],
                ]
            ],
            [
                'label' => 'Turmas',
                'icon'  => 'fa-users-rectangle',
                'children' => [
                    ['label' => 'Criar Turma', 'icon' => 'fa-folder-plus', 'url' => BASE_URL . 'pages/admin/turma_criar.php'],
                    ['label' => 'Gerir Turmas', 'icon' => 'fa-layer-group', 'url' => BASE_URL . 'pages/admin/turmas_gerir.php'],
                ]
            ],
            [
                'label' => 'Módulos',
                'icon'  => 'fa-book-bookmark',
                'children' => [
                    ['label' => 'Adicionar Módulo', 'icon' => 'fa-file-circle-plus', 'url' => BASE_URL . 'pages/admin/modulos_adicionar.php'],
                    ['label' => 'Gerir Módulos', 'icon' => 'fa-swatchbook', 'url' => BASE_URL . 'pages/admin/modulos_gerir.php'],
                ]
            ],
            [
                'label' => 'Horários',
                'icon'  => 'fa-calendar-days',
                'children' => [
                    ['label' => 'Adicionar Horário', 'icon' => 'fa-calendar-plus', 'url' => BASE_URL . 'pages/admin/horario_adicionar.php'],
                    ['label' => 'Gerir Horários', 'icon' => 'fa-calendar-check', 'url' => BASE_URL . 'pages/admin/horarios_gerir.php'],
                ]
            ],
            [
                'label' => 'Presenças',
                'icon'  => 'fa-clipboard-user',
                'children' => [
                    ['label' => 'Ver Presenças', 'icon' => 'fa-eye', 'url' => BASE_URL . 'pages/admin/presencas_ver.php'],
                    ['label' => 'Marcar Presenças', 'icon' => 'fa-check-double', 'url' => BASE_URL . 'pages/admin/presencas_marcar.php'],
                ]
            ],
            [
                'label' => 'Avaliações',
                'icon'  => 'fa-file-signature',
                'children' => [
                    ['label' => 'Marcar Avaliação', 'icon' => 'fa-pen-to-square', 'url' => BASE_URL . 'pages/admin/avaliacoes_marcar.php'],
                    ['label' => 'Resultados', 'icon' => 'fa-square-poll-vertical', 'url' => BASE_URL . 'pages/admin/avaliacoes_resultados.php'],
                    ['label' => 'Pauta Final', 'icon' => 'fa-clipboard-list', 'url' => BASE_URL . 'pages/admin/pauta_final.php'],
                ]
            ],
            [
                'label' => 'Trabalhos',
                'icon'  => 'fa-briefcase',
                'children' => [
                    ['label' => 'Marcar Trabalho', 'icon' => 'fa-thumbtack', 'url' => BASE_URL . 'pages/admin/trabalhos_marcar.php'],
                    ['label' => 'Gerir Trabalhos', 'icon' => 'fa-bars-progress', 'url' => BASE_URL . 'pages/admin/trabalhos_gerir.php'],
                ]
            ],
            [
                'label' => 'Formadores',
                'icon'  => 'fa-chalkboard-user',
                'children' => [
                    ['label' => 'Adicionar Formador', 'icon' => 'fa-user-tie', 'url' => BASE_URL . 'pages/admin/formador_adicionar.php'],
                    ['label' => 'Gerir Formadores', 'icon' => 'fa-address-book', 'url' => BASE_URL . 'pages/admin/formadores_gerir.php'],
                ]
            ],
            [
                'label' => 'Mensagens',
                'icon'  => 'fa-envelope',
                'children' => [
                    ['label' => 'Enviar Mensagem', 'icon' => 'fa-paper-plane', 'url' => BASE_URL . 'pages/admin/mensagens_enviar.php'],
                    ['label' => 'Gerir Mensagens', 'icon' => 'fa-envelope-open-text', 'url' => BASE_URL . 'pages/admin/mensagens_gerir.php'],
                ]
            ],
            [
                'label' => 'Anúncios',
                'icon'  => 'fa-bullhorn',
                'children' => [
                    ['label' => 'Fazer Anúncio', 'icon' => 'fa-plus-circle', 'url' => BASE_URL . 'pages/admin/anuncios_criar.php'],
                    ['label' => 'Gerir Anúncios', 'icon' => 'fa-list-check', 'url' => BASE_URL . 'pages/admin/anuncios_gerir.php'],
                ]
            ],
            [
                'label' => 'Administrador',
                'icon'  => 'fa-user-shield',
                'children' => [
                    ['label' => 'Utilizadores', 'icon' => 'fa-users', 'url' => BASE_URL . 'pages/admin/usuarios_gerir.php'],
                    ['label' => 'Logs do Sistema', 'icon' => 'fa-list', 'url' => BASE_URL . 'pages/admin/logs.php'],
                ]
            ],
        ]
    ],

    'formador' => [
        'only' => 2,
        'items' => [
            [
                'label' => 'Ensino',
                'icon'  => 'fa-chalkboard-user',
                'children' => [
                    ['label' => 'Turmas', 'icon' => 'fa-layer-group', 'url' => BASE_URL . 'pages/formador/formador_turmas.php'],
                    ['label' => 'Módulos', 'icon' => 'fa-swatchbook', 'url' => BASE_URL . 'pages/formador/formador_modulos.php'],
                    ['label' => 'Horários', 'icon' => 'fa-calendar-check', 'url' => BASE_URL . 'pages/formador/formador_horario.php'],
                ]
            ],
            [
                'label' => 'Presenças',
                'icon'  => 'fa-clipboard-user',
                'children' => [
                    ['label' => 'Marcar Presenças', 'icon' => 'fa-check-double', 'url' => BASE_URL . 'pages/formador/formador_presencas.php'],
                    ['label' => 'Gerir Presenças', 'icon' => 'fa-eye', 'url' => BASE_URL . 'pages/formador/formador_presencas_gerir.php'],
                ]
            ],
            [
                'label' => 'Avaliações',
                'icon'  => 'fa-file-signature',
                'children' => [
                    ['label' => 'Marcar Avaliação', 'icon' => 'fa-pen-to-square', 'url' => BASE_URL . 'pages/formador/formador_avaliacoes.php'],
                    ['label' => 'Resultados', 'icon' => 'fa-square-poll-vertical', 'url' => BASE_URL . 'pages/formador/formador_resultados.php'],
                    ['label' => 'Pauta Final', 'icon' => 'fa-clipboard-list', 'url' => BASE_URL . 'pages/formador/formador_pauta_final.php'],
                ]
            ],
            [
                'label' => 'Trabalhos',
                'icon'  => 'fa-briefcase',
                'children' => [
                    ['label' => 'Marcar Trabalhos', 'icon' => 'fa-thumbtack', 'url' => BASE_URL . 'pages/formador/formador_trabalhos_marcar.php'],
                    ['label' => 'Gerir Trabalhos', 'icon' => 'fa-bars-progress', 'url' => BASE_URL . 'pages/formador/formador_trabalhos_gerir.php'],
                ]
            ],
            [
                'label' => 'Anúncios',
                'icon'  => 'fa-bullhorn',
                'children' => [
                    ['label' => 'Fazer Anúncio', 'icon' => 'fa-plus-circle', 'url' => BASE_URL . 'pages/formador/formador_anuncios_criar.php'],
                    ['label' => 'Gerir Anúncios', 'icon' => 'fa-list-check', 'url' => BASE_URL . 'pages/formador/formador_anuncios_gerir.php'],
                ]
            ],
        ]
    ],

    'formando' => [
        'only' => 3,
        'items' => [
            [
                'label' => 'Horário',
                'icon'  => 'fa-calendar-days',
                'children' => [
                    ['label' => 'Meu Horário', 'icon' => 'fa-calendar-check', 'url' => BASE_URL . 'pages/formando/formando_horario.php'],
                    ['label' => 'Lista de Horários', 'icon' => 'fa-list', 'url' => BASE_URL . 'pages/formando/formando_horarios_lista.php'],
                ]
            ],
            [
                'label' => 'Turmas',
                'icon'  => 'fa-users-rectangle',
                'url'   => BASE_URL . 'pages/formando/formando_turmas.php',
            ],
            [
                'label' => 'Progresso Académico',
                'icon'  => 'fa-file-signature',
                'children' => [
                    ['label' => 'Meus Módulos', 'icon' => 'fa-swatchbook', 'url' => BASE_URL . 'pages/formando/formando_modulos.php'],
                    ['label' => 'Registo de Frequências', 'icon' => 'fa-clipboard-user', 'url' => BASE_URL . 'pages/formando/formando_presencas.php'],
                    ['label' => 'Avaliações', 'icon' => 'fa-square-poll-vertical', 'url' => BASE_URL . 'pages/formando/formando_notas.php'],
                ]
            ],
            [
                'label' => 'Trabalhos',
                'icon'  => 'fa-briefcase',
                'url'   => BASE_URL . 'pages/formando/formando_trabalhos.php',
            ],
            [
                'label' => 'Ficheiros',
                'icon'  => 'fa-folder-open',
                'url'   => BASE_URL . 'pages/formando/formando_ficheiros.php',
            ],
            [
                'label' => 'Mensagens',
                'icon'  => 'fa-envelope',
                'url'   => BASE_URL . 'pages/formando/formando_mensagens.php',
            ],
        ]
    ],

    'encarregado' => [
        'only' => 4,
        'items' => [
            [
                'label' => 'Educandos',
                'icon'  => 'fa-user-graduate',
                'url' => BASE_URL . 'pages/encarregado/encarregado_educandos.php',
            ],
            [
                'label' => 'Horário',
                'icon'  => 'fa-calendar-days',
                'children' => [
                    ['label' => 'Meu Horário', 'icon' => 'fa-calendar-check', 'url' => BASE_URL . 'pages/encarregado/encarregado_horario.php'],
                    ['label' => 'Lista de Horários', 'icon' => 'fa-list', 'url' => BASE_URL . 'pages/encarregado/encarregado_horarios_lista.php'],
                ]
            ],
            [
                'label' => 'Turmas',
                'icon'  => 'fa-users-rectangle',
                'url' => BASE_URL . 'pages/encarregado/encarregado_turmas.php',
            ],
            [
                'label' => 'Progresso Académico',
                'icon'  => 'fa-file-signature',
                'children' => [
                    ['label' => 'Módulos', 'icon' => 'fa-swatchbook', 'url' => BASE_URL . 'pages/encarregado/encarregado_modulos.php'],
                    ['label' => 'Registo de Frequências', 'icon' => 'fa-clipboard-user', 'url' => BASE_URL . 'pages/encarregado/encarregado_presencas.php'],
                    ['label' => 'Avaliações', 'icon' => 'fa-square-poll-vertical', 'url' => BASE_URL . 'pages/encarregado/encarregado_resultados.php'],
                ]
            ],
            [
                'label' => 'Trabalhos',
                'icon'  => 'fa-briefcase',
                'url' => BASE_URL . 'pages/encarregado/encarregado_trabalhos.php',
            ],
            [
                'label' => 'Ficheiros',
                'icon'  => 'fa-folder-open',
                'url' => BASE_URL . 'pages/encarregado/encarregado_ficheiros.php',
            ],
        ]
    ],

    'extras' => [
        ['label' => 'Ficheiros', 'icon' => 'fa-folder-open', 'url' => BASE_URL . 'pages/ficheiros.php', 'only' => 1],
        ['label' => 'Ficheiros', 'icon' => 'fa-folder-open', 'url' => BASE_URL . 'pages/ficheiros.php', 'only' => 2],
    ]
];
