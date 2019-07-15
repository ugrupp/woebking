import Headroom from 'headroom.js';

export default class Topbar {
  constructor(menu) {
    document.addEventListener('DOMContentLoaded', (event) => {
      this.menu = menu;
      this.topbar = document.querySelector('[data-topbar]');

      if (this.topbar) {
        // Headroom
        let headroom = new Headroom(this.topbar, {
          // vertical offset in px before element is first unpinned
          offset: this.topbar.offsetHeight,
          // scroll tolerance in px before state changes
          tolerance: 0,
          classes: {
            // when element is initialised
            initial: 'c-topbar--headroom-initialized',
            // when scrolling up
            pinned: 'c-topbar--pinned',
            // when scrolling down
            unpinned: 'c-topbar--unpinned',
            // when above offset
            top: 'c-topbar--top',
            // when below offset
            notTop: 'c-topbar--not-top',
            // when at bottom of scoll area
            bottom: 'c-topbar--bottom',
            // when not at bottom of scroll area
            notBottom: 'c-topbar--not-bottom',
          },
          onUnpin: () => {
            this.menu && this.menu.close();
          },
        });

        headroom.init();

        // update topbar mode (normal/compact)
        // window.addEventListener('scroll', this.updateTopbarMode.bind(this), false);
        // this.updateTopbarMode();
      }
    });
  }

  updateTopbarMode() {
    if (window.pageYOffset >= 100) {
      this.topbar.classList.add('c-topbar--compact');
    } else {
      this.topbar.classList.remove('c-topbar--compact');
    }
  }
}
