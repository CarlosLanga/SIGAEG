$(document).ready(function () {
    const $tabs = $("#perfil_tabs");
    if (!$tabs.length) return;

    const $buttons = $tabs.find(".tab-btn");
    const $indicator = $tabs.find(".tab-indicator");
    const $panels = $(".tab-panel");

    const getActiveButton = () => $tabs.find(".tab-btn.is-active").first();

    const setIndicator = ($btn) => {
        if (!$btn || !$btn.length || !$indicator.length) return;
        const rect = $btn[0].getBoundingClientRect();
        const containerRect = $tabs[0].getBoundingClientRect();
        const x = rect.left - containerRect.left + $tabs.scrollLeft();
        $indicator.css({
            width: `${rect.width}px`,
            transform: `translateX(${x}px)`
        });
    };

    const setActiveTab = ($btn) => {
        if (!$btn || !$btn.length) return;
        const targetId = $btn.data("tab");
        $buttons.removeClass("is-active");
        $btn.addClass("is-active");
        $panels.removeClass("is-active");
        $panels.filter(`#${targetId}`).addClass("is-active");
        setIndicator($btn);
    };

    $tabs.on("click", ".tab-btn", function () {
        setActiveTab($(this));
    });

    $(document).on("click", "[data-tab-target]", function () {
        const targetId = $(this).data("tab-target");
        const $btn = $buttons.filter(`[data-tab="${targetId}"]`).first();
        if ($btn.length) {
            setActiveTab($btn);
        }
    });

    $(window).on("resize", function () {
        setIndicator(getActiveButton());
    });

    $tabs.on("scroll", function () {
        setIndicator(getActiveButton());
    });

    setIndicator(getActiveButton());

    const $fotoCard = $(".perfil-photo-card");
    const $fotoInput = $("#perfil_foto_input");
    const $fotoPick = $("#perfil_foto_pick");
    const $fotoSave = $("#perfil_foto_save");
    const saveLabel = '<i class="fa-solid fa-cloud-arrow-up"></i><span>Guardar</span>';
    const removeLabel = '<i class="fa-solid fa-trash"></i><span>Remover foto</span>';
    const initials = ($fotoCard.data("initials") || "U").toString();
    let fotoMode = $fotoCard.data("has-photo") ? "remove" : "save";
    const updateUrl = $fotoCard.data("update-url");

    const setPreview = (src) => {
        const $preview = $("#perfil_photo_preview");
        if (!$preview.length) return;
        const $img = $preview.find("img");
        if ($img.length) {
            $img.attr("src", src);
        } else {
            $preview.find(".perfil-iniciais").remove();
            $preview.append(`<img src="${src}" alt="Foto de perfil">`);
        }
    };

    const setOverview = (src) => {
        const $overviewImg = $("[data-photo-role='overview']");
        if ($overviewImg.length) {
            $overviewImg.attr("src", src);
            return;
        }
        const $overviewInit = $("[data-photo-role='overview-iniciais']");
        if ($overviewInit.length) {
            $overviewInit.replaceWith(`<img src="${src}" alt="Foto de perfil" class="perfil-photo-img" data-photo-role="overview">`);
        }
    };

    const setPersonal = (src) => {
        const $personalImg = $("[data-photo-role='personal']");
        if ($personalImg.length) {
            $personalImg.attr("src", src);
            return;
        }
        const $personalInit = $("[data-photo-role='personal-iniciais']");
        if ($personalInit.length) {
            $personalInit.replaceWith(`<img src="${src}" alt="Foto de perfil" class="perfil-photo-img" data-photo-role="personal">`);
        }
    };

    const setGlobalAvatars = (src) => {
        const $targets = $(".top-right img, .mobile-profile-panel img, #profile-info img");
        if ($targets.length) {
            $targets.attr("src", src);
        }

        $(".top-right .avatar-initials, .mobile-profile-panel .avatar-initials, #profile-info .avatar-initials")
            .each(function () {
                $(this).replaceWith(`<img src="${src}" alt="Perfil">`);
            });
    };

    const setPreviewInitials = () => {
        const $preview = $("#perfil_photo_preview");
        if (!$preview.length) return;
        const $img = $preview.find("img");
        if ($img.length) {
            $img.replaceWith(`<span class="perfil-iniciais" data-photo-role="personal-iniciais">${initials}</span>`);
        } else {
            $preview.find(".perfil-iniciais").text(initials);
        }
    };

    const setOverviewInitials = () => {
        const $overviewImg = $("[data-photo-role='overview']");
        if ($overviewImg.length) {
            $overviewImg.replaceWith(`<span class="perfil-iniciais" data-photo-role="overview-iniciais">${initials}</span>`);
        } else {
            $("[data-photo-role='overview-iniciais']").text(initials);
        }
    };

    const setPersonalInitials = () => {
        const $personalImg = $("[data-photo-role='personal']");
        if ($personalImg.length) {
            $personalImg.replaceWith(`<span class="perfil-iniciais" data-photo-role="personal-iniciais">${initials}</span>`);
        } else {
            $("[data-photo-role='personal-iniciais']").text(initials);
        }
    };

    const setGlobalInitials = () => {
        const replaceWithInitials = ($container, extraClass) => {
            const $img = $container.find("img");
            if ($img.length) {
                $img.replaceWith(`<span class="avatar-initials ${extraClass}">${initials}</span>`);
            } else {
                $container.find(".avatar-initials").text(initials);
            }
        };

        replaceWithInitials($(".top-right .notif-wrapper"), "");
        replaceWithInitials($(".mobile-profile-panel .profile-header a"), "avatar-lg");
        replaceWithInitials($("#profile-info .profile-header a"), "avatar-lg");
    };

    const setFotoMode = (mode) => {
        fotoMode = mode;
        if (mode === "remove") {
            $fotoSave.prop("disabled", false).html(removeLabel).data("mode", "remove");
            return;
        }
        const hasFile = $fotoInput[0].files && $fotoInput[0].files.length > 0;
        $fotoSave.prop("disabled", !hasFile).html(saveLabel).data("mode", "save");
    };

    $fotoPick.on("click", function () {
        $fotoInput.trigger("click");
    });

    $fotoInput.on("change", function () {
        const file = this.files && this.files[0];
        if (!file) return;

        const maxSize = 2 * 1024 * 1024;
        if (file.size > maxSize) {
            showNotification("A foto não pode exceder 2MB.", false);
            $fotoInput.val("");
            return;
        }

        const previewUrl = URL.createObjectURL(file);
        setPreview(previewUrl);
        $fotoSave.prop("disabled", false);
    });

    const resetSaveButton = () => {
        setFotoMode(fotoMode);
    };

    $fotoSave.on("click", function () {
        if ($fotoSave.data("mode") === "remove") {
            $fotoSave.prop("disabled", true).html("<span>A remover...</span>");
            $.ajax({
                url: updateUrl,
                method: "POST",
                data: { action: "remove" },
                success: function (res) {
                    let data = res;
                    if (typeof res === "string") {
                        try {
                            data = JSON.parse(res);
                        } catch (err) {
                            showNotification("Resposta inválida do servidor.", false);
                            resetSaveButton();
                            return;
                        }
                    }

                    if (data && data.estado === "ok") {
                        setPreviewInitials();
                        setOverviewInitials();
                        setPersonalInitials();
                        setGlobalInitials();
                        showNotification("Foto removida com êxito.", true);
                        $fotoInput.val("");
                        setFotoMode("save");
                    } else {
                        showNotification(data && data.message ? data.message : "Erro ao remover a foto.", false);
                        resetSaveButton();
                    }
                },
                error: function () {
                    showNotification("Erro de ligação ao servidor.", false);
                    resetSaveButton();
                }
            });
            return;
        }

        const file = $fotoInput[0].files && $fotoInput[0].files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append("foto", file);

        $fotoSave.prop("disabled", true).html("<span>A guardar...</span>");
        const resetTimer = setTimeout(function () {
            resetSaveButton();
        }, 8000);

        $.ajax({
            url: updateUrl,
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            timeout: 15000,
            success: function (res) {
                let data = res;
                if (typeof res === "string") {
                    try {
                        data = JSON.parse(res);
                    } catch (err) {
                        showNotification("Resposta inválida do servidor.", false);
                        resetSaveButton();
                        return;
                    }
                }

                if (data && data.estado === "ok") {
                    setOverview(data.url);
                    setPersonal(data.url);
                    setGlobalAvatars(data.url);
                    showNotification("Foto actualizada com êxito.", true);
                    $fotoInput.val("");
                    setFotoMode("remove");
                } else {
                    showNotification(data && data.message ? data.message : "Erro ao actualizar a foto.", false);
                }
                resetSaveButton();
            },
            error: function () {
                showNotification("Erro de ligação ao servidor.", false);
                resetSaveButton();
            },
            complete: function () {
                clearTimeout(resetTimer);
                resetSaveButton();
            }
        });
    });

    setFotoMode(fotoMode);

    const $perfilForm = $("#perfil_form");
    const $perfilNome = $("#perfil_nome");
    const $perfilSave = $("#perfil_save");
    const $togglePass = $("#perfil_toggle_pass");
    const $passFields = $("#perfil_pass_fields");
    const $passAtual = $("#perfil_pass_atual");
    const $passNova = $("#perfil_pass_nova");
    const $passConfirmar = $("#perfil_pass_confirmar");
    const $deleteBtn = $("#perfil_delete");

    if ($perfilForm.length) {
        const initialNome = ($perfilNome.val() || "").trim();

        const hasPasswordData = () => {
            return (
                ($passAtual.val() || "").trim() ||
                ($passNova.val() || "").trim() ||
                ($passConfirmar.val() || "").trim()
            );
        };

        const isValidPassword = () => {
            if (!hasPasswordData()) return true;
            const atual = ($passAtual.val() || "").trim();
            const nova = ($passNova.val() || "").trim();
            const confirmar = ($passConfirmar.val() || "").trim();
            if (!atual || !nova || !confirmar) return false;
            if (nova !== confirmar) return false;
            return true;
        };

        const hasChanges = () => {
            const nome = ($perfilNome.val() || "").trim();
            return nome !== initialNome || hasPasswordData();
        };

        const validateForm = () => {
            const nomeVal = ($perfilNome.val() || "").trim();
            const ok = nomeVal.length > 0 && hasChanges() && isValidPassword();
            $perfilSave.prop("disabled", !ok);
        };

        $perfilNome.on("input", validateForm);
        $passAtual.on("input", validateForm);
        $passNova.on("input", validateForm);
        $passConfirmar.on("input", validateForm);

        $togglePass.on("click", function () {
            $passFields.slideToggle(200, function () {
                validateForm();
            });
        });

        $(document).on("click", ".toggle-pass", function () {
            const $btn = $(this);
            const $input = $btn.closest(".password-toggle").find("input");

            if ($input.attr("type") === "password") {
                $input.attr("type", "text");
                $btn.find("i").removeClass("fa-eye").addClass("fa-eye-slash");
            } else {
                $input.attr("type", "password");
                $btn.find("i").removeClass("fa-eye-slash").addClass("fa-eye");
            }
        });

        $perfilForm.on("submit", function (e) {
            e.preventDefault();
            if ($perfilSave.prop("disabled")) return;

            const defaultSaveHtml = '<i class="fa-solid fa-floppy-disk"></i><span>Guardar</span>';
            const payload = {
                nome_completo: ($perfilNome.val() || "").trim(),
                senha_actual: ($passAtual.val() || "").trim(),
                senha_nova: ($passNova.val() || "").trim(),
                senha_confirmar: ($passConfirmar.val() || "").trim()
            };

            $perfilSave.prop("disabled", true).html("<span>A guardar...</span>");

            $.ajax({
                url: $perfilForm.data("update-url"),
                method: "POST",
                data: payload,
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

                    if (data && data.estado === "ok") {
                        showNotification("Dados actualizados com êxito.", true);
                        $(".perfil-nome").text(payload.nome_completo);
                        $(".p-name").text(payload.nome_completo);
                        $passAtual.val("");
                        $passNova.val("");
                        $passConfirmar.val("");
                    } else {
                        showNotification(data && data.message ? data.message : "Erro ao actualizar dados.", false);
                    }
                },
                error: function () {
                    showNotification("Erro de ligação ao servidor.", false);
                },
                complete: function () {
                    $perfilSave.prop("disabled", false).html(defaultSaveHtml);
                    validateForm();
                }
            });
        });

        $deleteBtn.on("click", function () {
            const confirmacao = confirm("Tens certeza que deseja remover esta conta? Esta acção é irreversível.");
            if (!confirmacao) return;

            $.ajax({
                url: $perfilForm.data("delete-url"),
                method: "POST",
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

                    if (data && data.estado === "ok") {
                        window.location.href = `${$perfilForm.data("base-url")}index.php`;
                        return;
                    }
                    showNotification(data && data.message ? data.message : "Não foi possível remover a conta.", false);
                },
                error: function () {
                    showNotification("Erro de ligação ao servidor.", false);
                }
            });
        });

        validateForm();
    }

    const $contactoForms = $(".perfil-contacto-form");
    if ($contactoForms.length) {
        $contactoForms.each(function () {
            const $form = $(this);
            const $input = $form.find("input");
            const $btn = $form.find("button[type='submit']");
            const defaultHtml = $btn.html();
            const successMsg = $form.data("success") || "Contacto actualizado com êxito.";

            $form.on("submit", function (e) {
                e.preventDefault();
                const contacto = ($input.val() || "").trim();
                if (!contacto) {
                    showNotification("Informe o contacto.", false);
                    return;
                }

                $btn.prop("disabled", true).html("<span>A guardar...</span>");

                $.ajax({
                    url: $form.data("update-url"),
                    method: "POST",
                    data: { contacto: contacto },
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

                        if (data && data.estado === "ok") {
                            showNotification(successMsg, true);
                        } else {
                            showNotification(data && data.message ? data.message : "Erro ao actualizar o contacto.", false);
                        }
                    },
                    error: function () {
                        showNotification("Erro de ligação ao servidor.", false);
                    },
                    complete: function () {
                        $btn.prop("disabled", false).html(defaultHtml);
                    }
                });
            });
        });
    }
});
