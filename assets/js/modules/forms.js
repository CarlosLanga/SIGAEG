$(document).on("click", ".modal.open", function (e) {
    if ($(e.target).is(".modal")) {
        $(this).removeClass("open");
    }
});

$(function () {
    $("form[data-ajax='true']").on("submit", function (e) {
        e.preventDefault();

        const $form = $(this);
        const url = $form.attr("action");
        const entity = $form.data("entity") || "registo";
        const entityLabel = $form.data("entityLabel") || entity;
        const entityTitle = $form.data("entityTitle") || (entity.charAt(0).toUpperCase() + entity.slice(1));
        const successMsg = $form.data("successMsg") || `${entityTitle} adicionado com êxito!`;
        const errorMsg = $form.data("errorMsg") || `Erro ao adicionar ${entityLabel}.`;
        const existingMsg = $form.data("existingMsg") || `${entityTitle} já existente!`;

        $.post(url, $form.serialize())
            .done(function (res) {
                res = $.trim(res);

                if (res === "sucesso") {
                    showNotification(successMsg, true);
                    $form[0].reset();
                    $form.trigger("form:reset");
                } else if (res === "turma_existente" || res === "registro_existente") {
                    showNotification(existingMsg, false);
                } else if (res === "erro_codigo") {
                    showNotification(`Erro ao gerar código de convite.`, false);
                } else if (res === "erro_codigo_formando") {
                    showNotification(`O código digitado já está associado a outro formando.`, false);
                } else if (res === "erro_codigo_formador") {
                    showNotification(`O código digitado já está associado a outro formador.`, false);
                } else if (res === "erro_encarregado") {
                    showNotification(`Erro ao adicionar encarregado. Por favor, preencha todos os campos.`, false);
                } else if (res === "erro_conexao") {
                    showNotification(`Falha de conexão ao adicionar ${entity}.`, false);
                } else if (res === "datas_invalidas") {
                    showNotification(`As datas são inválidas. Verifique início e conclusão.`, false);
                } else if (res === "erro_data_nascimento") {
                    showNotification(`Informe a data de nascimento no formato dd.mm.aaaa.`, false);
                } else if (res === "conflito_turma") {
                    showNotification(`A turma já possui horário nesse intervalo.`, false);
                } else if (res === "conflito_formador") {
                    showNotification(`O formador já possui horário nesse intervalo.`, false);
                } else {
                    showNotification(errorMsg, false);
                }
            })
            .fail(function () {
                showNotification(errorMsg, false);
            });
    });
});
