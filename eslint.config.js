"use strict";

const {
  defineConfig,
  globalIgnores,
} = require("eslint/config");

const globals = require("globals");
const html = require("eslint-plugin-html");
const phpMarkup = require("eslint-plugin-php-markup");
const js = require("@eslint/js");

const {
  FlatCompat,
} = require("@eslint/eslintrc");

const compat = new FlatCompat({
  baseDirectory: __dirname,
  recommendedConfig: js.configs.recommended,
  allConfig: js.configs.all
});

module.exports = defineConfig([{
  "languageOptions": {
    sourceType: "script",
    globals: {
      ...globals.browser,
    },
  },

  "extends": compat.extends("google"),

  "plugins": {
    html,
    "php-markup": phpMarkup,
  },

  "rules": {
    "valid-jsdoc": "off",
    "no-invalid-this": "off",
    "camelcase": "off",
    "comma-dangle": "off",
    "guard-for-in": "off",
    "operator-linebreak": "off",
    "max-len": "off",

    "new-cap": ["error", {
      capIsNewExceptions: ["Error", "Warning", "Debug", "Polygon_calcArea", "Play", "Stop", "Panzoom"],
      newIsCapExceptionPattern: "^Asset..",
    }],

    "no-array-constructor": "off",

    "no-unused-vars": ["error", {
      "vars": "local",
      "args": "none",
      "ignoreRestSiblings": false,
    }],

    "no-var": "off",
    "prefer-rest-params": "off",
    "quotes": "off",
    "require-jsdoc": "off",
    "spaced-comment": "off",
  },

  "settings": {
    "php/php-extensions": [".php"],

    "php/markup-replacement": {
      "php": "",
      "=": "0",
    },

    "php/keep-eol": false,
    "php/remove-whitespace": false,
    "php/remove-empty-line": false,
    "php/remove-php-lint": false,
  },
}, {
  "files": ["**/*.*php"],

  "rules": {
    "eol-last": "off",
    "indent": "off",
  },
}, globalIgnores([
  "**/*.min.js",
  "web/api/lib",
  "web/includes/csrf/",
  "web/js/videojs.zoomrotate.js",
  "web/js/video-stream.js",
  "web/js/video-rtc.js",
  "web/js/fontfaceobserver.standalone.js",
  "web/skins/classic/js/bootstrap-4.5.0.js",
  "web/skins/classic/js/bootstrap.bundle.min.js",
  "web/skins/classic/js/bootstrap-table-1.23.5",
  "web/skins/classic/js/chosen",
  "web/skins/classic/js/dateTimePicker",
  "web/skins/classic/js/jquery-*.js",
  "web/skins/classic/js/jquery-ui-*",
  "web/skins/classic/js/jquery.js",
  "web/skins/classic/js/moment.js",
  "web/skins/classic/js/video.js",
  "web/skins/classic/assets",
  "web/js/janus.js",
  "web/js/ajaxQueue.js",
  "web/js/hls-1.6.13/",
  "web/js/noUiSlider-15.8.1/",
  "web/js/dms.js",
  "web/skins/classic/includes/export_functions.php",
  "web/skins/classic/views/events.php",
])]);
