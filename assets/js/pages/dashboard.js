document.addEventListener('DOMContentLoaded', function () {
    initDashboardChart();
    initDashboardSearch();
    initEncarregadoDashboardEducandos();
    initFormandoDashboardHorario();
});

function initDashboardChart() {
    const baseUrl = document.body.dataset.baseUrl || '';
    const canvas = document.getElementById('chartGenero');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const percentLabels = {
        id: 'percentLabels',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            const dataset = chart.data.datasets[0];
            const meta = chart.getDatasetMeta(0);
            const total = (dataset.data || []).reduce((sum, v) => sum + (Number(v) || 0), 0);
            if (!total) return;

            ctx.save();
            ctx.fillStyle = '#ffffff';
            ctx.font = '600 12px Poppins, Arial, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            meta.data.forEach((arc, i) => {
                const value = Number(dataset.data[i]) || 0;
                if (!value) return;
                const percent = Math.round((value / total) * 100);
                const pos = arc.tooltipPosition();
                ctx.fillText(`${percent}%`, pos.x, pos.y);
            });
            ctx.restore();
        }
    };

    fetch(`${baseUrl}api/formandos_genero_stats.php`)
        .then(r => r.json())
        .then(data => {
            if (!data.ok) return;

            const total = (data.masculino || 0) + (data.feminino || 0);
            const titleEl = document.querySelector('.chart-card .section-title');
            if (titleEl) titleEl.textContent = `Total de formandos: ${total}`;

            if (window.chartGenero && typeof window.chartGenero.destroy === 'function') {
                window.chartGenero.destroy();
            }

            window.chartGenero = new Chart(ctx, {
                type: 'doughnut',
                plugins: [percentLabels],
                data: {
                    labels: ['Masculino', 'Feminino'],
                    datasets: [{
                        data: [data.masculino, data.feminino],
                        backgroundColor: ['#3498db', '#e91e63'],
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    events: ['mousemove', 'mouseout', 'click', 'touchstart', 'touchmove'],
                    layout: {
                        padding: 12
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    animations: {
                        rotate: { duration: 1200, easing: 'easeOutQuart' },
                        scale: { duration: 1200, easing: 'easeOutQuart' }
                    },
                    hover: { mode: 'nearest', intersect: true, animationDuration: 200 },
                    interaction: { mode: 'nearest', intersect: true },
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 10,
                                boxHeight: 10,
                                padding: 16
                            },
                            onClick: (evt, item, legend) => {
                                const chart = legend.chart;
                                const dataset = chart.data.datasets[0];
                                const index = item.index;

                                const selected = chart.$selectedIndex === index ? null : index;
                                chart.$selectedIndex = selected;

                                dataset.hoverOffset = dataset.data.map((_, i) => (i === selected ? 18 : 8));
                                dataset.borderWidth = dataset.data.map((_, i) => (i === selected ? 3 : 0));
                                dataset.borderColor = dataset.data.map((_, i) => (i === selected ? '#ffffff' : 'transparent'));

                                if (selected !== null) {
                                    chart.setActiveElements([{ datasetIndex: 0, index: selected }]);
                                } else {
                                    chart.setActiveElements([]);
                                }
                                chart.update();
                            }
                        },
                        tooltip: { enabled: true }
                    }
                }
            });

            // Pra poder forçar a animacao
            requestAnimationFrame(() => {
                window.chartGenero.reset();
                window.chartGenero.update();
            });
        });
}

