$(function () {
    const $page = $(".presencas-page");
    if (!$page.length) return;

    const urls = {
        turmas: $page.data("turmas-url"),
        modulos: $page.data("modulos-url"),
        contexto: $page.data("contexto-url"),
        save: $page.data("save-url"),
        publicar: $page.data("publicar-url"),
    };

    const $turma = $("#presenca_turma");
    const $modulo = $("#presenca_modulo");
    const $data = $("#presenca_data");

    const $info = $("#presenca_info");
    const $cards = $("#presenca_cards");
    const $slotsWrap = $("#presenca_slots");
    const $slotsList = $("#slots_lista");
    const $acoes = $("#presenca_acoes");
    const $tabelaWrap = $("#presenca_tabela_wrap");
    const $tbody = $("#lista_presencas");
    const $estado = $("#presenca_estado");
    const $footer = $("#presenca_footer");

    const $btnTodos = $("#btn_todos_presentes");
    const $btnLimpar = $("#btn_limpar_presencas");
    const $selectMassa = $("#select_massa");
    const $btnAplicarMassa = $("#btn_aplicar_massa");
    const $btnPublicar = $("#btn_publicar_presencas");

    let current = {
        turma_id: 0,
        formador_modulo_id: 0,
        data: "",
        plano_id: 0,
        estado: "rascunho",
        permanentes: {},
    };

    let saveTimer = null;

    function escapeHtml(value) {
        return String(value == null ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function setInfo(message, type) {
        $info.removeClass("info-warning info-ok");
        if (type) $info.addClass(type);
        $info.text(message);
    }

    function hideSections() {
        $cards.hide();
        $slotsWrap.hide();
        $acoes.hide();
        $tabelaWrap.hide();
        $footer.hide();
    }

    function updateEstadoBadge(estado) {
        const texto = estado === "publicado" ? "Publicado" : "Rascunho";
        $estado.text(texto);
        $estado.toggleClass("is-publicado", estado === "publicado");
        $btnPublicar.prop("disabled", estado === "publicado");
    }

    function turnoSigla(nomeTurno) {
        if (!nomeTurno) return "";
        return String(nomeTurno).toLowerCase().includes("diurno") ? "CD" : "CN";
    }

    const qs = new URLSearchParams(window.location.search);
    const initialTurma = qs.get("turma_id");
    const initialModulo = qs.get("formador_modulo_id");
    const initialData = qs.get("data");

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
                    loadModulos(parseInt(initialTurma, 10) || 0);
                }
            })
            .fail(function () {});
    }

    function loadModulos(turmaId) {
        $modulo.html('<option value="">Seleccione o módulo</option>');
        if (!turmaId) return;

        $.getJSON(urls.modulos, { turma_id: turmaId })
            .done(function (res) {
                if (!res || !res.ok || !res.rows) return;
                let options = '<option value="">Seleccione o módulo</option>';
                res.rows.forEach((m) => {
                    const label = `${m.sigla_modulo} - ${m.nome_modulo}`;
                    options += `<option value="${m.id}">${escapeHtml(label)}</option>`;
                });
                $modulo.html(options);
                if (initialModulo) {
                    $modulo.val(initialModulo);
                }
                if (initialData) {
                    $data.val(initialData);
                }
                if (initialTurma && initialModulo && initialData) {
                    loadContext();
                }
            })
            .fail(function () {});
    }

    function renderCards(stats) {
        $("#card_total").text(stats.total || 0);
        $("#card_presentes").text(stats.presentes || 0);
        $("#card_ausentes").text(stats.ausentes || 0);
        $("#card_wd").text(stats.wd || 0);
        $("#card_d").text(stats.d || 0);
    }

    function renderSlots(slots, selected) {
        if (!slots || !slots.length) {
            $slotsList.html('<span class="slot-item">Sem intervalos definidos</span>');
            return;
        }
        const chosen = new Set((selected && selected.length ? selected : slots).map(String));
        let html = "";
        slots.forEach((slot) => {
            const checked = chosen.has(String(slot)) ? "checked" : "";
            html += `
                <label class="slot-item">
                    <input type="checkbox" class="slot-checkbox" value="${slot}" ${checked}>
                    <span>${escapeHtml(slot)}</span>
                </label>
            `;
        });
        $slotsList.html(html);
    }

    function renderTabela(rows, permanentes) {
        if (!rows.length) {
            $tbody.html('<tr><td colspan="5" class="empty-row">Nenhum formando encontrado</td></tr>');
            return;
        }

        let html = "";
        rows.forEach((r, idx) => {
            const situacao = r.situacao || "Presente";
            const observacao = r.observacao || "";
            html += `
                <tr data-id="${r.id}">
                    <td>${idx + 1}</td>
                    <td>${escapeHtml(r.nome_completo)}</td>
                    <td>${escapeHtml(r.codigo_formando || "-")}</td>
                    <td>
                        <select class="situacao-select table-select">
                            <option value="Presente" ${situacao === "Presente" ? "selected" : ""}>Presente</option>
                            <option value="Ausente" ${situacao === "Ausente" ? "selected" : ""}>Ausente</option>
                            <option value="WD" ${situacao === "WD" ? "selected" : ""}>WD (Withdrew)</option>
                            <option value="D" ${situacao === "D" ? "selected" : ""}>D (Desistiu)</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" class="obs-input table-input" value="${escapeHtml(observacao)}" placeholder="Opcional">
                    </td>
                </tr>
            `;
        });
        $tbody.html(html);
    }

    function collectRegistos() {
        const registos = [];
        $("#lista_presencas tr").each(function () {
            const $row = $(this);
            const id = parseInt($row.data("id"), 10);
            if (!id) return;
            registos.push({
                formando_id: id,
                situacao: $row.find(".situacao-select").val() || "Presente",
                observacao: $row.find(".obs-input").val() || "",
            });
        });
        return registos;
    }

    function collectSlots() {
        const slots = [];
        $(".slot-checkbox:checked").each(function () {
            slots.push($(this).val());
        });
        return slots;
    }

    function scheduleSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveDraft, 600);
    }

    function saveDraft(callback) {
        if (!current.turma_id || !current.formador_modulo_id || !current.data) return;
        const payload = {
            turma_id: current.turma_id,
            formador_modulo_id: current.formador_modulo_id,
            data: current.data,
            slots: collectSlots(),
            registos: collectRegistos(),
        };

        $.ajax({
            url: urls.save,
            method: "POST",
            data: JSON.stringify(payload),
            contentType: "application/json",
            dataType: "json",
        }).done(function (res) {
            if (!res || !res.ok) return;
            current.estado = res.estado || "rascunho";
            updateEstadoBadge(current.estado);
            if (typeof callback === "function") callback();
        });
    }

    function publish() {
        if (!current.turma_id || !current.formador_modulo_id || !current.data) return;
        saveDraft(function () {
            $.post(urls.publicar, {
                turma_id: current.turma_id,
                formador_modulo_id: current.formador_modulo_id,
                data: current.data,
            }, function (res) {
                if (res && res.ok) {
                    current.estado = "publicado";
                    updateEstadoBadge("publicado");
                    showNotification("Presenças publicadas com sucesso!", true);
                } else {
                    const msg = (res && res.msg) ? res.msg : "Erro ao publicar presenças.";
                    showNotification(msg, false);
                }
            }, "json");
        });
    }

    function loadContext() {
        const turma_id = parseInt($turma.val(), 10) || 0;
        const formador_modulo_id = parseInt($modulo.val(), 10) || 0;
        const data = $data.val();

        current.turma_id = turma_id;
        current.formador_modulo_id = formador_modulo_id;
        current.data = data;
        current.permanentes = {};

        if (!turma_id || !formador_modulo_id || !data) {
            setInfo("Seleccione a turma, módulo e data para continuar.", "info-warning");
            hideSections();
            return;
        }

        $.getJSON(urls.contexto, { turma_id, formador_modulo_id, data })
            .done(function (res) {
                if (!res || !res.ok) {
                    setInfo("Não foi possível carregar o contexto.", "info-warning");
                    hideSections();
                    return;
                }

                if (res.status === "ok") {
                    $info.hide();
                } else {
                    $info.show();
                    setInfo(res.mensagem || "Contexto carregado.", "info-warning");
                }

                if (res.status !== "ok") {
                    hideSections();
                    return;
                }

                current.plano_id = res.plano_id || 0;
                current.estado = res.estado || "rascunho";
                current.permanentes = res.permanentes || {};

                renderCards(res.stats || {});
                renderSlots(res.slots || [], res.selected_slots || []);
                renderTabela(res.rows || [], current.permanentes);
                updateEstadoBadge(current.estado);

                $cards.show();
                $slotsWrap.show();
                $acoes.show();
                $tabelaWrap.show();
                $footer.show();
            })
            .fail(function () {
                setInfo("Erro ao carregar o contexto.", "info-warning");
                hideSections();
            });
    }

    $turma.on("change", function () {
        const turmaId = parseInt($turma.val(), 10) || 0;
        loadModulos(turmaId);
        loadContext();
    });

    $modulo.on("change", loadContext);
    $data.on("change", loadContext);

    $slotsList.on("change", ".slot-checkbox", scheduleSave);
    $tbody.on("change", ".situacao-select", scheduleSave);
    $tbody.on("input", ".obs-input", scheduleSave);

    function isPermanentRow($row) {
        const val = $row.find(".situacao-select").val();
        return val === "WD" || val === "D";
    }

    $btnTodos.on("click", function () {
        $("#lista_presencas tr").each(function () {
            const $row = $(this);
            if (isPermanentRow($row)) return;
            $row.find(".situacao-select").val("Presente");
        });
        scheduleSave();
    });

    $btnLimpar.on("click", function () {
        $("#lista_presencas tr").each(function () {
            const $row = $(this);
            if (isPermanentRow($row)) return;
            $row.find(".situacao-select").val("Presente");
            $row.find(".obs-input").val("");
        });
        scheduleSave();
    });

    function aplicarMassa() {
        const val = $selectMassa.val();
        if (!val) return;
        $("#lista_presencas tr").each(function () {
            const $row = $(this);
            if (isPermanentRow($row)) return;
            $row.find(".situacao-select").val(val);
        });
        $selectMassa.val("");
        scheduleSave();
    }

    $btnAplicarMassa.on("click", aplicarMassa);

    $btnPublicar.on("click", publish);

    loadTurmas();
    setInfo("Seleccione a turma, módulo e data para continuar.", "info-warning");
});
