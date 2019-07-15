import SmoothScroll from 'smooth-scroll';

window.addEventListener('load', (event) => {
  if (window.scrollY === 0 && [...document.querySelectorAll('[data-scroll-content-hero]')].length) {
    let scroll = new SmoothScroll(null, {
      speed: 500,
      easing: 'easeOutQuad',
    });

    scroll.animateScroll(42); // magic number so the arrow is properly visible
  }
});
