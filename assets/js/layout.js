$(document).ready(function() {
    const $sidebar = $('#sidebar');
    const $toggleBtn = $('#toggle-sidebar');
    const $profileInfo = $('#profile-info');
    let openDropdown = null;

    $toggleBtn.on('click', function() {

        const isCollapsed = $sidebar.toggleClass('collapsed').hasClass('collapsed');

        const $icon = $(this).find('i');

        $icon.toggleClass('fa-bars-staggered fa-bars');

        if(isCollapsed) {
            openDropdown = $('.has-dropdown.open');
            
            $profileInfo.hide(); 
            $('.has-dropdown').removeClass('open');
            $('.submenu').hide();
        } else {
            $('.submenu').removeAttr('style');
            if (openDropdown && openDropdown.length) {
                openDropdown.addClass('open');
            }
            $(".has-dropdown.open > .submenu").show();
            openDropdown = null;
        }

        const estado = isCollapsed ? "colapsada" : "expandida";
        $.post(`${$("body").data("base-url")}api/user_preferencias.php`, { sidebar: estado });

    });

    $('.has-dropdown > a').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if($sidebar.hasClass('collapsed')) return;

        const $parent = $(this).parent();
        const $submenu = $(this).next('.submenu');

        if(isMobile) {
            $parent.toggleClass('open');
            $submenu.stop().slideToggle(250);
            return;
        }
        
        $parent.toggleClass('open');
        $submenu.slideToggle(250);

        if($parent.hasClass('open')) {
            openDropdown = $parent;
        } else {
            openDropdown = null;
        }
    });

    $('#btn-profile').on('click', function() {
        if($sidebar.hasClass('collapsed')) {
            $sidebar.removeClass('collapsed');
            $toggleBtn.find('i').addClass('fa-bars-staggered').removeClass('fa-bars');
            $profileInfo.show();
        } else {
            $profileInfo.stop().slideToggle(200);
        }
    }); 

    $('#btn-fullscreen').on('click', function() {
        const $icon = $(this).find('i');
        if(!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
            $icon.removeClass('fa-maximize').addClass('fa-minimize');
        } else if (document.exitFullscreen) {
            document.exitFullscreen();
            $icon.removeClass('fa-minimize').addClass('fa-maximize');
        }
    });

    function updateThemeIcon() {
        const $btns = $(".toggle-theme");
        const isDark = $("body").hasClass("dark");
        $btns.find("i").removeClass("fa-moon fa-sun")
            .addClass(isDark ? "fa-sun" : "fa-moon");

        $(".brand-logo").each(function () {
            const $logo = $(this);
            const nextSrc = isDark ? $logo.data("dark-logo") : $logo.data("light-logo");
            if (nextSrc) {
                $logo.attr("src", nextSrc);
            }
        });
    }

    $(".toggle-theme").on("click", function() {
        $("body").toggleClass("dark");
        const tema = $("body").hasClass("dark") ? "dark" : "light";

        $.post(`${$("body").data("base-url")}api/user_preferencias.php`, { tema });

        updateThemeIcon();
    });

    updateThemeIcon();

    let isMobile = false;
    let mobileMenuOpen = false;
    let mobileProfileOpen = false;

    function checkMobile() {
        isMobile = window.innerWidth <= 768;
        $("body").toggleClass("mobile-view", isMobile);
    }

    $(window).on("resize", checkMobile);
    checkMobile();

    $("#toggle-menu-mobile").on("click", function() {
        if ($sidebar.hasClass("collapsed")) {
            $sidebar.removeClass("collapsed");
            $("#toggle-sidebar i").addClass("fa-bars-staggered").removeClass("fa-bars");
        }

        if(mobileProfileOpen) closeProfile();
        toggleMenu();
    })

    $("#btn-profile-mobile").on("click", function() {
        if(mobileMenuOpen) closeMenu();
        toggleProfile();
    })

    function toggleMenu() {
        mobileMenuOpen = !mobileMenuOpen;
        $("body").toggleClass("menu-open", mobileMenuOpen);
    }

    function toggleProfile() {
        mobileProfileOpen = !mobileProfileOpen;
        $("body").toggleClass("profile-open", mobileProfileOpen);
    }

    function closeMenu() {
        mobileMenuOpen = false;
        $("body").removeClass("menu-open");
    }

    function closeProfile() {
        mobileProfileOpen = false;
        $("body").removeClass("profile-open");
    }

    $("#mobile-backdrop").on("click", function() {
        closeMenu();
        closeProfile();
    })

    $(".sidebar a").on("click", function (e) {
        if (!isMobile) return;

        const $link = $(this);
        const $parent = $link.parent();

        if ($parent.hasClass("has-dropdown")) {
            e.preventDefault();
            return;
        }

        closeMenu()
    })

    $(".sidebar").on("click", function(e) {
        e.stopPropagation();
    });

    $(".mobile-profile-panel").on("click", function(e) {
        e.stopPropagation();
    });

    // ==========================================
    // Sistema de Notificações
    // ==========================================
    const $desktopBadge = $('#badge-desktop');
    const $mobileBadge = $('#badge-mobile');
    const $banner = $('#global-notif-banner');
    const $bannerText = $('#global-notif-text');
    const $mobileNotifText = $('#mobile-notif-text');

    $('#global-notif-close').on('click', function() {
        $banner.slideUp(400);
    });

    function updateBadges(count) {
        if (count > 0) {
            $desktopBadge.text(count > 99 ? '99+' : count).show();
            $mobileBadge.text(count > 99 ? '99+' : count).show();
            $mobileNotifText.html(`<strong>${count}</strong> novas notificações`);
        } else {
            $desktopBadge.hide();
            $mobileBadge.hide();
            $mobileNotifText.text('Notificações');
        }
    }

    function checkNotifications() {
        $.ajax({
            url: `${$("body").data("base-url")}api/notificacoes_check.php`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.ok) {
                    updateBadges(res.unread_count);
                    
                    if (res.new_notifications && res.new_notifications.length > 0) {
                        // Mostra a última notificação
                        const latest = res.new_notifications[res.new_notifications.length - 1];
                        $bannerText.text(latest.mensagem);
                        
                        $banner.slideDown(400);
                    }
                }
            }
        });
    }

    // Iniciar verificação imediatamente e depois a cada 15s
    checkNotifications();
    setInterval(checkNotifications, 15000);
});
