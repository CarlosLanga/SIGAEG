$(function () {
    const $page = $(".presencas-ver");
    if (!$page.length) return;

    const baseUrl = $page.data("base-url") || "";
    const editUrl = $page.data("edit-url") || `${baseUrl}pages/admin/presencas_marcar.php`;
    const urls = {
        list: $page.data("list-url"),
        turmas: $page.data("turmas-url"),
        detalhe: $page.data("detalhe-url"),
        publicar: $page.data("publicar-url"),
        remove: $page.data("delete-url"),
        imprimir: $page.data("imprimir-url"),
    };

    const $tbody = $("#lista_presencas_ver");
    const $modal = $("#modal_presenca_ver");
    const $btnClose = $("#btn_fechar_presenca_ver");
    const $meta = $("#presenca_meta");
    const $cards = $("#presenca_cards");
    const $tbodyModal = $("#lista_presencas_modal");

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

    function formatDia(dia) {
        const map = { seg: "Segunda", ter: "Terça", qua: "Quarta", qui: "Quinta", sex: "Sexta" };
        return map[dia] || "";
    }

    function formatDate(iso) {
        if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return "-";
        const parts = iso.split("-");
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    function formatTime(iso) {
        if (!iso) return "-";
        const part = String(iso).replace("T", " ").split(".");
        const dt = part[0].split(" ");
        return dt[1] || "-";
    }

    function renderRows(rows, currentPage, limit) {
        if (!rows.length) {
            $tbody.html('<tr><td colspan="8" class="empty-row">Nenhuma presença encontrada</td></tr>');
            return;
        }

        const offset = (currentPage - 1) * limit;
        let html = "";
        rows.forEach((r, i) => {
            const sigla = turnoSigla(r.nome_turno || "");
            const turmaLabel = `${r.nome_turma || "-"}${sigla ? " - " + sigla : ""}`;
            const moduloLabel = r.sigla_modulo || "-";
            const marcadoPor = r.marcado_por || "-";
            const dia = formatDia(r.dia_semana);
            const dataAula = formatDate(r.data_aula);
            const hora = formatTime(r.actualizado_em);
            const marcadoEm = `${dia} | ${dataAula} ${hora}`;
            const aulas = formatAulas(r.aulas || "");
            const estado = (r.estado === "publicado") ? "Publicado" : "Rascunho";
            const estadoClass = (r.estado === "publicado") ? "status status-active" : "status status-started";
            const publishDisabled = r.estado === "publicado" ? "disabled" : "";

            html += `
                <tr data-id="${r.id}" data-turma="${r.turma_id}" data-modulo="${r.formador_modulo_id}" data-data="${r.data_aula}">
                    <td>${offset + i + 1}</td>
                    <td>${escapeHtml(turmaLabel)}</td>
                    <td>${escapeHtml(moduloLabel)}</td>
                    <td>${escapeHtml(marcadoPor)}</td>
                    <td>${escapeHtml(marcadoEm)}</td>
                    <td>${escapeHtml(aulas)}</td>
                    <td><span class="${estadoClass}">${estado}</span></td>
                    <td class="table-actions">
                        <button class="btn btn-outline btn-table btn-view" data-id="${r.id}" title="Ver">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <button class="btn btn-outline btn-table btn-edit" data-turma="${r.turma_id}" data-modulo="${r.formador_modulo_id}" data-data="${r.data_aula}" title="Editar">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-outline btn-table btn-publish" data-turma="${r.turma_id}" data-modulo="${r.formador_modulo_id}" data-data="${r.data_aula}" title="Publicar" ${publishDisabled}>
                            <i class="fa-solid fa-bullhorn"></i>
                        </button>
                        <button class="btn btn-outline btn-table btn-print" title="Imprimir">
                            <i class="fa-solid fa-print"></i>
                        </button>
                        <button class="btn btn-outline btn-table btn-delete" data-id="${r.id}" title="Eliminar">
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
        $("#filtro_turma").html(options);
    }

    const table = TableManager({
        root: ".presencas-ver",
        tbody: "#lista_presencas_ver",
        info: "#table_info",
        btnPrev: "#btn_prev",
        btnNext: "#btn_next",
        pageNumbers: "#table_page_numbers",
        pageSize: "#page_size",
        filter: "#filtro_turma",
        search: "#pesquisa_presenca",
        btnPrint: null,
        paginationMode: "numbered",
        limit: 20,
        render: renderRows,
        renderFilters: renderFilters,
    });

    function openModal(id) {
        $.getJSON(urls.detalhe, { id })
            .done(function (res) {
                if (!res || !res.ok) {
                    showNotification("Não foi possível carregar o detalhe.", false);
                    return;
                }

                const d = res.data || {};
                const sigla = turnoSigla(d.nome_turno || "");
                const turmaLabel = `${d.nome_turma || "-"}${sigla ? " - " + sigla : ""}`;
                const moduloLabel = d.sigla_modulo || "-";
                const dia = formatDia(d.dia_semana);
                const dataAula = formatDate(d.data_aula);
                const hora = formatTime(d.actualizado_em);
                const marcadoEm = `${dia} | ${dataAula} ${hora}`;
                const aulas = (res.slots || []).join(", ") || "-";
                const estado = (d.estado === "publicado") ? "Publicado" : "Rascunho";

                $meta.html(`
                    <div><strong>Turma:</strong> ${escapeHtml(turmaLabel)}</div>
                    <div><strong>Módulo:</strong> ${escapeHtml(moduloLabel)}</div>
                    <div><strong>Marcado por:</strong> ${escapeHtml(d.marcado_por || "-")}</div>
                    <div><strong>Data de marcação:</strong> ${escapeHtml(marcadoEm)}</div>
                    <div><strong>Aulas:</strong> ${escapeHtml(aulas)}</div>
                    <div><strong>Estado:</strong> ${escapeHtml(estado)}</div>
                `);

                const stats = res.stats || {};
                $cards.html(`
                    <div class="summary-card card-formandos">
                        <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="card-info">
                            <h3>Total</h3>
                            <p class="summary-value">${stats.total || 0}</p>
                        </div>
                    </div>
                    <div class="summary-card card-formadores">
                        <div class="card-icon"><i class="fa-solid fa-user-check"></i></div>
                        <div class="card-info">
                            <h3>Presenças</h3>
                            <p class="summary-value">${stats.presentes || 0}</p>
                        </div>
                    </div>
                    <div class="summary-card card-turmas">
                        <div class="card-icon"><i class="fa-solid fa-user-xmark"></i></div>
                        <div class="card-info">
                            <h3>Ausências</h3>
                            <p class="summary-value">${stats.ausentes || 0}</p>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="card-icon card-wd"><i class="fa-solid fa-user-minus"></i></div>
                        <div class="card-info">
                            <h3>WD</h3>
                            <p class="summary-value">${stats.wd || 0}</p>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="card-icon card-d"><i class="fa-solid fa-user-slash"></i></div>
                        <div class="card-info">
                            <h3>D</h3>
                            <p class="summary-value">${stats.d || 0}</p>
                        </div>
                    </div>
                `);

                const rows = res.rows || [];
                if (!rows.length) {
                    $tbodyModal.html('<tr><td colspan="5" class="empty-row">Nenhum registo encontrado</td></tr>');
                } else {
                    let html = "";
                    rows.forEach((r, i) => {
                        html += `
                            <tr>
                                <td>${i + 1}</td>
                                <td>${escapeHtml(r.nome_completo)}</td>
                                <td>${escapeHtml(r.codigo_formando || "-")}</td>
                                <td>${escapeHtml(r.situacao || "-")}</td>
                                <td>${escapeHtml(r.observacao || "")}</td>
                            </tr>
                        `;
                    });
                    $tbodyModal.html(html);
                }

                $modal.addClass("open");
            })
            .fail(function () {
                showNotification("Erro ao carregar o detalhe.", false);
            });
    }

    function closeModal() {
        $modal.removeClass("open");
    }

    function formatAulas(raw) {
        if (!raw) return "-";
        const parts = String(raw).split(",").map((p) => p.trim()).filter(Boolean);
        if (!parts.length) return "-";
        const first = parts[0].split("-")[0] || parts[0];
        const last = parts[parts.length - 1].split("-")[1] || parts[parts.length - 1];
        const total = parts.length;
        const label = `${first} - ${last}`;
        const suffix = total === 1 ? "(1 aula)" : `(${total} aulas)`;
        return `${label} ${suffix}`;
    }

    $tbody.on("click", ".btn-view", function () {
        const id = $(this).data("id");
        if (id) openModal(id);
    });

    $tbody.on("click", ".btn-edit", function () {
        const turma = $(this).data("turma");
        const modulo = $(this).data("modulo");
        const data = $(this).data("data");
        window.location.href = `${editUrl}?turma_id=${turma}&formador_modulo_id=${modulo}&data=${data}`;
    });

    $tbody.on("click", ".btn-publish", function () {
        if ($(this).is(":disabled")) return;
        const turma = $(this).data("turma");
        const modulo = $(this).data("modulo");
        const data = $(this).data("data");
        $.post(urls.publicar, { turma_id: turma, formador_modulo_id: modulo, data }, function (res) {
            if (res && res.ok) {
                showNotification("Presenças publicadas com sucesso!", true);
                table.refresh();
                return;
            }
            const msg = (res && res.msg) ? res.msg : "Erro ao publicar presenças.";
            showNotification(msg, false);
        }, "json");
    });

    $tbody.on("click", ".btn-print", function () {
        const id = $(this).closest("tr").data("id");
        if (!id) return;
        window.open(`${urls.imprimir}?id=${id}`, "_blank");
    });

    $tbody.on("click", ".btn-delete", function () {
        const id = $(this).data("id");
        if (!id) return;
        const ok = confirm("Tem certeza que deseja eliminar este registo?");
        if (!ok) return;
        $.post(urls.remove, { id }, function (res) {
            if (res && res.ok) {
                showNotification("Registo eliminado com sucesso!", true);
                table.refresh();
                return;
            }
            const msg = (res && res.msg) ? res.msg : "Erro ao eliminar registo.";
            showNotification(msg, false);
        }, "json");
    });

    $btnClose.on("click", closeModal);
    $modal.on("click", function (e) {
        if ($(e.target).is("#modal_presenca_ver")) closeModal();
    });
});
