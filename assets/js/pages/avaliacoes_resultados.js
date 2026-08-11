$(function () {
    const $page = $(".resultados-page");
    if (!$page.length) return;

    const urls = {
        turmas: $page.data("turmas-url"),
        modulos: $page.data("modulos-url"),
        avaliacoes: $page.data("avaliacoes-url"),
        contexto: $page.data("contexto-url"),
        save: $page.data("save-url"),
        publicar: $page.data("publicar-url"),
        limpar: $page.data("limpar-url"),
    };

    const $turma = $("#resultado_turma");
    const $modulo = $("#resultado_modulo");
    const $avaliacao = $("#resultado_avaliacao");
    const $info = $("#resultado_info");
    const $cards = $("#resultado_cards");
    const $tbody = $("#lista_resultados");
    const $tabelaWrap = $("#resultado_tabela_wrap");
    const $footer = $("#resultado_footer");
    const $estado = $("#resultado_estado");
    const $btnLimpar = $("#btn_limpar_resultados");
    const $btnPublicar = $("#btn_publicar_resultados");

    let modulosCache = [];
    let context = {
        turma_id: 0,
        formador_modulo_id: 0,
        modulo_id: 0,
        avaliacao_id: 0,
        vigencia_fim: null,
        vigencia_terminada: false,
        estado: "rascunho",
    };

    let saveTimer = null;
    const qs = new URLSearchParams(window.location.search);
    const initialTurma = qs.get("turma_id");
    const initialFormadorModulo = qs.get("formador_modulo_id");
    const initialAvaliacao = qs.get("avaliacao_id");

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

    function setInfo(message, warning) {
        if (!message) {
            $info.hide();
            return;
        }
        $info.text(message);
        $info.toggleClass("is-warning", !!warning);
        $info.show();
    }

    function formatDateDash(dateStr) {
        if (!dateStr) return "—";
        const parts = dateStr.split("-");
        if (parts.length !== 3) return dateStr;
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }

    function updateEstado(estado) {
        const texto = estado === "publicado" ? "Publicado" : "Rascunho";
        $estado.text(texto);
        $estado.toggleClass("is-publicado", estado === "publicado");
        $btnPublicar.prop("disabled", estado === "publicado");
    }

    function loadTurmas() {
        $.getJSON(urls.turmas)
            .done(function (rows) {
                let options = '<option value="">Seleccione a turma</option>';
                (rows || []).forEach((t) => {
                    const sigla = turnoSigla(t.nome_turno || "");
                    const label = `${t.nome_turma}${sigla ? " - " + sigla : ""}`;
                    options += `<option value="${t.id}">${escapeHtml(label)}</option>`;
                });
                $turma.html(options);
                if (initialTurma) {
                    $turma.val(initialTurma);
                    context.turma_id = parseInt(initialTurma, 10) || 0;
                    loadModulos(context.turma_id);
                }
            });
    }

    function loadModulos(turmaId) {
        $modulo.html('<option value="">Seleccione o módulo</option>');
        $avaliacao.html('<option value="">Seleccione a avaliação</option>');
        if (!turmaId) return;

        $.getJSON(urls.modulos, { turma_id: turmaId })
            .done(function (res) {
                if (!res || !res.ok || !res.rows) return;
                modulosCache = (res.rows || []).filter((m) => {
                    const sigla = (m.sigla_modulo || "").toString().trim().toUpperCase();
                    return sigla !== "RT";
                });
                let options = '<option value="">Seleccione o módulo</option>';
                modulosCache.forEach((m) => {
                    const label = `${m.sigla_modulo} - ${m.nome_modulo}`;
                    options += `<option value="${m.id}">${escapeHtml(label)}</option>`;
                });
                $modulo.html(options);
                if (initialFormadorModulo) {
                    $modulo.val(initialFormadorModulo);
                    const selected = modulosCache.find((m) => parseInt(m.id, 10) === parseInt(initialFormadorModulo, 10));
                    context.formador_modulo_id = selected ? (parseInt(selected.id, 10) || 0) : 0;
                    context.modulo_id = selected ? (parseInt(selected.modulo_id, 10) || 0) : 0;
                    loadAvaliacoes(context.turma_id, parseInt(initialFormadorModulo, 10));
                }
            });
    }

    function loadAvaliacoes(turmaId, moduloId) {
        $avaliacao.html('<option value="">Seleccione a avaliação</option>');
        if (!turmaId || !moduloId) return;

        const selected = modulosCache.find((m) => parseInt(m.id, 10) === moduloId);
        if (!selected) return;

        $.getJSON(urls.avaliacoes, { turma_id: turmaId, modulo_id: parseInt(selected.modulo_id, 10) || 0 })
            .done(function (res) {
                if (!res || !res.ok || !res.rows) return;
                let options = '<option value="">Seleccione a avaliação</option>';
                res.rows.forEach((a) => {
                    const data = formatDateDash(a.data_avaliacao || "");
                    const hora = a.hora_avaliacao ? ` • ${a.hora_avaliacao}` : "";
                    const label = `${a.titulo} (${data}${hora})`;
                    options += `<option value="${a.id}">${escapeHtml(label)}</option>`;
                });
                $avaliacao.html(options);
                if (initialAvaliacao) {
                    $avaliacao.val(initialAvaliacao);
                    context.avaliacao_id = parseInt(initialAvaliacao, 10) || 0;
                    loadContexto();
                }
            });
    }

    function renderCards(stats) {
        $("#card_total").text(stats.total || 0);
        $("#card_alcancados").text(stats.alcancados || 0);
        $("#card_nao_alcancados").text(stats.nao_alcancados || 0);
    }

    function updateCardsFromTable() {
        let total = 0;
        let alcancados = 0;
        let nao = 0;
        $tbody.find("tr[data-id]").each(function () {
            const $row = $(this);
            const notaVal = ($row.find(".nota-input").val() || "").trim();
            if (notaVal === "") {
                total += 1;
                return;
            }
            const notaNum = parseFloat(notaVal);
            total += 1;
            if (!isNaN(notaNum) && notaNum >= 80) alcancados += 1;
            if (!isNaN(notaNum) && notaNum < 80) nao += 1;
        });
        renderCards({ total, alcancados, nao_alcancados: nao });
    }

    function renderRows(rows) {
        if (!rows || !rows.length) {
            $tbody.html('<tr><td colspan="6" class="empty-row">Nenhum resultado disponível</td></tr>');
            return;
        }

        let html = "";
        rows.forEach((r, idx) => {
            const nota = r.nota_obtida == null ? "" : r.nota_obtida;
            const notaNum = nota === "" ? null : parseFloat(nota);
            const aActive = notaNum != null && notaNum >= 80;
            const naActive = notaNum != null && notaNum < 80;
            const dActive = r.situacao === "D";
            const wdActive = r.situacao === "WD";

            html += `
                <tr data-id="${r.formando_id}">
                    <td>${idx + 1}</td>
                    <td>${escapeHtml(r.nome_completo)}</td>
                    <td>${escapeHtml(r.codigo_formando)}</td>
                    <td>
                        <div class="nota-wrap">
                            <input type="number" min="0" max="100" class="nota-input" value="${escapeHtml(nota)}" />
                            <span>%</span>
                        </div>
                    </td>
                    <td>
                        <div class="resultado-pills">
                            <span class="resultado-pill pill-na ${naActive ? "is-ativo" : ""}">NA</span>
                            <span class="resultado-pill pill-a ${aActive ? "is-ativo" : ""}">A</span>
                            <span class="resultado-pill pill-d ${dActive ? "is-ativo" : ""}">D</span>
                            <span class="resultado-pill pill-wd ${wdActive ? "is-ativo" : ""}">WD</span>
                        </div>
                    </td>
                    <td>
                        <input type="text" class="resultado-obs" value="${escapeHtml(r.observacao || "")}" placeholder="Observações">
                    </td>
                </tr>
            `;
        });
        $tbody.html(html);
    }

    function updateRowIndicators($row) {
        const notaVal = ($row.find(".nota-input").val() || "").trim();
        const notaNum = notaVal === "" ? null : parseFloat(notaVal);
        const $pillA = $row.find(".pill-a");
        const $pillNA = $row.find(".pill-na");
        $pillA.toggleClass("is-ativo", notaNum != null && notaNum >= 80);
        $pillNA.toggleClass("is-ativo", notaNum != null && notaNum < 80);
    }

    function showSections() {
        $cards.show();
        $tabelaWrap.show();
        $footer.show();
    }

    function hideSections() {
        $cards.hide();
        $tabelaWrap.hide();
        $footer.hide();
    }

    function loadContexto() {
        if (!context.turma_id || !context.modulo_id || !context.avaliacao_id) {
            hideSections();
            setInfo("");
            return;
        }

        $.getJSON(urls.contexto, {
            turma_id: context.turma_id,
            modulo_id: context.modulo_id,
            avaliacao_id: context.avaliacao_id
        }).done(function (res) {
            if (!res || !res.ok) {
                hideSections();
                setInfo(res && res.mensagem ? res.mensagem : "Não foi possível carregar os resultados.", true);
                return;
            }

            context.formador_modulo_id = res.formador_modulo_id || 0;
            context.vigencia_fim = res.vigencia_fim || null;
            context.vigencia_terminada = !!res.vigencia_terminada;
            context.estado = res.estado || "rascunho";
            updateEstado(context.estado);

            const data = res.avaliacao && res.avaliacao.data ? formatDateDash(res.avaliacao.data) : "—";
            const hora = res.avaliacao && res.avaliacao.hora ? res.avaliacao.hora : "—";
            const titulo = res.avaliacao && res.avaliacao.titulo ? res.avaliacao.titulo : "Avaliação";
            setInfo(`Avaliação: ${titulo} • Data de realização: ${data} • Hora: ${hora}`, false);

            renderCards(res.stats || {});
            renderRows(res.rows || []);
            showSections();
        });
    }

    function collectRows() {
        const rows = [];
        $tbody.find("tr[data-id]").each(function () {
            const $row = $(this);
            const formandoId = parseInt($row.data("id"), 10) || 0;
            const nota = ($row.find(".nota-input").val() || "").trim();
            const obs = ($row.find(".resultado-obs").val() || "").trim();
            rows.push({ formando_id: formandoId, nota: nota, observacao: obs });
        });
        return rows;
    }

    function saveResultados() {
        if (!context.avaliacao_id) return;
        const rows = collectRows();
        clearTimeout(saveTimer);
        saveTimer = setTimeout(function () {
            $.ajax({
                url: urls.save,
                method: "POST",
                data: {
                    avaliacao_id: context.avaliacao_id,
                    rows: JSON.stringify(rows)
                },
                success: function (res) {
                    let data = res;
                    if (typeof res === "string") {
                        try {
                            data = JSON.parse(res);
                        } catch (err) {
                            showNotification("Resposta inválida do servidor.", false);
                            return;
                        }
                    }
                    if (data && data.ok) {
                        updateEstado("rascunho");
                    } else {
                        showNotification(data && data.message ? data.message : "Erro ao guardar resultados.", false);
                    }
                },
                error: function () {
                    showNotification("Erro de ligação ao servidor.", false);
                }
            });
        }, 800);
    }

    $turma.on("change", function () {
        const turmaId = parseInt($turma.val(), 10) || 0;
        context.turma_id = turmaId;
        context.modulo_id = 0;
        context.avaliacao_id = 0;
        loadModulos(turmaId);
        hideSections();
        setInfo("");
    });

    $modulo.on("change", function () {
        const moduloId = parseInt($modulo.val(), 10) || 0;
        const selected = modulosCache.find((m) => parseInt(m.id, 10) === moduloId);
        context.formador_modulo_id = selected ? (parseInt(selected.id, 10) || 0) : 0;
        context.modulo_id = selected ? (parseInt(selected.modulo_id, 10) || 0) : 0;
        context.avaliacao_id = 0;
        loadAvaliacoes(context.turma_id, moduloId);
        hideSections();
        setInfo("");
    });

    $avaliacao.on("change", function () {
        const avaliacaoId = parseInt($avaliacao.val(), 10) || 0;
        context.avaliacao_id = avaliacaoId;
        loadContexto();
    });

    $tbody.on("input", ".nota-input, .resultado-obs", function () {
        if ($(this).hasClass("nota-input")) {
            updateRowIndicators($(this).closest("tr"));
            updateCardsFromTable();
        }
        saveResultados();
    });

    $btnLimpar.on("click", function () {
        if (!context.avaliacao_id) return;
        if (!confirm("Deseja limpar todas as notas desta avaliação?")) return;
        $.ajax({
            url: urls.limpar,
            method: "POST",
            data: { avaliacao_id: context.avaliacao_id },
            success: function (res) {
                let data = res;
                if (typeof res === "string") {
                    try {
                        data = JSON.parse(res);
                    } catch (err) {
                        showNotification("Resposta inválida do servidor.", false);
                        return;
                    }
                }
                if (data && data.ok) {
                    showNotification("Resultados limpos com êxito.", true);
                    loadContexto();
                } else {
                    showNotification(data && data.message ? data.message : "Erro ao limpar resultados.", false);
                }
            },
            error: function () {
                showNotification("Erro de ligação ao servidor.", false);
            }
        });
    });

    $btnPublicar.on("click", function () {
        if (!context.avaliacao_id) return;
        $.ajax({
            url: urls.publicar,
            method: "POST",
            data: { avaliacao_id: context.avaliacao_id },
            success: function (res) {
                let data = res;
                if (typeof res === "string") {
                    try {
                        data = JSON.parse(res);
                    } catch (err) {
                        showNotification("Resposta inválida do servidor.", false);
                        return;
                    }
                }
                if (data && data.ok) {
                    showNotification("Resultados publicados com êxito.", true);
                    updateEstado("publicado");
                } else {
                    showNotification(data && data.message ? data.message : "Erro ao publicar resultados.", false);
                }
            },
            error: function () {
                showNotification("Erro de ligação ao servidor.", false);
            }
        });
    });

    loadTurmas();
});
