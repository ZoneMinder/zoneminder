// When using a bundler such as Webpack,
// include both noUiSlider and its stylesheet.
// Either import the default:
import noUiSlider from 'nouislider';
// Or the namespace:
import * as noUiSlider from 'nouislider';
import 'nouislider/dist/nouislider.css';

// Alternatively, you can include both files:
&lt;link href="nouislider.css" rel="stylesheet"&gt;
&lt;script src="nouislider.js"&gt;&lt;/script&gt;

// You can also use the ES6 module distribution
&lt;script type="module"&gt;
import noUiSlider from 'nouislider/dist/nouislider.mjs';
&lt;/script&gt;
