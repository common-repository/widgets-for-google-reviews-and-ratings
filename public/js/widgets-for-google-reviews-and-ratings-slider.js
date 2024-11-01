(function ($) {
    'use strict';
    $(document).ready(function () {
        const calculateSlidesToShow = () => Math.max(Math.floor($('.repocean-content-wrapper').width() / 305), 1);
        const initializeSlider = () => {
            const slidesToShow = calculateSlidesToShow();
            $('.repocean-slider-box-parent').slick({
                dots: false,
                autoplay: true,
                infinite: true,
                autoplaySpeed: 6100,
                prevArrow: '<button class="slide-arrow prev-arrow"></button>',
                nextArrow: '<button class="slide-arrow next-arrow"></button>',
                speed: 700,
                slidesToShow: slidesToShow,
                slidesToScroll: 1,
                variableWidth: false
            });
        };
        initializeSlider();
        $(window).on('resize', () => {
            $('.repocean-slider-box-parent').slick('unslick');
            initializeSlider();
        });
        setTimeout(() => {
            $('.slider-box-inner, .repocean-footer').show();
        }, 1);
    });
    $('.repocean-slider-main .button-content .readmore-button a').on('click', function (event) {
        event.preventDefault();
        const $description = $(this).closest('.slider-box-inner').find('.description');
        const fullHeight = $description[0].scrollHeight + 'px';
        $description.toggleClass('expanded').css('max-height', $description.hasClass('expanded') ? fullHeight : '40px');
        $(this).text($description.hasClass('expanded') ? 'Hide' : 'Read more');
    });
    document.addEventListener('DOMContentLoaded', () => {
        const reviewBox = document.querySelector('.review-box');
        reviewBox.style.setProperty('--rating', reviewBox.getAttribute('data-rating'));
    });
})(jQuery);
