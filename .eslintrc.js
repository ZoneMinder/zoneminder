"use strict";

module.exports = {
  "env": {
    "browser": true,
  },
  "extends": ["google"],
  "overrides": [{
    // eslint-plugin-html handles eol-last slightly different - it applies to
    // each set of script tags, so we turn it off here.
    "files": "**/*.*php",
    "rules": {
      "eol-last": "off",
      "indent": "off",
    },
  }],
  "plugins": [
    "html",
    "php-markup",
  ],
  "rules": {
    "camelcase": "off",
    "comma-dangle": "off",
    "guard-for-in": "off",
    "max-len": "off",
    "new-cap": ["error", {
      capIsNewExceptions: ["Error", "Warning", "Debug", "Polygon_calcArea", "Play", "Stop"],
      newIsCapExceptionPattern: "^Asset\.."
    }],
    "no-array-constructor": "off",
    "no-unused-vars": ["error", {
      "vars": "local",
      "args": "none",
      "ignoreRestSiblings": false
    }],
    "no-var": "off",
    "prefer-rest-params": "off",
    "quotes": "off",
    "require-jsdoc": "off",
    "spaced-comment": "off",
  },
  "settings": {
    "php/php-extensions": [".php"],
    "php/markup-replacement": {"php": "", "=": "0"},
    "php/keep-eol": false,
    "php/remove-whitespace": false,
    "php/remove-empty-line": false,
    "php/remove-php-lint": false
  },
};