function initDashboardSearch() {
    const $searchContainer = $('#dash-search-container');
    const $searchInput = $('#dash-search-input');
    if (!$searchContainer.length || !$searchInput.length) return;
    const $searchResults = $('#dash-search-results');
    const $searchAction = $('#dash-search-action');
    const $searchActionIcon = $('#dash-search-action-icon');
    const $overlay = $('#search-overlay');
    const menuItems = window.__IICAEG_MENU || [];
    let selectedIndex = -1;

    function updateSearchActionIcon() {
        const hasText = $searchInput.val().trim().length > 0;
        const iconClass = hasText ? 'fa-xmark' : 'fa-magnifying-glass';
        $searchAction.attr('aria-label', hasText ? 'Limpar pesquisa' : 'Pesquisar');
        $searchActionIcon
            .removeClass('fa-magnifying-glass fa-xmark')
            .addClass(iconClass);
        $searchContainer.toggleClass('has-text', hasText);
    }

    function activateSearch() {
        $searchContainer.addClass('active');
        $overlay.addClass('visible');
        renderResults($searchInput.val().trim());
    }

    function deactivateSearch() {
        $searchContainer.removeClass('active');
        $overlay.removeClass('visible');
        $searchResults.removeClass('visible');
        selectedIndex = -1;
    }

    function runSearch() {
        activateSearch();
        renderResults($searchInput.val().trim());
    }

    function clearSearch() {
        $searchInput.val('');
        updateSearchActionIcon();
        renderResults('');
        $searchInput.focus();
    }

    $searchInput.on('focus', activateSearch);

    $overlay.on('click', deactivateSearch);

    $searchAction.on('click', function() {
        if ($searchInput.val().trim().length > 0) {
            clearSearch();
        } else {
            runSearch();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $searchContainer.hasClass('active')) {
            deactivateSearch();
            $searchInput.blur();
        }
    });

    $searchInput.on('input', function() {
        updateSearchActionIcon();
        renderResults($(this).val().trim());
    });

    updateSearchActionIcon();

    function renderResults(query) {
        const q = query.toLowerCase();
        const filtered = q 
            ? menuItems.filter(item => 
                item.label.toLowerCase().includes(q) || 
                item.category.toLowerCase().includes(q)
            ) 
            : menuItems.slice(0, 8); // Mostrar sugestões se vazio

        if (q && filtered.length === 0) {
            $searchResults.html(`
                <div class="search-no-results">
                    <i class="fa-solid fa-face-frown"></i>
                    <p>Nenhum menu encontrado para "${query}"</p>
                </div>
            `).addClass('visible');
            return;
        }

        if (filtered.length === 0) {
            $searchResults.removeClass('visible');
            return;
        }

        // COM isso é possivel listar de categoria em categoria
        const groups = {};
        filtered.forEach(item => {
            if (!groups[item.category]) groups[item.category] = [];
            groups[item.category].push(item);
        });

        let html = '';
        Object.keys(groups).forEach(cat => {
            html += `<div class="search-cat-title">${cat}</div>`;
            groups[cat].forEach(item => {
                html += `
                    <a href="${item.url}" class="search-item">
                        <i class="fa-solid ${item.icon}"></i>
                        <span class="search-label">${item.label}</span>
                    </a>
                `;
            });
        });

        $searchResults.html(html).addClass('visible');
        selectedIndex = -1;
    }

    // Navegação por keys
    $searchInput.on('keydown', function(e) {
        const $items = $searchResults.find('.search-item');
        if (!$items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, $items.length - 1);
            highlightItem($items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, 0);
            highlightItem($items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIndex >= 0) {
                $items.eq(selectedIndex)[0].click();
            } else {
                runSearch();
            }
        }
    });

    function highlightItem($items) {
        $items.removeClass('selected');
        if (selectedIndex >= 0) {
            const $target = $items.eq(selectedIndex);
            $target.addClass('selected');
            $target[0].scrollIntoView({ block: 'nearest' });
        }
    }
}

function initEncarregadoDashboardEducandos() {
    const $card = $('.dashboard-educandos-card');
    if (!$card.length) return;

    const $details = $('#dashboard-educando-details');
    if (!$details.length) return;

    function updateDetails($row) {
        if (!$row || !$row.length) {
            return;
        }

        const fields = {
            nome: $row.attr('data-nome') || '—',
            codigo: $row.attr('data-codigo') || '—',
            turma: $row.attr('data-turma') || '—',
            turno: $row.attr('data-turno') || '—',
            certificado: $row.attr('data-certificado') || '—',
            curso: $row.attr('data-curso') || '—',
            'ano-ingresso': $row.attr('data-ano-ingresso') || '—',
            'ano-conclusao': $row.attr('data-ano-conclusao') || '—'
        };

        $details.find('[data-educando-field="nome"]').text(fields.nome);
        $details.find('[data-educando-field="codigo"]').text(fields.codigo);
        $details.find('[data-educando-field="turma"]').text(fields.turma);
        $details.find('[data-educando-field="turno"]').text(fields.turno);
        $details.find('[data-educando-field="certificado"]').text(fields.certificado);
        $details.find('[data-educando-field="curso"]').text(fields.curso);
        $details.find('[data-educando-field="ano-ingresso"]').text(fields['ano-ingresso']);
        $details.find('[data-educando-field="ano-conclusao"]').text(fields['ano-conclusao']);

        $card.find('tbody tr').removeClass('is-selected');
        $row.addClass('is-selected');
    }

    $card.on('change', 'input[name="educando_selection"]', function () {
        const id = $(this).val();
        const $row = $card.find(`tr[data-id="${id}"]`);
        updateDetails($row);
    });

    $card.on('click', 'tbody tr', function (e) {
        if ($(e.target).is('input[type="radio"]')) return;
        const $radio = $(this).find('input[type="radio"]');
        if ($radio.length) {
            $radio.prop('checked', true).trigger('change');
        }
    });

    const $selected = $card.find('input[name="educando_selection"]:checked');
    if ($selected.length) {
        const $row = $card.find(`tr[data-id="${$selected.val()}"]`);
        updateDetails($row);
    }
}

