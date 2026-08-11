$(function () {
    const base = $(".form-card").data("base-url") || "";

    function estadoLabel(estado) {
        const cls = estado === "Cadastrado" ? "status-active" : "status-started";
        return `<span class="status ${cls}">${estado}</span>`;
    }

    function turnoSigla(nomeTurno) {
        if (!nomeTurno) return "";
        return nomeTurno.toLowerCase().includes("diurno") ? "CD" : "CN";
    }

    function renderTurmasBadges(turmas) {
        const $wrap = $("#formador_turmas_badges");
        if (!turmas || !turmas.length) {
            $wrap.html('<span class="badge badge-empty">Sem turmas</span>');
            return;
        }

        let html = "";
        turmas.forEach(t => {
            const sigla = turnoSigla(t.nome_turno);
            const label = `${t.nome_turma}${sigla ? " - " + sigla : ""}`;
            const cls = t.papel === "dt" ? "badge-dt" : "badge-docente";
            html += `<a href="${base}pages/admin/turmas_gerir.php?turma_id=${t.id}" class="badge ${cls}">${label}</a>`;
        });
        $wrap.html(html);
    }

    function renderModulosBadges(modulos) {
        const $wrap = $("#formador_modulos_badges");
        if (!modulos || !modulos.length) {
            $wrap.html('<span class="badge badge-empty">Sem módulos</span>');
            return;
        }

        let html = "";
        modulos.forEach(m => {
            let cls = "badge-mod-start";
            if (m.estado === "Em vigência") cls = "badge-mod-active";
            if (m.estado === "Concluído") cls = "badge-mod-done";
            html += `<a href="${base}pages/admin/modulos_gerir.php?modulo=${m.sigla_modulo}" class="badge ${cls}">${m.sigla_modulo}</a>`;
        });
        $wrap.html(html);
    }

    TableManager({
        root: ".form-card",
        tbody: "#lista_formadores",
        info: "#table_info",
        btnPrev: "#btn_prev",
        btnNext: "#btn_next",
        pageNumbers: "#table_page_numbers",
        pageSize: "#page_size",
        search: "#pesquisa_formador",
        btnPrint: null,
        paginationMode: "numbered",
        limit: 20,
        render: function (rows, currentPage, limit) {
            const $tbody = $("#lista_formadores");
            if (!rows.length) {
                $tbody.html('<tr><td colspan="8" class="empty-row">Nenhum formador encontrado</td></tr>');
                return;
            }

            const offset = (currentPage - 1) * limit;
            let html = "";
            rows.forEach((r, i) => {
                html += `
                    <tr>
                        <td>${offset + i + 1}</td>
                        <td>${r.nome || '-'}</td>
                        <td>${r.sexo || '-'}</td>
                        <td>${r.codigo_formador || '-'}</td>
                        <td>${r.total_turmas || 0}</td>
                        <td>${r.total_modulos || 0}</td>
                        <td>${estadoLabel(r.estado)}</td>
                        <td class="table-actions">
                            <button class="btn btn-outline btn-table btn-edit" data-id="${r.id}">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-outline btn-table btn-delete" data-id="${r.id}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            $tbody.html(html);
        }
    });

    const $modal = $("#modal_editar_formador");
    const $form = $("#form_editar_formador");

    function abrirModal() {
        $modal.addClass("open");
    }

    function fecharModal() {
        $modal.removeClass("open");
    }

    $("#btn_fechar_editar_formador, #btn_cancelar_editar_formador").on("click", function () {
        fecharModal();
    });

    $modal.on("click", function (e) {
        if ($(e.target).is("#modal_editar_formador")) {
            fecharModal();
        }
    });

    $(document).on("click", ".btn-edit", function () {
        const id = $(this).data("id");
        if (!id) return;

        const base = $(".form-card").data("base-url") || "";

        $.getJSON(`${base}api/formador_detalhe.php`, { id }, function (res) {
            if (!res.ok) {
                showNotification("Erro ao carregar dados do formador.", false);
                return;
            }

            const data = res.data || {};
            $("#formador_id").val(data.id || "");
            $("#formador_nome").val(data.nome_completo || "");
            $("#codigo_formador").val(data.codigo_formador || "");
            $("#telefone").val(data.telefone || "");
            $("#especialidade").val(data.especialidade || "");
            $("#email").val(data.email || "");
            $("#email").data("original-email", (data.email || "").toString());
            $form.find("[data-codegen-output]").val(data.codigo_convite || "");
            $form.find("[data-codegen-hidden]").val(data.codigo_convite || "");

            $("#titulo").val(data.titulo || "");

            $("input[name='sexo'][value='" + data.sexo + "']").prop("checked", true);

            const cursos = res.cursos || [];
            $("#cursos_checkboxes input[type='checkbox']").each(function () {
                const id = parseInt($(this).val(), 10);
                $(this).prop("checked", cursos.includes(id));
            });

            renderTurmasBadges(res.turmas || []);
            renderModulosBadges(res.modulos || []);

            if (window.IICAEGMasks) {
                window.IICAEGMasks.refresh($form);
            }

            abrirModal();
        });
    });

    $form.on("form:reset", function () {
        fecharModal();
        $("#pesquisa_formador").trigger("input");
    });

    let deleteFormadorId = null;

    function abrirConfirmacaoRemocaoFormador(id) {
        deleteFormadorId = id;
        $("#modal_confirmar_remocao_formador").fadeIn(150);
    }

    function fecharConfirmacaoRemocaoFormador() {
        deleteFormadorId = null;
        $("#modal_confirmar_remocao_formador").fadeOut(150);
    }

    $("#btn_cancelar_remocao_formador, #modal_confirmar_remocao_formador").on("click", function (e) {
        if (e.target !== this) return;
        fecharConfirmacaoRemocaoFormador();
    });

    $("#btn_confirmar_remocao_formador").on("click", function () {
        if (!deleteFormadorId) return;

        $.post(`${base}api/formador_delete.php`, { id: deleteFormadorId }, function (res) {
            fecharConfirmacaoRemocaoFormador();
            if (res && res.ok) {
                showNotification("Formador removido com sucesso!", true);
                $("#pesquisa_formador").trigger("input");
                return;
            }
            const msg = (res && res.msg) ? res.msg : "Erro ao remover formador.";
            showNotification(msg, false);
        }, "json").fail(function () {
            fecharConfirmacaoRemocaoFormador();
            showNotification("Erro ao remover formador.", false);
        });
    });

    $(document).on("click", ".btn-delete", function () {
        const id = $(this).data("id");
        if (!id) return;
        abrirConfirmacaoRemocaoFormador(id);
    });
});
