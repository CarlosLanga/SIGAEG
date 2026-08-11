$(function () {
    const $page = $(".trabalhos-gerir-page");
    if (!$page.length) return;

    const urls = {
        base: ($page.data("base-url") || "").toString(),
        turmas: $page.data("turmas-url"),
        modulos: $page.data("modulos-url"),
        list: $page.data("list-url"),
        detail: $page.data("detail-url"),
        del: $page.data("delete-url"),
    };

    const $turma = $("#filtro_turma_trabalho");
    const $modulo = $("#filtro_modulo_trabalho");
    const $btnVer = $("#btn_ver_trabalhos");
    const $tools = $("#trabalhos_table_tools");
    const $tableWrap = $("#trabalhos_table_wrap");
    const $search = $("#pesquisa_trabalho");
    const $tbody = $("#lista_trabalhos");
    const $listHeader = $(".trabalhos-list-header");
    const $listSection = $(".trabalhos-gerir-page");
    const $detailPanel = $("#painel_detalhe_trabalho");
    const $floatingBack = $("#btn_voltar_trabalhos_floating");

    let rowsCache = [];
    let detalheId = 0;

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

    function formatDate(dateStr) {
        if (!dateStr || dateStr === "0000-00-00") return "-";
        const parts = String(dateStr).split("-");
        if (parts.length !== 3) return dateStr;
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }

    function estadoLabel(value) {
        const labels = {
            rascunho: "Rascunho",
            publicado: "Publicado",
            encerrado: "Encerrado"
        };
        return labels[value] || value || "-";
    }

    function tipoLabel(value) {
        const labels = {
            individual: "Individual",
            grupo: "Grupo",
            pratico: "Prático",
            projecto: "Projecto"
        };
        return labels[value] || value || "-";
    }

    function turmaLabel(row) {
        const sigla = turnoSigla(row.nome_turno || "");
        return `${row.nome_turma || "-"}${sigla ? " - " + sigla : ""}`;
    }

    function moduloLabel(row) {
        const sigla = row.sigla_modulo || "";
        const nome = row.nome_modulo || "";
        return `${sigla}${sigla && nome ? " - " : ""}${nome}` || "-";
    }

    function editarUrl(id) {
        return `${urls.base}pages/admin/trabalhos_marcar.php?trabalho_id=${encodeURIComponent(id)}`;
    }

    function loadTurmas() {
        $.getJSON(urls.turmas).done(function (rows) {
            let options = '<option value="">Seleccione a turma</option>';
            (rows || []).forEach((t) => {
                const sigla = turnoSigla(t.nome_turno || "");
                const label = `${t.nome_turma}${sigla ? " - " + sigla : ""}`;
                options += `<option value="${t.id}">${escapeHtml(label)}</option>`;
            });
            $turma.html(options);
        });
    }

    function loadModulos(turmaId) {
        $modulo.html('<option value="">Seleccione o módulo</option>');
        if (!turmaId) return;

        $.getJSON(urls.modulos, { turma_id: turmaId }).done(function (res) {
            if (!res || !res.ok || !res.rows) return;
            let options = '<option value="">Seleccione o módulo</option>';
            (res.rows || []).forEach((m) => {
                const sigla = (m.sigla_modulo || "").toString().trim().toUpperCase();
                if (sigla === "RT") return;
                options += `<option value="${m.modulo_id}">${escapeHtml(`${m.sigla_modulo} - ${m.nome_modulo}`)}</option>`;
            });
            $modulo.html(options);
        });
    }

    function renderRows(rows) {
        if (!rows.length) {
            $tbody.html('<tr><td colspan="10" class="empty-row">Nenhum trabalho encontrado</td></tr>');
            return;
        }

        let html = "";
        rows.forEach((row, index) => {
            const nota = row.pontuacao_maxima ? `${escapeHtml(row.pontuacao_maxima)}%` : "Sem nota";
            const estado = row.estado || "rascunho";
            html += `
                <tr data-id="${row.id}">
                    <td>${index + 1}</td>
                    <td><strong>${escapeHtml(row.titulo)}</strong></td>
                    <td>${escapeHtml(turmaLabel(row))}</td>
                    <td>${escapeHtml(moduloLabel(row))}</td>
                    <td>${escapeHtml(tipoLabel(row.tipo))}</td>
                    <td>${nota}</td>
                    <td>${escapeHtml(formatDate(row.data_publicacao))}</td>
                    <td>${escapeHtml(formatDate(row.data_entrega))}</td>
                    <td><span class="badge-estado is-${escapeHtml(estado)}">${escapeHtml(estadoLabel(estado))}</span></td>
                    <td>
                        <div class="table-actions">
                            <a class="trabalho-icon-btn btn-edit-trabalho" href="${editarUrl(row.id)}" title="Editar">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <button type="button" class="trabalho-icon-btn trabalho-icon-btn-danger btn-delete-trabalho" data-id="${row.id}" title="Eliminar">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        $tbody.html(html);
    }

    function applySearch() {
        const term = ($search.val() || "").toString().trim().toLowerCase();
        if (!term) {
            renderRows(rowsCache);
            return;
        }
        const filtered = rowsCache.filter((row) => [
            row.titulo,
            row.tipo,
            row.estado,
            row.nome_turma,
            row.sigla_modulo,
            row.nome_modulo
        ].join(" ").toLowerCase().includes(term));
        renderRows(filtered);
    }

    function loadTrabalhos() {
        const turmaId = parseInt($turma.val(), 10) || 0;
        const moduloId = parseInt($modulo.val(), 10) || 0;

        if (!turmaId || !moduloId) {
            showNotification("Seleccione a turma e o módulo.", false);
            return;
        }

        $btnVer.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin"></i><span>A carregar...</span>');

        $.getJSON(urls.list, { turma_id: turmaId, modulo_id: moduloId })
            .done(function (res) {
                rowsCache = res && res.ok ? (res.rows || []) : [];
                $tools.show();
                $tableWrap.show();
                $search.val("");
                renderRows(rowsCache);
            })
            .fail(function () {
                showNotification("Erro ao carregar trabalhos.", false);
            })
            .always(function () {
                $btnVer.prop("disabled", false).html('<i class="fa-solid fa-magnifying-glass"></i><span>Ver trabalhos</span>');
            });
    }

    function renderSubmissoes(rows) {
        if (!rows || !rows.length) {
            $("#lista_submissoes_trabalho").html('<tr><td colspan="6" class="empty-row">Ainda não há submissões registadas para este trabalho</td></tr>');
            return;
        }
    }

    function abrirDetalhe(id) {
        detalheId = id;
        $listHeader.hide();
        $listSection.hide();
        $detailPanel.show();
        $floatingBack.addClass("is-visible");
        window.scrollTo({ top: 0, behavior: "smooth" });

        $.getJSON(urls.detail, { id }).done(function (res) {
            if (!res || !res.ok) {
                showNotification("Erro ao carregar detalhes do trabalho.", false);
                fecharDetalhe();
                return;
            }

            const d = res.data || {};
            const estado = d.estado || "rascunho";
            $("#detalhe_estado")
                .attr("class", `badge-estado is-${estado}`)
                .text(estadoLabel(estado));
            $("#detalhe_titulo").text(d.titulo || "-");
            $("#detalhe_meta").text(`${tipoLabel(d.tipo)} | Publicado em ${formatDate(d.data_publicacao)} | Prazo ${formatDate(d.data_entrega)}`);
            $("#detalhe_descricao").text(d.descricao || "Sem descrição registada.");
            $("#detalhe_turma").text(turmaLabel(d));
            $("#detalhe_modulo").text(moduloLabel(d));
            $("#detalhe_duracao").text(`${formatDate(d.data_inicio)} a ${formatDate(d.data_fim)}`);
            $("#detalhe_formador").text(d.formador_nome || "-");
            $("#card_submissoes").text((res.submissoes || []).length);
            $("#card_ficheiros").text((res.ficheiros || []).length);
            $("#card_nota").text(d.pontuacao_maxima ? `${d.pontuacao_maxima}%` : "Sem nota");
            $("#btn_ver_ficheiros").prop("disabled", !(res.ficheiros || []).length);
            renderSubmissoes(res.submissoes || []);
        }).fail(function () {
            showNotification("Erro ao carregar detalhes do trabalho.", false);
            fecharDetalhe();
        });
    }

    function fecharDetalhe() {
        detalheId = 0;
        $detailPanel.hide();
        $listHeader.show();
        $listSection.show();
        $floatingBack.removeClass("is-visible");
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    $turma.on("change", function () {
        loadModulos(parseInt($turma.val(), 10) || 0);
        $tools.hide();
        $tableWrap.hide();
        rowsCache = [];
        renderRows([]);
    });

    $btnVer.on("click", loadTrabalhos);
    $search.on("input", applySearch);

    $tbody.on("click", "tr[data-id]", function (event) {
        if ($(event.target).closest("a, button").length) return;
        abrirDetalhe(parseInt($(this).data("id"), 10) || 0);
    });

    $tbody.on("click", ".btn-delete-trabalho", function () {
        const id = parseInt($(this).data("id"), 10) || 0;
        if (!id) return;
        if (!confirm("Tem certeza de que deseja eliminar este trabalho?")) return;

        $.post(urls.del, { id }, function (res) {
            let dataRes = res;
            if (typeof res === "string") {
                try {
                    dataRes = JSON.parse(res);
                } catch (err) {
                    showNotification("Resposta inválida do servidor.", false);
                    return;
                }
            }
            if (dataRes && dataRes.ok) {
                showNotification("Trabalho eliminado com êxito.", true);
                loadTrabalhos();
                return;
            }
            showNotification(dataRes && dataRes.message ? dataRes.message : "Erro ao eliminar o trabalho.", false);
        }).fail(function () {
            showNotification("Erro de ligação ao servidor.", false);
        });
    });

    $("#breadcrumb_voltar_trabalhos, #btn_voltar_trabalhos_floating").on("click", function (event) {
        event.preventDefault();
        fecharDetalhe();
    });

    $("#btn_submeter_trabalho_admin").on("click", function () {
        showNotification("A submissão de trabalhos pelo administrador será implementada na próxima fase.", false);
    });

    $("#btn_editar_trabalho_detalhe").on("click", function () {
        if (!detalheId) return;
        window.location.href = editarUrl(detalheId);
    });

    loadTurmas();
});
