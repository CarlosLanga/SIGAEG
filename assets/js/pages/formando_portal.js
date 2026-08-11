$(function () {
    const $page = $(".formando-portal");
    if (!$page.length) return;

    const mode = String($page.data("mode") || "");
    const role = String($page.data("role") || "formando");
    const apiUrl = $page.data("api-url");
    const gradeUrl = $page.data("grade-url");
    const downloadUrl = $page.data("download-url");
    const $toolbar = $("#portal_toolbar");
    const $content = $("#portal_content");
    const $modal = $("#portal_modal");
    const $modalTitle = $("#portal_modal_title");
    const $modalBody = $("#portal_modal_body");
    const $educando = $("#portal_educando");

    let context = { turmas: [], educandos: [] };

    function esc(value) {
        return String(value == null ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function params(extra = {}) {
        const out = { ...extra };
        if ($educando.length && $educando.val()) out.educando_id = $educando.val();
        return out;
    }

    function turmaOptions(selected) {
        return (context.turmas || []).map((t) => {
            const actual = Number(t.actual) === 1 ? " (actual)" : "";
            return `<option value="${t.id}" ${String(t.id) === String(selected) ? "selected" : ""}>${esc(t.nome_turma || "-")}${actual}</option>`;
        }).join("");
    }

    function statusBadge(value) {
        const text = value || "-";
        const lower = String(text).toLowerCase();
        const cls = lower.includes("presente") || text === "A" || lower.includes("submetido") ? "status-active"
            : lower.includes("ausente") || text === "NA" ? "status-started"
                : "status-done";
        return `<span class="status ${cls}">${esc(text)}</span>`;
    }

    function progressBar(percent) {
        const p = Math.max(0, Math.min(100, Number(percent || 0)));
        return `<div class="portal-progress"><div class="portal-progress-track"><div class="portal-progress-fill" style="width:${p}%"></div></div><div class="portal-progress-text">${p}%</div></div>`;
    }

    function moduloEstadoLabel(value) {
        if (value === "por_iniciar") return "Por iniciar";
        if (value === "concluido") return "Concluí­do";
        return "Em curso";
    }

    function moduloResultadoBadge(value) {
        const result = value && value !== "-" ? String(value) : "-";
        const cls = result === "A" ? "is-a" : (["NA", "D", "WD"].includes(result) ? "is-na" : "");
        return `<span class="module-result ${cls}">${esc(result)}</span>`;
    }

    function moduloProgress(percent, result) {
        const p = Math.max(0, Math.min(100, Number(percent || 0)));
        const cls = result === "A" ? "is-a" : (["NA", "D", "WD"].includes(result) ? "is-na" : "is-pending");
        const width = ["NA", "D", "WD"].includes(result) ? 10 : p;
        return `<div class="module-progress ${cls}"><div class="module-progress-track"><div class="module-progress-fill" style="width:${width}%"></div></div><span>${p}%</span></div>`;
    }

    function table(headers, rows, empty = "Sem dados disponí­veis.") {
        if (!rows.length) return `<div class="portal-empty">${esc(empty)}</div>`;
        return `<div class="table-wrap"><table class="data-table"><thead><tr>${headers.map(h => `<th>${esc(h)}</th>`).join("")}</tr></thead><tbody>${rows.join("")}</tbody></table></div>`;
    }

    function openModal(title, body) {
        $modalTitle.text(title);
        $modalBody.html(body);
        $modal.addClass("open");
    }

    function closeModal() {
        $modal.removeClass("open");
        $modalBody.empty();
    }

    function renderGrade(data, target) {
        const days = data.days || [];
        const slots = data.slots || [];
        const cells = data.cells || {};
        if (!days.length || !slots.length) {
            target.html('<div class="portal-empty">Sem grelha disponí­vel.</div>');
            return;
        }
        let html = '<div class="horario-grade-wrap"><div class="horario-grade-scroll"><table class="horario-grade-table"><thead><tr><th class="horario-grade-hour">Horas</th>';
        days.forEach(day => html += `<th>${esc(day.label)}</th>`);
        html += '</tr></thead><tbody>';
        slots.forEach((slot, index) => {
            html += `<tr><td class="horario-grade-hour">${esc(slot.label)}</td>`;
            days.forEach(day => {
                const sigla = cells[`${day.key}__${slot.code}`] || "";
                html += `<td>${sigla ? `<div class="horario-preview-slot is-filled"><span class="horario-preview-text">${esc(sigla)}</span></div>` : '<div class="horario-preview-slot"></div>'}</td>`;
            });
            html += '</tr>';
            if (index === 5 && slots.length > 6) html += `<tr class="horario-grade-break"><td colspan="${days.length + 1}">Intervalo maior</td></tr>`;
        });
        html += '</tbody></table></div></div>';

        const details = data.cell_details || {};
        const seen = {};
        Object.keys(details).forEach((key) => {
            const item = details[key] || {};
            const sigla = item.sigla_modulo || "";
            if (sigla && !seen[sigla]) seen[sigla] = item;
        });
        const resumo = Object.keys(seen).map((sigla) => {
            const item = seen[sigla];
            return `<p class="horario-resumo-item"><strong>${esc(sigla)}</strong> - ${esc(item.nome_modulo || "Sem nome")} | ${esc(item.formador_nome || "Sem formador")}</p>`;
        }).join("");
        if (resumo) {
            html += `<div class="horario-resumo-wrap"><h3 class="section-title">Descrições</h3><div class="horario-resumo-conteudo"><div class="horario-resumo-grupo">${resumo}</div></div></div>`;
        }
        target.html(html);
    }

    function loadGrade(turmaId, semestre, bloco, target) {
        target.html('<div class="portal-empty"><i class="fa-solid fa-spinner fa-spin"></i> A processar grelha...</div>');
        $.getJSON(gradeUrl, params({ turma_id: turmaId, semestre, bloco })).done((res) => {
            if (!res || !res.ok) {
                target.html('<div class="portal-empty">Não foi possível processar a grelha.</div>');
                return;
            }
            renderGrade(res, target);
        });
    }

    function setupTurmaToolbar(onChange) {
        const selected = context.turmas[0] ? context.turmas[0].id : "";
        $toolbar.html(`<div class="portal-filter-grid"><label class="form-field"><span>Turma</span><select id="portal_turma">${turmaOptions(selected)}</select></label></div>`);
        $("#portal_turma").on("change", onChange);
        return selected;
    }

    function renderMeuHorario() {
        const turma = setupTurmaToolbar(() => renderMeuHorarioGrid($("#portal_turma").val()));
        renderMeuHorarioGrid(turma);
    }

    function renderMeuHorarioGrid(turmaId) {
        $content.html('<div class="portal-empty">A processar o horário actual...</div>');
        $.getJSON(apiUrl, params({ action: "horario_current", turma_id: turmaId })).done((res) => {
            if (!res || !res.ok || !res.plano) {
                $content.html('<div class="portal-empty">Sem horário corrente para esta turma.</div>');
                return;
            }
            const meta = `<div class="horario-meta"><div><strong>Semestre:</strong> ${Number(res.plano.semestre) === 1 ? "I" : "II"}</div><div><strong>Bloco:</strong> ${esc(res.plano.bloco)}</div></div><div id="portal_grade_current"></div>`;
            $content.html(meta);
            loadGrade(res.plano.turma_id, res.plano.semestre, res.plano.bloco, $("#portal_grade_current"));
        });
    }

    function renderHorariosLista() {
        const turma = setupTurmaToolbar(() => loadHorarios($("#portal_turma").val()));
        loadHorarios(turma);
    }

    function loadHorarios(turmaId) {
        $content.html('<div class="portal-empty">A processar horários ...</div>');
        $.getJSON(apiUrl, params({ action: "horarios", turma_id: turmaId })).done((res) => {
            const rows = (res.rows || []).map((r, i) => `
                <tr>
                    <td>${i + 1}</td><td>${esc(r.nome_turma)}</td><td>${esc(r.bloco)}</td><td>${Number(r.semestre) === 1 ? "I" : "II"}</td>
                    <td>${esc(r.actualizado_em || r.publicado_em || "-")}</td>
                    <td class="portal-actions"><button class="btn btn-outline btn-table btn-grade" data-turma="${r.turma_id}" data-semestre="${r.semestre}" data-bloco="${r.bloco}"><i class="fa-solid fa-eye"></i></button></td>
                </tr>`).join("");
            $content.html(table(["#", "Turma", "Bloco", "Semestre", "Última actualização", "Acção"], rows));
        });
    }

    function renderTurmas() {
        $toolbar.empty();
        $.getJSON(apiUrl, params({ action: "turmas" })).done((res) => {
            const rows = (res.rows || []).map((r, i) => `
                <tr>
                    <td>${i + 1}</td><td>${esc(r.nome_turma)}</td><td>${esc(r.nome_turno || "-")}</td><td>${esc(r.certificado_vocacional || "-")}</td><td>${esc(r.ano_lectivo || "-")}</td>
                    <td>${Number(r.actual) === 1 ? statusBadge("Actual") : statusBadge("Histórico")}</td>
                    <td class="portal-actions"><button class="btn btn-outline btn-table btn-turma" data-id="${r.id}"><i class="fa-solid fa-eye"></i></button></td>
                </tr>`).join("");
            $content.html(table(["#", "Turma", "Turno", "CV", "Ano lectivo", "Estado", "Acção"], rows));
        });
    }

    function renderModulos() {
        const turma = setupTurmaToolbar(() => loadModulos($("#portal_turma").val()));
        loadModulos(turma);
    }

    function loadModulos(turmaId) {
        $content.html('<div class="portal-empty">A processar módulos...</div>');
        $.getJSON(apiUrl, params({ action: "modulos", turma_id: turmaId })).done((res) => {
            const allRows = (res.rows || []);

            let page = 1;
            const pageSizeOptions = [10, 20, 50];
            let pageSize = 20;

            function renderPage() {
                if (!allRows.length) {
                    $content.html('<div class="portal-empty">Nenhum módulo encontrado.</div>');
                    return;
                }

                const total = allRows.length;
                const totalPages = Math.max(1, Math.ceil(total / pageSize));
                page = Math.max(1, Math.min(page, totalPages));
                const start = (page - 1) * pageSize;
                const pageRows = allRows.slice(start, start + pageSize);

                const rowsHtml = pageRows.map((r, i) => `
                    <tr>
                        <td>${start + i + 1}</td>
                        <td><strong>${esc(r.sigla_modulo || "-")}</strong></td>
                        <td>${esc(r.formador_nome || "-")}</td>
                        <td>${esc(moduloEstadoLabel(r.estado_modulo || r.estado))}</td>
                        <td>${moduloResultadoBadge(r.resultado)}</td>
                        <td class="portal-actions"><button class="btn btn-outline btn-table btn-modulo" data-turma="${r.turma_id}" data-fm="${r.formador_modulo_id}"><i class="fa-solid fa-eye"></i></button></td>
                    </tr>`).join("");

                const tableHtml = `<div class="table-wrap"><table class="data-table"><thead><tr>${["#", "Módulo", "Formador", "Estado", "Resultado", "Acção"].map(h => `<th>${esc(h)}</th>`).join("")}</tr></thead><tbody>${rowsHtml}</tbody></table></div>`;

                const footerHtml = `
                    <div class="table-footer table-footer-modern">
                        <div class="table-footer-meta">
                            <label class="table-page-size">
                                <span>Linhas por página</span>
                                <div class="select-wrap">
                                    <select id="portal_page_size">${pageSizeOptions.map(s => `<option value="${s}" ${s === pageSize ? 'selected' : ''}>${s}</option>`).join('')}</select>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </label>
                            <div class="table-info" id="portal_table_info">${start + 1}-${Math.min(start + pageSize, total)} de ${total}</div>
                        </div>
                        <div class="table-pagination table-pagination-modern" id="portal_table_pagination">
                            <button class="btn btn-outline btn-table table-nav-btn" id="portal_btn_prev" type="button" aria-label="Página anterior">
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            <div class="table-page-numbers" id="portal_table_page_numbers"></div>
                            <button class="btn btn-outline btn-table table-nav-btn" id="portal_btn_next" type="button" aria-label="Próxima página">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>`;

                $content.html(tableHtml + footerHtml);

                const $pageNumbers = $("#portal_table_page_numbers");
                $pageNumbers.empty();
                for (let p = 1; p <= totalPages; p++) {
                    const cls = p === page ? 'btn-active' : '';
                    $pageNumbers.append(`<button type="button" class="btn btn-outline btn-table table-page-link ${cls}" data-page="${p}">${p}</button>`);
                }

                $("#portal_btn_prev").prop('disabled', page <= 1).off('click').on('click', () => { page = Math.max(1, page - 1); renderPage(); });
                $("#portal_btn_next").prop('disabled', page >= totalPages).off('click').on('click', () => { page = Math.min(totalPages, page + 1); renderPage(); });
                $("#portal_table_page_numbers .table-page-link").off('click').on('click', function () { page = Number($(this).data('page') || 1); renderPage(); });
                $("#portal_page_size").off('change').on('change', function () { pageSize = Number($(this).val() || 20); page = 1; renderPage(); });
            }

            renderPage();
        });
    }

    function renderFrequencias() {
        const turma = setupTurmaToolbar(() => loadFrequencias($("#portal_turma").val()));
        loadFrequencias(turma);
    }

    function loadFrequencias(turmaId) {
        $content.html('<div class="portal-empty">A processar frequfªncias...</div>');
        $.getJSON(apiUrl, params({ action: "frequencias", turma_id: turmaId })).done((res) => {
            const rows = (res.rows || []).map((r) => `<tr><td>${esc(r.data_aula || "-")}</td><td>${esc(r.sigla_modulo || "-")}</td><td>${esc(r.aulas || "-")}</td><td>${statusBadge(r.situacao)}</td><td>${esc(r.observacao || "-")}</td></tr>`).join("");
            $content.html(table(["Data", "Módulo", "Aulas", "Situação", "Observação"], rows));
        });
    }

    function renderAvaliacoes() {
        const turma = setupTurmaToolbar(() => loadAvalOptions($("#portal_turma").val()));
        loadAvalOptions(turma);
    }

    function loadAvalOptions(turmaId, moduloId) {
        $.getJSON(apiUrl, params({ action: "avaliacoes_options", turma_id: turmaId, modulo_id: moduloId || "" })).done((res) => {
            const modulos = res.modulos || [];
            const selectedModulo = moduloId || (modulos[0] ? modulos[0].modulo_id : "");
            const avals = res.avaliacoes || [];
            const selectedAval = avals[0] ? avals[0].id : "";
            $toolbar.find(".avaliacao-extra").remove();
            $toolbar.find(".portal-filter-grid").append(`
                <label class="form-field avaliacao-extra"><span>Módulo</span><select id="portal_modulo">${modulos.map(m => `<option value="${m.modulo_id}" ${String(m.modulo_id) === String(selectedModulo) ? "selected" : ""}>${esc(m.sigla_modulo || m.nome_modulo)}</option>`).join("")}</select></label>
                <label class="form-field avaliacao-extra"><span>Avaliação</span><select id="portal_avaliacao">${avals.map(a => `<option value="${a.id}">${esc(a.titulo)} - ${esc(a.data_avaliacao || "")}</option>`).join("")}</select></label>
            `);
            $("#portal_modulo").on("change", function () { loadAvalOptions(turmaId, $(this).val()); });
            $("#portal_avaliacao").on("change", loadResultado);
            if (selectedAval) loadResultado();
            else $content.html('<div class="portal-empty">Sem avaliações para o módulo seleccionado.</div>');
        });
    }

    function loadResultado() {
        $.getJSON(apiUrl, params({ action: "avaliacao_resultado", turma_id: $("#portal_turma").val(), avaliacao_id: $("#portal_avaliacao").val() })).done((res) => {
            const d = res.data;
            if (!d) { $content.html('<div class="portal-empty">Sem resultado registado.</div>'); return; }
            $content.html(`<div class="portal-info-grid">
                <div class="portal-info-item"><span>Avaliação</span><strong>${esc(d.titulo)}</strong></div>
                <div class="portal-info-item"><span>Módulo</span><strong>${esc(d.sigla_modulo)}</strong></div>
                <div class="portal-info-item"><span>Nota</span><strong>${esc(d.nota_obtida ?? "-")}</strong></div>
                <div class="portal-info-item"><span>Resultado</span><strong>${esc(d.resultado || "-")}</strong></div>
                <div class="portal-info-item"><span>Observação</span><strong>${esc(d.observacao || "-")}</strong></div>
            </div>`);
        });
    }

    function renderTrabalhos() {
        const turma = setupTurmaToolbar(() => loadTrabalhos($("#portal_turma").val()));
        loadTrabalhos(turma);
    }

    function loadTrabalhos(turmaId, moduloId) {
        $.getJSON(apiUrl, params({ action: "trabalhos", turma_id: turmaId, modulo_id: moduloId || "" })).done((res) => {
            const modulos = res.modulos || [];
            $toolbar.find(".trabalho-extra").remove();
            $toolbar.find(".portal-filter-grid").append(`<label class="form-field trabalho-extra"><span>Módulo</span><select id="portal_modulo"><option value="">Todos</option>${modulos.map(m => `<option value="${m.modulo_id}" ${String(m.modulo_id) === String(moduloId) ? "selected" : ""}>${esc(m.sigla_modulo || m.nome_modulo)}</option>`).join("")}</select></label>`);
            $("#portal_modulo").on("change", function () { loadTrabalhos(turmaId, $(this).val()); });
            const rows = (res.rows || []).map((r, i) => `<tr><td>${i + 1}</td><td>${esc(r.titulo)}</td><td>${esc(r.sigla_modulo)}</td><td>${esc(r.data_entrega || "-")}</td><td>${statusBadge(r.submissao_id ? "Submetido" : "Pendente")}</td><td class="portal-actions"><button class="btn btn-outline btn-table btn-trabalho" data-id="${r.id}"><i class="fa-solid fa-eye"></i></button></td></tr>`).join("");
            $content.html(table(["#", "Título", "Módulo", "Prazo", "Estado", "Acção"], rows));
        });
    }

    function renderFicheiros() {
        $toolbar.html('<div class="ficheiros-search"><i class="fa-solid fa-search"></i><input type="text" id="portal_file_search" placeholder="Pesquisar ficheiros..."></div>');
        $content.html('<div class="ficheiros-categorias" id="portal_ficheiros_categorias">\n                <div class="ficheiros-loading">\n                    <i class="fa-solid fa-spinner fa-spin"></i>\n                    <span>A processar ficheiros ...</span>\n                </div>\n            </div>');
        loadFicheiros();
    }

    function loadFicheiros(term = "") {
        $.getJSON(apiUrl, params({ action: "ficheiros" })).done((res) => {
            let rows = res.rows || [];
            if (term) rows = rows.filter(r => `${r.titulo} ${r.descricao} ${r.nome_original}`.toLowerCase().includes(term));

            const byCat = { geral: [], turma: [], meus: [] };
            rows.forEach(r => {
                const c = String(r.categoria || 'geral');
                if (!byCat[c]) byCat[c] = [];
                byCat[c].push(r);
            });

            const categories = [
                { key: 'geral', label: 'Geral', rows: byCat.geral || [] },
                { key: 'turma', label: 'Ficheiros de Turma', rows: byCat.turma || [] },
                { key: 'meus', label: 'Meus Ficheiros', rows: byCat.meus || [] }
            ];

            function fileTypeClass(name) {
                const n = (name || '').toLowerCase();
                if (/\.pdf$/i.test(n)) return 'pdf';
                if (/\.(doc|docx)$/i.test(n)) return 'word';
                if (/\.(xls|xlsx)$/i.test(n)) return 'excel';
                if (/\.(ppt|pptx)$/i.test(n)) return 'powerpoint';
                if (/\.(zip|rar)$/i.test(n)) return 'zip';
                return 'text';
            }

            function getIconHtml(name) {
                const n = (name || '').toLowerCase();
                if (/\.pdf$/i.test(n)) return '<i class="fa-solid fa-file-pdf"></i>';
                if (/\.(doc|docx)$/i.test(n)) return '<i class="fa-solid fa-file-word"></i>';
                if (/\.(xls|xlsx)$/i.test(n)) return '<i class="fa-solid fa-file-excel"></i>';
                if (/\.(ppt|pptx)$/i.test(n)) return '<i class="fa-solid fa-file-powerpoint"></i>';
                if (/\.(zip|rar)$/i.test(n)) return '<i class="fa-solid fa-file-zipper"></i>';
                return '<i class="fa-solid fa-file-lines"></i>';
            }

            const html = categories.map((cat, index) => {
                const rowsCat = cat.rows || [];
                const body = rowsCat.length ? rowsCat.map(r => {
                    const type = fileTypeClass(r.nome_original || '');
                    return `
                    <article class="ficheiro-item">
                        <div class="ficheiro-preview ${type}">
                            ${getIconHtml(r.nome_original || '')}
                        </div>
                        <div class="ficheiro-info">
                            <h3>${esc(r.titulo)}</h3>
                            <p>${esc(r.descricao || 'Sem descrição')} ${r.nome_turma ? ' | ' + esc(r.nome_turma) : ''}</p>
                            <div class="ficheiro-downloads"><i class="fa-solid fa-download"></i> ${Number(r.downloads || 0)}</div>
                        </div>
                        <div class="ficheiro-actions">
                            <a class="ficheiro-btn ficheiro-btn-primary" href="${downloadUrl}?id=${encodeURIComponent(r.id)}"><i class="fa-solid fa-download"></i><span>Baixar</span></a>
                        </div>
                    </article>
                `;
                }).join('') : '<div class="ficheiros-empty">Nenhum ficheiro disponível.</div>';

                const collapsed = index > 0 ? ' is-collapsed' : '';
                return `
                    <section class="ficheiro-categoria${collapsed}" data-category="${esc(cat.key)}">
                        <button type="button" class="ficheiro-categoria-header">
                            <span class="ficheiro-categoria-title"><i class="fa-solid fa-folder"></i> ${esc(cat.label)}</span>
                            <span class="ficheiro-categoria-meta"><strong>${rowsCat.length}</strong><span>itens</span><i class="fa-solid fa-chevron-down"></i></span>
                        </button>
                        <div class="ficheiro-categoria-body">${body}</div>
                    </section>
                `;
            }).join('');

            $content.html(`<div class="ficheiros-categorias">${html}</div>`);
        });
    }

    $toolbar.on("input", "#portal_file_search", function () { loadFicheiros($(this).val().toLowerCase().trim()); });

    $content.on("click", ".btn-grade", function () {
        openModal("Horário", '<div id="portal_modal_grade"></div>');
        loadGrade($(this).data("turma"), $(this).data("semestre"), $(this).data("bloco"), $("#portal_modal_grade"));
    });

    $content.on("click", ".btn-turma", function () {
        $.getJSON(apiUrl, params({ action: "turma_detail", turma_id: $(this).data("id") })).done((res) => {
            const t = res.turma || {};
            const info = `<div class="portal-info-grid"><div class="portal-info-item"><span>Turma</span><strong>${esc(t.nome_turma)}</strong></div><div class="portal-info-item"><span>Turno</span><strong>${esc(t.nome_turno || "-")}</strong></div><div class="portal-info-item"><span>Director</span><strong>${esc(t.director_turma || "-")}</strong></div><div class="portal-info-item"><span>CV</span><strong>${esc(t.certificado_vocacional || "-")}</strong></div><div class="portal-info-item"><span>Ano</span><strong>${esc(t.ano_lectivo || "-")}</strong></div></div>`;
            const rows = (res.formandos || []).map((f, i) => `<tr><td>${i + 1}</td><td>${esc(f.nome_completo)}</td><td>${esc(f.codigo_formando || "-")}</td><td>${esc(f.sexo || "-")}</td></tr>`).join("");
            openModal("Detalhes da Turma", info + "<h3 class='section-title' style='margin-top:18px;'>Formandos</h3>" + table(["#", "Nome", "Código", "Sexo"], rows));
        });
    });

    $content.on("click", ".btn-modulo", function () {
        $.getJSON(apiUrl, params({ action: "modulo_detail", turma_id: $(this).data("turma"), formador_modulo_id: $(this).data("fm") })).done((res) => {
            const m = res.modulo || {};
            const f = res.formando || {};
            const resultado = f.resultado || m.resultado || "-";
            const info = `<div class="portal-info-grid"><div class="portal-info-item"><span>Módulo</span><strong>${esc(m.sigla_modulo || "-")}</strong></div><div class="portal-info-item"><span>Nome do módulo</span><strong>${esc(m.nome_modulo || "-")}</strong></div><div class="portal-info-item"><span>Formador</span><strong>${esc(m.formador_nome || "-")}</strong></div><div class="portal-info-item"><span>Estado</span><strong>${esc(moduloEstadoLabel(m.estado_modulo || m.estado))}</strong></div><div class="portal-info-item"><span>Início</span><strong>${esc(m.data_inicio || "-")}</strong></div><div class="portal-info-item"><span>Conclusão</span><strong>${esc(m.data_fim || "-")}</strong></div></div>`;
            const rows = `<tr><td>1</td><td>${esc(f.nome_completo || "-")}</td><td>${esc(f.codigo_formando || "-")}</td><td>${moduloProgress(f.progresso ?? m.progresso, resultado)}</td><td>${moduloResultadoBadge(resultado)}</td></tr>`;
            openModal("Detalhes do Módulo", info + "<h3 class='section-title' style='margin-top:18px;'>Progresso do Formando</h3>" + table(["#", "Nome", "Código", "Progresso", "Resultado"], [rows]));
        });
    });

    $content.on("click", ".btn-trabalho", function () {
        $.getJSON(apiUrl, params({ action: "trabalho_detail", turma_id: $("#portal_turma").val(), id: $(this).data("id") })).done((res) => {
            const d = res.data || {};
            const canSubmit = role === "formando";
            const submitted = d.ficheiro_nome ? `<p><strong>Submissão:</strong> ${esc(d.ficheiro_nome)} em ${esc(d.submetido_em || "-")}</p>` : "<p><strong>Submissão:</strong> Pendente</p>";
            const form = canSubmit ? `<form class="portal-submit-form" id="portal_submit_form" enctype="multipart/form-data"><input type="hidden" name="action" value="trabalho_submit"><input type="hidden" name="turma_id" value="${esc(d.turma_id)}"><input type="hidden" name="trabalho_id" value="${esc(d.id)}"><label class="form-field"><span>Comentário</span><textarea name="comentario" rows="3">${esc(d.comentario || "")}</textarea></label><label class="form-field"><span>Ficheiro</span><input type="file" name="ficheiro" required></label><button type="submit" class="btn"><i class="fa-solid fa-upload"></i> Submeter trabalho</button></form>` : "";
            openModal("Detalhes do Trabalho", `<div class="portal-info-grid"><div class="portal-info-item"><span>Título</span><strong>${esc(d.titulo)}</strong></div><div class="portal-info-item"><span>Módulo</span><strong>${esc(d.sigla_modulo)}</strong></div><div class="portal-info-item"><span>Prazo</span><strong>${esc(d.data_entrega || "-")}</strong></div></div><p style="margin:14px 0;">${esc(d.descricao || "Sem descrição.")}</p>${submitted}${form}`);
        });
    });

    $content.on("click", ".ficheiro-categoria-header", function () {
        $(this).closest(".ficheiro-categoria").toggleClass("is-collapsed");
    });

    $modalBody.on("submit", "#portal_submit_form", function (event) {
        event.preventDefault();
        const fd = new FormData(this);
        $.ajax({ url: apiUrl, method: "POST", data: fd, processData: false, contentType: false }).done((res) => {
            if (typeof res === "string") { try { res = JSON.parse(res); } catch (e) { } }
            if (res && res.ok) {
                closeModal();
                loadTrabalhos($("#portal_turma").val(), $("#portal_modulo").val());
            } else {
                alert((res && res.msg) || "Não foi possível submeter.");
            }
        });
    });

    $("#portal_modal_close").on("click", closeModal);
    $modal.on("click", function (e) { if ($(e.target).is("#portal_modal")) closeModal(); });
    $educando.on("change", init);

    function init() {
        $toolbar.empty();
        $content.html('<div class="portal-empty"><i class="fa-solid fa-spinner fa-spin"></i> A carregar...</div>');
        $.getJSON(apiUrl, params({ action: "context" })).done((res) => {
            if (!res || !res.ok) { $content.html(`<div class="portal-empty">${esc((res && res.msg) || "A buscar informações... Por favor, aguarde!")}</div>`); return; }
            context = res;
            if ($educando.length && !$educando.children().length) {
                $educando.html((res.educandos || []).map(e => `<option value="${e.id}">${esc(e.nome_completo)} (${esc(e.codigo_formando || "-")})</option>`).join(""));
            }
            if (!context.turmas || !context.turmas.length) {
                $content.html('<div class="portal-empty">Sem turmas associadas.</div>');
                return;
            }
            if (mode === "horario") renderMeuHorario();
            else if (mode === "horarios") renderHorariosLista();
            else if (mode === "turmas") renderTurmas();
            else if (mode === "modulos") renderModulos();
            else if (mode === "frequencias") renderFrequencias();
            else if (mode === "avaliacoes") renderAvaliacoes();
            else if (mode === "trabalhos") renderTrabalhos();
            else if (mode === "ficheiros") renderFicheiros();
        });
    }

    init();
});

