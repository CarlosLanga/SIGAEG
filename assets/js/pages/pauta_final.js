$(function () {
    const $page = $(".pauta-page");
    if (!$page.length) return;

    const urls = {
        turmas: $page.data("turmas-url"),
        modulos: $page.data("modulos-url"),
        contexto: $page.data("contexto-url"),
        imprimir: $page.data("imprimir-url")
    };

    const $turma = $("#pauta_turma");
    const $modulo = $("#pauta_modulo");
    const $btnGerar = $("#pauta_gerar");
    const $info = $("#pauta_info");
    const $header = $("#pauta_header");
    const $meta = $("#pauta_meta");
    const $tabelaWrap = $("#pauta_tabela_wrap");
    const $head = $("#pauta_head");
    const $body = $("#pauta_body");
    const $emptyState = $("#pauta_empty_state");
    const $emptyText = $("#pauta_empty_text");
    const $emptyIllustration = $("#pauta_empty_illustration");
    const $emptyLightTpl = $("#pauta_empty_light_tpl");
    const $emptyDarkTpl = $("#pauta_empty_dark_tpl");
    const $btnImprimir = $("#pauta_imprimir");

    let modulosCache = [];
    let current = {
        turma_id: 0,
        formador_modulo_id: 0,
        modulo_id: 0
    };

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

    function getUnavailableSvgHtml() {
        return $("body").hasClass("dark") ? $emptyDarkTpl.html() : $emptyLightTpl.html();
    }

    function hideUnavailable() {
        $emptyState.hide();
        $emptyIllustration.empty();
    }

    function showUnavailable(message) {
        hidePauta();
        setInfo("");
        $emptyText.text(message || "Pauta ainda nao disponivel.");
        $emptyIllustration.html(getUnavailableSvgHtml());
        $emptyState.show();
    }

    function setInfo(message, warning) {
        if (!message) {
            $info.hide();
            return;
        }
        hideUnavailable();
        $info.text(message);
        $info.toggleClass("is-warning", !!warning);
        $info.show();
    }

    function hidePauta() {
        $header.hide();
        $tabelaWrap.hide();
    }

    function showPauta() {
        $header.show();
        $tabelaWrap.show();
    }

    function loadTurmas() {
        $.getJSON(urls.turmas).done(function (rows) {
            let options = '<option value="">Seleccione a turma</option>';
            (rows || []).forEach(function (t) {
                const sigla = turnoSigla(t.nome_turno || "");
                const label = t.nome_turma + (sigla ? " - " + sigla : "");
                options += '<option value="' + t.id + '">' + escapeHtml(label) + "</option>";
            });
            $turma.html(options);
        });
    }

    function loadModulos(turmaId) {
        $modulo.html('<option value="">Seleccione o modulo</option>');
        if (!turmaId) return;

        $.getJSON(urls.modulos, { turma_id: turmaId }).done(function (res) {
            if (!res || !res.ok || !res.rows) return;
            modulosCache = (res.rows || []).filter(function (m) {
                const sigla = (m.sigla_modulo || "").toString().trim().toUpperCase();
                return sigla !== "RT";
            });
            let options = '<option value="">Seleccione o modulo</option>';
            modulosCache.forEach(function (m) {
                const label = m.sigla_modulo + " - " + m.nome_modulo;
                options += '<option value="' + m.id + '">' + escapeHtml(label) + "</option>";
            });
            $modulo.html(options);
        });
    }

    function renderTable(asList, rows) {
        const headCells = ["<th>#</th>", "<th>Nome</th>", "<th>Codigo</th>"];
        (asList || []).forEach(function (_as, idx) {
            headCells.push("<th>AS" + (idx + 1) + "</th>");
        });
        headCells.push("<th>Resultado</th>");
        $head.html("<tr>" + headCells.join("") + "</tr>");

        if (!rows || !rows.length) {
            $body.html('<tr><td colspan="5" class="empty-row">Nenhuma pauta disponivel</td></tr>');
            return;
        }

        let html = "";
        rows.forEach(function (r, idx) {
            const asHtml = (r.as || []).map(function (val) {
                if (!val) return "<td>&mdash;</td>";
                const cls = val === "A" ? "is-a" : (val === "NA" ? "is-na" : (val === "WD" ? "is-wd" : "is-d"));
                return '<td><span class="pauta-pill ' + cls + '">' + escapeHtml(val) + "</span></td>";
            }).join("");

            let resultadoCell = "&mdash;";
            if (r.resultado) {
                const cls = r.resultado === "A" ? "is-a" : (r.resultado === "NA" ? "is-na" : (r.resultado === "WD" ? "is-wd" : "is-d"));
                resultadoCell = '<span class="pauta-pill ' + cls + '">' + escapeHtml(r.resultado) + "</span>";
            }

            html +=
                "<tr>" +
                    "<td>" + (idx + 1) + "</td>" +
                    "<td>" + escapeHtml(r.nome) + "</td>" +
                    "<td>" + escapeHtml(r.codigo) + "</td>" +
                    asHtml +
                    "<td>" + resultadoCell + "</td>" +
                "</tr>";
        });
        $body.html(html);
    }

    $turma.on("change", function () {
        const turmaId = parseInt($turma.val(), 10) || 0;
        current.turma_id = turmaId;
        current.modulo_id = 0;
        current.formador_modulo_id = 0;
        loadModulos(turmaId);
        hidePauta();
        hideUnavailable();
        setInfo("");
    });

    $modulo.on("change", function () {
        const moduloId = parseInt($modulo.val(), 10) || 0;
        const selected = modulosCache.find(function (m) { return parseInt(m.id, 10) === moduloId; });
        current.formador_modulo_id = selected ? (parseInt(selected.id, 10) || 0) : 0;
        current.modulo_id = selected ? (parseInt(selected.modulo_id, 10) || 0) : 0;
        hidePauta();
        hideUnavailable();
        setInfo("");
    });

    $btnGerar.on("click", function () {
        if (!current.turma_id || !current.modulo_id) {
            showNotification("Seleccione a turma e o modulo.", false);
            return;
        }

        $.getJSON(urls.contexto, {
            turma_id: current.turma_id,
            modulo_id: current.modulo_id
        }).done(function (res) {
            if (!res || !res.disponivel) {
                showUnavailable(res && res.mensagem ? res.mensagem : "Pauta ainda nao disponivel.");
                return;
            }

            const cab = res.cabecalho || {};
            $meta.html(
                "<span>Turma: " + escapeHtml(cab.turma || "—") + "</span>" +
                "<span>Modulo: " + escapeHtml(cab.modulo || "—") + "</span>" +
                "<span>Formador: " + escapeHtml(cab.formador || "—") + "</span>"
            );

            renderTable(res.as_list || [], res.rows || []);
            hideUnavailable();
            setInfo("");
            showPauta();
        }).fail(function () {
            hidePauta();
            hideUnavailable();
            setInfo("Nao foi possivel gerar a pauta.", true);
        });
    });

    $(".toggle-theme").on("click", function () {
        if (!$emptyState.is(":visible")) return;
        window.setTimeout(function () {
            $emptyIllustration.html(getUnavailableSvgHtml());
        }, 220);
    });

    $btnImprimir.on("click", function () {
        if (!current.turma_id || !current.modulo_id) {
            showNotification("Seleccione a turma e o modulo para imprimir.", false);
            return;
        }

        const url = urls.imprimir + "?turma_id=" + encodeURIComponent(current.turma_id) + "&modulo_id=" + encodeURIComponent(current.modulo_id);
        window.open(url, "_blank");
    });

    loadTurmas();
});
