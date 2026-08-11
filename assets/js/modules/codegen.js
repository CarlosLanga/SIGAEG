$(function () {
    $("[data-codegen-btn]").each(function () {
        const $btn = $(this);
        const $form = $btn.closest("form");
        const $email = $form.find("[data-codegen-email]");
        const $msg = $form.find("[data-codegen-msg]");
        const $codeInput = $form.find("[data-codegen-output]");
        const $hidden = $form.find("[data-codegen-hidden]");
        const getOriginalEmail = () => ($email.data("original-email") || "").toString().trim().toLowerCase();

        function setMessage(text) {
            $msg.text(text || "");
        }

        function setButtonDisabled(disabled) {
            $btn.prop("disabled", !!disabled);
        }

        function clearCode() {
            $codeInput.val("");
            $hidden.val("");
        }

        function isEmailValido(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function syncState() {
            const email = $email.val().trim();
            const original = getOriginalEmail();

            if (!email) {
                setMessage("");
                clearCode();
                setButtonDisabled(true);
                return;
            }

            if (!isEmailValido(email)) {
                setMessage("Digite um email válido.");
                clearCode();
                setButtonDisabled(true);
                return;
            }

            if (original && email.toLowerCase() === original) {
                setMessage("Este é o email actual do formando.");
                setButtonDisabled(true);
                return;
            }

            setMessage("");
            clearCode();
            setButtonDisabled(false);
        }

        $email.on("input", syncState);
        syncState();

        $btn.on("click", function () {
            const email = $email.val().trim();
            const original = getOriginalEmail();

            if (!isEmailValido(email)) {
                setMessage("Digite um email válido.");
                setButtonDisabled(true);
                return;
            }

            if (original && email.toLowerCase() === original) {
                setMessage("Este é o email actual do formando.");
                setButtonDisabled(true);
                return;
            }

            const url = $form.data("codegen-url");
            const nivel = $form.find("input[name='nivel_destinado']").val() || 0;

            if (!url) {
                setMessage("Configuração inválida do formulário.");
                setButtonDisabled(true);
                return;
            }

            setButtonDisabled(true);

            $.post(url, { email, nivel_destinado: nivel }, function (res) {
                if (res.estado === "utilizado") {
                    setMessage("Este email já está associado a um utilizador.");
                    clearCode();
                    return;
                }

                if (res.estado === "codigo_existente") {
                    setMessage("Já existe um código para este email.");
                    $codeInput.val(res.codigo || "");
                    $hidden.val(res.codigo || "");
                    return;
                }

                if (res.estado === "ok") {
                    $codeInput.val(res.codigo);
                    $hidden.val(res.codigo);
                    return;
                }

                setMessage("Erro ao gerar código.");
            }, "json").always(function () {
                if ($msg.text() === "Código gerado." || $msg.text() === "Já existe um código para este email.") {
                    setButtonDisabled(true);
                    return;
                }
                if ($msg.text() === "") {
                    setButtonDisabled(false);
                }
            });
        });
    });
});
