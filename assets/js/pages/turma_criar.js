$(function () {
    const $cert = $("#certificado_vocacional");
    const $curso = $("#curso_id");
    const $turno = $("#turno_id");
    const $formador = $("#formador_id");
    const $ano = $("#ano_lectivo");
    const $secao = $("#seccao");
    const $nome = $("#nome_turma");

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

    function carregarFormadores() {
        const cursoId = $curso.val();
        $formador.html('<option value="">Seleccione um formador</option>');

        if (!cursoId) return;

        $.getJSON(`${$("form").data("base-url")}api/formadores_por_curso.php`, { curso_id: cursoId }, function (res) {
            if (!res.length) return;
            res.forEach(f => {
                const label = f.nome_formatado || f.nome_completo;
                $formador.append(`<option value="${f.id}">${label}</option>`);
            });
        });
    }

    function gerarNomeTurma() {
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

    renderCursos();
    $("form").on("form:reset", function () {
        $("#nome_turma").val("");
    });

});
