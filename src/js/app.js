//
// JS entry file
// --------------------------------------------------

// SVG <use> polyfill
import 'svgxuse';

// Babel polyfill
// This import will transpile to single core-js module imports. Only the polyfills needed for our target browsers will be imported.
// Powered by `useBuiltIns: usage` in .babelrc
import 'babel-polyfill'; // eslint-disable-line import/no-unresolved

// module imports
import './modules/object-fit-images';
import './modules/fontfaceobserver';
import './modules/initial-scroll';
import './modules/scroll-to';

import Menu from './modules/menu';
import Topbar from './modules/topbar';
import Overlays from './modules/overlays';

// init modules
let menu = new Menu();
new Topbar(menu);
new Overlays();
