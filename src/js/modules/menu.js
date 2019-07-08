export default class Menu {
  constructor() {
    this.inited = false;

    document.addEventListener('DOMContentLoaded', (event) => {
      this.el = document.querySelector('[data-menu]');

      // init success depends on root element being found
      if (this.el) {
        this.inited = true;
        this.togglers = [...document.querySelectorAll('[data-menu-toggler]')];
        this.links = [...this.el.querySelectorAll('[data-menu-link]')];
      } else {
        console.error('Error: Menu could not be initialized.'); // eslint-disable-line no-console
        return this;
      }

      this.initTogglers();
      this.initOutsideClick();
      this.initCloseOnLinkClick();
    });

    return this;
  }

  // Helper method to determine if overlay has been inited. Should be called by all public methods.
  isInited() {
    if (!this.inited) {
      console.error('Error: Tried to call menu method prior to initialization.'); // eslint-disable-line no-console
      return false;
    }
    return true;
  }

  initTogglers() {
    // openers
    this.togglers.forEach((toggler, idx) => {
      toggler.addEventListener('click', (e) => {
        if (this.isInited()) {
          this.toggle();
          e.preventDefault();
          e.stopPropagation();
          return false;
        }
      }, false);
    });
  }

  // close overlay on click "anywhere"
  initOutsideClick() {
    document.addEventListener('click', (e) => {
      if (this.el !== e.target && !this.el.contains(e.target)) {
        this.close();
      }
    });
  }

  // close overlay when link has been clicked
  initCloseOnLinkClick() {
    this.links.forEach((link) => {
      link.addEventListener('click', () => {
        this.close();
      });
    });
  }

  // Toggles overlay
  toggle() {
    if (this.isInited()) {
      this.el.classList.contains('is-open') ? this.close() : this.open();
    }
  }

  // Opens overlay
  open() {
    if (this.isInited()) {
      this.el.classList.add('is-open');
      document.body.classList.add('is-menu-open');
    }
  }

  // Closes overlay
  close() {
    if (this.isInited()) {
      this.el.classList.remove('is-open');
      document.body.classList.remove('is-menu-open');
    }
  }
}
