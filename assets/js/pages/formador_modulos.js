$(function () {
    const $page = $(".formador-modulos-page");
    if (!$page.length) return;

    const detailUrl = $page.data("detail-url");
    const $pageHeader = $(".content-body > .page-header").first();
    const $tableSection = $(".content-body > .card.form-card").first();
    const $detailPanel = $("#painel_detalhe_modulo");
    const $floatingBack = $("#btn_voltar_modulos_floating");
    const $formandosBody = $("#lista_formandos_modulo");
    const $formandosSearch = $("#pesquisa_formandos_modulo");

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

    function turmaLabel(row) {
        const sigla = turnoSigla(row.nome_turno || "");
        return `${row.nome_turma || "-"}${sigla ? " - " + sigla : ""}`;
    }

    function estadoLabel(estado) {
        const map = {
            por_iniciar: "Por iniciar",
            em_vigencia: "Em vigência",
            concluido: "Concluído",
        };
        return map[estado] || "-";
    }

    function estadoClass(estado) {
        if (estado === "em_vigencia") return "status-active";
        if (estado === "concluido") return "status-done";
        return "status-started";
    }

    function tipoLabel(tipo) {
        return tipo === "vocacional" ? "Vocacional" : "Genérico";
    }

    function formatDate(date) {
        if (!date || !/^\d{4}-\d{2}-\d{2}$/.test(String(date))) return "—";
        const parts = String(date).split("-");
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    function renderProgress(row) {
        const result = row.resultado || "-";
        const progress = Math.max(0, Math.min(100, parseInt(row.progresso, 10) || 0));
        const cls = result === "A" ? "is-a" : (result === "NA" ? "is-na" : (["WD", "D"].includes(result) ? "is-stop" : "is-pending"));
        const label = result === "A" ? "100%" : (result === "-" ? (progress ? `${progress}%` : "-") : result);
        return `
            <div class="module-progress ${cls}">
                <div class="module-progress-track">
                    <div class="module-progress-fill" style="width:${progress}%"></div>
                </div>
                <span>${escapeHtml(label)}</span>
            </div>
        `;
    }

    function renderFormandos() {
        const term = ($formandosSearch.val() || "").toString().trim().toLowerCase();
        const rows = currentFormandos.filter((row) => {
            if (!term) return true;
            return [row.nome_completo, row.codigo_formando, row.resultado]
                .some((value) => String(value || "").toLowerCase().includes(term));
        });

        if (!rows.length) {
            $formandosBody.html('<tr><td colspan="5" class="empty-row">Nenhum formando encontrado</td></tr>');
            return;
        }

        let html = "";
        rows.forEach((row, index) => {
            const result = row.resultado || "-";
            const resultClass = result === "A" ? "is-a" : (result === "NA" ? "is-na" : (["WD", "D"].includes(result) ? "is-stop" : "is-pending"));
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(row.nome_completo || "-")}</td>
                    <td>${escapeHtml(row.codigo_formando || "-")}</td>
                    <td>${renderProgress(row)}</td>
                    <td><span class="module-result ${resultClass}">${escapeHtml(result)}</span></td>
                </tr>
            `;
        });
        $formandosBody.html(html);
    }

    function openDetails(id) {
        const formadorModuloId = parseInt(id, 10) || 0;
        if (!formadorModuloId) return;

        $pageHeader.hide();
        $tableSection.hide();
        $detailPanel.removeAttr("style").show();
        $floatingBack.addClass("is-visible");
        $detailPanel.find(".modulo-detail-stack").addClass("detail-loading");
        window.scrollTo({ top: 0, behavior: "smooth" });

        $.getJSON(detailUrl, { formador_modulo_id: formadorModuloId })
            .done(function (res) {
                $detailPanel.find(".modulo-detail-stack").removeClass("detail-loading");
                if (!res || !res.ok) {
                    closeDetails();
                    showNotification(res && res.msg ? res.msg : "Erro ao carregar detalhes do módulo.", false);
                    return;
                }

                const m = res.modulo || {};
                $("#detalhe_modulo_titulo").text(m.sigla_modulo || "—");
                $("#detalhe_modulo_nome").text(m.nome_modulo || "—");
                $("#detalhe_modulo_turma").text(turmaLabel(m));
                $("#detalhe_modulo_formador").text(m.formador_nome || "—");
                $("#detalhe_modulo_tipo").text(tipoLabel(m.tipo_modulo));
                $("#detalhe_modulo_inicio").text(formatDate(m.data_inicio));
                $("#detalhe_modulo_fim").text(formatDate(m.data_fim));
                $("#detalhe_modulo_estado").html(`<span class="status ${estadoClass(m.estado)}">${escapeHtml(estadoLabel(m.estado))}</span>`);
                $("#detalhe_avaliacoes_meta").text(`${res.avaliacoes_total || 0} avaliação(ões) publicada(s)`);

                currentFormandos = res.formandos || [];
                $formandosSearch.val("");
                renderFormandos();
            })
            .fail(function () {
                $detailPanel.find(".modulo-detail-stack").removeClass("detail-loading");
                closeDetails();
                showNotification("Erro ao carregar detalhes do módulo.", false);
            });
    }

    function closeDetails() {
        $detailPanel.hide();
        $pageHeader.show();
        $tableSection.show();
        $floatingBack.removeClass("is-visible");
        currentFormandos = [];
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    TableManager({
        root: ".formador-modulos-page",
        tbody: "#lista_modulos_formador",
        info: "#table_info",
        btnPrev: "#btn_prev",
        btnNext: "#btn_next",
        pageNumbers: "#table_page_numbers",
        pageSize: "#page_size",
        filter: null,
        search: "#pesquisa_modulo",
        btnPrint: null,
        paginationMode: "numbered",
        limit: 20,
        render: function (rows, currentPage, limit) {
            if (!rows.length) {
                $("#lista_modulos_formador").html('<tr><td colspan="6" class="empty-row">Nenhum módulo encontrado</td></tr>');
                return;
            }

            const offset = (currentPage - 1) * limit;
            let html = "";
            rows.forEach((row, index) => {
                html += `
                    <tr data-id="${row.id}">
                        <td>${offset + index + 1}</td>
                        <td><strong>${escapeHtml(row.sigla_modulo || "-")}</strong>
                        </td>
                        <td>${escapeHtml(turmaLabel(row))}</td>
                        <td>${escapeHtml(row.formador_nome || "-")}</td>
                        <td><span class="status ${estadoClass(row.estado)}">${escapeHtml(estadoLabel(row.estado))}</span></td>
                        <td class="table-actions">
                            <button class="btn btn-outline btn-table btn-view-modulo" data-id="${row.id}" title="Ver">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            $("#lista_modulos_formador").html(html);
        },
        renderFilters: function () {}
    });

    $(document).on("click", ".btn-view-modulo", function () {
        openDetails($(this).data("id"));
    });

    $("#breadcrumb_voltar_modulos, #btn_voltar_modulos_floating").on("click", function (event) {
        event.preventDefault();
        closeDetails();
    });

    $formandosSearch.on("input", renderFormandos);
});
