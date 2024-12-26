(function (Drupal) {
  'use strict';

  Drupal.behaviors.swiperInit = {
    attach: function (context, settings) {
      once('swiperInit', '.mySwiper', context).forEach(function (element) {
        new Swiper(element, {
          observer: true,
          observeParents: true,
          navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
          },
          pagination: {
            el: ".swiper-pagination",
          },
          spaceBetween: 16,
          breakpoints: {
            1: {
              slidesPerView: 1,
            },
            640: {
              slidesPerView: 2,
            },
            1200: {
              slidesPerView: 3,
            }
          }
        });
      });
    }
  };
})(Drupal);
