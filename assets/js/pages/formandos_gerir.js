$(function () {
    // Sessão
    const SS = {
        KEY_PESQUISA: 'fg_pesquisa',
        KEY_FILTRO:   'fg_filtro_turma',
        KEY_PAGINA:   'fg_pagina',
        KEY_DETALHE:  'fg_detalhe_id',
        KEY_ORDEM:    'fg_detalhe_ordem',
        get: (k)      => sessionStorage.getItem(k),
        set: (k, v)   => sessionStorage.setItem(k, v),
        del: (...ks)  => ks.forEach(k => sessionStorage.removeItem(k)),
    };
    function turnoSigla(nomeTurno) {
        if (!nomeTurno) return "";
        return nomeTurno.toLowerCase().includes("diurno") ? "CD" : "CN";
    }

    const $formEdit = $("#form_editar_formando");
    const $certEdit = $formEdit.find("#certificado_vocacional");
    const $cursoEdit = $formEdit.find("#curso_id");
    const $turnoEdit = $formEdit.find("#turno_id");
    const $turmaEdit = $formEdit.find("#turma_id");

    function resetTurmaEdit(msg) {
        $turmaEdit.html(`<option value="" disabled selected>${msg || "Seleccione a qualificação e o turno"}</option>`);
        $turmaEdit.prop("disabled", true);
    }

    function resetTurnoEdit() {
        $turnoEdit.val("");
    }

    function renderCursosEdit() {
        const val = $certEdit.val();
        $cursoEdit.empty();

        if (val === "CV3") {
            $cursoEdit.html('<option value="">Electricidade Industrial - Depreciado</option>');
            $cursoEdit.prop("disabled", true);
            resetTurnoEdit();
            resetTurmaEdit("Nenhuma turma disponível");
            return;
        }

        if (val === "CV4") {
            $cursoEdit.append('<option value="3">Técnico de Suporte Informático</option>');
            $cursoEdit.prop("disabled", false);
        } else if (val === "CV5") {
            $cursoEdit.append('<option value="1">Programação de Aplicações Web</option>');
            $cursoEdit.append('<option value="2">Administração de Sistemas de Redes Informáticas</option>');
            $cursoEdit.prop("disabled", false);
        } else {
            $cursoEdit.append('<option value="">Seleccione uma qualificação</option>');
            $cursoEdit.prop("disabled", false);
        }

        resetTurnoEdit();
        resetTurmaEdit();
    }

    function carregarTurmasEdit(turmaAtual) {
        const url = $formEdit.data("turmas-url");
        const cursoId = $cursoEdit.val();
        const turnoId = $turnoEdit.val();

        if (!cursoId || !turnoId) {
            resetTurmaEdit();
            return;
        }

        $turmaEdit.prop("disabled", false);

        $.getJSON(url, { curso_id: cursoId, turno_id: turnoId }, function (data) {
            if (!data.length) {
                resetTurmaEdit("Nenhuma turma disponível");
                return;
            }

            let options = '<option value="">Seleccione a turma</option>';
            const turnoTexto = $turnoEdit.find("option:selected").text();
            const turnoSigla = turnoTexto.includes("Diurno") ? "CD" : "CN";

            data.forEach(t => {
                const selected = turmaAtual && turmaAtual == t.id ? "selected" : "";
                options += `<option value="${t.id}" ${selected}>${t.nome_turma} - ${turnoSigla}</option>`;
            });

            $turmaEdit.html(options);
        });
    }

    if ($formEdit.length) {
        $certEdit.on("change", renderCursosEdit);
        $cursoEdit.on("change", function () {
            carregarTurmasEdit();
        });
        $turnoEdit.on("change", function () {
            carregarTurmasEdit();
        });
    }

    TableManager({
        root: ".form-card",
        tbody: "#lista_formandos",
        info: "#table_info",
        btnPrev: "#btn_prev",
        btnNext: "#btn_next",
        pageNumbers: "#table_page_numbers",
        pageSize: "#page_size",
        filter: "#filtro_turma",
        search: "#pesquisa_formando",
        btnPrint: "#btn_imprimir",
        paginationMode: "numbered",
        limit: 20,
        render: function (rows, currentPage, limit) {
            const $tbody = $("#lista_formandos");
            if (!rows.length) {
                $tbody.html('<tr><td colspan="8" class="empty-row">Nenhum formando encontrado</td></tr>');
                return;
            }

            const offset = (currentPage - 1) * limit;
            let html = "";
            rows.forEach((r, i) => {
                const ordem = offset + i + 1;
                const sigla = turnoSigla(r.nome_turno);
                const turma = r.nome_turma ? `${r.nome_turma} ${sigla ? " - " + sigla : ""}` : "-";
                const estadoClass = r.estado === "Cadastrado" ? "status-active" : "status-started";

                html += `
                    <tr data-id="${r.id}" data-ordem="${ordem}">
                        <td>${ordem}</td>
                        <td>${r.nome_completo}</td>
                        <td>${r.sexo}</td>
                        <td>${r.codigo_formando}</td>
                        <td>${turma}</td>
                        <td>${r.data_criacao || "-"}</td>
                        <td><span class="status ${estadoClass}">${r.estado}</span></td>
                        <td class="table-actions">
                            <button class="btn btn-outline btn-table btn-edit" data-id="${r.id}" title="Editar">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-outline btn-table btn-delete" data-id="${r.id}" title="Remover">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            $tbody.html(html);
        },
        renderFilters: function (rows) {
            const $filtro = $("#filtro_turma");
            let options = '<option value="all">Todas as turmas</option>';
            rows.forEach(t => {
                const sigla = turnoSigla(t.nome_turno);
                options += `<option value="${t.id}">${t.nome_turma} ${sigla ? " - " + sigla : ""}</option>`;
            });
            $filtro.html(options);

            const filtroSalvo = SS.get(SS.KEY_FILTRO);
            if (filtroSalvo && filtroSalvo !== 'all') {
                if ($filtro.find('option[value="' + filtroSalvo + '"]').length) {
                    $filtro.val(filtroSalvo).trigger('change');
                }
            }
        },
        onReady: function (tm) {
            const pesquisaSalva = SS.get(SS.KEY_PESQUISA);
            if (pesquisaSalva) {
                $("#pesquisa_formando").val(pesquisaSalva).trigger('input');
            }

            const detalheSalvo = SS.get(SS.KEY_DETALHE);
            const ordemSalva   = SS.get(SS.KEY_ORDEM);
            if (detalheSalvo) {
                abrirDetalhe(detalheSalvo, ordemSalva ? Number(ordemSalva) : undefined);
            }
        },
    });

    $("#btn_imprimir").on("click", function () {
        const turma = $("#filtro_turma").val();
        if (turma === "all") {
            alert("Selecione uma turma para imprimir.");
            return;
        }
        window.open(`${$(".form-card").data("base-url")}api/formandos_imprimir.php?turma_id=${turma}`, "_blank");
    });

    // Detalhes do formand painel
    const $pageHeader = $(".content-body > .page-header").first();
    const $tableSection = $(".content-body > .card.form-card").first();
    const $detailPanel = $("#painel_detalhe_formando");
    const $floatingBack = $("#btn_voltar_floating");
    const $horarioMeta = $("#detalhe_horario_meta");
    const $horarioList = $("#detalhe_horario_list");
    const $horarioGrid = $("#detalhe_horario_grid");
    const $btnHorarioToggleAll = $("#btn_horario_toggle_all");
    const $btnVerHorarioTurma = $("#btn_ver_horario_turma");
    const baseUrl = $(".form-card").data("base-url");
    const horarioHojeUrl = `${baseUrl}api/formando_horario_hoje.php`;
    const horarioGradePreviewUrl = `${baseUrl}api/horario_grade_preview.php`;
    const horarioGradePrintUrl = `${baseUrl}api/horario_grade_print.php`;
    let detalheFormandoId = null;
    let detalheTurmaId = 0;
    let detalheNumeroCartao = null;
    let horarioHojeRows = [];
    let horarioHojeGroups = [];
    let horarioHojeTimer = null;
    let horarioMostrarTudo = false;
    let horarioPlano = null;
    let horarioGradeLoaded = false;

    if ($floatingBack.length) {
        $floatingBack.appendTo("body");
    }

    function formatarData(data) {
        if (!data || data === "0000-00-00") return "—";
        const partes = data.split("-");
        if (partes.length !== 3) return data;
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    function getInitialsJS(name) {
        if (!name) return "--";
        const parts = name.trim().split(/\s+/);
        const first = parts[0] ? parts[0][0].toUpperCase() : "";
        const last = parts.length > 1 ? parts[parts.length - 1][0].toUpperCase() : first;
        return first + last;
    }

    function escapeHtmlDetail(value) {
        return String(value == null ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function hmToMinutes(hm) {
        if (!/^\d{2}:\d{2}$/.test(String(hm || ""))) return null;
        return (parseInt(hm.slice(0, 2), 10) * 60) + parseInt(hm.slice(3, 5), 10);
    }

        function computeSlotProgress(row) {
        const start = hmToMinutes(row.inicio_hora);
        const end = hmToMinutes(row.fim_hora);
        if (start === null || end === null || end <= start) {
            return { status: "upcoming", progress: 0 };
        }

        const now = new Date();
        const nowMinutes = (now.getHours() * 60) + now.getMinutes();
        if (nowMinutes < start) return { status: "upcoming", progress: 0 };
        if (nowMinutes >= end) return { status: "completed", progress: 100 };

        const progress = Math.round(((nowMinutes - start) / (end - start)) * 100);
        return { status: "current", progress: Math.max(0, Math.min(100, progress)) };
    }

    function keyHorarioGrupo(row) {
        return [
            row.formador_modulo_id || "",
            row.sigla_modulo || "",
            row.nome_modulo || "",
            row.formador_nome || ""
        ].join("|");
    }

    function ordenarSlots(rows) {
        return [...rows].sort((a, b) => {
            const aa = hmToMinutes(a.inicio_hora) ?? 0;
            const bb = hmToMinutes(b.inicio_hora) ?? 0;
            return aa - bb;
        });
    }

    function agruparHorarioConsecutivo(rows) {
        const sorted = ordenarSlots(rows);
        const grupos = [];

        sorted.forEach((row) => {
            const inicio = row.inicio_hora || "";
            const fim = row.fim_hora || "";
            const inicioMin = hmToMinutes(inicio);
            const fimMin = hmToMinutes(fim);
            const key = keyHorarioGrupo(row);
            const ultimo = grupos.length ? grupos[grupos.length - 1] : null;

            const consecutivo = !!ultimo
                && ultimo._key === key
                && inicioMin !== null
                && ultimo._fimMin !== null
                && (inicioMin - ultimo._fimMin <= 30)
                && (inicioMin - ultimo._fimMin >= 0);

            if (consecutivo) {
                ultimo.fim_hora = fim;
                ultimo._fimMin = fimMin;
                ultimo.total_slots += 1;
                return;
            }

            grupos.push({
                ...row,
                inicio_hora: inicio,
                fim_hora: fim,
                total_slots: 1,
                _key: key,
                _fimMin: fimMin
            });
        });

        return grupos;
    }

    function canShowFullGrade() {
        return detalheTurmaId > 0
            && horarioPlano
            && [1, 2].includes(Number(horarioPlano.semestre))
            && [1, 2].includes(Number(horarioPlano.bloco));
    }

    function atualizarEstadoBotoesHorario() {
        $btnVerHorarioTurma.prop("disabled", !canShowFullGrade());
    }

    function renderHorarioHoje() {
        if (!$horarioList.length || !$horarioMeta.length) return;

        if (!horarioHojeGroups.length) {
            $horarioList.html("");
            $btnHorarioToggleAll.hide();
            return;
        }

        const gruposEmCurso = horarioHojeGroups.filter((g) => computeSlotProgress(g).status === "current");
        const deveMostrarToggle = gruposEmCurso.length < horarioHojeGroups.length;
        const gruposVisiveis = horarioMostrarTudo ? horarioHojeGroups : gruposEmCurso;

        $btnHorarioToggleAll
            .text(horarioMostrarTudo ? "Mostrar em curso" : "Mostrar tudo")
            .toggle(deveMostrarToggle);

        if (!gruposVisiveis.length) {
            $horarioList.html('<p class="detail-empty-note"><i class="fa-solid fa-clock"></i> Sem módulo a decorrer agora.</p>');
            return;
        }

        let html = "";
        gruposVisiveis.forEach((row) => {
            const state = computeSlotProgress(row);
            const statusLabel = state.status === "current"
                ? "A decorrer"
                : (state.status === "completed" ? "Concluido" : "Por iniciar");
            const modulo = row.sigla_modulo || "-";
            const formador = row.formador_nome || "Sem formador";
            const slotsTxt = row.total_slots > 1 ? `${row.total_slots} tempos consecutivos` : "1 tempo";

            html += `
                <div class="detail-horario-item">
                    <div class="detail-horario-item-head">
                        <span class="detail-horario-time">${escapeHtmlDetail(row.inicio_hora)} - ${escapeHtmlDetail(row.fim_hora)}</span>
                        <span class="detail-horario-status ${state.status}">${statusLabel}</span>
                    </div>
                    <div class="detail-horario-module">${escapeHtmlDetail(modulo)}</div>
                    <div class="detail-horario-formador">${escapeHtmlDetail(formador)} • ${escapeHtmlDetail(slotsTxt)}</div>
                    ${state.status === 'current' ? `
                    <div class="detail-horario-progress">
                        <div class="detail-horario-progress-track">
                            <div class="detail-horario-progress-fill" style="width:${state.progress}%"></div>
                        </div>
                        <div class="detail-horario-progress-text">${state.progress}%</div>
                    </div>
                    ` : ''}
                </div>
            `;
        });

        $horarioList.html(html);
    }

    function renderHorarioGrade(preview) {
        const days = Array.isArray(preview.days) ? preview.days : [];
        const slots = Array.isArray(preview.slots) ? preview.slots : [];
        const cells = preview.cells || {};

        if (!days.length || !slots.length) {
            $horarioGrid.html('<tr><td><p class="detail-empty-note"><i class="fa-solid fa-circle-info"></i> Sem grelha disponível.</p></td></tr>');
            return;
        }

        let html = '<thead><tr><th class="horario-grade-hour">Horas</th>';
        days.forEach((d) => {
            html += `<th>${escapeHtmlDetail(d.label || "")}</th>`;
        });
        html += "</tr></thead><tbody>";

        slots.forEach((slot) => {
            html += `<tr><td class="horario-grade-hour">${escapeHtmlDetail(slot.label || "")}</td>`;
            days.forEach((day) => {
                const key = `${day.key}__${slot.code}`;
                const siglaModulo = cells[key] || "";
                const content = siglaModulo
                    ? `<div class="horario-preview-slot is-filled"><span class="horario-preview-text">${escapeHtmlDetail(siglaModulo)}</span></div>`
                    : `<div class="horario-preview-slot"></div>`;
                html += `<td>${content}</td>`;
            });
            html += "</tr>";

            if (!preview.is_nocturno && slot.code === "11:00-11:45") {
                html += `<tr class="horario-grade-break"><td colspan="${days.length + 1}">Intervalo maior</td></tr>`;
            }
        });

        html += "</tbody>";
        $horarioGrid.html(html);

        const $meta = $("#detalhe_horario_meta_modal");
        if ($meta.length && preview.turma) {
            const sigla = turnoSigla(preview.turma.nome_turno || "");
            const turmaLabel = `${preview.turma.nome_turma || "-"}${sigla ? " - " + sigla : ""}`;
            const estado = Number(preview.publicado) === 1 ? "Publicado" : "Não publicado";
            const semestreLabel = Number(horarioPlano.semestre) === 1 ? "I" : Number(horarioPlano.semestre) === 2 ? "II" : "-";
            const statusClass = Number(preview.publicado) === 1 ? "status-published" : "status-draft";
            const metaHtml = `
                <div><strong>Turma:</strong> ${escapeHtmlDetail(turmaLabel)}</div>
                <div><strong>Ano lectivo:</strong> ${escapeHtmlDetail(preview.turma.ano_lectivo || "-")}</div>
                <div><strong>Semestre:</strong> ${escapeHtmlDetail(semestreLabel)}</div>
                <div><strong>Bloco:</strong> ${escapeHtmlDetail(horarioPlano.bloco)}º</div>
                <div><strong>Estado:</strong> <span class="status ${statusClass}">${escapeHtmlDetail(estado)}</span></div>
            `;
            $meta.html(metaHtml);
        }
    }

    function carregarHorarioGradeCompleta() {
        if (!canShowFullGrade()) return;

        $horarioGrid
            .show()
            .html('<tr><td><p class="detail-empty-note"><i class="fa-solid fa-spinner fa-spin"></i> A carregar grelha completa...</p></td></tr>');

        $.getJSON(horarioGradePreviewUrl, {
            turma_id: detalheTurmaId,
            semestre: Number(horarioPlano.semestre),
            bloco: Number(horarioPlano.bloco)
        }, function (res) {
            if (!res || !res.ok) {
                $horarioGrid.html('<tr><td><p class="detail-empty-note"><i class="fa-solid fa-circle-exclamation"></i> Nao foi possivel carregar a grelha.</p></td></tr>');
                return;
            }

            renderHorarioGrade(res);
            horarioGradeLoaded = true;
        }).fail(function () {
            $horarioGrid.html('<tr><td><p class="detail-empty-note"><i class="fa-solid fa-circle-exclamation"></i> Nao foi possivel carregar a grelha.</p></td></tr>');
        });
    }

    function carregarHorarioHoje(formandoId) {
        if (!$horarioList.length || !$horarioMeta.length) return;

        if (horarioHojeTimer) {
            clearInterval(horarioHojeTimer);
            horarioHojeTimer = null;
        }

        horarioMostrarTudo = false;
        horarioPlano = null;
        horarioGradeLoaded = false;
        $horarioMeta.text("A carregar horario...");
        $horarioList.html("");
        $horarioGrid.hide().html("");
        $btnHorarioToggleAll.hide();
        atualizarEstadoBotoesHorario();

        $.getJSON(horarioHojeUrl, { formando_id: formandoId }, function (res) {
            if (!res || !res.ok) {
                $horarioMeta.text("Nao foi possivel carregar o horario de hoje.");
                return;
            }

            const dia = res.dia_label ? `Hoje (${res.dia_label})` : "Hoje";
            $horarioMeta.text(dia);
            horarioPlano = res.plano || null;
            atualizarEstadoBotoesHorario();

            horarioHojeRows = Array.isArray(res.rows) ? res.rows : [];
            horarioHojeGroups = agruparHorarioConsecutivo(horarioHojeRows);

            if (!horarioHojeRows.length) {
                $horarioList.html('<p class="detail-empty-note"><i class="fa-solid fa-circle-info"></i> Sem aulas para hoje.</p>');
                $btnHorarioToggleAll.hide();
                return;
            }

            renderHorarioHoje();
            horarioHojeTimer = setInterval(renderHorarioHoje, 30000);
        }).fail(function () {
            $horarioMeta.text("Nao foi possivel carregar o horario de hoje.");
            $horarioList.html("");
        });
    }

    $btnHorarioToggleAll.on("click", function () {
        horarioMostrarTudo = !horarioMostrarTudo;
        renderHorarioHoje();
    });

    $btnVerHorarioTurma.on("click", function () {
        if (!canShowFullGrade()) return;
        $("#modal_horario_turma").addClass("open");
        
        if (!horarioGradeLoaded) {
            carregarHorarioGradeCompleta();
        } else {
            $horarioGrid.show();
        }
    });

    $("#btn_fechar_horario").on("click", function () {
        $("#modal_horario_turma").removeClass("open");
    });

    // Balanco de módulos
    let modulosBalancoOriginal = [];
    let modulosBalancoVisivel = false;

    function carregarBalancoModulos(formandoId) {
        const base = $(".form-card").data("base-url");
        $("#balanco_modulos_container").empty();
        $("#filtro_cv_balanco").html('<option value="">A carregar CVs...</option>');
        $("#freq_desc_text").text("A carregar dados dos módulos...");
        $("#freq_media_val").text("0");
        $("#freq_gauge_path").removeClass("is-danger");
        $("#freq_desc_text").removeClass("is-danger");
        $("#btn_balanco_ver_tudo").hide();
        modulosBalancoOriginal = [];
        modulosBalancoVisivel = false;

        $.getJSON(`${base}api/formando_balanco_modulos.php`, { formando_id: formandoId }, function (res) {
            if (!res.ok) {
                $("#freq_desc_text").text("Não foi possível carregar o balanço de módulos.");
                return;
            }

            // Popula filtro
            let options = '';
            if (res.cvs && res.cvs.length) {
                res.cvs.forEach(cv => {
                    options += `<option value="${cv.id}" ${cv.selected ? "selected" : ""}>${escapeHtmlDetail(cv.label)}</option>`;
                });
            } else {
                options = '<option value="">Sem cursos associados</option>';
            }
            $("#filtro_cv_balanco").html(options);

            modulosBalancoOriginal = res.modulos || [];
            renderizarBarrasModulos();

            const st = res.stats;
            const $desc = $("#freq_desc_text");
            const $gauge = $("#freq_gauge_path");
            
            const media = res.media_geral || 0;
            const maxDash = 125.6;
            const offset = maxDash - (maxDash * (media / 100));
            $("#freq_media_val").text(media);
            $gauge.css("stroke-dashoffset", maxDash); // Reset
            setTimeout(() => {
                $gauge.css("stroke-dashoffset", offset);
            }, 100);

            if (st.reprovados > 0) {
                $gauge.addClass("is-danger");
                $desc.addClass("is-danger").text(`Iminência de reprovação! O formando reprovou em ${st.reprovados} módulo(s).`);
            } else {
                $gauge.removeClass("is-danger");
                $desc.removeClass("is-danger").text(`${st.concluidos} concluído(s), ${st.em_curso} em curso e ${st.por_iniciar} por realizar.`);
            }
        }).fail(function() {
            $("#freq_desc_text").text("Erro ao buscar dados dos módulos.");
        });
    }

    function renderizarBarrasModulos() {
        const $container = $("#balanco_modulos_container");
        $container.empty();
        
        if (!modulosBalancoOriginal.length) {
            $container.html('<p class="detail-empty-note">Sem módulos registados.</p>');
            $("#btn_balanco_ver_tudo").hide();
            return;
        }

        const maxVisiveis = 6;
        let modsParaMostrar = modulosBalancoOriginal;
        
        if (!modulosBalancoVisivel && modulosBalancoOriginal.length > maxVisiveis) {
            modsParaMostrar = modulosBalancoOriginal.slice(0, maxVisiveis);
            $("#btn_balanco_ver_tudo").show().text(`Ver todos (${modulosBalancoOriginal.length})`);
        } else if (modulosBalancoOriginal.length > maxVisiveis) {
            $("#btn_balanco_ver_tudo").show().text("Ver menos");
        } else {
            $("#btn_balanco_ver_tudo").hide();
        }

        modsParaMostrar.forEach(m => {
            let fillClass = "is-pending";
            if (m.reprovado) {
                fillClass = "is-danger";
            } else if (m.estado === "Concluído") {
                fillClass = "is-completed";
            } else if (m.estado === "Em vigência") {
                fillClass = "is-active";
            }

            const html = `
                <div class="freq-bar-item" title="${escapeHtmlDetail(m.nome)} (${escapeHtmlDetail(m.estado)})">
                    <span class="freq-bar-val">${m.progress}%</span>
                    <div class="freq-bar-bg">
                        <div class="freq-bar-fill ${fillClass}" style="height: 0%"></div>
                    </div>
                    <div class="freq-bar-label">${escapeHtmlDetail(m.sigla)}</div>
                </div>
            `;
            const $bar = $(html);
            $container.append($bar);
            
            setTimeout(() => {
                $bar.find(".freq-bar-fill").css("height", m.progress + "%");
            }, 150);
        });
    }

    $("#btn_balanco_ver_tudo").on("click", function() {
        modulosBalancoVisivel = !modulosBalancoVisivel;
        renderizarBarrasModulos();
    });

    function abrirDetalhe(id, ordem) {
        SS.set(SS.KEY_DETALHE, id);
        if (ordem) SS.set(SS.KEY_ORDEM, ordem);
        detalheFormandoId = id;
        if (Number.isFinite(Number(ordem)) && Number(ordem) > 0) {
            detalheNumeroCartao = Number(ordem);
        } else {
            const $linha = $("#lista_formandos tr[data-id='" + id + "']");
            const fallbackOrdem = Number($linha.data("ordem"));
            detalheNumeroCartao = Number.isFinite(fallbackOrdem) && fallbackOrdem > 0 ? fallbackOrdem : null;
        }
        const base = $(".form-card").data("base-url");

        $pageHeader.hide();
        $tableSection.hide();
        $detailPanel.removeAttr("style").show();
        $floatingBack.addClass("is-visible");

        window.scrollTo({ top: 0, behavior: "smooth" });

        $detailPanel.find(".detail-layout").addClass("detail-loading");

        $.getJSON(`${base}api/formando_detalhe.php`, { id }, function (res) {
            $detailPanel.find(".detail-layout").removeClass("detail-loading");

            if (!res.ok) {
                fecharDetalhe();
                showNotification("Erro ao carregar dados do formando.", false);
                return;
            }

            const d = res.data;
            const enc = res.encarregado || {};

            detalheTurmaId = d.turma_id || 0;

            $("#detalhe_iniciais").text(getInitialsJS(d.nome_completo));

            $("#detalhe_nome").text(d.nome_completo || "—");
            $("#detalhe_codigo_badge span").text(d.codigo_formando || "—");

            // estado badge
            const cadastrado = !!d.usuario_id;
            const $estadoBadge = $("#detalhe_estado_badge");
            $estadoBadge
                .text(cadastrado ? "Cadastrado" : "Não cadastrado")
                .removeClass("detail-estado-active detail-estado-inactive")
                .addClass(cadastrado ? "detail-estado-active" : "detail-estado-inactive");

            // turma bage
            const sigla = turnoSigla(d.nome_turno);
            const turmaTexto = d.nome_turma
                ? `${d.nome_turma}${sigla ? " - " + sigla : ""}`
                : "—";
            $("#detalhe_turma_badge").text(turmaTexto).addClass("badge-turma");

            // info peSSOAL
            $("#detalhe_sexo").text(d.sexo || "—");
            $("#detalhe_data_nascimento").text(formatarData(d.data_nascimento));
            $("#detalhe_contacto").text(d.contacto || "—");
            $("#detalhe_email").text(d.email || "—");
            $("#detalhe_documento").text(d.numero_documento || "—");

            // INFORMAÇ~PES ACADÉMICAS
            $("#detalhe_codigo").text(d.codigo_formando || "—");
            $("#detalhe_turma").text(turmaTexto);
            $("#detalhe_cv").text(d.certificado_vocacional || "—");
            $("#detalhe_ano_ingresso").text(d.ano_ingresso || "—");
            $("#detalhe_ano_conclusao").text(d.ano_conclusao || "—");
            $("#detalhe_data_registo").text(formatarData(d.data_criacao));

            // Dados  de Encarregado ou seja, informações
            if (enc.id) {
                $("#detalhe_encarregado_grid").show();
                $("#detalhe_enc_vazio").hide();
                $("#detalhe_enc_nome").text(enc.nome_completo || "—");
                $("#detalhe_enc_parentesco").text(enc.parentesco || "—");
                $("#detalhe_enc_email").text(enc.email || "—");
                $("#detalhe_enc_contacto").text(enc.contacto || "—");
            } else {
                $("#detalhe_encarregado_grid").hide();
                $("#detalhe_enc_vazio").show();
            }

            // cartão de formando
            const numeroCartao = detalheNumeroCartao
                ? String(detalheNumeroCartao).padStart(2, "0")
                : "—";
            $("#cartao_codigo").text(numeroCartao);
            $("#cartao_ano_ingresso").text(d.ano_ingresso || "—");
            $("#cartao_nome").text(d.nome_completo || "—");
            $("#cartao_ano_lectivo").text(d.ano_ingresso || "—");
            $("#cartao_cv").text(d.certificado_vocacional || "—");
            $("#cartao_turma").text(turmaTexto);
            $("#cartao_numero_estudante").text(d.codigo_formando || "—");
            $("#cartao_curso").text(d.nome_curso || "—");
            $("#cartao_foto").text(getInitialsJS(d.nome_completo));
            carregarHorarioHoje(id);
            
            // Iniciar balanço de módulos
            carregarBalancoModulos(id);
        });
    }

    function fecharDetalhe() {
        $detailPanel.hide();
        $pageHeader.show();
        $tableSection.show();
        $floatingBack.removeClass("is-visible");
        if (horarioHojeTimer) {
            clearInterval(horarioHojeTimer);
            horarioHojeTimer = null;
        }
        horarioHojeRows = [];
        detalheFormandoId = null;
        detalheNumeroCartao = null;
        // Limpar estado do painel ao fechar
        SS.del(SS.KEY_DETALHE, SS.KEY_ORDEM);
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    $(document).on("click", "#lista_formandos tr[data-id]", function (e) {
        // Vai permitir não abrir o detalhe se o clique for no botão de editar ou eliminar
        if ($(e.target).closest(".btn-edit, .btn-delete").length) return;

        const id = $(this).data("id");
        const ordem = Number($(this).data("ordem"));
        if (id) {
            // Guardar filtros e pesquisa antes de abrir o detalhe
            SS.set(SS.KEY_PESQUISA, $("#pesquisa_formando").val() || '');
            SS.set(SS.KEY_FILTRO, $("#filtro_turma").val() || 'all');
            abrirDetalhe(id, ordem);
        }
    });

    $("#btn_voltar_lista, #breadcrumb_voltar, #btn_voltar_floating").on("click", function (e) {
        e.preventDefault();
        fecharDetalhe();
    });

    $("#btn_editar_detalhe").on("click", function () {
        if (!detalheFormandoId) return;
        const $editBtn = $("#lista_formandos .btn-edit[data-id='" + detalheFormandoId + "']");
        if ($editBtn.length) {
            $editBtn.trigger("click");
        }
    });

    let deleteFormandoId = null;

    function abrirConfirmacaoRemocao(id) {
        deleteFormandoId = id;
        $("#modal_confirmar_remocao_formando").fadeIn(150);
    }

    function fecharConfirmacaoRemocao() {
        deleteFormandoId = null;
        $("#modal_confirmar_remocao_formando").fadeOut(150);
    }

    $("#btn_cancelar_remocao_formando, #modal_confirmar_remocao_formando").on("click", function (e) {
        if (e.target !== this) return;
        fecharConfirmacaoRemocao();
    });

    $("#btn_confirmar_remocao_formando").on("click", function () {
        if (!deleteFormandoId) return;

        const base = $(".form-card").data("base-url");
        $.post(`${base}api/formando_delete.php`, { id: deleteFormandoId }, function (res) {
            const resposta = (res || "").toString().trim();
            fecharConfirmacaoRemocao();
            if (resposta === "sucesso") {
                if (detalheFormandoId === deleteFormandoId) {
                    fecharDetalhe();
                }
                showNotification("Formando removido com sucesso!", true);
                $("#pesquisa_formando").trigger("input");
                return;
            }
            showNotification("Erro ao remover formando", false);
        });
    });

    // Eliminar btn no detalhe
    $("#btn_eliminar_detalhe").on("click", function () {
        if (!detalheFormandoId) return;
        abrirConfirmacaoRemocao(detalheFormandoId);
    });

    // baixar cartao de formando btn
    $("#btn_baixar_cartao").on("click", function () {
        if (!detalheFormandoId) return;
        
        const $btn = $(this);
        const originalText = $btn.html();
        $btn.html('<i class="fa-solid fa-spinner fa-spin"></i> <span>A gerar...</span>').prop("disabled", true);
        
        const cardElement = document.getElementById("cartao_formando");
        
        setTimeout(() => {
            if (typeof html2canvas === "undefined") {
                showNotification("Erro: Biblioteca html2canvas não encontrada.", false);
                $btn.html(originalText).prop("disabled", false);
                return;
            }
            const clone = cardElement.cloneNode(true);
            const wrapper = document.createElement("div");
            wrapper.style.position = "absolute";
            wrapper.style.top = "0";
            wrapper.style.left = "-9999px";
            wrapper.appendChild(clone);
            document.body.appendChild(wrapper);

            html2canvas(clone, {
                scale: 2,
                backgroundColor: null,
                useCORS: true,
                logging: false
            }).then(canvas => {
                document.body.removeChild(wrapper);
                const imgData = canvas.toDataURL("image/png");
                const nomeCartao = $("#cartao_nome").text().replace(/\s+/g, "_") || "Formando";
                
                const link = document.createElement("a");
                link.download = `Cartao_${nomeCartao}.png`;
                link.href = imgData;
                link.click();
                
                $btn.html(originalText).prop("disabled", false);
                showNotification("Cartão gerado com sucesso!", true);
            }).catch(err => {
                if (document.body.contains(wrapper)) document.body.removeChild(wrapper);
                console.error("Erro ao gerar cartão:", err);
                showNotification("Erro ao gerar a imagem do cartão.", false);
                $btn.html(originalText).prop("disabled", false);
            });
        }, 100);
    });

    $(document).on("click", ".btn-delete", function () {
        const id = $(this).data("id");
        if (!id) return;
        abrirConfirmacaoRemocao(id);
    });

    // Modal de editar formando
    const $modal = $("#modal_editar_formando");
    const $form = $("#form_editar_formando");

    function abrirModal() {
        $modal.addClass("open");
    }

    function fecharModal() {
        $modal.removeClass("open");
    }

    $("#btn_fechar_editar, #btn_cancelar_editar").on("click", function () {
        fecharModal();
    });

    function renderCursos(cert) {
        const $curso = $form.find("#curso_id");
        $curso.empty();

        if (cert === "CV3") {
            $curso.html('<option value="">Electricidade Industrial - Depreciado</option>');
            $curso.prop("disabled", true);
            return;
        }

        if (cert === "CV4") {
            $curso.append('<option value="3">Técnico de Suporte Informático</option>');
        } else if (cert === "CV5") {
            $curso.append('<option value="1">Programação de Aplicações Web</option>');
            $curso.append('<option value="2">Administração de Sistemas de Redes Informáticas</option>');
        } else {
            $curso.append('<option value="">Seleccione uma qualificação</option>');
        }

        $curso.prop("disabled", false);
    }

    function carregarTurmas(cursoId, turnoId, turmaAtual) {
        const url = $form.data("turmas-url");
        const $turma = $form.find("#turma_id");

        if (!cursoId || !turnoId) {
            $turma.html('<option value="">Seleccione a turma</option>');
            return;
        }

        $.getJSON(url, { curso_id: cursoId, turno_id: turnoId }, function (data) {
            let options = '<option value="">Seleccione a turma</option>';
            const turnoTexto = $form.find("#turno_id option:selected").text();
            const turnoSigla = turnoTexto.includes("Diurno") ? "CD" : "CN";

            data.forEach(t => {
                const selected = turmaAtual && turmaAtual == t.id ? "selected" : "";
                options += `<option value="${t.id}" ${selected}>${t.nome_turma} - ${turnoSigla}</option>`;
            });

            $turma.html(options);
        });
    }

    $(document).on("click", ".btn-edit", function () {
        const id = $(this).data("id");
        const base = $(".form-card").data("base-url");

        $.getJSON(`${base}api/formando_detalhe.php`, { id }, function (res) {
            if (!res.ok) {
                alert("Erro ao carregar dados.");
                return;
            }

            $("#formando_id").val(res.data.id);
            $("#editar_nome").val(res.data.nome_completo);
            $("#editar_documento").val(res.data.numero_documento);
            $("#editar_contacto").val(res.data.contacto || "");
            $("#editar_codigo_formando").val(res.data.codigo_formando);
            $("#editar_data_nascimento").val(res.data.data_nascimento || "");
            $("#editar_ano_ingresso").val(res.data.ano_ingresso || "");
            $("#editar_ano_conclusao").val(res.data.ano_conclusao || "");
            $("#editar_email").val(res.data.email || "");
            $("#editar_email").data("original-email", res.data.email || "");

            $("#certificado_vocacional").val(res.data.certificado_vocacional || "");
            renderCursos(res.data.certificado_vocacional || "");
            $("#curso_id").val(res.data.curso_id || "");
            $("#turno_id").val(res.data.turno_id || "");

            carregarTurmas(res.data.curso_id, res.data.turno_id, res.data.turma_id);
            carregarTurmasEdit(res.data.turma_id);

            $("input[name='sexo'][value='" + res.data.sexo + "']").prop("checked", true);

            $("#editar_codigo_convite").val(res.data.codigo_convite || "");
            $("#codigo_gerado").val(res.data.codigo_convite || "");

            // Encarregado
            const enc = res.encarregado || {};
            const $encEmail = $form.find("[data-enc-email]");
            const $encId = $form.find("[data-enc-id]");
            const $encNome = $form.find("[data-enc-nome]");
            const $encGrau = $form.find("[data-enc-grau]");
            const $encContacto = $form.find("[data-enc-contacto]");
            const $encCodigo = $form.find("[data-enc-codigo]");             
            const $encBtn = $form.find("[data-enc-btn]");
            const $encMsg = $form.find("[data-enc-msg]");

            $encEmail.val(enc.email || "");
            $encId.val(enc.id || "");
            $encNome.val(enc.nome_completo || "");
            $encGrau.val(enc.parentesco || "");
            $encContacto.val(enc.contacto || "");
            $encCodigo.val(enc.codigo_convite || "");

            if (window.IICAEGMasks) {
                window.IICAEGMasks.refresh($form);
            }

            if (enc.id) {
                $encMsg.text("Encarregado já existente. Confirme os dados.");
                $encBtn.prop("disabled", true);
                $encNome.prop("disabled", false);
                $encGrau.prop("disabled", false);
                $encContacto.prop("disabled", false);
            } else {
                $encMsg.text("");
                $encBtn.prop("disabled", true);
                $encNome.prop("disabled", true);
                $encGrau.prop("disabled", true);
                $encContacto.prop("disabled", true);
            }

            abrirModal();
        });
    });

    $form.on("submit", function (e) {
        e.preventDefault();
        const url = $form.attr("action");

        $.post(url, $form.serialize(), function (res) {
            if (res.trim() === "sucesso") {
                showNotification("Formando actualizado com sucesso!", true);
                fecharModal();
                $("#pesquisa_formando").trigger("input");
                return;
            }
            if (res.trim() === "erro_data_nascimento") {
                showNotification("Informe a data de nascimento no formato dd.mm.aaaa.", false);
                return;
            }
            showNotification("Erro ao actualizar formando", false);
        });
    });
});
