$(function () {
    const $card = $(".form-card");
    const baseUrl = $card.data("base-url") || "";

    const $modal = $("#modal_editar_turma");
    const $form = $("#form_editar_turma");
    const $cert = $("#certificado_vocacional");
    const $curso = $("#curso_id");
    const $turno = $("#turno_id");
    const $formador = $("#formador_id");
    const $ano = $("#ano_lectivo");
    const $secao = $("#seccao");
    const $nome = $("#nome_turma");
    let allowAutoName = true;
    const targetTurmaId = new URLSearchParams(window.location.search).get("turma_id");

    function abrirModalTurma() {
        $modal.addClass("open");
    }

    function fecharModalTurma() {
        $modal.removeClass("open");
    }

    function renderCursos() {
        const val = $cert.val();
        $curso.empty();

        if (val === "CV3") {
            $curso.html('<option value="">Electricidade Industrial - Depreciado</option>');
            $curso.prop("disabled", true);
            return;
        }

        if (val === "CV4") {
            $curso.append('<option value="3" data-sigla="TSI">Técnico de Suporte Informático</option>');
        } else if (val === "CV5") {
            $curso.append('<option value="1" data-sigla="PAW">Programação de Aplicações Web</option>');
            $curso.append('<option value="2" data-sigla="ASRI">Administração de Sistemas de Redes Informáticas</option>');
        } else {
            $curso.append('<option value="">Seleccione uma qualificação</option>');
        }

        $curso.prop("disabled", false);
    }

    function carregarFormadores(selectedId) {
        const cursoId = $curso.val();
        $formador.html('<option value="">Seleccione um formador</option>');

        if (!cursoId) return;

        $.getJSON(`${baseUrl}api/formadores_por_curso.php`, { curso_id: cursoId }, function (res) {
            if (!res.length) return;
            res.forEach(f => {
                const selected = selectedId && String(selectedId) === String(f.id) ? "selected" : "";
                const label = f.nome_formatado || f.nome_completo;
                $formador.append(`<option value="${f.id}" ${selected}>${label}</option>`);
            });
        });
    }

    function gerarNomeTurma() {
        if (!allowAutoName) return;
        const ano = $ano.val().trim();
        const secao = $secao.val().trim().toUpperCase();
        const cursoOption = $curso.find("option:selected");
        const sigla = cursoOption.data("sigla") || "";

        if (!ano || !sigla || !secao) {
            $nome.val("");
            return;
        }

        const ultimoDigito = ano.slice(-1);
        $nome.val(`${sigla}${ultimoDigito}${secao}`);
    }

    function deriveSecao(nomeTurma) {
        if (!nomeTurma) return "";
        const match = nomeTurma.trim().match(/([A-Za-z])$/);
        return match ? match[1].toUpperCase() : "";
    }

    $cert.on("change", function () {
        renderCursos();
        carregarFormadores();
        gerarNomeTurma();
    });

    $curso.on("change", function () {
        carregarFormadores();
        gerarNomeTurma();
    });

    $ano.on("input", gerarNomeTurma);
    $secao.on("input", gerarNomeTurma);

    $("#btn_fechar_editar_turma, #btn_cancelar_editar_turma").on("click", function () {
        fecharModalTurma();
    });

    $(document).on("click", ".btn-edit", function () {
        const id = $(this).data("id");
        if (!id) return;

        $.getJSON(`${baseUrl}api/turma_detalhe.php`, { id }, function (res) {
            if (!res.ok) return;

            allowAutoName = false;
            $("#turma_id").val(res.data.id);
            $ano.val(res.data.ano_lectivo || "");
            $cert.val(res.data.certificado_vocacional || "");
            renderCursos();
            $curso.val(res.data.curso_id || "");
            $turno.val(res.data.turno_id || "");
            $secao.val(deriveSecao(res.data.nome_turma || ""));
            $nome.val(res.data.nome_turma || "");

            carregarFormadores(res.data.dt_id || "");
            allowAutoName = true;

            abrirModalTurma();
        });
    });

    $form.on("form:reset", function () {
        fecharModalTurma();
        $("#pesquisa_turma").trigger("input");
    });

    TableManager({
        root: ".form-card",
        tbody: "#lista_turmas",
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
            const $tbody = $("#lista_turmas");
            if (!rows.length) {
                $tbody.html('<tr><td colspan="8" class="empty-row">Nenhuma turma encontrada</td></tr>');
                return;
            }

            const offset = (currentPage - 1) * limit;
            let html = "";
            rows.forEach((r, i) => {
                html += `
                    <tr data-id="${r.id}">
                        <td>${offset + i + 1}</td>
                        <td>${r.nome_turma}</td>
                        <td>${r.nome_turno || '-'}</td>
                        <td>${r.certificado_vocacional || '-'}</td>
                        <td>${r.total_formandos}</td>
                        <td>${r.director_turma || '-'}</td>
                        <td>${r.ano_lectivo || '-'}</td>
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

            if (targetTurmaId) {
                const $row = $tbody.find(`tr[data-id='${targetTurmaId}']`);
                if ($row.length) {
                    $row.addClass("row-target");
                    $row[0].scrollIntoView({ behavior: "smooth", block: "center" });
                }
            }
        },
        renderFilters: function () {}
    });

    let deleteTurmaId = null;

    function abrirConfirmacaoRemocaoTurma(id) {
        deleteTurmaId = id;
        $("#modal_confirmar_remocao_turma").fadeIn(150);
    }

    function fecharConfirmacaoRemocaoTurma() {
        deleteTurmaId = null;
        $("#modal_confirmar_remocao_turma").fadeOut(150);
    }

    $("#btn_cancelar_remocao_turma, #modal_confirmar_remocao_turma").on("click", function (e) {
        if (e.target !== this) return;
        fecharConfirmacaoRemocaoTurma();
    });

    $("#btn_confirmar_remocao_turma").on("click", function () {
        if (!deleteTurmaId) return;

        $.post(`${baseUrl}api/turma_delete.php`, { id: deleteTurmaId }, function (res) {
            fecharConfirmacaoRemocaoTurma();
            if (res && res.ok) {
                showNotification("Turma removida com sucesso!", true);
                $("#pesquisa_turma").trigger("input");
                return;
            }
            const msg = (res && res.msg) ? res.msg : "Erro ao remover turma";
            showNotification(msg, false);
        }, "json").fail(function () {
            fecharConfirmacaoRemocaoTurma();
            showNotification("Erro ao remover turma", false);
        });
    });

    $(document).on("click", ".btn-delete", function () {
        const id = $(this).data("id");
        if (!id) return;
        abrirConfirmacaoRemocaoTurma(id);
    });
});
