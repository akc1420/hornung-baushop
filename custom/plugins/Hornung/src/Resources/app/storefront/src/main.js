// import MagnifierPlugin from "src/plugin/magnifier/magnifier.plugin";
// MagnifierPlugin.options.zoomFactor = 1;

const gallerySlider = document.getElementsByClassName("gallery-slider-col col order-1 order-md-2");

if(gallerySlider.length > 0) {
    if(window.outerWidth < 778) {
        gallerySlider[0].removeAttribute("data-zoom-modal")
    }
}

window.addEventListener('load', (event) => {

  let header = $(".header-main");

  window.onscroll = () => {
      if (
          !header.hasClass("sticky") &&
          document.documentElement.scrollTop > 150
      ) {
          header.addClass("sticky");
          console.log('added sticky header class');
          setHeaderTimer();
      }
  
      if (
          header.hasClass("sticky") &&
          document.documentElement.scrollTop < 150
      ) {
          header.removeClass("sticky");
          console.log('removed sticky header class');
      }
  };

  function setHeaderTimer() {
      console.log('setHeaderTimer triggerd');
      header.addClass("sticky-force");
      console.log('added force sticky header class');
      setTimeout(() => {
          header.removeClass("sticky-force");
          console.log('removed force sticky header class');
      }, 3000);
  }

  $('body').addClass("is-init");
  console.log('page is fully loaded');
});