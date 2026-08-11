$(function () {
    const $page = $(".horario-gestao");
    if (!$page.length) return;

    const urls = {
        list: $page.data("list-url"),
        turmas: $page.data("turmas-url"),
        preview: $page.data("preview-url"),
        publish: $page.data("publicar-url"),
        remove: $page.data("delete-url"),
        anos: $page.data("anos-url"),
    };

    const $tbody = $("#lista_horarios");
    const $filter = $("#filtro_turma");
    const $search = $("#pesquisa_horario");

    const $modalView = $("#modal_ver_horario");
    const $btnCloseView = $("#btn_fechar_ver_horario");
    const $previewTable = $("#horario_preview_table");
    const $meta = $("#horario_meta");

    const $modalPrint = $("#modal_imprimir_global");
    const $btnOpenPrint = $("#btn_imprimir_global");
    const $btnClosePrint = $("#btn_fechar_imprimir_global");
    const $btnCancelPrint = $("#btn_cancelar_imprimir_global");
    const $btnConfirmPrint = $("#btn_confirmar_imprimir_global");
    const $printAnos = $("#print_anos");

    let anosLoaded = false;

    function escapeHtml(value) {
        return String(value == null ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatDateTime(iso) {
        if (!iso) return "-";
        const part = String(iso).replace("T", " ").split(".");
        const value = part[0] || iso;
        const dt = value.split(" ");
        if (dt.length !== 2) return value;
        const date = dt[0].split("-");
        if (date.length !== 3) return value;
        return `${date[2]}/${date[1]}/${date[0]} ${dt[1]}`;
    }

    function turnoSigla(nomeTurno) {
        if (!nomeTurno) return "";
        return String(nomeTurno).toLowerCase().includes("diurno") ? "CD" : "CN";
    }

    function renderEmpty() {
        $tbody.html('<tr><td colspan="8" class="empty-row">Nenhum horário encontrado</td></tr>');
    }

    function renderRows(rows, currentPage, limit) {
        if (!rows.length) {
            renderEmpty();
            return;
        }

        const offset = (currentPage - 1) * limit;
        let html = "";
        rows.forEach((r, i) => {
            const sigla = turnoSigla(r.nome_turno || "");
            const turmaLabel = `${r.nome_turma || "-"}${sigla ? " - " + sigla : ""}`;
            const director = r.director_turma || "-";
            const semestreVal = parseInt(r.semestre, 10) || 0;
            const semestre = semestreVal === 1 ? "I" : semestreVal === 2 ? "II" : "-";
            const bloco = r.bloco ? `${r.bloco}º` : "-";
            const updated = formatDateTime(r.actualizado_em);
            const publicado = Number(r.publicado) === 1;
            const statusClass = publicado ? "status status-published" : "status status-draft";
            const statusText = publicado ? "Publicado" : "Não publicado";
            const publishDisabled = publicado ? "disabled" : "";
            const publishTitle = publicado ? "Horário já publicado" : "Publicar horário";

            html += `
                <tr data-id="${r.id}">
                    <td>${offset + i + 1}</td>
                    <td>${escapeHtml(turmaLabel)}</td>
                    <td>${escapeHtml(director)}</td>
                    <td>${escapeHtml(semestre)}</td>
                    <td>${escapeHtml(bloco)}</td>
                    <td>${escapeHtml(updated)}</td>
                    <td><span class="${statusClass}">${statusText}</span></td>
                    <td class="table-actions">
                        <button class="btn btn-outline btn-table btn-view" data-turma="${r.turma_id}" data-semestre="${r.semestre}" data-bloco="${r.bloco}" title="Ver horário">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <button class="btn btn-outline btn-table btn-edit" data-turma="${r.turma_id}" data-semestre="${r.semestre}" data-bloco="${r.bloco}" title="Editar horário">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-outline btn-table btn-print" data-turma="${r.turma_id}" data-semestre="${r.semestre}" data-bloco="${r.bloco}" title="Imprimir horário">
                            <i class="fa-solid fa-print"></i>
                        </button>
                        <button class="btn btn-outline btn-table btn-publish" data-turma="${r.turma_id}" data-semestre="${r.semestre}" data-bloco="${r.bloco}" title="${publishTitle}" ${publishDisabled}>
                            <i class="fa-solid fa-bullhorn"></i>
                        </button>
                        <button class="btn btn-outline btn-table btn-delete" data-turma="${r.turma_id}" data-semestre="${r.semestre}" data-bloco="${r.bloco}" title="Eliminar horário">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        $tbody.html(html);
    }

    function renderFilters(rows) {
        let options = '<option value="all">Todas as turmas</option>';
        (rows || []).forEach((t) => {
            const sigla = turnoSigla(t.nome_turno || "");
            const label = `${t.nome_turma}${sigla ? " - " + sigla : ""}`;
            options += `<option value="${t.id}">${escapeHtml(label)}</option>`;
        });
        $filter.html(options);
    }

    const table = TableManager({
        root: ".horario-gestao",
        tbody: "#lista_horarios",
        info: "#table_info",
        btnPrev: "#btn_prev",
        btnNext: "#btn_next",
        pageNumbers: "#table_page_numbers",
        pageSize: "#page_size",
        filter: "#filtro_turma",
        search: "#pesquisa_horario",
        btnPrint: null,
        paginationMode: "numbered",
        limit: 20,
        render: renderRows,
        renderFilters: renderFilters,
    });

    function closeView() {
        $modalView.removeClass("open");
        $previewTable.empty();
        $meta.empty();
    }

    function openView(criteria) {
        $.getJSON(urls.preview, criteria)
            .done(function (res) {
                if (!res || !res.ok) {
                    showNotification("Não foi possível carregar o horário.", false);
                    return;
                }

                const turma = res.turma || {};
                const sigla = turnoSigla(turma.nome_turno || "");
                const turmaLabel = `${turma.nome_turma || "-"}${sigla ? " - " + sigla : ""}`;
                const estado = Number(res.publicado) === 1 ? "Publicado" : "Não publicado";
                const semestreLabel = criteria.semestre === 1 ? "I" : criteria.semestre === 2 ? "II" : "-";
                const metaHtml = `
                    <div><strong>Turma:</strong> ${escapeHtml(turmaLabel)}</div>
                    <div><strong>Ano lectivo:</strong> ${escapeHtml(turma.ano_lectivo || "-")}</div>
                    <div><strong>Semestre:</strong> ${escapeHtml(semestreLabel)}</div>
                    <div><strong>Bloco:</strong> ${escapeHtml(res && res.bloco ? res.bloco : criteria.bloco)}º</div>
                    <div><strong>Estado:</strong> ${escapeHtml(estado)}</div>
                `;
                $meta.html(metaHtml);

                const days = res.days || [];
                const slots = res.slots || [];
                const cells = res.cells || {};

                let html = '<thead><tr><th class="horario-grade-hour">Horas</th>';
                days.forEach((d) => {
                    html += `<th>${escapeHtml(d.label)}</th>`;
                });
                html += "</tr></thead><tbody>";

                slots.forEach((slot) => {
                    html += `<tr><td class="horario-grade-hour">${escapeHtml(slot.label)}</td>`;
                    days.forEach((day) => {
                        const key = `${day.key}__${slot.code}`;
                        const siglaModulo = cells[key] || "";
                        const content = siglaModulo
                            ? `<div class="horario-preview-slot is-filled"><span class="horario-preview-text">${escapeHtml(siglaModulo)}</span></div>`
                            : `<div class="horario-preview-slot"></div>`;
                        html += `<td>${content}</td>`;
                    });
                    html += "</tr>";

                    if (!res.is_nocturno && slot.code === "11:00-11:45") {
                        html += `<tr class="horario-grade-break"><td colspan="${days.length + 1}">Intervalo maior</td></tr>`;
                    }
                });
                html += "</tbody>";

                $previewTable.html(html);
                $modalView.addClass("open");
            })
            .fail(function () {
                showNotification("Erro ao carregar horário.", false);
            });
    }

    function loadAnos() {
        if (anosLoaded) return;
        $.getJSON(urls.anos, function (rows) {
            if (!rows || !rows.length) {
                $printAnos.html('<div class="radio-empty">Sem ano lectivo disponível</div>');
                return;
            }
            let html = "";
            rows.forEach((ano, idx) => {
                const checked = idx === 0 ? "checked" : "";
                html += `<label class="radio-item">
                        <input type="radio" name="print_ano" value="${ano}" ${checked}> ${escapeHtml(ano)}</label>`;
            });
            $printAnos.html(html);
            anosLoaded = true;
        });
    }

    function openPrintModal() {
        loadAnos();
        $modalPrint.addClass("open");
    }

    function closePrintModal() {
        $modalPrint.removeClass("open");
    }

    function publishHorario(criteria) {
        $.post(urls.publish, criteria, function (res) {
            if (res && res.ok) {
                showNotification("Horário publicado com sucesso!", true);
                table.refresh();
                return;
            }
            const msg = (res && res.msg) ? res.msg : "Erro ao publicar horário.";
            showNotification(msg, false);
        }, "json").fail(function () {
            showNotification("Erro ao publicar horário.", false);
        });
    }

    let deleteHorarioCriteria = null;

    function openDeleteModal(criteria) {
        deleteHorarioCriteria = criteria;
        $("#modal_confirmar_remocao_horario").fadeIn(150);
    }

    function closeDeleteModal() {
        deleteHorarioCriteria = null;
        $("#modal_confirmar_remocao_horario").fadeOut(150);
    }

    $("#btn_cancelar_remocao_horario, #modal_confirmar_remocao_horario").on("click", function (e) {
        if (e.target !== this) return;
        closeDeleteModal();
    });

    $("#btn_confirmar_remocao_horario").on("click", function () {
        if (!deleteHorarioCriteria) return;

        $.post(urls.remove, deleteHorarioCriteria, function (res) {
            closeDeleteModal();
            if (res && res.ok) {
                showNotification("Horário removido com sucesso!", true);
                table.refresh();
                return;
            }
            const msg = (res && res.msg) ? res.msg : "Erro ao remover horário.";
            showNotification(msg, false);
        }, "json").fail(function () {
            closeDeleteModal();
            showNotification("Erro ao remover horário.", false);
        });
    });

    function deleteHorario(criteria) {
        openDeleteModal(criteria);
    }

    $tbody.on("click", ".btn-view", function () {
        const criteria = {
            turma_id: $(this).data("turma"),
            semestre: $(this).data("semestre"),
            bloco: $(this).data("bloco"),
        };
        openView(criteria);
    });

    $tbody.on("click", ".btn-edit", function () {
        const turma = $(this).data("turma");
        const semestre = $(this).data("semestre");
        const bloco = $(this).data("bloco");
        const url = `horario_adicionar.php?turma_id=${turma}&semestre=${semestre}&bloco=${bloco}`;
        window.location.href = url;
    });

    $tbody.on("click", ".btn-print", function () {
        showNotification("Impressão por horário será adicionada depois.", false);
    });

    $tbody.on("click", ".btn-publish", function () {
        if ($(this).is(":disabled")) return;
        const criteria = {
            turma_id: $(this).data("turma"),
            semestre: $(this).data("semestre"),
            bloco: $(this).data("bloco"),
        };
        publishHorario(criteria);
    });

    $tbody.on("click", ".btn-delete", function () {
        const criteria = {
            turma_id: $(this).data("turma"),
            semestre: $(this).data("semestre"),
            bloco: $(this).data("bloco"),
        };
        deleteHorario(criteria);
    });

    $btnCloseView.on("click", closeView);
    $modalView.on("click", function (e) {
        if ($(e.target).is("#modal_ver_horario")) {
            closeView();
        }
    });

    $(document).on("keydown", function (e) {
        if (e.key === "Escape" && $modalView.hasClass("open")) {
            closeView();
        }
    });

    $btnOpenPrint.on("click", openPrintModal);
    $btnClosePrint.add($btnCancelPrint).on("click", closePrintModal);

    $modalPrint.on("click", function (e) {
        if ($(e.target).is("#modal_imprimir_global")) {
            closePrintModal();
        }
    });

    $btnConfirmPrint.on("click", function () {
        const ano = $("input[name='print_ano']:checked").val();
        const semestre = $("input[name='print_semestre']:checked").val();
        const bloco = $("input[name='print_bloco']:checked").val();

        if (!ano || !semestre || !bloco) {
            showNotification("Selecione ano, semestre e bloco.", false);
            return;
        }

        showNotification("Impressão global será adicionada depois.", false);
        closePrintModal();
    });
});
