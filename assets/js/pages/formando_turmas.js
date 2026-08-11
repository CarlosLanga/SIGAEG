$(function () {
    const $page = $(".formando-turmas-page");
    if (!$page.length) return;

    const urls = {
        detail: $page.data("detail-url"),
    };

    const $pageHeader = $(".content-body > .page-header").first();
    const $tableSection = $(".content-body > .card.form-card").first();
    const $detailPanel = $("#painel_detalhe_turma");
    const $floatingBack = $("#btn_voltar_turmas_floating");
    const $formandosBody = $("#lista_formandos_turma");
    const $detailTitle = $("#detalhe_titulo_pagina");

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

    function renderFormandos(rows) {
        if (!rows.length) {
            $formandosBody.html('<tr><td colspan="3" class="empty-row">Nenhum formando encontrado</td></tr>');
            return;
        }

        let html = "";
        rows.forEach((row, index) => {
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(row.nome_completo || "-")}</td>
                    <td>${escapeHtml(row.codigo_formando || "-")}</td>
                </tr>
            `;
        });
        $formandosBody.html(html);
    }

    function fillDetails(data) {
        const turma = data.turma || {};
        const nomeTurma = turma.nome_turma || "—";

        $detailTitle.text(nomeTurma);
        $("#detalhe_turma_nome").text(nomeTurma);
        $("#detalhe_turma_turno").text(turma.nome_turno || "—");
        $("#detalhe_cv_valor").text(turma.certificado_vocacional || "—");
        $("#detalhe_curso").text(turma.nome_curso || "—");
        $("#detalhe_ano").text(turma.ano_lectivo || "—");
        $("#detalhe_total_formandos").text(turma.total_formandos || 0);
        $("#detalhe_director").text(turma.director_turma || "—");

        renderFormandos(data.formandos || []);
    }

    function openDetails(id) {
        const turmaId = parseInt(id, 10) || 0;
        if (!turmaId) return;

        $pageHeader.hide();
        $tableSection.hide();
        $detailPanel.removeAttr("style").show();
        $floatingBack.addClass("is-visible");
        $detailPanel.addClass("detail-loading");
        window.scrollTo({ top: 0, behavior: "smooth" });

        $.getJSON(urls.detail, { turma_id: turmaId })
            .done(function (res) {
                $detailPanel.removeClass("detail-loading");
                if (!res || !res.ok) {
                    closeDetails();
                    showNotification(res && res.msg ? res.msg : "Erro ao carregar detalhes da turma.", false);
                    return;
                }
                fillDetails(res);
            })
            .fail(function () {
                $detailPanel.removeClass("detail-loading");
                closeDetails();
                showNotification("Erro ao carregar detalhes da turma.", false);
            });
    }

    function closeDetails() {
        $detailPanel.hide().removeClass("detail-loading");
        $pageHeader.show();
        $tableSection.show();
        $floatingBack.removeClass("is-visible");
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    TableManager({
        root: ".formando-turmas-page",
        tbody: "#lista_turmas_formando",
        info: "#table_info",
        btnPrev: "#btn_prev",
        btnNext: "#btn_next",
        pageNumbers: "#table_page_numbers",
        pageSize: "#page_size",
        filter: null,
        search: "#pesquisa_turma",
        btnPrint: null,
        paginationMode: "numbered",
        emptyColspan: 7,
        limit: 20,
        render: function (rows, currentPage, limit) {
            if (!rows.length) {
                $("#lista_turmas_formando").html('<tr><td colspan="7" class="empty-row">Nenhuma turma encontrada</td></tr>');
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
                        <td>${escapeHtml(row.director_turma || "-")}</td>
                        <td>${escapeHtml(row.ano_lectivo || "-")}</td>
                        <td class="table-actions">
                            <button class="btn btn-outline btn-table btn-view-turma" data-id="${row.id}" title="Ver detalhes">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            $("#lista_turmas_formando").html(html);
        },
        renderFilters: function () {},
    });

    $(document).on("click", ".btn-view-turma", function () {
        openDetails($(this).data("id"));
    });

    $("#breadcrumb_voltar_turmas, #btn_voltar_turmas_floating").on("click", function (e) {
        e.preventDefault();
        closeDetails();
    });
});
