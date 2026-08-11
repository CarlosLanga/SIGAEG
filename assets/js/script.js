function showNotification(message, isSuccess = false) {
    const notif = document.createElement('div');
    notif.className = `notification-popup ${isSuccess ? 'success' : ''}`;
    notif.innerHTML = `
        <span class="notif-icon">${isSuccess ? '✓' : '✕'}</span>
        <span class="notif-text">${message}</span>
        <button class="close-btn">&times;</button>
        <div class="progress-bar"></div>
    `;
    
    document.body.appendChild(notif);
    
    notif.querySelector('.close-btn').addEventListener('click', () => {
        notif.remove();
    });
    
    setTimeout(() => notif.remove(), 4000);
}

function showForm(formId) {
    $(".form-box").removeClass("active");
    $("#" + formId).addClass("active");
    $(".error-placeholder").html("");
}
 
$(document).ready(function() {
    $('form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const container = form.closest('.form-box');
        const btn = form.find('button[type="submit"]');

        const isLogin = btn.attr('name') === 'login' || form.parent().attr('id') === 'login-form';
        btn.prop('disabled', true).text('A processar...');

        $.ajax({
            url: './api/entrar_cadastrar.php',
            method: 'POST',
            data: form.serialize() + (isLogin ? '&login=1' : '&register=1'), 
            success: function(response) {
                console.log("Resposta do Servidor:", response); 
            
                let res = response.trim();
            
                if (res.includes("sucesso_admin")) {
                    showNotification("Sessão iniciada com êxito!", true);
                    setTimeout(() => window.location.href = 'pages/admin/dashboard.php', 500);
                } else if (res.includes("sucesso_formador")) {
                    showNotification("Sessão iniciada com êxito!", true);
                    setTimeout(() => window.location.href = 'pages/formador/dashboard.php', 500);
                } else if (res.includes("sucesso_formando")) {
                    showNotification("Sessão iniciada com êxito!", true);
                    setTimeout(() => window.location.href = 'pages/formando/dashboard.php', 500);
                } else if (res.includes("sucesso_encarregado")) {
                    showNotification("Sessão iniciada com êxito!", true);
                    setTimeout(() => window.location.href = 'pages/encarregado/dashboard.php', 500);
                } else if (res.includes("sucesso_user")) {
                    showNotification("Sessão iniciada com êxito!", true);
                    setTimeout(() => window.location.href = 'pages/formando/dashboard.php', 500);
                } else if (res.includes("sucesso_cadastrado")) {
                    showNotification("Cadastro realizado com êxito! Por favor, inicie sessão.", true);
                    setTimeout(() => showForm('login-form'), 1500);
                } else {
                    showNotification(res, false);
                }
                btn.prop('disabled', false).text(isLogin ? 'Entrar' : 'Cadastrar');
            },
            error: function(xhr) {
                console.error("Erro AJAX:", xhr.responseText);
                showNotification("Erro crítico de conexão.", false);
                btn.prop('disabled', false).text(isLogin ? 'Entrar' : 'Cadastrar');
            }
        });
    })

    $(document).on("click", ".toggle-pass", function() {
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
})