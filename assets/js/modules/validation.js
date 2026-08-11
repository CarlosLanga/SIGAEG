$(function () {
    $("form[data-validate='true']").each(function () {
        const $form = $(this);
        const $submit = $form.find("button[type='submit']");

        function validar() {
            let valido = true;

            $form.find("[required]").each(function () {
                const $el = $(this);

                if ($el.is(":disabled")) return;

                if ($el.is(":checkbox, :radio")) {
                    const name = $el.attr("name");
                    if (!$form.find(`[name='${name}']:checked`).length) {
                        valido = false;
                        return false;
                    }
                } else if (!$el.val()) {
                    valido = false;
                    return false;
                }
            });

            $submit.prop("disabled", !valido);
        }

        $form.on("keyup change", "input, select, textarea", validar);
        validar();
    });
});
