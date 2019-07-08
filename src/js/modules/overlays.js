class Overlay {
  constructor(el) {
    this.el = el;

    // init success depends on DOM elements being found
    if (this.el) {
      this.initTogglers();
    }
  }

  initTogglers() {
    // closers
    let closers = [...this.el.querySelectorAll('[data-overlay-close]')];
    closers.forEach((closer) => {
      closer.addEventListener('click', (e) => {
        this.close();

        e.preventDefault();
        e.stopPropagation();
        return false;
      });
    });

    // openers
    let openers = [...document.querySelectorAll(`[data-overlay-opener]`)];
    openers.forEach((opener) => {
      if (opener.getAttribute('href').replace('#', '') === this.el.id) {
        opener.addEventListener('click', (e) => {
          this.open();

          e.preventDefault();
          e.stopPropagation();
          return false;
        });
      }
    });
  }

  // Toggles overlay
  toggle() {
    this.el.classList.contains('is-open') ? this.close() : this.open();
  }

  // Opens overlay
  open() {
    this.el.classList.add('is-open');
    document.body.classList.add('is-overlay-open');
  }

  // Closes overlay
  close() {
    this.el.classList.remove('is-open');
    document.body.classList.remove('is-overlay-open');
  }
}


export default class Overlays {
  constructor() {
    document.addEventListener('DOMContentLoaded', (event) => {
      this.overlays = [...document.querySelectorAll('[data-overlay]')];

      this.overlays.forEach((overlayEl) => {
        new Overlay(overlayEl);
      });
    });
  }
}
