"use strict";

module.exports = {
  "env": {
    "browser": true,
  },
  "extends": ["google"],
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
};
