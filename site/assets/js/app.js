//
// JS entry file
// --------------------------------------------------

// import svgs to build sprite
const svgs = import.meta.globEager('../svg/*.svg')
svgs;

// module imports
import './modules/fontfaceobserver';
import './modules/initial-scroll';
import './modules/scroll-to';

import Menu from './modules/menu';
import Topbar from './modules/topbar';
import Overlays from './modules/overlays';
import TerraliftForm from './modules/terralift-form';

// init modules
let menu = new Menu();
new Topbar(menu);
new Overlays();
new TerraliftForm();
