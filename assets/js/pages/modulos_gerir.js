$(function () {
    const $card = $(".form-card");
    const baseUrl = $card.data("base-url") || "";
    const $modal = $("#modal_editar_modulo");
    const $form = $("#form_editar_modulo");
    const $turma = $("#turma_id");
    const $modulo = $("#modulo_id");
    const $moduloInfo = $("#modulo_info");
    const $tipoModulo = $("#tipo_modulo");
    const $formador = $("#formador_id");
    const $inicio = $("#data_inicio");
    const $fim = $("#data_fim");
    const $tipoFiltro = $("#filtro_tipo_modulo");

    function turnoSigla(nomeTurno) {
        if (!nomeTurno) return "";
        return nomeTurno.toLowerCase().includes("diurno") ? "CD" : "CN";
    }

    function estadoClass(estado) {
        if (estado === "Nao iniciado" || estado === "Por iniciar") return "status-started";
        if (estado === "Em vigencia") return "status-active";
        if (estado === "Concluido") return "status-done";
        return "";
    }

    const targetModulo = new URLSearchParams(window.location.search).get("modulo");

    const table = TableManager({
        root: ".form-card",
        tbody: "#lista_modulos",
        info: "#table_info",
        btnPrev: "#btn_prev",
        btnNext: "#btn_next",
        pageNumbers: "#table_page_numbers",
        pageSize: "#page_size",
        filter: "#filtro_turma",
        search: "#pesquisa_modulo",
        btnPrint: null,
        paginationMode: "numbered",
        limit: 20,
        extraParams: function () {
            return { tipo_modulo: $tipoFiltro.val() || "" };
        },
        render: function (rows, currentPage, limit) {
            const $tbody = $("#lista_modulos");
            if (!rows.length) {
                $tbody.html('<tr><td colspan="7" class="empty-row">Nenhum modulo encontrado</td></tr>');
                return;
            }

            const offset = (currentPage - 1) * limit;
            let html = "";
            rows.forEach((r, i) => {
                const sigla = turnoSigla(r.nome_turno);
                const turma = r.nome_turma ? `${r.nome_turma} ${sigla ? " - " + sigla : ""}` : "-";
                const estadoCls = estadoClass(r.estado);
                const tipoLabel = r.tipo_modulo === "vocacional" ? "Vocacional" : "Genérico";
                const fmId = r.id || "";
                const moduloId = r.modulo_id || "";
                const tipo = r.tipo_modulo || "";

                html += `
                    <tr data-sigla="${(r.sigla_modulo || "").toString().toLowerCase()}">
                        <td>${offset + i + 1}</td>
                        <td>${r.sigla_modulo || "-"}</td>
                        <td>${tipoLabel}</td>
                        <td>${turma}</td>
                        <td>${r.formador || "-"}</td>
                        <td><span class="status ${estadoCls}">${r.estado || "-"}</span></td>
                        <td class="table-actions">
                            <button class="btn btn-outline btn-table btn-edit" data-id="${fmId}" data-modulo-id="${moduloId}" data-tipo="${tipo}">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-outline btn-table btn-delete" data-id="${fmId}" data-modulo-id="${moduloId}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            $tbody.html(html);

            if (targetModulo) {
                const target = targetModulo.toString().toLowerCase();
                const $row = $tbody.find(`tr[data-sigla='${target}']`);
                if ($row.length) {
                    $row.addClass("row-target");
                    $row[0].scrollIntoView({ behavior: "smooth", block: "center" });
                }
            }
        },
        renderFilters: function (rows) {
            const $filtro = $("#filtro_turma");
            let options = '<option value="all">Todas as turmas</option>';
            rows.forEach(t => {
                const sigla = turnoSigla(t.nome_turno);
                options += `<option value="${t.id}">${t.nome_turma} ${sigla ? " - " + sigla : ""}</option>`;
            });
            $filtro.html(options);
        },
    });

    $tipoFiltro.on("change", function () {
        table.resetPage();
    });

    $("#btn_imprimir").closest(".toolbar-right").hide();

    function abrirModal() {
        $modal.addClass("open");
    }

    function fecharModal() {
        $modal.removeClass("open");
    }

    $("#btn_fechar_editar_modulo, #btn_cancelar_editar_modulo").on("click", function () {
        fecharModal();
    });

    $(document).on("click", ".btn-edit", function () {
        const fmId = $(this).data("id");
        const moduloId = $(this).data("modulo-id");
        const tipo = $(this).data("tipo");

        if (!fmId) return;

        $.getJSON(`${baseUrl}api/modulo_detalhe.php`, { id: fmId }, function (res) {
            if (!res.ok) {
                showNotification("Erro ao carregar detalhes do modulo.", false);
                return;
            }

            const sigla = turnoSigla(res.data.nome_turno);
            const turmaLabel = res.data.nome_turma
                ? `${res.data.nome_turma} ${sigla ? " - " + sigla : ""}`
                : "-";

            $("#fm_id").val(res.data.id);
            $("#turma_id_hidden").val(res.data.turma_id);
            $("#modulo_id_hidden").val(res.data.modulo_id);

            $turma.html(`<option value="${res.data.turma_id}">${turmaLabel}</option>`);
            $modulo.html(`<option value="${res.data.modulo_id}">${res.data.sigla_modulo}</option>`);
            $moduloInfo.val(`${res.data.codigo_modulo} - ${res.data.nome_modulo}`);
            $tipoModulo.val(res.data.tipo_modulo === "vocacional" ? "Vocacional" : "Genérico");
            $inicio.val(res.data.data_inicio || "");
            $fim.val(res.data.data_fim || "");

            $formador.html('<option value="">Seleccione um formador</option>');
            $.getJSON(`${baseUrl}api/formadores_por_curso.php`, { curso_id: res.data.curso_id }, function (rows) {
                rows.forEach(f => {
                    const selected = String(f.id) === String(res.data.formador_id) ? "selected" : "";
                    const label = f.nome_formatado || f.nome_completo;
                    $formador.append(`<option value="${f.id}" ${selected}>${label}</option>`);
                });
            });

            abrirModal();
        });
    });

    let deleteModuloFmId = null;

    function abrirConfirmacaoRemocaoModulo(id) {
        deleteModuloFmId = id;
        $("#modal_confirmar_remocao_modulo").fadeIn(150);
    }

    function fecharConfirmacaoRemocaoModulo() {
        deleteModuloFmId = null;
        $("#modal_confirmar_remocao_modulo").fadeOut(150);
    }

    $("#btn_cancelar_remocao_modulo, #modal_confirmar_remocao_modulo").on("click", function (e) {
        if (e.target !== this) return;
        fecharConfirmacaoRemocaoModulo();
    });

    $("#btn_confirmar_remocao_modulo").on("click", function () {
        if (!deleteModuloFmId) return;

        $.post(`${baseUrl}api/modulo_delete.php`, { fm_id: deleteModuloFmId }, function (res) {
            fecharConfirmacaoRemocaoModulo();
            if (res && res.ok) {
                showNotification("Módulo removido com sucesso!", true);
                table.refresh();
                return;
            }
            const msg = (res && res.msg) ? res.msg : "Erro ao remover módulo.";
            showNotification(msg, false);
        }, "json").fail(function () {
            fecharConfirmacaoRemocaoModulo();
            showNotification("Erro ao remover módulo.", false);
        });
    });

    $(document).on("click", ".btn-delete", function () {
        const fmId = $(this).data("id");
        if (!fmId) return;
        abrirConfirmacaoRemocaoModulo(fmId);
    });

    $form.on("form:reset", function () {
        fecharModal();
        table.refresh();
    });
});

