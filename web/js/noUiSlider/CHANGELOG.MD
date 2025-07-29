# Changelog

### 15.8.1 (*2024-06-21*)
- Fixed: Typescript definitions for `getTooltips` and `getOrigins` (#1273);

### 15.8.0 (*2024-06-10*)
- Added: Allow `connect` option to get updated (#1272);
- Added: `invert-connects` behaviour (#1262, #1272);

### 15.7.2 (*2024-05-14*)
- Added: `getPositions` to Typescript definitions (#1270);
- Added: Allow `null` in `set` Typescript definitions (#1271);

### 15.7.1 (*2023-06-14*)
- Added: `module` field to `package.json` (#1247, #1248);

### 15.7.0 (*2023-02-06*)
- Added: `disable` and `enable` methods to disable slider or handles (#1158, #1227);

### 15.6.1 (*2022-08-12*)
- Fixed: (Typescript) type for keys in `range` (#1218);

### 15.6.0 (*2022-05-01*)
- Added: `smooth-steps` `behaviour` flag (#627);

### 15.5.1 (*2022-01-21*)
- Added: `dist` to release (#1148);
- Updated release tooling (#1173, #1174);

### 15.5.0 (*2021-09-01*)
- Fixed: Compositing issues in Safari **[this is a Potential BC-break when using a custom stylesheet!]** (#987, #998, #1029, #1154);
- Updated: example on "merging overlapping tooltips" to support this change;
- Changed: divisibility of `margin`, `padding` and `limit` by `step` is no longer enforced (#1163);

### 15.4.0 (*2021-08-14*)
- Added: `handleAttributes` option (#1019);
- Added: `drag-all` behaviour (#756, #841, #1116);
- Added: ESM/ES6 module distribution `dist/nouislider.mjs` (#1149);
- Added: support for ranges where `min` = `max` (#236, #359, #578, #633, #676, #790);
- Added: `getPositions` method;
- Fixed: Duplicate change/set events when using `snap` behaviour (#1106);

### 15.3.0 (*2021-08-10*)
- Added: `keyboardMultiplier` option (#1151);

### 15.2.0 (*2021-06-15*)
- Added: `unencoded` boolean argument to `get` method to return raw slider values (#1145);
- Added: support for "partial" formatters in `pips.format`, `ariaFormat` and `tooltips` options (#1140);

### 15.1.1 (*2021-05-09*)
- Fixed: `tooltips` option accepts one `Formatter` for all tooltips (#1101);

### 15.1.0 (*2021-05-09*)
- Added: `drag` event when dragging connecting elements (#887, #1135);

### 15.0.0 (*2021-05-02*)
- noUiSlider is now written and distributed as TypeScript;
- Types are now provided. If you are currently using [@types/nouislider](https://www.npmjs.com/package/@types/nouislider), this should be replaced;
- The distributed files have moved from `distribute` to `dist` in the NPM package, and are no longer in the repository; You may need to change the path to the noUiSlider CSS file if you are importing it using a package manager;
- Fixed: Slider now properly works in multiple layers of Shadow DOM (#1121);
- Removed: `version` from exceptions and export;
- Removed: Bower support;

### 14.7.0 (*2021-04-06*)
- noUiSlider is now build with TypeScript. This release contains no further changes.

### 14.6.4 (*2021-03-18*)
- Fixed: Fixed `updateOptions` with falsy value for `start` (#1127);

### 14.6.3 (*2020-11-19*)
- Fixed: Fixed removing namespaced event listeners, internal listeners getting removed (#1109);

### 14.6.2 (*2020-09-16*)
- Fixed: Ignore erroneous mouse events on taps for iOS 13.4 (#1095);
- Added: `exactInput` argument to `set` and `setHandle` methods (#436, #1094);

### 14.6.1 (*2020-08-17*)
- Fixed: Pips in count mode ignores pip at end of range (#1088);

### 14.6.0 (*2020-06-27*)
- Added: `keyboardPageMultiplier` and `keyboardDefaultStep` options (#1083);
- Fixed: Ignore erroneous tap events for iOS (#1057, #1079);

### 14.5.0 (*2020-05-20*)
- Added: Support for `margin`, `padding` and `limit` on non-linear sliders (#911, #1030, #1031, #1071);

### 14.4.0 (*2020-05-06*)
- Added: `getOrigins` and `getTooltips` methods;
- Added: Default styling to support merging overlapping tooltips (#1032);

### 14.3.0 (*2020-05-05*)
- Added: Default `cssClasses` are now exposed and can be modified;
- Fixed: Destroying sliders with multiple classes in `cssClasses` fails (#1069);

### 14.2.0 (*2020-03-27*)
- Added: Slider api as event parameter (#1058);
- Added: Allow multiple classes in `cssClasses` option (#1054);
- Fixed: Slider not working within shadow DOM (#1060);
- Fixed: Last pip not rendered if it is also the first and at the slider edge (#1063);

### 14.1.1 (*2019-12-15*)
- Fixed: Text direction is not correctly determined when the slider is not in the DOM (#1038);

### 14.1.0 (*2019-12-04*)
- Fixed: Styling requires a root `html` node, so noUiSlider can't be used in shadow dom (#1035);
- Added: Support for PageUp/PageDown and Home/End keys in keyboard support (#1036);

### 14.0.3 (*2019-10-10*)
- Fixed: Initialising handle values near the slider edge does not always respect `margin` (#1009);

### 14.0.2 (*2019-06-28*)
- Fixed: Keyboard interaction uses formatter when it does not need to (#1000);

### 14.0.1 (*2019-06-21*)
- Fixed: Visual regression in Safari caused by fixing #987 (#998);

### 14.0.0 (*2019-06-20*)
- Fixed: `change` & `slide` events should fire on keyboard control (#994);
- Fixed: `.noUi-origin` overflows document on vertical sliders (#987);
- Fixed: Clicking to right of handle doesn't move it when it's at the same point as another (#965);
- Added: Additional documentation on number formatting (#978, #985);

### 13.1.5 (*2019-04-24*)
- Fixed: Full-range padding (#880);

### 13.1.4 (*2019-03-20*)
- Fixed: Keyboard interaction does not work with `snap` option (#961);

### 13.1.3 (*2019-03-15*)
- Fixed: Keyboard interaction allows handles to "push" other handles (#960);
- Fixed: Update event fires for all handles during keyboard interaction (#960);

### 13.1.2 (*2019-03-13*)
- Fixed: Handle disappears in Safari on tap (#927);
- Fixed: Disabled slider still accepts keyboard interaction (#953);

### 13.1.1 (*2019-02-14*)
- Fixed: Slider hang when using a zero-length range (#948);

### 13.1.0 (*2019-02-08*)
- Fixed: Updating `pips` using `updateOptions` (#933);
- Added: Updating `tooltips` using `updateOptions` (#946);

### 13.0.0 (*2019-02-06*)
noUiSlider 13 does not include any breaking API changes.
Keyboard support is now built-in, so any custom implementations should be removed when upgrading.
Alternatively, built-in keyboard support can be disabled using `keyboardSupport: false`.
- Added: Built-in keyboard support (#724);
- Added: `.noUi-touch-area` element (#924);
- Fixed: Dragging a range does not check for handle disabled state (#938);
- Fixed: Incorrect CSS transform in pips (#931);

### 12.1.0 (*2018-10-25*)
- Added: `unconstrained` behaviour (#747, #815, #913);
- Added: `setHandle` API (#917);
- Changed: point to `nouislider.js` in `package.json`.`main` (#921);

### 12.0.0 (*2018-09-14*)
- Change: License changed to MIT;
- Change: Build process is now based on NPM scripts, phasing out the Grunt task runner.
- Fixed: Aria values are now as per spec (#889);
- Change: Pips formatting are now written as HTML (#875);
- Change: The `filter` option is now called for all pips (#754);
- Added: The `filter` option can now return `-1` to hide a pip (#754);
- Added: `keyboardSupport` option (#867, #895);
- Added: `documentElement` option (#821);

### 11.1.0 (*2018-04-02*)
- Change: `null` options are now handled consistently (#856);
- Fixed: Missing transform origin in IE9 (#870);
- Fixed: `padding` on one side of the slider could not exceed `50%` (#865);

### 11.0.3 (*2018-01-21*)
Refactor of source code. There are no meaningful changes in the distributed files;

### 11.0.2 (*2018-01-20*)
- Fixed: Slider ignores clicks on `.noUi-target` outside of `.noUi-base` (#842);
- Fixed: `.noUi-origin` moving out of the page causes horizontal scrolling (#852);
- Fixed: Relative `.noUi-handle` has unintended margin (#854);

### 11.0.0 (*2018-01-12*)
noUiSlider 11 does not include any breaking API changes.
Unless major changes were made to the stylesheet or you specifically depend
on the handle/connect order in the DOM, there should be no issues upgrading.
- Change: Use CSS transforms for handle movement, resulting in a massive performance improvement (#718);
- Change: Support multitouch by default;
- Change: Handle stacking is now on `.noUi-origin` instead of `.noUi-handle`;
- Added: A `.noUi-connects` element holding all `.noUi-connect` elements;
- Added: `[data-value]` property for `.noUi-value` in pips (#733);
- Added: `padding` option can now take an array for different padding values at both sides of a slider (#822);
- Removed: `useRequestAnimationFrame` option. No longer needed with CSS transforms;
- Removed: `multitouch` option. Now enabled by default;
- Fixed: Slider could ignore end events it should handle (#704, #805, #834);
- Fixed: Stop depending on array type (#801);
- Fixed: `set` method might bypass margin option (#823);
- Fixed: Alignment of pips for RTL sliders (#795);
- Fixed: Several issues regarding pips (#812, #826, #832);

### 10.1.0 (*2017-07-26*)
- Added: `multitouch` option (#793);

### 10.0.0 (*2017-05-28*)
- Change: Change event listeners to be passive (#785);
- Fixed: Pips are now updated when calling `updateOptions` (#669);
- Fixed: Content Security Policy issue with pips;
- Added: `removePips` method;
- Added: aria support (#685);
- Added: `ariaFormat` option (controls `aria-valuetext`);
- Fixed: throw a better error when mistakenly trying to initialize noUiSlider with `null` (#658);
- Fixed: Made order of events consistent and documented it (#775);
- Fixed: Border radius of connect bar, white space wrapping of tooltips (#773, #774);
- Fixed: Slider now uses `ownerDocument` instead of `document` (#767);

### 9.2.0 (*2017-01-17*)
- Added: Version number to exceptions;
- Added: `noUiSlider.version` holds current version number;
- Added: Throw exception on invalid `pips` configuration (#721);
- Added: Merged pull request that uses less preprocessor to generate CSS (#735);

### 9.1.0 (*2016-12-10*)
- Fixed: Slider not properly handling multitouch (#700, #704);
- Fixed: Removed a querySelector for the currently active handle (#720);
- Fixed: Removed iOS/webkit flashes on tap;
- Fixed: Incorrect error when using margin/limit with a step smaller than 0 (#736);
- Fixed: Drag option using incorrect cursor arrows (#681);
- Added: New `padding` option (#711);
- Added: Re-introduced `.noUi-handle-lower` and `.noUi-handle-upper` classes removed in 9.0.0;
- Added: Compatibility for legacy `connect` options removed in 9.0.0;

### 9.0.0 (*2016-09-26*)
- Added: Support for **more than 2 handles**;
- Added: `format` option can be updated (#641);
- Added: `reset` method the return slider to start values (#673);
- Change: `connect` option is now implemented as a separate node;
- Change: all event arguments, including the handle number, are now in slider order;
- Change: `updateOptions` now **modifies the original options** object. The reference in `slider.noUiSlider.options` remains up to date (#678);
- Change: more events fire when using various `behaviour` options (#664);
- Change: on `rtl` sliders, handles are now visually positioned from the sliders `right`/`bottom` edge;
- Change: events for `rtl` sliders now fire in the same order as for `ltr` sliders (with incremental handleNumbers);
- Change: internal `Spectrum` component is no longer `direction` aware;
- Change: `limit` and `margin` must be divisible by `step` (if set);
- Removed: `.noUi-stacking` class. Handles now stack themselves;
- ~~Removed~~ (returned in 9.1.0): `.noUi-handle-lower` and `.noUi-handle-upper` classes;
- Removed: `.noUi-background`. Use `.noUi-target` instead;
- ~~Removed~~ (backward compatibility in 9.1.0): `connect: 'lower'` and `connect: 'upper'`. These settings are replaced by `connect: [true, false]`;
- Fixed: default tooltip color (#687);
- Fixed: `margin` and `limit` calculated improperly after calling `updateOptions` with a new `range` option;
- Fixed: `range` option was required in update, even when not updating it (#682);
- Fixed: Cursor styling is now consistent for disabled handles and sliders (#644);
- Fixed: Sliders now ignore touches when the screen is touched multiple times (#649, #663, #668);
