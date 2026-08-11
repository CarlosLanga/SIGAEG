$(function () {
    const $allBtns = $("[data-enc-btn]");
    if (!$allBtns.length) return;

    $allBtns.each(function () {
        const $btn = $(this);
        const $form = $btn.closest("form");
        const $email = $form.find("[data-enc-email]");
        const $msg = $form.find("[data-enc-msg]");
        const $id = $form.find("[data-enc-id]");
        const $codigo = $form.find("[data-enc-codigo]");
        const $nome = $form.find("[data-enc-nome]");
        const $grau = $form.find("[data-enc-grau]");
        const $contacto = $form.find("[data-enc-contacto]");
        const allowEdit = $form.data("enc-allow-edit") == 1;

        function setDisabled(disabled) {
            $nome.prop("disabled", disabled);
            $grau.prop("disabled", disabled);
            $contacto.prop("disabled", disabled);
        }

        function syncEncBtn() {
            const email = $email.val().trim();
            const original = ($email.data("original-email") || "").toString().trim().toLowerCase();
            const current = email.toLowerCase();
            const valido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

            $msg.text("");

            if (!email) {
                $btn.prop("disabled", true);
                $id.val("");
                $codigo.val("");
                $nome.val("");
                $grau.val("");
                $contacto.val("");
                setDisabled(!allowEdit);
                return;
            }

            if (original && current === original) {
                $btn.prop("disabled", true);
                setDisabled(false);
                return;
            }

            if (!valido) {
                $btn.prop("disabled", true);
                $msg.text("Digite um email válido.");
                setDisabled(true);
                return;
            }

            $btn.prop("disabled", false);
            $id.val("");
            $codigo.val("");
            $nome.val("");
            $grau.val("");
            $contacto.val("");
            setDisabled(!allowEdit);
        }

        setDisabled(true);

        $email.on("input", syncEncBtn);
        syncEncBtn();


        $btn.on("click", function () {
            const email = $email.val().trim();
            const emailValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

            if (!emailValido) {
                $msg.text("Digite um email válido.");
                $btn.prop("disabled", true);
                return;
            }

            $btn.prop("disabled", true);

            $.post(`${$form.data("base-url")}api/encarregado_info.php`, { email }, function (res) {
                if (res.estado === "bloqueado") {
                    $msg.text("Este email pertence a outro utilizador.");
                    setDisabled(true);
                    return;
                }

            if (res.estado === "existente") {
                $msg.text("Encarregado já existente. Confirme os dados.");
                $id.val(res.dados.id);
                $nome.val(res.dados.nome_completo);
                $grau.val(res.dados.parentesco || "");
                $contacto.val(res.dados.contacto || "");
                if (window.IICAEGMasks) {
                    window.IICAEGMasks.refresh($form);
                }
                setDisabled(false);
                return;
            }

                if (res.estado === "novo") {
                    const url = $form.data("codegen-url");
                    $.post(url, { email, nivel_destinado: 4 }, function (r2) {
                        if (r2.estado === "ok" || r2.estado === "codigo_existente") {
                            $msg.text("");
                            $codigo.val(r2.codigo || "");
                            setDisabled(false);
                            return;
                        }
                        $msg.text("Erro ao gerar código.");
                        setDisabled(true);
                    }, "json").always(function () {
                        $btn.prop("disabled", false);
                    });
                    return;
                }

                $msg.text("Erro ao verificar encarregado.");
            }, "json");
        });
    });
});
