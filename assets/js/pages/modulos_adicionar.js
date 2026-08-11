$(function () {
    const baseUrl = $("form").data("base-url") || "";

    const $turma = $("#turma_id");
    const $tipo = $("#tipo_modulo");
    const $modulo = $("#modulo_id");
    const $moduloInfo = $("#modulo_info");
    const $formador = $("#formador_id");

    let turmaCurso = null;

    function turnoSigla(nomeTurno) {
        if (!nomeTurno) return "";
        return nomeTurno.toLowerCase().includes("diurno") ? "CD" : "CN";
    }

    function carregarTurmas() {
        $.getJSON(`${baseUrl}api/turmas_select.php`, function (rows) {
            let options = '<option value="">Seleccione a turma</option>';
            rows.forEach(t => {
                const sigla = turnoSigla(t.nome_turno);
                options += `<option value="${t.id}" data-curso="${t.curso_id}">
                    ${t.nome_turma} ${sigla ? " - " + sigla : ""}
                </option>`;
            });
            $turma.html(options);
        });
    }

    function carregarModulos(turmaId, cursoId, tipoModulo) {
        $modulo.html('<option value="">Seleccione um módulo</option>');
        $moduloInfo.val("");

        if (!turmaId || !cursoId) return;

        $.getJSON(`${baseUrl}api/modulos_por_turma.php`, { turma_id: turmaId, curso_id: cursoId, tipo_modulo: tipoModulo || "" }, function (rows) {
            rows.forEach(m => {
                const disabled = m.ja_registado == 1 ? "disabled" : "";
                $modulo.append(
                    `<option value="${m.id}" data-info="${m.codigo_modulo} - ${m.nome_modulo}" ${disabled}>
                        ${m.sigla_modulo}
                    </option>`
                );
            });
        });
    }

    function carregarFormadores(cursoId) {
        $formador.html('<option value="">Seleccione um formador</option>');
        if (!cursoId) return;

        $.getJSON(`${baseUrl}api/formadores_por_curso.php`, { curso_id: cursoId }, function (rows) {
            rows.forEach(f => {
                const label = f.nome_formatado || f.nome_completo;
                $formador.append(`<option value="${f.id}">${label}</option>`);
            });
        });
    }

    $turma.on("change", function () {
        const turmaId = $(this).val();
        turmaCurso = $(this).find("option:selected").data("curso") || null;

        carregarModulos(turmaId, turmaCurso, $tipo.val());
        carregarFormadores(turmaCurso);
    });

    $tipo.on("change", function () {
        carregarModulos($turma.val(), turmaCurso, $(this).val());
    });

    $modulo.on("change", function () {
        const info = $(this).find("option:selected").data("info") || "";
        $moduloInfo.val(info);
    });

    $("form").on("form:reset", function () {
        $moduloInfo.val("");
        $tipo.val("");
    });

    carregarTurmas();
});
