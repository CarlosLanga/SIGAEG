$(function () {
    const $cards = $(".summary-card");
    const $container = $(".summary-cards");

    function animateCount($el, target) {
        const duration = 900;
        const startTime = performance.now();

        function step(now) {
            const progress = Math.min((now - startTime) / duration, 1);
            const value = Math.floor(target * progress);
            $el.text(value);
            if (progress < 1) requestAnimationFrame(step);
        }

        requestAnimationFrame(step);
    }

    $cards.on("mouseenter", function () {
        $(this).addClass("hover");
    }).on("mouseleave", function () {
        $(this).removeClass("hover");
    });

    if (!$container.length) return;

    const base = $container.data("base-url") || $("body").data("base-url") || "";

    $.getJSON(`${base}api/dashboard_stats.php`, function (data) {
        if (!data || !data.ok) return;

        $cards.each(function () {
            const $card = $(this);
            const key = $card.data("key");
            const value = data[key] ?? 0;
            const $valueEl = $card.find(".summary-value");
            animateCount($valueEl, value);
        });
    });
});
