$(function () {
    const $page = $(".anuncios-creator-page");
    if (!$page.length) return;

    const isFormador = String($page.data("formador-mode")) === "1";

    const urls = {
        save: $page.data("save-url"),
        turmas: $page.data("turmas-url"),
        modulos: $page.data("modulos-url")
    };

    const $form = $("#form_anuncio");
    const $titulo = $("#anuncio_titulo");
    const $prioridade = $("#anuncio_prioridade");
    const $publico = $("#anuncio_publico");
    const $turmaWrapper = $("#anuncio_turma_wrapper");
    const $turma = $("#anuncio_turma");
    const $moduloWrapper = $("#anuncio_modulo_wrapper");
    const $modulo = $("#anuncio_modulo");
    const $anexo = $("#anuncio_anexo");
    const $descricaoHidden = $("#anuncio_descricao");
    const $btnPublish = $("#btn_publish");
    const $btnReset = $("#btn_reset");

    const $previewEmpty = $("#preview_empty");
    const $previewCard = $("#preview_card");
    const $previewTitle = $("#preview_title");
    const $previewContent = $("#preview_content");
    const $previewBadgePrioridade = $("#preview_badge_prioridade");
    const $previewBadgeAlvo = $("#preview_badge_alvo");
    const $previewAttachment = $("#preview_attachment");
    const $previewAttachmentName = $("#preview_attachment_name");
    const $previewEventDates = $("#preview_event_dates");
    const $previewEventDatesText = $("#preview_event_dates_text");

    const $anexoPlaceholder = $("#anexo_placeholder");
    const $anexoSelected = $("#anexo_selected");
    const $anexoNomeDisplay = $("#anexo_nome_display");
    const $anexoRemove = $("#anexo_remove");

    const quill = new Quill('#quill_editor', {
        theme: 'snow',
        placeholder: 'Escreva a mensagem do anúncio aqui...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'color': [] }, { 'background': [] }],
                ['link'],
                ['clean']
            ]
        }
    });

    function formatTurmaLabel(t) {
        const sigla = String(t.nome_turno || "").toLowerCase().includes("diurno") ? "CD" : "CN";
        return `${t.nome_turma} - ${sigla}`;
    }

    function loadTurmas() {
        const placeholder = isFormador
            ? '<option value="">Seleccione a turma destinada...</option>'
            : '<option value="">Seleccione a turma...</option>';

        $.getJSON(urls.turmas).done(function (res) {
            let options = placeholder;
            const rows = Array.isArray(res) ? res : (res.rows || []);
            rows.forEach(t => {
                options += `<option value="${t.id}">${formatTurmaLabel(t)}</option>`;
            });
            $turma.html(options);
        }).fail(function () {
            $turma.html('<option value="">Erro ao carregar turmas</option>');
        });
    }

    function resetModulos() {
        if (!$modulo.length) return;
        $modulo.html('<option value="">Todos os módulos da turma</option>');
        $moduloWrapper.hide();
    }

    function loadModulos(turmaId) {
        if (!isFormador || !$modulo.length || !turmaId) {
            resetModulos();
            return;
        }

        $.getJSON(urls.modulos, { turma_id: turmaId }).done(function (rows) {
            const list = Array.isArray(rows) ? rows : [];
            let options = '<option value="">Todos os módulos da turma</option>';

            list.forEach(m => {
                const label = m.sigla_modulo
                    ? `${m.sigla_modulo} — ${m.nome_modulo}`
                    : m.nome_modulo;
                options += `<option value="${m.modulo_id}">${label}</option>`;
            });

            $modulo.html(options);
            $moduloWrapper.toggle(list.length > 0);
        }).fail(function () {
            resetModulos();
        });
    }

    loadTurmas();

    if (isFormador) {
        $turmaWrapper.show();
        $turma.prop("required", true);
    } else {
        syncTurmaVisibility();
    }

    const $duracaoWrapper = $("#evento_duracao_wrapper");
    const $btnDuracao = $("#btn_selecionar_duracao");
    const $duracaoLabel = $("#duracao_label");
    const $eventoInicio = $("#evento_data_inicio");
    const $eventoFim = $("#evento_data_fim");
    const baseUrl = $("body").data("base-url") || "/iicaeg_sistema/";

    // Lib do easepick para os ranges de calendário
    let easepickInstance = null;

    function initEasepick() {
        if (easepickInstance) return;

        if (typeof easepick === 'undefined' || typeof easepick.create !== 'function') {
            console.error('[Easepick] Biblioteca não carregada. Verifique o caminho do script.');
            return;
        }

        try {
            easepickInstance = new easepick.create({
                element: document.getElementById('easepick_input'),
                css: [
                    baseUrl + 'assets/css/lib/easepick.css',
                    baseUrl + 'assets/css/pages/anuncios_easepick_theme.css'
                ],
                grid: 2,
                calendars: 2,
                lang: 'pt-BR',
                zIndex: 9999,
                plugins: ['RangePlugin', 'LockPlugin'],
                RangePlugin: {
                    tooltip: true
                },
                LockPlugin: {
                    minDate: new Date()
                },
                setup(picker) {
                    picker.on('select', (e) => {
                        const { start, end } = e.detail;
                        if (start) {
                            const d1 = start.format('DD/MM/YYYY');
                            $eventoInicio.val(start.format('YYYY-MM-DD'));
                            if (end) {
                                const d2 = end.format('DD/MM/YYYY');
                                $duracaoLabel.text(d1 + "  \u2192  " + d2);
                                $eventoFim.val(end.format('YYYY-MM-DD'));
                            } else {
                                $duracaoLabel.text(d1);
                                $eventoFim.val("");
                            }
                            $btnDuracao.addClass("has-date");
                        }
                        updatePreview();
                    });
                }
            });
        } catch (err) {
            console.error('[Easepick] Erro ao inicializar:', err);
            easepickInstance = null;
        }
    }

    $btnDuracao.on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        initEasepick();
        if (!easepickInstance) {
            console.warn('[Easepick] Instância não disponível.');
            return;
        }
        if (easepickInstance.isShown()) {
            easepickInstance.hide();
        } else {
            easepickInstance.show();
        }
    });

    function clearDuracao() {
        $eventoInicio.val("");
        $eventoFim.val("");
        $duracaoLabel.text("Seleccionar data(s)");
        $btnDuracao.removeClass("has-date");
        if (easepickInstance) {
            easepickInstance.clear();
        }
    }

    function syncEventoVisibility() {
        if ($prioridade.val() === "evento") {
            $duracaoWrapper.show();
            initEasepick();
        } else {
            $duracaoWrapper.hide();
            clearDuracao();
        }
    }

    $prioridade.on("change", function () {
        syncEventoVisibility();
        updatePreview();
    });

    function syncTurmaVisibility() {
        if (isFormador) {
            $turmaWrapper.show();
            $turma.prop("required", true);
            return;
        }

        if ($publico.val() === "turma") {
            $turmaWrapper.show();
            $turma.prop("required", true);
        } else {
            $turmaWrapper.hide();
            $turma.prop("required", false);
            $turma.val("");
            resetModulos();
        }
    }

    if (!isFormador) {
        $publico.on("change", function () {
            syncTurmaVisibility();
            updatePreview();
        });
    }

    $turma.on("change", function () {
        const turmaId = $(this).val();
        if (isFormador) {
            loadModulos(turmaId);
        }
        updatePreview();
    });

    if ($modulo.length) {
        $modulo.on("change", updatePreview);
    }

    // Anexos
    $anexo.on("change", function () {
        const file = this.files[0];
        if (file) {
            $anexoPlaceholder.hide();
            $anexoNomeDisplay.text(file.name);
            $anexoSelected.show();
        } else {
            resetAnexoUI();
        }
        updatePreview();
    });

    $anexoRemove.on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        $anexo.val("");
        resetAnexoUI();
        updatePreview();
    });

    function resetAnexoUI() {
        $anexoSelected.hide();
        $anexoPlaceholder.show();
    }

    // previsualizador
    function updatePreview() {
        const titleText = $titulo.val().trim();
        const contentHtml = quill.root.innerHTML;
        const isContentEmpty = quill.getText().trim().length === 0;
        
        if (!titleText && isContentEmpty) {
            $previewEmpty.show();
            $previewCard.hide();
            return;
        }

        $previewEmpty.hide();
        $previewCard.show();

        $previewTitle.text(titleText || "Sem título");

        if (isContentEmpty) {
            $previewContent.html('<p style="color: var(--text-muted); font-style: italic;">Sem conteúdo</p>');
        } else {
            $previewContent.html(contentHtml);
        }
        $previewContent.html(isContentEmpty ? "" : contentHtml);
        $previewContent.toggle(!isContentEmpty);

        const prioridade = $prioridade.val();
        $previewCard.removeClass("priority-importante priority-evento");
        if (prioridade === "importante") {
            $previewBadgePrioridade.text("Importante").removeClass("badge-evento").addClass("badge-importante").show();
            $previewCard.addClass("priority-importante");
        } else if (prioridade === "evento") {
            $previewBadgePrioridade.text("Evento").removeClass("badge-importante").addClass("badge-evento").show();
            $previewCard.addClass("priority-evento");
        } else {
            $previewBadgePrioridade.hide();
        }

        const publico = $publico.val();
        let publicoText = "Todos";

        if (isFormador) {
            const turmaNome = $turma.find("option:selected").text();
            const moduloNome = $modulo.val() ? $modulo.find("option:selected").text() : "";
            if (!$turma.val()) {
                publicoText = "Turma";
            } else if (moduloNome) {
                publicoText = `${turmaNome} · ${moduloNome}`;
            } else {
                publicoText = turmaNome;
            }
        } else if (publico === "formadores") {
            publicoText = "Formadores";
        } else if (publico === "formandos") {
            publicoText = "Formandos";
        } else if (publico === "encarregados") {
            publicoText = "Encarregados";
        } else if (publico === "turma") {
            const turmaNome = $turma.find("option:selected").text();
            publicoText = $turma.val() ? turmaNome : "Turma";
        }

        $previewBadgeAlvo.text(publicoText);

        const file = $anexo[0].files[0];
        if (file) {
            $previewAttachmentName.text(file.name);
            $previewAttachment.show();
        } else {
            $previewAttachment.hide();
        }

        // Exibir datas do evento se for tipo evento
        const inicio = $eventoInicio.val();
        const fim = $eventoFim.val();
        if ($prioridade.val() === "evento" && inicio) {
            const formatDate = (dateStr) => {
                if (!dateStr) return "";
                const date = new Date(dateStr + "T00:00:00");
                const daysOfWeek = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                const dayName = daysOfWeek[date.getDay()];
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${dayName}, ${day}/${month}/${year}`;
            };
            
            const dataInicio = formatDate(inicio);
            const dataFim = formatDate(fim);
            
            if (dataFim && dataFim !== dataInicio) {
                $previewEventDatesText.text(`${dataInicio} - ${dataFim}`);
            } else {
                $previewEventDatesText.text(dataInicio);
            }
            $previewEventDates.show();
        } else {
            $previewEventDates.hide();
        }
    }

    $titulo.on("input", updatePreview);
    $prioridade.on("change", updatePreview);
    quill.on("text-change", updatePreview);

    $form.on("submit", function (e) {
        e.preventDefault();

        const isContentEmpty = quill.getText().trim().length === 0;
        if (isContentEmpty) {
            showNotification("O conteúdo do anúncio não pode estar vazio.", false);
            return;
        }

        $descricaoHidden.val(quill.root.innerHTML);

        const formData = new FormData(this);
        
        $btnPublish.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin"></i> A publicar...');

        $.ajax({
            url: urls.save,
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                let data = res;
                if (typeof res === "string") {
                    try { data = JSON.parse(res); } catch (e) {}
                }

                if (data && data.ok) {
                    showNotification("Anúncio publicado com êxito!", true);
                    resetForm();
                } else {
                    showNotification(data && data.message ? data.message : "Erro ao publicar anúncio.", false);
                }
            },
            error: function () {
                showNotification("Erro de ligação ao servidor.", false);
            },
            complete: function () {
                $btnPublish.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i> Publicar');
            }
        });
    });

    // function de redefinir o form
    function resetForm() {
        $form[0].reset();
        quill.setContents([]);
        if (isFormador) {
            $publico.val("turma");
        }
        syncTurmaVisibility();
        if (isFormador) {
            resetModulos();
            loadTurmas();
        }
        syncEventoVisibility();
        resetAnexoUI();
        updatePreview();
    }

    $btnReset.on("click", function() {
        setTimeout(() => {
            quill.setContents([]);
            if (isFormador) {
                $publico.val("turma");
            }
            syncTurmaVisibility();
            if (isFormador) {
                resetModulos();
                loadTurmas();
            }
            syncEventoVisibility();
            resetAnexoUI();
            updatePreview();
        }, 10);
    });
});
