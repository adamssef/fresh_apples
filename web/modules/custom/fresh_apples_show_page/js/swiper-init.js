(function (Drupal) {
  'use strict';

  Drupal.behaviors.swiperInit = {
    attach: function (context, settings) {
      once('swiperInit', '.mySwiper', context).forEach(function (element) {
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
          slidesPerView: 3,
          sildesPerGroup: 4,
          centeredSlides: true,
          loop: true
        });
      });
    }
  };
})(Drupal);
