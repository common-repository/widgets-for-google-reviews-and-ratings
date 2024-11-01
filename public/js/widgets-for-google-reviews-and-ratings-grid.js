(function ($) {
    'use strict';
    $(document).ready(function () {
        let gridBoxes = $('.grid-box');
        let itemsToShow = 9;
        let visibleItems = 0;
        gridBoxes.slice(0, itemsToShow).each(function (index, element) {
            setTimeout(function () {
                $(element).addClass('show');
            }, index * 100);
        });
        visibleItems += itemsToShow;
        $('#loadMore').on('click', function () {
            let nextItems = gridBoxes.slice(visibleItems, visibleItems + itemsToShow);
            nextItems.each(function (index, element) {
                setTimeout(function () {
                    $(element).addClass('show');
                }, index * 100);
            });
            visibleItems += itemsToShow;
            if (visibleItems >= gridBoxes.length) {
                $(this).hide();
            }
        });
        $('.repocean-grid-main .button-content .readmore-button a').on('click', function (event) {
            event.preventDefault();
            const $description = $(this).closest('.grid-box-inner').find('.description');
            const fullHeight = $description[0].scrollHeight + 'px';
            $description.toggleClass('expanded').css('max-height', $description.hasClass('expanded') ? fullHeight : '40px');
            $(this).text($description.hasClass('expanded') ? 'Hide' : 'Read more');
        });
    });
})(jQuery);
