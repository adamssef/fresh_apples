(function (Drupal) {
  'use strict';

  Drupal.behaviors.swiperInit = {
    attach: function (context, settings) {
      once('swiperInit', '.mySwiper', context).forEach(function (element) {
        console.log('swiper-init.js loaded');
        new Swiper(element, {
          slidePerGroup: 1,
          navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
          },
          pagination: {
            el: ".swiper-pagination",
            clickable: true,
            dynamicBullets: true,
          },
          spaceBetween: 16,
          slidesPerView: 1,
          slidesPerGroup: 1,
          centeredSlides: true,
          loop: false
        });
      });
    }
  };
})(Drupal);
