$(function () {
    const $page = $(".trabalhos-page");
    if (!$page.length) return;

    const urls = {
        turmas: $page.data("turmas-url"),
        modulos: $page.data("modulos-url"),
        listar: $page.data("listar-url"),
        save: $page.data("save-url"),
        del: $page.data("delete-url"),
        detail: $page.data("detail-url"),
    };

    const $turma = $("#trabalho_turma");
    const $modulo = $("#trabalho_modulo");
    const $tipo = $("#trabalho_tipo");
    const $titulo = $("#trabalho_titulo");
    const $publicacao = $("#trabalho_publicacao");
    const $entrega = $("#trabalho_entrega");
    const $pontuacao = $("#trabalho_pontuacao");
    const $estado = $("#trabalho_estado");
    const $descricao = $("#trabalho_descricao");
    const $info = $("#trabalho_info");
    const $tbody = $("#trabalhos_lista");
    const $btnAdd = $("#trabalho_adicionar");
    const $btnClear = $("#trabalho_limpar");

    let modulosCache = [];
    let trabalhosCache = [];
    let editingId = 0;
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

    function formatDate(dateStr) {
        if (!dateStr) return "-";
        const parts = dateStr.split("-");
        if (parts.length !== 3) return dateStr;
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }

    function estadoLabel(value) {
        const labels = {
            rascunho: "Rascunho",
            publicado: "Publicado",
            encerrado: "Encerrado"
        };
        return labels[value] || value || "-";
    }

    function tipoLabel(value) {
        const labels = {
            individual: "Individual",
            grupo: "Grupo",
            pratico: "Prático",
            projecto: "Projecto"
        };
        return labels[value] || value || "-";
    }

    function dentroDaDuracao(data) {
        if (!data) return false;
        if (current.data_inicio && data < current.data_inicio) return false;
        if (current.data_fim && data > current.data_fim) return false;
        return true;
    }

    function loadTurmas() {
        return $.getJSON(urls.turmas)
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
        modulosCache = [];
        if (!turmaId) return $.Deferred().resolve().promise();

        return $.getJSON(urls.modulos, { turma_id: turmaId })
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

    function setModuloData(formadorModuloId) {
        const selected = modulosCache.find((m) => parseInt(m.id, 10) === formadorModuloId);
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
            const inicio = formatDate(current.data_inicio);
            const fim = current.data_fim ? formatDate(current.data_fim) : "sem data de fim";
            setInfo(`Duração do módulo: ${inicio} a ${fim}.`, false);
        } else {
            setInfo("Este módulo não possui datas de duração registadas.", true);
        }
    }

    function renderLista(rows) {
        trabalhosCache = rows || [];
        if (!rows || !rows.length) {
            $tbody.html('<tr><td colspan="7" class="empty-row">Nenhum trabalho marcado ainda</td></tr>');
            return;
        }

        let html = "";
        rows.forEach((r, idx) => {
            const estado = (r.estado || "rascunho").toString();
            const descricao = (r.descricao || "").toString().trim();
            const pontuacao = r.pontuacao_maxima ? ` | ${escapeHtml(r.pontuacao_maxima)}%` : "";
            html += `
                <tr data-id="${r.id}">
                    <td>${idx + 1}</td>
                    <td>
                        <strong>${escapeHtml(r.titulo)}</strong>
                        ${descricao ? `<span class="trabalho-descricao-preview">${escapeHtml(descricao)}</span>` : ""}
                    </td>
                    <td>${escapeHtml(tipoLabel(r.tipo))}${pontuacao}</td>
                    <td>${escapeHtml(formatDate(r.data_publicacao))}</td>
                    <td>${escapeHtml(formatDate(r.data_entrega))}</td>
                    <td>
                        <span class="badge-estado is-${escapeHtml(estado)}">${escapeHtml(estadoLabel(estado))}</span>
                    </td>
                    <td>
                        <div class="trabalho-acoes-col">
                            <button type="button" class="trabalho-icon-btn btn-editar-trabalho" title="Editar">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button type="button" class="trabalho-icon-btn trabalho-icon-btn-danger btn-remover-trabalho" title="Eliminar">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        $tbody.html(html);
    }

    function loadTrabalhos() {
        if (!current.turma_id || !current.modulo_id) {
            renderLista([]);
            return;
        }

        $.getJSON(urls.listar, { turma_id: current.turma_id, modulo_id: current.modulo_id })
            .done(function (res) {
                if (!res || !res.ok) {
                    renderLista([]);
                    return;
                }
                renderLista(res.rows || []);
            });
    }

    function clearFields() {
        editingId = 0;
        $tipo.val("individual");
        $titulo.val("");
        $publicacao.val("");
        $entrega.val("");
        $pontuacao.val("");
        $estado.val("rascunho");
        $descricao.val("");
        $btnAdd.html('<i class="fa-solid fa-plus"></i><span>Marcar trabalho</span>');
    }

    function loadTrabalhoForEdit(id) {
        if (!id || !urls.detail) return;

        $.getJSON(urls.detail, { id }).done(function (res) {
            if (!res || !res.ok || !res.data) {
                showNotification("Não foi possível carregar o trabalho para edição.", false);
                return;
            }

            const item = res.data;
            editingId = parseInt(item.id, 10) || 0;
            current.turma_id = parseInt(item.turma_id, 10) || 0;
            $turma.val(String(current.turma_id));

            loadModulos(current.turma_id).done(function () {
                setModuloData(parseInt(item.formador_modulo_id, 10) || 0);
                $modulo.val(String(item.formador_modulo_id || ""));
                $tipo.val(item.tipo || "individual");
                $titulo.val(item.titulo || "");
                $publicacao.val(item.data_publicacao || "");
                $entrega.val(item.data_entrega || "");
                $pontuacao.val(item.pontuacao_maxima || "");
                $estado.val(item.estado || "rascunho");
                $descricao.val(item.descricao || "");
                $btnAdd.html('<i class="fa-solid fa-floppy-disk"></i><span>Guardar alterações</span>');
                loadTrabalhos();
            });
        }).fail(function () {
            showNotification("Erro ao carregar trabalho para edição.", false);
        });
    }

    $turma.on("change", function () {
        const turmaId = parseInt($turma.val(), 10) || 0;
        current.turma_id = turmaId;
        setModuloData(0);
        loadModulos(turmaId);
        renderLista([]);
    });

    $modulo.on("change", function () {
        const formadorModuloId = parseInt($modulo.val(), 10) || 0;
        setModuloData(formadorModuloId);
        loadTrabalhos();
    });

    $btnClear.on("click", clearFields);

    $btnAdd.on("click", function () {
        const titulo = ($titulo.val() || "").trim();
        const dataPublicacao = ($publicacao.val() || "").trim();
        const dataEntrega = ($entrega.val() || "").trim();
        const nota = ($pontuacao.val() || "").trim();

        if (!current.turma_id || !current.formador_modulo_id || !current.modulo_id) {
            showNotification("Seleccione a turma e o módulo.", false);
            return;
        }
        if (!titulo || !dataPublicacao || !dataEntrega) {
            showNotification("Preencha o título, a data de publicação e o prazo de entrega.", false);
            return;
        }
        if (dataEntrega < dataPublicacao) {
            showNotification("O prazo de entrega não pode ser anterior à data de publicação.", false);
            return;
        }
        if (!dentroDaDuracao(dataPublicacao) || !dentroDaDuracao(dataEntrega)) {
            showNotification("As datas do trabalho devem estar dentro da duração do módulo.", false);
            return;
        }
        if (nota !== "" && (Number.isNaN(Number(nota)) || Number(nota) < 0 || Number(nota) > 100)) {
            showNotification("A nota deve estar entre 0 e 100%.", false);
            return;
        }

        $btnAdd.prop("disabled", true).html("<span>A guardar...</span>");

        $.ajax({
            url: urls.save,
            method: "POST",
            data: {
                id: editingId,
                turma_id: current.turma_id,
                formador_modulo_id: current.formador_modulo_id,
                modulo_id: current.modulo_id,
                titulo: titulo,
                tipo: $tipo.val(),
                descricao: $descricao.val(),
                data_publicacao: dataPublicacao,
                data_entrega: dataEntrega,
                pontuacao_maxima: $pontuacao.val(),
                estado: $estado.val()
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
                    showNotification(editingId ? "Trabalho actualizado com êxito." : "Trabalho marcado com êxito.", true);
                    clearFields();
                    loadTrabalhos();
                } else {
                    showNotification(dataRes && dataRes.message ? dataRes.message : "Erro ao marcar o trabalho.", false);
                }
            },
            error: function () {
                showNotification("Erro de ligação ao servidor.", false);
            },
            complete: function () {
                $btnAdd.prop("disabled", false).html(editingId ? '<i class="fa-solid fa-floppy-disk"></i><span>Guardar alterações</span>' : '<i class="fa-solid fa-plus"></i><span>Marcar trabalho</span>');
            }
        });
    });

    $tbody.on("click", ".btn-editar-trabalho", function () {
        const id = parseInt($(this).closest("tr").data("id"), 10) || 0;
        const item = trabalhosCache.find((row) => parseInt(row.id, 10) === id);
        if (!item) return;

        editingId = id;
        $tipo.val(item.tipo || "individual");
        $titulo.val(item.titulo || "");
        $publicacao.val(item.data_publicacao || "");
        $entrega.val(item.data_entrega || "");
        $pontuacao.val(item.pontuacao_maxima || "");
        $estado.val(item.estado || "rascunho");
        $descricao.val(item.descricao || "");
        $btnAdd.html('<i class="fa-solid fa-floppy-disk"></i><span>Guardar alterações</span>');
        document.querySelector(".trabalhos-page")?.scrollIntoView({ behavior: "smooth", block: "start" });
    });

    $tbody.on("click", ".btn-remover-trabalho", function () {
        const id = parseInt($(this).closest("tr").data("id"), 10) || 0;
        if (!id) return;
        if (!confirm("Tem certeza de que deseja eliminar este trabalho?")) return;

        $.ajax({
            url: urls.del,
            method: "POST",
            data: { id: id },
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
                    showNotification("Trabalho eliminado com êxito.", true);
                    if (editingId === id) {
                        clearFields();
                    }
                    loadTrabalhos();
                } else {
                    showNotification(dataRes && dataRes.message ? dataRes.message : "Erro ao eliminar o trabalho.", false);
                }
            },
            error: function () {
                showNotification("Erro de ligação ao servidor.", false);
            }
        });
    });

    loadTurmas().done(function () {
        const params = new URLSearchParams(window.location.search);
        const trabalhoId = parseInt(params.get("trabalho_id") || "0", 10) || 0;
        if (trabalhoId > 0) {
            loadTrabalhoForEdit(trabalhoId);
        }
    });
});
