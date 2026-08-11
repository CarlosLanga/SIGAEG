$(function () {
    const $page = $(".ficheiros-page");
    if (!$page.length) return;

    const urls = {
        base: ($page.data("base-url") || "").toString(),
        list: $page.data("list-url"),
        upload: $page.data("upload-url"),
        turmas: $page.data("turmas-url"),
        download: $page.data("download-url"),
        delete: ($page.data("base-url") || "").toString() + "api/ficheiros_delete.php",
    };
    const nivel = parseInt($page.data("nivel"), 10) || 0;
    const $categories = $("#ficheiros_categorias");
    const $modal = $("#modal_ficheiro");
    const $form = $("#form_ficheiro");
    const $categoria = $("#ficheiro_categoria");
    const $campoTurma = $("#campo_turma_ficheiro");
    const $turma = $("#ficheiro_turma");
    const $fileInput = $("#ficheiro_upload");
    const $fileLabel = $("#ficheiro_upload_label");
    const $btnSubmit = $("#btn_publicar_ficheiro");
    let allCategoriesData = [];

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

    function getFileType(name) {
        const nameLower = (name || "").toLowerCase();
        if (/\.(jpg|jpeg|png)$/i.test(nameLower)) return 'image';
        if (/\.pdf$/i.test(nameLower)) return 'pdf';
        if (/\.(doc|docx)$/i.test(nameLower)) return 'word';
        if (/\.(xls|xlsx)$/i.test(nameLower)) return 'excel';
        if (/\.(ppt|pptx)$/i.test(nameLower)) return 'powerpoint';
        if (/\.(zip|rar)$/i.test(nameLower)) return 'zip';
        return 'text';
    }

    function fileIcon(row) {
        const name = (row.nome_original || "").toLowerCase();
        if (/\.(jpg|jpeg|png)$/i.test(name)) {
            return `<img src="${urls.base}${escapeHtml(row.caminho)}" alt="">`;
        }
        if (/\.pdf$/i.test(name)) return '<i class="fa-solid fa-file-pdf"></i>';
        if (/\.(doc|docx)$/i.test(name)) return '<i class="fa-solid fa-file-word"></i>';
        if (/\.(xls|xlsx)$/i.test(name)) return '<i class="fa-solid fa-file-excel"></i>';
        if (/\.(ppt|pptx)$/i.test(name)) return '<i class="fa-solid fa-file-powerpoint"></i>';
        if (/\.(zip|rar)$/i.test(name)) return '<i class="fa-solid fa-file-zipper"></i>';
        return '<i class="fa-solid fa-file-lines"></i>';
    }

    function turmaLabel(row) {
        if (!row.nome_turma) return "";
        const sigla = turnoSigla(row.nome_turno || "");
        return `${row.nome_turma}${sigla ? " - " + sigla : ""}`;
    }

    function renderItem(row, catKey) {
        const subtitle = [row.descricao || "Sem descrição", turmaLabel(row)].filter(Boolean).join(" | ");
        const fileType = getFileType(row.nome_original || "");
        
        let adminActionsHtml = "";
        let showActions = false;
        
        if (catKey !== "meus") {
            if (nivel === 1) {
                showActions = true;
            } else if (nivel === 2 && catKey === "turma") {
                showActions = true;
            }
        }
        
        if (showActions) {
            adminActionsHtml = `
                <div class="ficheiro-admin-actions">
                    <button type="button" class="ficheiro-admin-btn btn-edit" title="Editar" data-id="${row.id}" data-titulo="${escapeHtml(row.titulo)}" data-descricao="${escapeHtml(row.descricao)}" data-categoria="${escapeHtml(row.categoria || catKey)}" data-turma="${row.turma_id || ''}" data-nome-original="${escapeHtml(row.nome_original || '')}">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button type="button" class="ficheiro-admin-btn btn-remove" title="Remover" data-id="${row.id}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
        }

        return `
            <article class="ficheiro-item">
                <div class="ficheiro-preview ${fileType}">
                    ${fileIcon(row)}
                    ${adminActionsHtml}
                </div>
                <div class="ficheiro-info">
                    <h3>${escapeHtml(row.titulo)}</h3>
                    <p>${escapeHtml(subtitle)}</p>
                    <div class="ficheiro-downloads">
                        <i class="fa-solid fa-download"></i>
                        ${Number(row.downloads || 0)}
                    </div>
                </div>
                <div class="ficheiro-actions">
                    <a class="ficheiro-btn ficheiro-btn-primary" href="${urls.download}?id=${encodeURIComponent(row.id)}">
                        <i class="fa-solid fa-download"></i>
                        <span>Baixar</span>
                    </a>
                </div>
            </article>
        `;
    }

    function renderCategories(categories) {
        if (!categories || !categories.length) {
            $categories.html('<div class="ficheiros-empty">Nenhuma categoria disponível.</div>');
            return;
        }

        const html = categories.map((cat, index) => {
            const rows = cat.rows || [];
            const body = rows.length
                ? rows.map(row => renderItem(row, cat.key)).join("")
                : '<div class="ficheiros-empty">Nenhum ficheiro nesta categoria.</div>';
            const collapsed = index > 0 ? " is-collapsed" : "";

            return `
                <section class="ficheiro-categoria${collapsed}" data-category="${escapeHtml(cat.key)}">
                    <button type="button" class="ficheiro-categoria-header">
                        <span class="ficheiro-categoria-title">
                            <i class="fa-solid fa-folder"></i>
                            ${escapeHtml(cat.label)}
                        </span>
                        <span class="ficheiro-categoria-meta">
                            <strong>${rows.length}</strong>
                            <span>itens</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </span>
                    </button>
                    <div class="ficheiro-categoria-body">${body}</div>
                </section>
            `;
        }).join("");

        $categories.html(html);
    }

    function loadFiles() {
        $categories.html('<div class="ficheiros-loading"><i class="fa-solid fa-spinner fa-spin"></i><span>A carregar ficheiros...</span></div>');
        $.getJSON(urls.list)
            .done(function (res) {
                if (!res || !res.ok) {
                    $categories.html('<div class="ficheiros-empty">Não foi possível carregar os ficheiros.</div>');
                    return;
                }
                allCategoriesData = res.categories || [];
                renderCategories(allCategoriesData);
            })
            .fail(function () {
                $categories.html('<div class="ficheiros-empty">Erro ao carregar os ficheiros.</div>');
            });
    }

    function loadTurmas() {
        $.getJSON(urls.turmas).done(function (res) {
            let options = '<option value="">Seleccione a turma</option>';
            if (res && res.ok) {
                (res.rows || []).forEach((t) => {
                    const sigla = turnoSigla(t.nome_turno || "");
                    const label = `${t.nome_turma}${sigla ? " - " + sigla : ""}`;
                    options += `<option value="${t.id}">${escapeHtml(label)}</option>`;
                });
            }
            $turma.html(options);
        });
    }

    function openModal() {
        $form[0].reset();
        $("#ficheiro_id").val("");
        $("#modal_ficheiro_titulo").text("Adicionar ficheiro");
        $("#ficheiro_upload").prop("required", true);
        $fileLabel.text("Seleccionar ficheiro");
        if (nivel === 1) {
            $campoTurma.hide();
        } else {
            $campoTurma.show();
        }
        $modal.addClass("open").attr("aria-hidden", "false");
    }

    function closeModal() {
        $modal.removeClass("open").attr("aria-hidden", "true");
    }

    $("#btn_abrir_modal_ficheiro").on("click", openModal);
    $("[data-close-modal]").on("click", closeModal);

    $categories.on("click", ".btn-edit", function(e) {
        e.preventDefault();
        e.stopPropagation();
        const $btn = $(this);
        
        $form[0].reset();
        
        $("#ficheiro_id").val($btn.data("id"));
        $("#ficheiro_titulo").val($btn.data("titulo"));
        $("#ficheiro_descricao").val($btn.data("descricao"));
        
        const cat = $btn.data("categoria");
        if ($categoria.length) {
            $categoria.val(cat).trigger('change');
        }
        
        const turma = $btn.data("turma");
        if ($turma.length && turma) {
            $turma.val(turma);
        }
        
        $("#modal_ficheiro_titulo").text("Editar ficheiro");
        $("#ficheiro_upload").prop("required", false);
        const nomeOriginal = $btn.data("nome-original");
        $fileLabel.text(nomeOriginal ? nomeOriginal : "Seleccionar novo ficheiro (opcional)");
        
        if (nivel === 1) {
            $campoTurma.toggle(cat === "turma");
        }
        
        $modal.addClass("open").attr("aria-hidden", "false");
    });

    let deleteFileId = null;

    function openDeleteModal(id) {
        deleteFileId = id;
        $("#modal_confirmar_remocao_ficheiro").fadeIn(150);
    }

    function closeDeleteModal() {
        deleteFileId = null;
        $("#modal_confirmar_remocao_ficheiro").fadeOut(150);
    }

    $("#btn_cancelar_remocao_ficheiro, #modal_confirmar_remocao_ficheiro").on("click", function (e) {
        if (e.target !== this) return;
        closeDeleteModal();
    });

    $("#btn_confirmar_remocao_ficheiro").on("click", function () {
        if (!deleteFileId) return;

        $.post(urls.delete, { id: deleteFileId })
            .done(function (res) {
                closeDeleteModal();
                let data = res;
                if (typeof res === "string") {
                    try { data = JSON.parse(res); } catch (e) {}
                }
                
                if (data && data.ok) {
                    showNotification("Ficheiro removido com êxito.", true);
                    loadFiles();
                } else {
                    showNotification(data && data.message ? data.message : "Erro ao remover ficheiro.", false);
                }
            })
            .fail(function () {
                closeDeleteModal();
                showNotification("Erro de ligação ao servidor.", false);
            });
    });

    $categories.on("click", ".btn-remove", function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $btn = $(this);
        const id = $btn.data("id");
        if (!id) return;
        openDeleteModal(id);
    });

    $categoria.on("change", function () {
        $campoTurma.toggle($categoria.val() === "turma");
    });

    $fileInput.on("change", function () {
        const file = this.files && this.files[0] ? this.files[0] : null;
        $fileLabel.text(file ? file.name : "Seleccionar ficheiro");
    });

    $categories.on("click", ".ficheiro-categoria-header", function () {
        $(this).closest(".ficheiro-categoria").toggleClass("is-collapsed");
    });

    $form.on("submit", function (event) {
        event.preventDefault();
        const formData = new FormData($form[0]);

        $btnSubmit.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin"></i><span>A publicar...</span>');

        $.ajax({
            url: urls.upload,
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
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
                    showNotification("Ficheiro publicado com êxito.", true);
                    closeModal();
                    loadFiles();
                    return;
                }

                showNotification(data && data.message ? data.message : "Erro ao publicar ficheiro.", false);
            },
            error: function () {
                showNotification("Erro de ligação ao servidor.", false);
            },
            complete: function () {
                $btnSubmit.prop("disabled", false).html('<i class="fa-solid fa-paper-plane"></i><span>Publicar</span>');
            }
        });
    });

    $("#ficheiros_search_input").on("input", function() {
        const term = $(this).val().toLowerCase().trim();
        if (!term) {
            renderCategories(allCategoriesData);
            return;
        }
        
        const filteredCategories = allCategoriesData.map(cat => {
            const filteredRows = (cat.rows || []).filter(row => {
                const title = (row.titulo || "").toLowerCase();
                const desc = (row.descricao || "").toLowerCase();
                const origName = (row.nome_original || "").toLowerCase();
                return title.includes(term) || desc.includes(term) || origName.includes(term);
            });
            
            return {
                ...cat,
                rows: filteredRows
            };
        }).filter(cat => cat.rows.length > 0);
        
        renderCategories(filteredCategories);
    });

    loadTurmas();
    loadFiles();
});