function initFormandoDashboardHorario() {
    const $section = $('#dash_formando_horario');
    if (!$section.length) return;

    const $meta = $('#detalhe_horario_meta');
    const $list = $('#detalhe_horario_list');
    const $toggle = $('#btn_horario_toggle_all');
    const $view = $('#btn_ver_horario_turma');
    const $modal = $('#modal_horario_turma');
    const $grid = $('#detalhe_horario_grid');
    const $modalMeta = $('#detalhe_horario_meta_modal');

    const horarioUrl = $section.data('horario-url');
    const gradeUrl = $section.data('grade-url');
    let rows = [];
    let groups = [];
    let plano = null;
    let turmaId = 0;
    let mostrarTudo = false;
    let gradeLoaded = false;
    let timer = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function minutes(time) {
        const match = /^(\d{2}):(\d{2})$/.exec(String(time || '').trim());
        if (!match) return null;
        return (Number(match[1]) * 60) + Number(match[2]);
    }

    function slotState(group) {
        const start = minutes(group.inicio_hora);
        const end = minutes(group.fim_hora);
        const now = new Date();
        const current = (now.getHours() * 60) + now.getMinutes();

        if (start === null || end === null) {
            return { status: 'upcoming', progress: 0 };
        }
        if (current < start) {
            return { status: 'upcoming', progress: 0 };
        }
        if (current >= end) {
            return { status: 'completed', progress: 100 };
        }

        const duration = Math.max(1, end - start);
        const progress = Math.max(0, Math.min(100, Math.round(((current - start) / duration) * 100)));
        return { status: 'current', progress };
    }

    function groupSchedule(items) {
        const grouped = [];
        items.forEach((item) => {
            const last = grouped[grouped.length - 1];
            const sameModule = last
                && String(last.formador_modulo_id || '') === String(item.formador_modulo_id || '')
                && String(last.formador_nome || '') === String(item.formador_nome || '');

            if (sameModule) {
                last.fim_hora = item.fim_hora || last.fim_hora;
                last.slots.push(item.slot_codigo);
                return;
            }

            grouped.push({
                ...item,
                inicio_hora: item.inicio_hora,
                fim_hora: item.fim_hora,
                slots: [item.slot_codigo]
            });
        });
        return grouped;
    }

    function canViewGrade() {
        return turmaId > 0
            && plano
            && [1, 2].includes(Number(plano.semestre))
            && [1, 2].includes(Number(plano.bloco));
    }

    function renderToday() {
        if (!$list.length) return;

        if (!groups.length) {
            $list.html('<p class="detail-empty-note"><i class="fa-solid fa-clock"></i> Sem aulas para hoje.</p>');
            $toggle.hide();
            return;
        }

        const currentGroups = groups.filter((group) => slotState(group).status === 'current');
        const visibleGroups = mostrarTudo ? groups : currentGroups;
        const showToggle = currentGroups.length < groups.length;

        $toggle
            .toggle(showToggle)
            .text(mostrarTudo ? 'Mostrar em curso' : 'Mostrar tudo');

        if (!visibleGroups.length) {
            $list.html('<p class="detail-empty-note"><i class="fa-solid fa-clock"></i> Sem modulo a decorrer agora.</p>');
            return;
        }

        const html = visibleGroups.map((group) => {
            const state = slotState(group);
            const label = state.status === 'current'
                ? 'Em curso'
                : state.status === 'completed'
                    ? 'Concluido'
                    : 'Por iniciar';
            const modulo = group.sigla_modulo || group.codigo_modulo || 'Modulo';
            const formador = group.formador_nome || 'Sem formador';
            const slotsTxt = `${group.slots.length} tempo${group.slots.length > 1 ? 's' : ''}`;

            return `
                <div class="detail-horario-item">
                    <div class="detail-horario-item-head">
                        <span class="detail-horario-time">${escapeHtml(group.inicio_hora)} - ${escapeHtml(group.fim_hora)}</span>
                        <span class="detail-horario-status ${state.status}">${label}</span>
                    </div>
                    <div class="detail-horario-module">${escapeHtml(modulo)}</div>
                    <div class="detail-horario-formador">${escapeHtml(formador)} &bull; ${escapeHtml(slotsTxt)}</div>
                    <div class="detail-horario-progress">
                        <div class="detail-horario-progress-track">
                            <div class="detail-horario-progress-fill" style="width:${state.progress}%"></div>
                        </div>
                        <div class="detail-horario-progress-text">${state.progress}%</div>
                    </div>
                </div>
            `;
        }).join('');

        $list.html(html);
    }

    function renderGrade(data) {
        const days = Array.isArray(data.days) ? data.days : [];
        const slots = Array.isArray(data.slots) ? data.slots : [];
        const cells = data.cells || {};

        if (!days.length || !slots.length) {
            $grid.html('<tr><td><p class="detail-empty-note"><i class="fa-solid fa-circle-info"></i> Sem grelha disponivel.</p></td></tr>');
            return;
        }

        let html = '<thead><tr><th class="horario-grade-hour">Horas</th>';
        days.forEach((day) => {
            html += `<th>${escapeHtml(day.label || '')}</th>`;
        });
        html += '</tr></thead><tbody>';

        slots.forEach((slot, index) => {
            html += `<tr><td class="horario-grade-hour">${escapeHtml(slot.label || '')}</td>`;
            days.forEach((day) => {
                const sigla = cells[`${day.key}__${slot.code}`] || '';
                html += `<td>${sigla
                    ? `<div class="horario-preview-slot is-filled"><span class="horario-preview-text">${escapeHtml(sigla)}</span></div>`
                    : '<div class="horario-preview-slot"></div>'}</td>`;
            });
            html += '</tr>';

            if (index === 5 && slots.length > 6) {
                html += `<tr class="horario-grade-break"><td colspan="${days.length + 1}">Intervalo maior</td></tr>`;
            }
        });
        html += '</tbody>';

        $grid.html(html);
    }

    function renderModalMeta(data) {
        const turma = data.turma || {};
        const semestre = Number(plano.semestre) === 1 ? 'I' : Number(plano.semestre) === 2 ? 'II' : '-';
        $modalMeta.html(`
            <div><strong>Turma:</strong> ${escapeHtml(turma.nome_turma || '')}</div>
            <div><strong>Turno:</strong> ${escapeHtml(turma.nome_turno || '')}</div>
            <div><strong>Semestre:</strong> ${semestre}</div>
            <div><strong>Bloco:</strong> ${escapeHtml(plano.bloco || '')}º</div>
        `);
    }

    function loadGrade() {
        if (!canViewGrade()) {
            $grid.html('<tr><td><p class="detail-empty-note"><i class="fa-solid fa-circle-info"></i> Sem horario publicado para abrir.</p></td></tr>');
            return;
        }

        $grid.html('<tr><td><p class="detail-empty-note"><i class="fa-solid fa-spinner fa-spin"></i> A carregar grelha...</p></td></tr>');
        $modalMeta.empty();

        $.getJSON(gradeUrl, {
            turma_id: turmaId,
            semestre: Number(plano.semestre),
            bloco: Number(plano.bloco)
        }).done(function (res) {
            if (!res || !res.ok) {
                $grid.html('<tr><td><p class="detail-empty-note"><i class="fa-solid fa-circle-exclamation"></i> Nao foi possivel carregar a grelha.</p></td></tr>');
                return;
            }
            renderModalMeta(res);
            renderGrade(res);
            gradeLoaded = true;
        }).fail(function () {
            $grid.html('<tr><td><p class="detail-empty-note"><i class="fa-solid fa-circle-exclamation"></i> Nao foi possivel carregar a grelha.</p></td></tr>');
        });
    }

    function loadToday() {
        $meta.html('A carregar hor&aacute;rio...');
        $list.empty();
        $view.prop('disabled', true);

        $.getJSON(horarioUrl).done(function (res) {
            if (!res || !res.ok) {
                $meta.text('Nao foi possivel carregar o horario.');
                return;
            }

            const dia = res.dia_label ? `Hoje (${res.dia_label})` : 'Hoje';
            $meta.text(dia);
            rows = Array.isArray(res.rows) ? res.rows : [];
            groups = groupSchedule(rows);
            plano = res.plano || null;
            turmaId = Number(res.turma_id || 0);
            gradeLoaded = false;
            mostrarTudo = false;
            $view.prop('disabled', !canViewGrade());

            renderToday();
            if (timer) clearInterval(timer);
            timer = setInterval(renderToday, 30000);
        }).fail(function () {
            $meta.text('Nao foi possivel carregar o horario.');
            $list.empty();
        });
    }

    $toggle.on('click', function () {
        mostrarTudo = !mostrarTudo;
        renderToday();
    });

    $view.on('click', function () {
        $modal.addClass('open');
        if (!gradeLoaded) {
            loadGrade();
        }
    });

    $('#btn_fechar_horario').on('click', function () {
        $modal.removeClass('open');
    });

    $modal.on('click', function (event) {
        if ($(event.target).is('#modal_horario_turma')) {
            $modal.removeClass('open');
        }
    });

    loadToday();
}
