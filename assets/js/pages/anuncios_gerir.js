$(function () {
    const $page = $(".anuncios-gerir-page");
    if (!$page.length) return;

    const urls = {
        base: ($page.data("base-url") || "").toString(),
        list: $page.data("list-url"),
        del: $page.data("delete-url"),
    };
    const isFormador = String($page.data("formador-mode")) === "1";

    const $tbody = $("#lista_anuncios");
    const $search = $("#pesquisa_anuncio");
    const $filtroPrioridade = $("#filtro_prioridade");

    const $modal = $("#anuncio_modal");
    const $confirm = $("#anuncio_confirm");

    let rowsCache = [];
    let deleteId = 0;
    let searchTimer = null;
    const colspan = isFormador ? 8 : 9;

    function notify(message, isSuccess) {
        if (typeof showNotification === "function") {
            showNotification(message, !!isSuccess);
        }
    }

    function escapeHtml(value) {
        return String(value == null ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatDateTime(value) {
        if (!value || value.startsWith("0000")) return "-";
        const d = new Date(value.replace(" ", "T"));
        if (isNaN(d.getTime())) return value;
        const pad = (n) => String(n).padStart(2, "0");
        return `${pad(d.getDate())}-${pad(d.getMonth() + 1)}-${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function formatDate(value) {
        if (!value || value.startsWith("0000")) return "-";
        const parts = String(value).split(" ")[0].split("-");
        if (parts.length !== 3) return value;
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }

    function prioridadeLabel(value) {
        return { normal: "Normal", importante: "Importante", evento: "Evento" }[value] || value || "-";
    }

    function publicoLabel(row) {
        const mapa = {
            todos: "Todos",
            formadores: "Formadores",
            formandos: "Formandos",
            encarregados: "Encarregados",
            turma: "Turma",
        };
        if (row.publico_alvo === "turma") {
            let label = row.nome_turma || "Turma específica";
            if (row.sigla_modulo) label += ` (${row.sigla_modulo})`;
            return label;
        }
        return mapa[row.publico_alvo] || row.publico_alvo || "-";
    }

    function renderRows(rows) {
        if (!rows.length) {
            $tbody.html(`<tr><td colspan="${colspan}" class="empty-row">Nenhum anúncio encontrado</td></tr>`);
            return;
        }

        const html = rows.map((row, i) => {
            const expiracao = row.data_expiracao ? formatDate(row.data_expiracao) : "Sem expiração";
            const expiradoTag = Number(row.expirado) === 1
                ? '<span class="anuncio-status-tag expirado">Expirado</span>'
                : "";
            const anexo = row.anexo_caminho
                ? `<a class="anuncio-anexo-link" href="${escapeHtml(urls.base + row.anexo_caminho)}" target="_blank" rel="noopener" title="${escapeHtml(row.anexo_nome || "Anexo")}"><i class="fa-solid fa-paperclip"></i></a>`
                : '<span class="text-muted">-</span>';

            const autorCol = isFormador
                ? ""
                : `<td>${escapeHtml(row.autor_nome || "-")}</td>`;

            return `
                <tr>
                    <td>${i + 1}</td>
                    <td class="anuncio-titulo-cell">${escapeHtml(row.titulo)}</td>
                    <td><span class="anuncio-badge prio-${escapeHtml(row.prioridade)}">${prioridadeLabel(row.prioridade)}</span></td>
                    <td>${escapeHtml(publicoLabel(row))}</td>
                    ${autorCol}
                    <td>${formatDateTime(row.data_publicacao)}</td>
                    <td>${expiracao} ${expiradoTag}</td>
                    <td class="anuncio-anexo-cell">${anexo}</td>
                    <td class="anuncio-acoes-cell">
                        <button type="button" class="icon-btn ver" data-id="${row.id}" title="Ver">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <button type="button" class="icon-btn delete" data-id="${row.id}" title="Remover">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
        }).join("");

        $tbody.html(html);
    }

    function applyFilters() {
        const term = ($search.val() || "").toString().trim().toLowerCase();
        const prio = ($filtroPrioridade.val() || "").toString();

        let filtered = rowsCache.slice();
        if (prio) {
            filtered = filtered.filter((r) => r.prioridade === prio);
        }
        if (term) {
            filtered = filtered.filter((r) => {
                const haystack = [
                    r.titulo, r.publico_alvo, r.nome_turma, r.sigla_modulo,
                    r.nome_modulo, r.autor_nome,
                ].join(" ").toLowerCase();
                return haystack.includes(term);
            });
        }
        renderRows(filtered);
    }

    function loadList() {
        $tbody.html(`<tr><td colspan="${colspan}" class="empty-row">A carregar anúncios...</td></tr>`);
        $.getJSON(urls.list)
            .done(function (res) {
                if (!res || !res.ok) {
                    $tbody.html(`<tr><td colspan="${colspan}" class="empty-row">${escapeHtml((res && res.message) || "Erro ao carregar.")}</td></tr>`);
                    return;
                }
                rowsCache = res.rows || [];
                applyFilters();
            })
            .fail(function () {
                $tbody.html(`<tr><td colspan="${colspan}" class="empty-row">Erro ao comunicar com o servidor.</td></tr>`);
            });
    }

    function openModal(row) {
        $("#modal_titulo").text(row.titulo || "-");
        $("#modal_badge_prioridade")
            .text(prioridadeLabel(row.prioridade))
            .attr("class", "anuncio-badge prio-" + (row.prioridade || "normal"));
        $("#modal_badge_alvo").text(publicoLabel(row));

        const autor = isFormador ? "" : ` • por ${row.autor_nome || "-"}`;
        $("#modal_meta").text(`Publicado em ${formatDateTime(row.data_publicacao)}${autor}`);

        if (row.prioridade === "evento" && row.evento_data_inicio) {
            let txt = formatDate(row.evento_data_inicio);
            if (row.evento_data_fim && row.evento_data_fim !== row.evento_data_inicio) {
                txt += " até " + formatDate(row.evento_data_fim);
            }
            $("#modal_event_text").text(txt);
            $("#modal_event").show();
        } else {
            $("#modal_event").hide();
        }

        $("#modal_content").html(row.descricao || "");

        if (row.anexo_caminho) {
            $("#modal_anexo")
                .attr("href", urls.base + row.anexo_caminho)
                .show();
            $("#modal_anexo_nome").text(row.anexo_nome || "Descarregar anexo");
        } else {
            $("#modal_anexo").hide();
        }

        $modal.css("display", "flex");
    }

    function closeModal() {
        $modal.hide();
    }

    function openConfirm(id, titulo) {
        deleteId = id;
        $("#anuncio_confirm_text").text(
            `Tem a certeza que deseja remover o anúncio "${titulo}"? Esta acção é irreversível.`
        );
        $confirm.css("display", "flex");
    }

    function closeConfirm() {
        deleteId = 0;
        $confirm.hide();
    }

    function doDelete() {
        if (deleteId <= 0) return;
        const $ok = $("#anuncio_confirm_ok").prop("disabled", true);
        $.post(urls.del, { id: deleteId })
            .done(function (res) {
                if (res && res.ok) {
                    notify("Anúncio removido com sucesso.", true);
                    rowsCache = rowsCache.filter((r) => Number(r.id) !== Number(deleteId));
                    applyFilters();
                } else {
                    notify((res && res.message) || "Erro ao remover.", false);
                }
            })
            .fail(function () {
                notify("Erro ao comunicar com o servidor.", false);
            })
            .always(function () {
                $ok.prop("disabled", false);
                closeConfirm();
            });
    }

    // Eventos
    $tbody.on("click", ".icon-btn.ver", function () {
        const id = Number($(this).data("id"));
        const row = rowsCache.find((r) => Number(r.id) === id);
        if (row) openModal(row);
    });

    $tbody.on("click", ".icon-btn.delete", function () {
        const id = Number($(this).data("id"));
        const row = rowsCache.find((r) => Number(r.id) === id);
        openConfirm(id, row ? row.titulo : "");
    });

    $search.on("input", function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applyFilters, 200);
    });
    $filtroPrioridade.on("change", applyFilters);

    $("#anuncio_modal_close").on("click", closeModal);
    $modal.on("click", function (e) {
        if (e.target === this) closeModal();
    });

    $("#anuncio_confirm_cancel").on("click", closeConfirm);
    $("#anuncio_confirm_ok").on("click", doDelete);
    $confirm.on("click", function (e) {
        if (e.target === this) closeConfirm();
    });

    $(document).on("keydown", function (e) {
        if (e.key === "Escape") {
            closeModal();
            closeConfirm();
        }
    });

    loadList();
});
