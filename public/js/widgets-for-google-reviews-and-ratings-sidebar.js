(function ($) {
    'use strict';
    $(document).ready(function () {
        const $slider = $('.repocean-sidebar-main .slider-start').slick({
            dots: false,
            prevArrow: '<button class="slide-arrow prev-arrow"></button>',
            nextArrow: '<button class="slide-arrow next-arrow"></button>',
            infinite: true,
            autoplay: true,
            speed: 700,
            autoplaySpeed: 6100,
            slidesToShow: 1,
            slidesToScroll: 1,
            adaptiveHeight: true
        });
        $(".prev-btn").on('click', () => $slider.slick("slickPrev"));
        $(".next-btn").on('click', () => $slider.slick("slickNext"));
        $slider.on("afterChange", function (event, slick, currentSlide) {
            $(".prev-btn").toggleClass("slick-disabled", currentSlide === 0);
            $(".next-btn").toggleClass("slick-disabled", currentSlide === slick.slideCount - 1);
        });
        $(".prev-btn").addClass("slick-disabled");
        $('.repocean-sidebar-main .button-content .readmore-button a').on('click', function (event) {
            event.preventDefault();
            const $this = $(this);
            const $description = $this.closest('.bottom-part-inner').find('.description');
            const isExpanded = $description.hasClass('expanded');
            $description.toggleClass('expanded').css('max-height', isExpanded ? '40px' : $description[0].scrollHeight + 'px');
            $this.text(isExpanded ? 'Read more' : 'Hide');
            $slider.slick('refresh');
        });
    });
})(jQuery);
