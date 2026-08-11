$(function () {
    const $page = $(".horario-page");
    if (!$page.length) return;

    const urls = {
        turmas: $page.data("turmas-url"),
        get: $page.data("grade-get-url"),
        set: $page.data("grade-set-url"),
        remove: $page.data("grade-remove-url"),
    };

    const $turma = $("#horario_turma_id");
    const $semestre = $("#horario_semestre");
    const $bloco = $("#horario_bloco");

    const $gradeWrap = $("#horario_grade_wrap");
    const $gradeTable = $("#horario_grade_table");

    const $resumoWrap = $("#horario_resumo_wrap");
    const $resumoConteudo = $("#horario_resumo_conteudo");

    const $modal = $("#modal_horario_slot");
    const $modalTitle = $("#modal_horario_slot .modal-header h2");
    const $btnFecharModal = $("#btn_fechar_modal_slot");
    const $btnCancelarModal = $("#btn_cancelar_modal_slot");
    const $btnRemoverSlot = $("#btn_remover_slot");

    const $modulosGenericos = $("#slot_modulos_genericos");
    const $modulosVocacionais = $("#slot_modulos_vocacionais");
    const $modulosOutros = $("#slot_modulos_outros");

    const DAYS = [
        { key: "seg", label: "2ª Feira" },
        { key: "ter", label: "3ª Feira" },
        { key: "qua", label: "4ª Feira" },
        { key: "qui", label: "5ª Feira" },
        { key: "sex", label: "6ª Feira" },
    ];

    const SLOT_SETS = {
        diurno: [
            { code: "07:00-07:45", label: "07:00 - 07:45" },
            { code: "07:45-08:30", label: "07:45 - 08:30" },
            { code: "08:35-09:20", label: "08:35 - 09:20" },
            { code: "09:20-10:05", label: "09:20 - 10:05" },
            { code: "10:10-10:55", label: "10:10 - 10:55" },
            { code: "11:00-11:45", label: "11:00 - 11:45" },
            { breakRow: true, label: "Intervalo maior" },
            { code: "12:05-12:50", label: "12:05 - 12:50" },
            { code: "12:50-13:35", label: "12:50 - 13:35" },
            { code: "13:40-14:25", label: "13:40 - 14:25" },
            { code: "14:25-15:10", label: "14:25 - 15:10" },
            { code: "15:10-15:55", label: "15:10 - 15:55" },
            { code: "15:55-16:40", label: "15:55 - 16:40" },
        ],
        nocturno: [
            { code: "17:00-17:45", label: "17:00 - 17:45" },
            { code: "17:45-18:30", label: "17:45 - 18:30" },
            { code: "18:35-19:20", label: "18:35 - 19:20" },
            { code: "19:20-20:05", label: "19:20 - 20:05" },
            { code: "20:10-20:55", label: "20:10 - 20:55" },
            { code: "21:00-21:45", label: "21:00 - 21:45" },
        ],
    };

    const state = {
        exists: false,
        modules: [],
        modulesByFmId: {},
        cells: {},
        activeCell: null,
        turmasById: {},
        slotRows: SLOT_SETS.diurno,
        slotCodes: SLOT_SETS.diurno.filter((r) => !r.breakRow).map((r) => r.code),
    };

    function turnoSigla(nomeTurno) {
        if (!nomeTurno) return "";
        return nomeTurno.toLowerCase().includes("diurno") ? "CD" : "CN";
    }

    function escapeHtml(value) {
        return String(value == null ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function normalizeText(text) {
        let out = String(text || "").trim().toLowerCase();
        if (typeof out.normalize === "function") {
            out = out.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        }
        return out.replace(/\s+/g, " ");
    }

    function isNocturnoTurno(nome) {
        return normalizeText(nome).includes("nocturno");
    }

    function formatDate(iso) {
        if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return "-";
        const parts = iso.split("-");
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    function slotKey(day, slot) {
        return `${day}__${slot}`;
    }

    function getCriteria() {
        return {
            turma_id: parseInt($turma.val(), 10) || 0,
            semestre: parseInt($semestre.val(), 10) || 0,
            bloco: parseInt($bloco.val(), 10) || 0,
        };
    }

    function hasCriteria(criteria) {
        const c = criteria || getCriteria();
        return c.turma_id > 0 && (c.semestre === 1 || c.semestre === 2) && (c.bloco === 1 || c.bloco === 2);
    }

    function hideBoard() {
        $gradeWrap.hide();
        $resumoWrap.hide();
        $gradeTable.empty();
        $resumoConteudo.empty();
        closeModal();
    }

    function indexModules(rows) {
        state.modules = Array.isArray(rows) ? rows : [];
        state.modulesByFmId = {};
        state.modules.forEach((m) => {
            state.modulesByFmId[String(m.formador_modulo_id)] = m;
        });
    }

    function mapCells(rows) {
        const map = {};
        (rows || []).forEach((row) => {
            if (!row || !row.dia_semana || !row.slot_codigo) return;
            map[slotKey(row.dia_semana, row.slot_codigo)] = String(row.formador_modulo_id || "");
        });
        return map;
    }

    function renderGrade() {
        const slotRows = state.slotRows || SLOT_SETS.diurno;
        let html = '<thead><tr><th class="horario-grade-hour">Horas</th>';
        DAYS.forEach((d) => {
            html += `<th>${escapeHtml(d.label)}</th>`;
        });
        html += "</tr></thead><tbody>";

        slotRows.forEach((slot) => {
            if (slot.breakRow) {
                html += `<tr class="horario-grade-break"><td colspan="${DAYS.length + 1}">${escapeHtml(slot.label)}</td></tr>`;
                return;
            }

            html += `<tr><td class="horario-grade-hour">${escapeHtml(slot.label)}</td>`;

            DAYS.forEach((day) => {
                const key = slotKey(day.key, slot.code);
                const fmId = state.cells[key] || "";
                const modulo = fmId ? state.modulesByFmId[String(fmId)] : null;

                if (modulo) {
                    const rawTipo = normalizeText(modulo.tipo_modulo);
                    const tipo = rawTipo === "vocacional" ? "vocacional" : rawTipo === "outro" ? "outro" : "generico";
                    html += `
                        <td class="slot-cell">
                            <button
                                type="button"
                                class="slot-chip slot-open"
                                data-dia="${day.key}"
                                data-slot="${slot.code}"
                                data-tipo="${tipo}"
                                title="Clique para alterar/remover"
                            >
                                ${escapeHtml(modulo.sigla_modulo || "MOD")}
                            </button>
                        </td>
                    `;
                } else {
                    html += `
                        <td class="slot-cell">
                            <button
                                type="button"
                                class="slot-btn slot-open"
                                data-dia="${day.key}"
                                data-slot="${slot.code}"
                                title="Adicionar modulo"
                            >
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    `;
                }
            });

            html += "</tr>";
        });

        html += "</tbody>";
        $gradeTable.html(html);
    }

    function renderResumo() {
        const usedIds = Object.values(state.cells).filter(Boolean);
        const uniqueIds = [];
        const seen = {};

        usedIds.forEach((id) => {
            const key = String(id);
            if (!seen[key]) {
                seen[key] = true;
                uniqueIds.push(key);
            }
        });

        if (!uniqueIds.length) {
            $resumoConteudo.html('<div class="horario-resumo-grupo"><p class="horario-resumo-item">Sem módulos no horário.</p></div>');
            return;
        }

        const groups = { generico: [], vocacional: [], outro: [] };

        uniqueIds.forEach((id) => {
            const mod = state.modulesByFmId[id];
            if (!mod) return;
            const tipo = normalizeText(mod.tipo_modulo);
            if (tipo === "generico") groups.generico.push(mod);
            else if (tipo === "vocacional") groups.vocacional.push(mod);
            else groups.outro.push(mod);
        });

        ["generico", "vocacional", "outro"].forEach((k) => {
            groups[k].sort((a, b) => String(a.sigla_modulo || "").localeCompare(String(b.sigla_modulo || "")));
        });

        function makeGroup(title, items) {
            if (!items.length) return "";
            let out = `<div class="horario-resumo-grupo"><h4>${escapeHtml(title)}</h4>`;
            items.forEach((m) => {
                const sigla = m.sigla_modulo || "-";
                const nome = m.nome_modulo || "-";
                const formador = `${m.formador_titulo || ""} ${m.formador_nome || ""}`.trim() || "-";
                const periodo =
                    m.data_inicio && m.data_fim
                        ? `${formatDate(m.data_inicio)} a ${formatDate(m.data_fim)}`
                        : "Sem periodo";
                out += `<p class="horario-resumo-item"><strong>${escapeHtml(sigla)}</strong> - ${escapeHtml(nome)} | ${escapeHtml(formador)} | ${escapeHtml(periodo)}</p>`;
            });
            out += "</div>";
            return out;
        }

        const html =
            makeGroup("Módulos Genéricos", groups.generico) +
            makeGroup("Módulos Vocacionais", groups.vocacional) +
            makeGroup("Outros Módulos", groups.outro);

        $resumoConteudo.html(html);
    }

    function closeModal() {
        $modal.removeClass("open");
        state.activeCell = null;
        $btnRemoverSlot.hide().prop("disabled", true);
    }

    function openModal(day, slot) {
        if (!state.slotCodes.includes(slot)) return;

        state.activeCell = { day, slot };

        const dayObj = DAYS.find((d) => d.key === day);
        const dayLabel = dayObj ? dayObj.label : day;
        $modalTitle.text(`Selecionar módulo - ${dayLabel} (${slot})`);

        renderModalModules();
        $modal.addClass("open");
    }

    function isDisabledModulo(mod, currentFmId) {
        const tipo = normalizeText(mod.tipo_modulo);
        const sigla = normalizeText(mod.sigla_modulo);
        if (tipo === "outro" || sigla === "rt") return false;
        if (Number(mod.disabled) === 1 && String(mod.formador_modulo_id) !== String(currentFmId || "")) return true;
        const st = normalizeText(mod.estado);
        return (st === "concluido" || st === "em vigencia") && String(mod.formador_modulo_id) !== String(currentFmId || "");
    }

    function renderModalModules() {
        $modulosGenericos.empty();
        $modulosVocacionais.empty();
        $modulosOutros.empty();

        if (!state.activeCell) return;

        const currentFmId = state.cells[slotKey(state.activeCell.day, state.activeCell.slot)] || "";
        const hasCurrent = !!currentFmId;
        $btnRemoverSlot.toggle(hasCurrent).prop("disabled", !hasCurrent);

        if (!state.modules.length) {
            const empty = '<span class="badge badge-empty">Nenhum módulo iniciado nesta turma</span>';
            $modulosGenericos.html(empty);
            $modulosVocacionais.html(empty);
            $modulosOutros.html(empty);
            return;
        }

        let countGen = 0;
        let countVoc = 0;
        let countOut = 0;

        state.modules.forEach((mod) => {
            const rawTipo = normalizeText(mod.tipo_modulo);
            const tipo = rawTipo === "vocacional" ? "vocacional" : rawTipo === "outro" ? "outro" : "generico";
            const isCurrent = String(currentFmId) === String(mod.formador_modulo_id);
            const disabled = isDisabledModulo(mod, currentFmId);

            const $btn = $(`
                <button type="button" class="slot-module-btn" data-id="${mod.formador_modulo_id}" data-tipo="${tipo}">
                    ${escapeHtml(mod.sigla_modulo || "SEM SIGLA")}
                </button>
            `);

            if (disabled) $btn.prop("disabled", true);
            if (isCurrent) $btn.addClass("is-current");

            if (tipo === "vocacional") {
                $modulosVocacionais.append($btn);
                countVoc += 1;
            } else if (tipo === "outro") {
                $modulosOutros.append($btn);
                countOut += 1;
            } else {
                $modulosGenericos.append($btn);
                countGen += 1;
            }
        });

        if (!countGen) $modulosGenericos.html('<span class="badge badge-empty">Sem módulos genéricos</span>');
        if (!countVoc) $modulosVocacionais.html('<span class="badge badge-empty">Sem módulos vocacionais</span>');
        if (!countOut) $modulosOutros.html('<span class="badge badge-empty">Sem outros módulos</span>');
    }

    function refreshAfterChange() {
        renderGrade();
        renderResumo();
    }

    function saveInCell(formadorModuloId) {
        if (!state.activeCell) return;

        const criteria = getCriteria();
        if (!hasCriteria(criteria)) return;

        $.ajax({
            url: urls.set,
            method: "POST",
            dataType: "json",
            data: {
                turma_id: criteria.turma_id,
                semestre: criteria.semestre,
                bloco: criteria.bloco,
                dia_semana: state.activeCell.day,
                slot_codigo: state.activeCell.slot,
                formador_modulo_id: formadorModuloId,
            },
        })
            .done(function (res) {
                if (!res || !res.ok) return;

                state.cells[slotKey(state.activeCell.day, state.activeCell.slot)] = String(formadorModuloId);
                state.exists = true;

                closeModal();
                refreshAfterChange();
            })
            .fail(function () {});
    }

    function removeFromCell() {
        if (!state.activeCell) return;

        const criteria = getCriteria();
        if (!hasCriteria(criteria)) return;

        $.ajax({
            url: urls.remove,
            method: "POST",
            dataType: "json",
            data: {
                turma_id: criteria.turma_id,
                semestre: criteria.semestre,
                bloco: criteria.bloco,
                dia_semana: state.activeCell.day,
                slot_codigo: state.activeCell.slot,
            },
        })
            .done(function (res) {
                if (!res || !res.ok) return;

                delete state.cells[slotKey(state.activeCell.day, state.activeCell.slot)];
                state.exists = Object.keys(state.cells).length > 0;

                closeModal();
                refreshAfterChange();
            })
            .fail(function () {});
    }

    function loadGrade() {
        const criteria = getCriteria();
        if (!hasCriteria(criteria)) {
            hideBoard();
            return;
        }

        const turmaInfo = state.turmasById[String(criteria.turma_id)] || {};
        const turnoNome = String(turmaInfo.nome_turno || "");
        if (isNocturnoTurno(turnoNome)) {
            state.slotRows = SLOT_SETS.nocturno;
        } else {
            state.slotRows = SLOT_SETS.diurno;
        }
        state.slotCodes = state.slotRows.filter((r) => !r.breakRow).map((r) => r.code);

        $.getJSON(urls.get, criteria)
            .done(function (res) {
                if (!res || !res.ok) {
                    hideBoard();
                    return;
                }

                state.exists = !!res.exists;
                indexModules(res.modules || []);
                state.cells = mapCells(res.cells || []);

                $gradeWrap.show();
                $resumoWrap.show();

                renderGrade();
                renderResumo();
            })
            .fail(function () {
                hideBoard();
            });
    }

    function renderTurmas(rows) {
        let options = '<option value="">Selecione a turma</option>';
        state.turmasById = {};
        (rows || []).forEach((t) => {
            state.turmasById[String(t.id)] = t;
            const sigla = turnoSigla(t.nome_turno);
            const label = `${t.nome_turma}${sigla ? ` - ${sigla}` : ""}`;
            options += `<option value="${t.id}">${escapeHtml(label)}</option>`;
        });
        $turma.html(options);
    }

    function loadTurmas() {
        $.getJSON(urls.turmas)
            .done(function (rows) {
                renderTurmas(rows);

                const qs = new URLSearchParams(window.location.search);
                const turma = qs.get("turma_id");
                const semestre = qs.get("semestre");
                const bloco = qs.get("bloco");

                if (turma) $turma.val(turma);
                if (semestre === "1" || semestre === "2") $semestre.val(semestre);
                if (bloco === "1" || bloco === "2") $bloco.val(bloco);

                if (hasCriteria()) loadGrade();
            })
            .fail(function () {});
    }

    $turma.add($semestre).add($bloco).on("change", loadGrade);

    $gradeTable.on("click", ".slot-open", function () {
        const day = String($(this).data("dia") || "");
        const slot = String($(this).data("slot") || "");
        if (!day || !slot) return;
        openModal(day, slot);
    });

    $modal.on("click", ".slot-module-btn", function () {
        if ($(this).is(":disabled")) return;
        const fmId = parseInt($(this).data("id"), 10) || 0;
        if (!fmId) return;
        saveInCell(fmId);
    });

    $btnRemoverSlot.on("click", removeFromCell);
    $btnFecharModal.add($btnCancelarModal).on("click", closeModal);

    $modal.on("click", function (e) {
        if ($(e.target).is("#modal_horario_slot")) {
            closeModal();
        }
    });

    $(document).on("keydown", function (e) {
        if (e.key === "Escape" && $modal.hasClass("open")) {
            closeModal();
        }
    });

    hideBoard();
    loadTurmas();
});
