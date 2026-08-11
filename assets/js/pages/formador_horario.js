$(function () {
    const $page = $(".formador-horarios-page");
    if (!$page.length) return;

    const urls = {
        detail: $page.data("detail-url"),
        print: $page.data("print-url"),
    };

    const $pageHeader = $(".content-body > .page-header").first();
    const $tableSection = $(".content-body > .card.form-card").first();
    const $detailPanel = $("#painel_detalhe_horario");
    const $floatingBack = $("#btn_voltar_horarios_floating");
    const $grid = $("#detalhe_horario_grid");
    const $meta = $("#detalhe_horario_meta");
    const $resumo = $("#detalhe_horario_resumo");

    let currentHorario = null;

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

    function turmaLabel(turma) {
        const sigla = turnoSigla(turma.nome_turno || "");
        return `${turma.nome_turma || "-"}${sigla ? " - " + sigla : ""}`;
    }

    function semestreLabel(value) {
        const n = parseInt(value, 10);
        if (n === 1) return "I";
        if (n === 2) return "II";
        return "-";
    }

    function formatDate(iso) {
        if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(String(iso))) return "—";
        const parts = String(iso).split("-");
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    function formatDateTime(iso) {
        if (!iso) return "-";
        const value = String(iso).replace("T", " ").split(".")[0];
        const dt = value.split(" ");
        if (dt.length !== 2) return value;
        const parts = dt[0].split("-");
        if (parts.length !== 3) return value;
        return `${parts[2]}/${parts[1]}/${parts[0]} ${dt[1]}`;
    }

    function renderGrid(data) {
        const days = data.days || [];
        const slots = data.slots || [];
        const cells = data.cells || {};

        if (!days.length || !slots.length) {
            $grid.html('<tr><td><p class="detail-empty-note"><i class="fa-solid fa-circle-info"></i> Sem grelha disponível.</p></td></tr>');
            return;
        }

        let html = '<thead><tr><th class="horario-grade-hour">Horas</th>';
        days.forEach((day) => {
            html += `<th>${escapeHtml(day.label)}</th>`;
        });
        html += "</tr></thead><tbody>";

        slots.forEach((slot) => {
            html += `<tr><td class="horario-grade-hour">${escapeHtml(slot.label)}</td>`;
            days.forEach((day) => {
                const key = `${day.key}__${slot.code}`;
                const siglaModulo = cells[key] || "";
                const content = siglaModulo
                    ? `<div class="horario-preview-slot is-filled"><span class="horario-preview-text">${escapeHtml(siglaModulo)}</span></div>`
                    : '<div class="horario-preview-slot"></div>';
                html += `<td>${content}</td>`;
            });
            html += "</tr>";

            if (!data.is_nocturno && slot.code === "11:00-11:45") {
                html += `<tr class="horario-grade-break"><td colspan="${days.length + 1}">Intervalo maior</td></tr>`;
            }
        });

        html += "</tbody>";
        $grid.html(html);
    }

    function renderResumo(modules) {
        const rows = modules || [];
        if (!rows.length) {
            $resumo.html('<div class="horario-resumo-grupo"><p class="horario-resumo-item">Sem módulos no horário.</p></div>');
            return;
        }

        const groups = { generico: [], vocacional: [], outro: [] };
        rows.forEach((module) => {
            const tipo = String(module.tipo_modulo || "generico").toLowerCase();
            if (tipo === "vocacional") groups.vocacional.push(module);
            else if (tipo === "outro") groups.outro.push(module);
            else groups.generico.push(module);
        });

        function makeGroup(title, items) {
            if (!items.length) return "";
            let html = `<div class="horario-resumo-grupo"><h4>${escapeHtml(title)}</h4>`;
            items.forEach((module) => {
                const formador = `${module.formador_titulo || ""} ${module.formador_nome || ""}`.trim() || "-";
                const periodo = module.data_inicio && module.data_fim
                    ? `${formatDate(module.data_inicio)} a ${formatDate(module.data_fim)}`
                    : "Sem período";
                html += `<p class="horario-resumo-item"><strong>${escapeHtml(module.sigla_modulo || "-")}</strong> - ${escapeHtml(module.nome_modulo || "-")} | ${escapeHtml(formador)} | ${escapeHtml(periodo)}</p>`;
            });
            html += "</div>";
            return html;
        }

        $resumo.html(
            makeGroup("Módulos Genéricos", groups.generico) +
            makeGroup("Módulos Vocacionais", groups.vocacional) +
            makeGroup("Outros Módulos", groups.outro)
        );
    }

    function fillDetails(data) {
        currentHorario = data.plano || null;
        const plano = data.plano || {};
        $("#detalhe_horario_titulo").text(turmaLabel(plano));
        $meta.html(`
            <div><strong>Turma:</strong> ${escapeHtml(turmaLabel(plano))}</div>
            <div><strong>Bloco:</strong> ${escapeHtml(plano.bloco || "-")}º</div>
            <div><strong>Semestre:</strong> ${escapeHtml(semestreLabel(plano.semestre))}</div>
            <div><strong>Data de início:</strong> ${escapeHtml(formatDate(data.data_inicio))}</div>
            <div><strong>Data de fim:</strong> ${escapeHtml(formatDate(data.data_fim))}</div>
            <div><strong>Última actualização:</strong> ${escapeHtml(formatDateTime(plano.actualizado_em))}</div>
        `);
        renderGrid(data);
        renderResumo(data.modules || []);
    }

    function openDetails(id) {
        const horarioId = parseInt(id, 10) || 0;
        if (!horarioId) return;

        $pageHeader.hide();
        $tableSection.hide();
        $detailPanel.removeAttr("style").show();
        $floatingBack.addClass("is-visible");
        $detailPanel.find(".horario-detail-stack").addClass("detail-loading");
        window.scrollTo({ top: 0, behavior: "smooth" });

        $.getJSON(urls.detail, { id: horarioId })
            .done(function (res) {
                $detailPanel.find(".horario-detail-stack").removeClass("detail-loading");
                if (!res || !res.ok) {
                    closeDetails();
                    showNotification(res && res.msg ? res.msg : "Erro ao carregar horário.", false);
                    return;
                }
                fillDetails(res);
            })
            .fail(function () {
                $detailPanel.find(".horario-detail-stack").removeClass("detail-loading");
                closeDetails();
                showNotification("Erro ao carregar horário.", false);
            });
    }

    function closeDetails() {
        $detailPanel.hide();
        $pageHeader.show();
        $tableSection.show();
        $floatingBack.removeClass("is-visible");
        currentHorario = null;
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    TableManager({
        root: ".formador-horarios-page",
        tbody: "#lista_horarios_formador",
        info: "#table_info",
        btnPrev: "#btn_prev",
        btnNext: "#btn_next",
        pageNumbers: "#table_page_numbers",
        pageSize: "#page_size",
        filter: null,
        search: "#pesquisa_horario",
        btnPrint: null,
        paginationMode: "numbered",
        limit: 20,
        render: function (rows, currentPage, limit) {
            if (!rows.length) {
                $("#lista_horarios_formador").html('<tr><td colspan="6" class="empty-row">Nenhum horário encontrado</td></tr>');
                return;
            }

            const offset = (currentPage - 1) * limit;
            let html = "";
            rows.forEach((row, index) => {
                html += `
                    <tr data-id="${row.id}">
                        <td>${offset + index + 1}</td>
                        <td>${escapeHtml(turmaLabel(row))}</td>
                        <td>${escapeHtml(row.bloco || "-")}º</td>
                        <td>${escapeHtml(semestreLabel(row.semestre))}</td>
                        <td>${escapeHtml(formatDateTime(row.actualizado_em))}</td>
                        <td class="table-actions">
                            <button class="btn btn-outline btn-table btn-print-horario" data-turma="${row.turma_id}" data-semestre="${row.semestre}" data-bloco="${row.bloco}" title="Imprimir">
                                <i class="fa-solid fa-print"></i>
                            </button>
                            <button class="btn btn-outline btn-table btn-view-horario" data-id="${row.id}" title="Ver detalhes">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            $("#lista_horarios_formador").html(html);
        },
        renderFilters: function () {}
    });

    $(document).on("click", ".btn-view-horario", function () {
        openDetails($(this).data("id"));
    });

    $(document).on("click", ".btn-print-horario", function () {
        const turma = $(this).data("turma");
        const semestre = $(this).data("semestre");
        const bloco = $(this).data("bloco");
        window.open(`${urls.print}?turma_id=${encodeURIComponent(turma)}&semestre=${encodeURIComponent(semestre)}&bloco=${encodeURIComponent(bloco)}`, "_blank");
    });

    $("#btn_imprimir_horario_detalhe").on("click", function () {
        if (!currentHorario) return;
        window.open(`${urls.print}?turma_id=${encodeURIComponent(currentHorario.turma_id)}&semestre=${encodeURIComponent(currentHorario.semestre)}&bloco=${encodeURIComponent(currentHorario.bloco)}`, "_blank");
    });

    $("#breadcrumb_voltar_horarios, #btn_voltar_horarios_floating").on("click", function (event) {
        event.preventDefault();
        closeDetails();
    });
});
