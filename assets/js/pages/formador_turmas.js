$(function () {
    const $page = $(".formador-turmas-page");
    if (!$page.length) return;

    const urls = {
        base: ($page.data("base-url") || "").toString(),
        detail: $page.data("detail-url"),
        print: $page.data("print-url"),
        horario: $page.data("horario-url"),
    };

    const $pageHeader = $(".content-body > .page-header").first();
    const $tableSection = $(".content-body > .card.form-card").first();
    const $detailPanel = $("#painel_detalhe_turma");
    const $floatingBack = $("#btn_voltar_turmas_floating");
    const $formandosBody = $("#lista_formandos_turma");
    const $formandosSearch = $("#pesquisa_formandos_turma");
    const $horarioList = $("#detalhe_horario_list");
    const $horarioMeta = $("#detalhe_horario_meta");
    const $btnVerHorario = $("#btn_ver_horario_turma");
    const $modalHorario = $("#modal_horario_turma");
    const $horarioGrid = $("#detalhe_horario_grid");
    const $horarioMetaModal = $("#detalhe_horario_meta_modal");

    let currentTurmaId = 0;
    let currentTurma = null;
    let currentPlano = null;
    let currentFormandos = [];

    if ($floatingBack.length) {
        $floatingBack.appendTo("body");
    }

    function escapeHtml(value) {
        return String(value == null ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function turnoSigla(nomeTurno) {
        if (!nomeTurno) return "";
        return String(nomeTurno).toLowerCase().includes("diurno") ? "CD" : "CN";
    }

    function initials(text) {
        const value = String(text || "").trim();
        if (!value) return "--";
        const letters = value.replace(/[^A-Za-z0-9]/g, "").slice(0, 3);
        return letters ? letters.toUpperCase() : "--";
    }

    function turmaLabel(row) {
        const sigla = turnoSigla(row.nome_turno || "");
        return `${row.nome_turma || "-"}${sigla ? " - " + sigla : ""}`;
    }

    function hmToMinutes(hm) {
        if (!/^\d{2}:\d{2}$/.test(String(hm || ""))) return null;
        return (parseInt(hm.slice(0, 2), 10) * 60) + parseInt(hm.slice(3, 5), 10);
    }

    function computeSlotProgress(row) {
        const start = hmToMinutes(row.inicio_hora);
        const end = hmToMinutes(row.fim_hora);
        if (start === null || end === null || end <= start) {
            return { status: "upcoming", progress: 0 };
        }

        const now = new Date();
        const nowMinutes = (now.getHours() * 60) + now.getMinutes();
        if (nowMinutes < start) return { status: "upcoming", progress: 0 };
        if (nowMinutes >= end) return { status: "completed", progress: 100 };

        const progress = Math.round(((nowMinutes - start) / (end - start)) * 100);
        return { status: "current", progress: Math.max(0, Math.min(100, progress)) };
    }

    function groupSchedule(rows) {
        const groups = [];
        (rows || []).forEach((row) => {
            const key = [
                row.formador_modulo_id || "",
                row.sigla_modulo || "",
                row.nome_modulo || "",
                row.formador_nome || ""
            ].join("|");
            const start = hmToMinutes(row.inicio_hora);
            const end = hmToMinutes(row.fim_hora);
            const last = groups.length ? groups[groups.length - 1] : null;
            const consecutive = last
                && last._key === key
                && start !== null
                && last._end !== null
                && start >= last._end
                && start - last._end <= 30;

            if (consecutive) {
                last.fim_hora = row.fim_hora;
                last._end = end;
                last.total_slots += 1;
                return;
            }

            groups.push({ ...row, _key: key, _end: end, total_slots: 1 });
        });
        return groups;
    }

    function renderFormandos() {
        const term = ($formandosSearch.val() || "").toString().trim().toLowerCase();
        const rows = currentFormandos.filter((row) => {
            if (!term) return true;
            return [
                row.nome_completo,
                row.codigo_formando,
                row.sexo,
                row.estado
            ].some((value) => String(value || "").toLowerCase().includes(term));
        });

        if (!rows.length) {
            $formandosBody.html('<tr><td colspan="5" class="empty-row">Nenhum formando encontrado</td></tr>');
            return;
        }

        let html = "";
        rows.forEach((row, idx) => {
            const estadoClass = row.estado === "Cadastrado" ? "status-active" : "status-started";
            html += `
                <tr>
                    <td>${idx + 1}</td>
                    <td>${escapeHtml(row.nome_completo || "-")}</td>
                    <td>${escapeHtml(row.sexo || "-")}</td>
                    <td>${escapeHtml(row.codigo_formando || "-")}</td>
                    <td><span class="status ${estadoClass}">${escapeHtml(row.estado || "-")}</span></td>
                </tr>
            `;
        });
        $formandosBody.html(html);
    }

    function renderHorario(horario) {
        currentPlano = horario && horario.plano ? horario.plano : null;
        $btnVerHorario.prop("disabled", !(currentPlano && currentPlano.semestre && currentPlano.bloco));

        if (!horario || !horario.has_schedule) {
            $horarioMeta.text("Turma sem horário registado.");
            $horarioList.html('<p class="detail-empty-note"><i class="fa-solid fa-circle-info"></i> Sem horário disponível.</p>');
            return;
        }

        $horarioMeta.text(horario.dia_label ? `Hoje (${horario.dia_label})` : "Hoje");
        const groups = groupSchedule(horario.rows || []);

        if (!groups.length) {
            $horarioList.html('<p class="detail-empty-note"><i class="fa-solid fa-clock"></i> Sem aulas marcadas para hoje.</p>');
            return;
        }

        let html = "";
        groups.forEach((row) => {
            const state = computeSlotProgress(row);
            const statusLabel = state.status === "current"
                ? "A decorrer"
                : (state.status === "completed" ? "Concluído" : "Por iniciar");
            const modulo = row.sigla_modulo || "-";
            const slotsTxt = row.total_slots > 1 ? `${row.total_slots} tempos consecutivos` : "1 tempo";
            const mineClass = Number(row.is_current_formador || 0) === 1 ? " is-current-formador" : "";

            html += `
                <div class="detail-horario-item${mineClass}">
                    <div class="detail-horario-item-head">
                        <span class="detail-horario-time">${escapeHtml(row.inicio_hora)} - ${escapeHtml(row.fim_hora)}</span>
                        <span class="detail-horario-status ${state.status}">${statusLabel}</span>
                    </div>
                    <div class="detail-horario-module">${escapeHtml(modulo)}</div>
                    <div class="detail-horario-formador">${escapeHtml(row.formador_nome || "Sem formador")} • ${escapeHtml(slotsTxt)}</div>
                    ${state.status === "current" ? `
                    <div class="detail-horario-progress">
                        <div class="detail-horario-progress-track">
                            <div class="detail-horario-progress-fill" style="width:${state.progress}%"></div>
                        </div>
                        <div class="detail-horario-progress-text">${state.progress}%</div>
                    </div>` : ""}
                </div>
            `;
        });
        $horarioList.html(html);
    }

    function fillDetails(data) {
        const turma = data.turma || {};
        currentTurma = turma;
        currentFormandos = data.formandos || [];

        $("#detalhe_turma_nome").text(turma.nome_turma || "—");
        $("#detalhe_turma_turno").text(turma.nome_turno || "—");
        $("#detalhe_cv_valor").text(turma.certificado_vocacional || "—");
        $("#detalhe_curso").text(turma.nome_curso || "—");
        $("#detalhe_ano").text(turma.ano_lectivo || "—");
        $("#detalhe_total_formandos").text(turma.total_formandos || 0);
        $("#detalhe_director").text(turma.director_turma || "—");

        $formandosSearch.val("");
        renderFormandos();
        renderHorario(data.horario || {});
    }

    function openDetails(id) {
        currentTurmaId = parseInt(id, 10) || 0;
        if (!currentTurmaId) return;

        $pageHeader.hide();
        $tableSection.hide();
        $detailPanel.removeAttr("style").show();
        $floatingBack.addClass("is-visible");
        $detailPanel.find(".detail-layout").addClass("detail-loading");
        window.scrollTo({ top: 0, behavior: "smooth" });

        $.getJSON(urls.detail, { turma_id: currentTurmaId })
            .done(function (res) {
                $detailPanel.find(".detail-layout").removeClass("detail-loading");
                if (!res || !res.ok) {
                    closeDetails();
                    showNotification(res && res.msg ? res.msg : "Erro ao carregar detalhes da turma.", false);
                    return;
                }
                fillDetails(res);
            })
            .fail(function () {
                $detailPanel.find(".detail-layout").removeClass("detail-loading");
                closeDetails();
                showNotification("Erro ao carregar detalhes da turma.", false);
            });
    }

    function closeDetails() {
        $detailPanel.hide();
        $pageHeader.show();
        $tableSection.show();
        $floatingBack.removeClass("is-visible");
        currentTurmaId = 0;
        currentTurma = null;
        currentPlano = null;
        currentFormandos = [];
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    function renderHorarioGrade(preview) {
        const days = Array.isArray(preview.days) ? preview.days : [];
        const slots = Array.isArray(preview.slots) ? preview.slots : [];
        const cells = preview.cells || {};

        if (!days.length || !slots.length) {
            $horarioGrid.html('<tr><td><p class="detail-empty-note"><i class="fa-solid fa-circle-info"></i> Sem grelha disponível.</p></td></tr>');
            return;
        }

        let html = '<thead><tr><th class="horario-grade-hour">Horas</th>';
        days.forEach((day) => {
            html += `<th>${escapeHtml(day.label || "")}</th>`;
        });
        html += "</tr></thead><tbody>";

        slots.forEach((slot) => {
            html += `<tr><td class="horario-grade-hour">${escapeHtml(slot.label || "")}</td>`;
            days.forEach((day) => {
                const key = `${day.key}__${slot.code}`;
                const siglaModulo = cells[key] || "";
                const content = siglaModulo
                    ? `<div class="horario-preview-slot is-filled"><span class="horario-preview-text">${escapeHtml(siglaModulo)}</span></div>`
                    : '<div class="horario-preview-slot"></div>';
                html += `<td>${content}</td>`;
            });
            html += "</tr>";

            if (!preview.is_nocturno && slot.code === "11:00-11:45") {
                html += `<tr class="horario-grade-break"><td colspan="${days.length + 1}">Intervalo maior</td></tr>`;
            }
        });

        html += "</tbody>";
        $horarioGrid.html(html);

        const turma = preview.turma || currentTurma || {};
        const label = turmaLabel(turma);
        const semestreLabel = Number(currentPlano.semestre) === 1 ? "I" : "II";
        const estado = Number(preview.publicado) === 1 ? "Publicado" : "Não publicado";
        const statusClass = Number(preview.publicado) === 1 ? "status-published" : "status-draft";
        $horarioMetaModal.html(`
            <div><strong>Turma:</strong> ${escapeHtml(label)}</div>
            <div><strong>Ano lectivo:</strong> ${escapeHtml(turma.ano_lectivo || "-")}</div>
            <div><strong>Semestre:</strong> ${escapeHtml(semestreLabel)}</div>
            <div><strong>Bloco:</strong> ${escapeHtml(currentPlano.bloco)}º</div>
            <div><strong>Estado:</strong> <span class="status ${statusClass}">${escapeHtml(estado)}</span></div>
        `);
    }

    TableManager({
        root: ".formador-turmas-page",
        tbody: "#lista_turmas_formador",
        info: "#table_info",
        btnPrev: "#btn_prev",
        btnNext: "#btn_next",
        pageNumbers: "#table_page_numbers",
        pageSize: "#page_size",
        filter: null,
        search: "#pesquisa_turma",
        btnPrint: null,
        paginationMode: "numbered",
        limit: 20,
        render: function (rows, currentPage, limit) {
            if (!rows.length) {
                $("#lista_turmas_formador").html('<tr><td colspan="8" class="empty-row">Nenhuma turma encontrada</td></tr>');
                return;
            }

            const offset = (currentPage - 1) * limit;
            let html = "";
            rows.forEach((row, index) => {
                html += `
                    <tr data-id="${row.id}">
                        <td>${offset + index + 1}</td>
                        <td>${escapeHtml(row.nome_turma || "-")}</td>
                        <td>${escapeHtml(row.nome_turno || "-")}</td>
                        <td>${escapeHtml(row.certificado_vocacional || "-")}</td>
                        <td>${escapeHtml(row.total_formandos || 0)}</td>
                        <td>${escapeHtml(row.director_turma || "-")}</td>
                        <td>${escapeHtml(row.ano_lectivo || "-")}</td>
                        <td class="table-actions">
                            <button class="btn btn-outline btn-table btn-view-turma" data-id="${row.id}" title="Ver">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            $("#lista_turmas_formador").html(html);
        },
        renderFilters: function () {}
    });

    $(document).on("click", ".btn-view-turma", function () {
        openDetails($(this).data("id"));
    });

    $("#breadcrumb_voltar_turmas, #btn_voltar_turmas_floating").on("click", function (e) {
        e.preventDefault();
        closeDetails();
    });

    $formandosSearch.on("input", renderFormandos);

    $("#btn_imprimir_formandos").on("click", function () {
        if (!currentTurmaId) return;
        window.open(`${urls.print}?turma_id=${encodeURIComponent(currentTurmaId)}`, "_blank");
    });

    $btnVerHorario.on("click", function () {
        if (!currentTurmaId || !currentPlano) return;

        $modalHorario.addClass("open");
        $horarioGrid.html('<tr><td><p class="detail-empty-note"><i class="fa-solid fa-spinner fa-spin"></i> A carregar grelha...</p></td></tr>');
        $horarioMetaModal.empty();

        $.getJSON(urls.horario, {
            turma_id: currentTurmaId,
            semestre: currentPlano.semestre,
            bloco: currentPlano.bloco
        }).done(function (res) {
            if (!res || !res.ok) {
                $horarioGrid.html('<tr><td><p class="detail-empty-note"><i class="fa-solid fa-circle-exclamation"></i> Não foi possível carregar a grelha.</p></td></tr>');
                return;
            }
            renderHorarioGrade(res);
        }).fail(function () {
            $horarioGrid.html('<tr><td><p class="detail-empty-note"><i class="fa-solid fa-circle-exclamation"></i> Não foi possível carregar a grelha.</p></td></tr>');
        });
    });

    $("#btn_fechar_horario").on("click", function () {
        $modalHorario.removeClass("open");
    });

    $modalHorario.on("click", function (event) {
        if ($(event.target).is("#modal_horario_turma")) {
            $modalHorario.removeClass("open");
        }
    });
});
