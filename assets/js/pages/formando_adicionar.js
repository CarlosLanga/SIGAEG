$(function () {
    const $cert = $("#certificado_vocacional");
    const $curso = $("#curso_id");
    const $turno = $("#turno_id");
    const $turma = $("#turma_id");

    function resetTurma(msg) {
        $turma.html(`<option value="" disabled selected>${msg || "Seleccione a qualificação e o turno"}</option>`);
        $turma.prop("disabled", true);
    }

    function resetTurno() {
        $turno.val("");
    }

    function renderCursos() {
        const val = $cert.val();
        $curso.empty();

        if (val === "CV3") {
            $curso.html('<option value="">Electricidade Industrial - Depreciado</option>');
            $curso.prop("disabled", true);
            resetTurno();
            resetTurma("Nenhuma turma disponível");
            return;
        }

        if (val === "CV4") {
            $curso.append('<option value="3">Técnico de Suporte Informático</option>');
            $curso.prop("disabled", false);
        } else if (val === "CV5") {
            $curso.append('<option value="1">Programação de Aplicações Web</option>');
            $curso.append('<option value="2">Administração de Sistemas de Redes Informáticas</option>');
            $curso.prop("disabled", false);
        } else {
            $curso.append('<option value="">Seleccione uma qualificação</option>');
            $curso.prop("disabled", false);
        }

        resetTurno();
        resetTurma();
    }

    function carregarTurmas() {
        const $form = $turma.closest("form");
        const url = $form.data("turmas-url");
        const cursoId = $curso.val();
        const turno = $turno.val();

        if (!cursoId || !turno) {
            resetTurma();
            return;
        }

        $turma.prop("disabled", false);

        $.getJSON(url, { curso_id: cursoId, turno_id: turno }, function (data) {
            if (!data.length) {
                resetTurma("Nenhuma turma disponível");
                return;
            }

            let options = '<option value="">Seleccione a turma</option>';
            const turnoTexto = $("#turno_id option:selected").text();
            const turnoSigla = turnoTexto.includes("Diurno") ? "CD" : "CN";

            data.forEach(t => {
                options += `<option value="${t.id}">${t.nome_turma} - ${turnoSigla}</option>`;
            });

            $turma.html(options);
        });
    }

    $cert.on("change", renderCursos);
    $curso.on("change", carregarTurmas);
    $turno.on("change", carregarTurmas);

    renderCursos();
    resetTurma();
});
