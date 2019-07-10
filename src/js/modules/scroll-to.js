import SmoothScroll from 'smooth-scroll';

document.addEventListener('DOMContentLoaded', (event) => {
  new SmoothScroll('[data-scroll-top]', {
    speed: 500,
    easing: 'easeOutQuad',
  });

  new SmoothScroll('[data-scroll-content]', {
    speed: 500,
    easing: 'easeOutQuad',
  });
});
