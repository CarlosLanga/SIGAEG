$(function () {
    const $page = $(".avaliacoes-page");
    if (!$page.length) return;

    const urls = {
        turmas: $page.data("turmas-url"),
        modulos: $page.data("modulos-url"),
        listar: $page.data("listar-url"),
        save: $page.data("save-url"),
        del: $page.data("delete-url"),
    };

    const $turma = $("#avaliacao_turma");
    const $modulo = $("#avaliacao_modulo");
    const $titulo = $("#avaliacao_titulo");
    const $data = $("#avaliacao_data");
    const $hora = $("#avaliacao_hora");
    const $info = $("#avaliacao_info");
    const $tbody = $("#avaliacoes_lista");
    const $btnAdd = $("#avaliacao_adicionar");
    const baseUrl = ($page.data("base-url") || "").toString();
    const resultadosUrl = ($page.data("resultados-url") || `${baseUrl}pages/admin/avaliacoes_resultados.php`).toString();
    const $btnClear = $("#avaliacao_limpar");

    let modulosCache = [];
    let current = {
        turma_id: 0,
        formador_modulo_id: 0,
        modulo_id: 0,
        data_inicio: null,
        data_fim: null
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

    function setInfo(message, warning) {
        if (!message) {
            $info.hide();
            return;
        }
        $info.text(message);
        $info.toggleClass("is-warning", !!warning);
        $info.show();
    }

    function formatDateBr(dateStr) {
        if (!dateStr) return "—";
        const parts = dateStr.split("-");
        if (parts.length !== 3) return dateStr;
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
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
            });
    }

    function loadModulos(turmaId) {
        $modulo.html('<option value="">Seleccione o módulo</option>');
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
            });
    }

    function setModuloData(moduloId) {
        const selected = modulosCache.find((m) => parseInt(m.id, 10) === moduloId);
        if (!selected) {
            current.formador_modulo_id = 0;
            current.modulo_id = 0;
            current.data_inicio = null;
            current.data_fim = null;
            setInfo("");
            return;
        }
        current.formador_modulo_id = parseInt(selected.id, 10) || 0;
        current.modulo_id = parseInt(selected.modulo_id, 10) || 0;
        current.data_inicio = selected.data_inicio || null;
        current.data_fim = selected.data_fim || null;

        if (current.data_inicio || current.data_fim) {
            const inicio = formatDateBr(current.data_inicio);
            const fim = current.data_fim ? formatDateBr(current.data_fim) : "sem data de fim";
            setInfo(`Vigência do módulo: ${inicio} a ${fim}.`, false);
        } else {
            setInfo("Este módulo não possui datas de vigência registadas.", true);
        }
    }

    function dentroDaVigencia(data) {
        if (!data) return false;
        if (current.data_inicio && data < current.data_inicio) return false;
        if (current.data_fim && data > current.data_fim) return false;
        return true;
    }

    function updateTituloOptions(rows) {
        const used = new Set(
            (rows || []).map((r) => (r.titulo || "").toString().trim().toLowerCase())
        );
        $titulo.find("option").each(function () {
            const val = ($(this).val() || "").toString().trim();
            if (!val) return;
            const disabled = used.has(val.toLowerCase());
            $(this).prop("disabled", disabled);
        });
        if ($titulo.find("option:selected").prop("disabled")) {
            $titulo.val("");
        }
    }

    function renderLista(rows, hasHora) {
        if (!rows || !rows.length) {
            $tbody.html('<tr><td colspan="6" class="empty-row">Nenhuma avaliação registada ainda</td></tr>');
            updateTituloOptions([]);
            return;
        }
        const hoje = new Date().toISOString().slice(0, 10);
        let html = "";
        rows.forEach((r, idx) => {
            const data = r.data_avaliacao || "";
            const hora = hasHora ? (r.hora_avaliacao || "—") : "—";
            const publicado = r.estado_resultado === "publicado";
            const estadoPassada = data && data <= hoje;
            const label = publicado ? "Realizada" : (estadoPassada ? "Pendente" : "Agendada");
            const badgeClass = publicado ? "is-passada" : (estadoPassada ? "is-pendente" : "");
            html += `
                <tr data-id="${r.id}">
                    <td>${idx + 1}</td>
                    <td>${escapeHtml(r.titulo)}</td>
                    <td>${escapeHtml(formatDateBr(data))}</td>
                    <td>${escapeHtml(hora)}</td>
                    <td>
                        <span class="badge-estado ${badgeClass}">
                            ${label}
                        </span>
                    </td>
                    <td>
                        <div class="avaliacao-acoes-col">
                            <button type="button" class="btn btn-outline btn-table btn-ver-resultados" title="Resultados">
                                <i class="fa-solid fa-square-poll-vertical"></i>
                            </button>
                            <button type="button" class="btn btn-outline btn-table btn-remover-avaliacao" title="Eliminar">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        $tbody.html(html);
        updateTituloOptions(rows);
    }

    function loadAvaliacoes() {
        if (!current.turma_id || !current.modulo_id) {
            renderLista([], true);
            return;
        }
        $.getJSON(urls.listar, { turma_id: current.turma_id, modulo_id: current.modulo_id })
            .done(function (res) {
                if (!res || !res.ok) {
                    renderLista([], true);
                    return;
                }
                renderLista(res.rows || [], !!res.hasHora);
            });
    }

    $turma.on("change", function () {
        const turmaId = parseInt($turma.val(), 10) || 0;
        current.turma_id = turmaId;
        modulosCache = [];
        loadModulos(turmaId);
        setInfo("");
        renderLista([], true);
        updateTituloOptions([]);
    });

    $modulo.on("change", function () {
        const moduloId = parseInt($modulo.val(), 10) || 0;
        setModuloData(moduloId);
        loadAvaliacoes();
        updateTituloOptions([]);
    });

    $btnClear.on("click", function () {
        $titulo.val("");
        $data.val("");
        $hora.val("");
        setInfo("");
    });

    $btnAdd.on("click", function () {
        const titulo = ($titulo.val() || "").trim();
        const data = ($data.val() || "").trim();
        const hora = ($hora.val() || "").trim();

        if (!current.turma_id || !current.formador_modulo_id || !current.modulo_id) {
            showNotification("Seleccione a turma e o módulo.", false);
            return;
        }
        if (!titulo || !data || !hora) {
            showNotification("Preencha a avaliação, a data e a hora.", false);
            return;
        }
        if (!dentroDaVigencia(data)) {
            showNotification("A data da avaliação deve estar dentro do período de vigência do módulo.", false);
            return;
        }

        $btnAdd.prop("disabled", true).html("<span>A guardar...</span>");

        $.ajax({
            url: urls.save,
            method: "POST",
            data: {
                turma_id: current.turma_id,
                formador_modulo_id: current.formador_modulo_id,
                modulo_id: current.modulo_id,
                titulo: titulo,
                data_avaliacao: data,
                hora_avaliacao: hora
            },
            success: function (res) {
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
                    showNotification("Avaliação registada com êxito.", true);
                    $titulo.val("");
                    $data.val("");
                    $hora.val("");
                    loadAvaliacoes();
                } else {
                    showNotification(dataRes && dataRes.message ? dataRes.message : "Erro ao registar a avaliação.", false);
                }
            },
            error: function () {
                showNotification("Erro de ligação ao servidor.", false);
            },
            complete: function () {
                $btnAdd.prop("disabled", false).html('<i class="fa-solid fa-plus"></i><span>Registar avaliação</span>');
            }
        });
    });

    $tbody.on("click", ".btn-remover-avaliacao", function () {
        const $row = $(this).closest("tr");
        const id = parseInt($row.data("id"), 10) || 0;
        if (!id) return;
        if (!confirm("Tem certeza de que deseja eliminar esta avaliação?")) return;

        $.ajax({
            url: urls.del,
            method: "POST",
            data: { 
                id: id 
            },
            success: function (res) {
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
                    showNotification("Avaliação eliminada com êxito.", true);
                    loadAvaliacoes();
                } else {
                    showNotification(dataRes && dataRes.message ? dataRes.message : "Erro ao eliminar a avaliação.", false);
                }
            },
            error: function () {
                showNotification("Erro de ligação ao servidor.", false);
            }
        });
    });

    $tbody.on("click", ".btn-ver-resultados", function () {
        const $row = $(this).closest("tr");
        const id = parseInt($row.data("id"), 10) || 0;
        if (!id) return;
        if (!current.turma_id || !current.formador_modulo_id) return;

        const url = `${resultadosUrl}?turma_id=${current.turma_id}&formador_modulo_id=${current.formador_modulo_id}&avaliacao_id=${id}`;
        window.location.href = url;
    });

    loadTurmas();
});
