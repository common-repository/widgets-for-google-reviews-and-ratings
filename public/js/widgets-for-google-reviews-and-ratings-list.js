(function ($) {
    'use strict';
    $(document).ready(function () {
        let listBoxes = $('.list-box');
        let itemsToShow = 10;
        let visibleItems = 0;
        listBoxes.slice(0, itemsToShow).each(function (index, element) {
            setTimeout(function () {
                $(element).addClass('show');
            }, index * 100);
        });
        visibleItems += itemsToShow;
        $('#loadMoreList').on('click', function () {
            let nextItems = listBoxes.slice(visibleItems, visibleItems + itemsToShow);
            nextItems.each(function (index, element) {
                setTimeout(function () {
                    $(element).addClass('show');
                }, index * 100);
            });
            visibleItems += itemsToShow;
            if (visibleItems >= listBoxes.length) {
                $(this).hide();
            }
        });
        $('.repocean-list-main .button-content .readmore-button a').on('click', function (event) {
            event.preventDefault();
            const $description = $(this).closest('.list-box-inner').find('.description');
            const fullHeight = $description[0].scrollHeight + 'px';
            $description.toggleClass('expanded').css('max-height', $description.hasClass('expanded') ? fullHeight : '40px');
            $(this).text($description.hasClass('expanded') ? 'Hide' : 'Read more');
        });
    });
})(jQuery);
