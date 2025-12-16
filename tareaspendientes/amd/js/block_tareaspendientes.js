define(['jquery'], function($) {

    const SELECTOR = '#block-tareaspendientes-tasks';

    function init() {

        $(SELECTOR).on('click keydown', '.block-tareaspendientes-toggle', function(e) {
            if (e.type === 'click' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();

                const $toggle = $(this);
                const $list = $toggle.next('ul');

                $toggle.toggleClass('open');
                $list.slideToggle();

                $toggle.attr(
                    'aria-expanded',
                    $toggle.hasClass('open')
                );
            }
        });

        refresh();
    }

    function refresh() {
        setInterval(function() {
            $.get(
                M.cfg.wwwroot + '/blocks/tareaspendientes/ajax.php',
                function(html) {
                    $(SELECTOR).html(html);
                }
            );
        }, 60000);
    }

    return { init: init };
});


