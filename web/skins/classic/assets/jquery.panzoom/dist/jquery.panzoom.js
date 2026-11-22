/**
* Panzoom 4.6.2 for panning and zooming elements using CSS transforms
* Copyright Timmy Willison and other contributors
* https://github.com/timmywil/panzoom/blob/main/MIT-License.txt
*/
(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
  typeof define === 'function' && define.amd ? define(factory) :
  (global = typeof globalThis !== 'undefined' ? globalThis : global || self, global.Panzoom = factory());
})(this, (function () { 'use strict';

  if (typeof window !== 'undefined') {
    // Support: IE11 only
    if (window.NodeList && !NodeList.prototype.forEach) {
      NodeList.prototype.forEach = Array.prototype.forEach;
    }
    // Support: IE11 only
    // CustomEvent is an object instead of a constructor
    if (typeof window.CustomEvent !== 'function') {
      window.CustomEvent = function CustomEvent(event, params) {
        params = params || { bubbles: false, cancelable: false, detail: null };
        var evt = document.createEvent('CustomEvent');
        evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
        return evt
      };
    }
  }

  const isIE = typeof document !== 'undefined' && !!document.documentMode;
  /**
   * Lazy creation of a CSS style declaration
   */
  let divStyle;
  function createStyle() {
      if (divStyle) {
          return divStyle;
      }
      return (divStyle = document.createElement('div').style);
  }
  /**
   * Proper prefixing for cross-browser compatibility
   */
  const prefixes = ['webkit', 'moz', 'ms'];
  const prefixCache = {};
  function getPrefixedName(name) {
      if (prefixCache[name]) {
          return prefixCache[name];
      }
      const divStyle = createStyle();
      if (name in divStyle) {
          return (prefixCache[name] = name);
      }
      const capName = name[0].toUpperCase() + name.slice(1);
      let i = prefixes.length;
      while (i--) {
          const prefixedName = `${prefixes[i]}${capName}`;
          if (prefixedName in divStyle) {
              return (prefixCache[name] = prefixedName);
          }
      }
  }
  /**
   * Gets a style value expected to be a number
   */
  function getCSSNum(name, style) {
      return parseFloat(style[getPrefixedName(name)]) || 0;
  }
  function getBoxStyle(elem, name, style = window.getComputedStyle(elem)) {
      // Support: FF 68+
      // Firefox requires specificity for border
      const suffix = name === 'border' ? 'Width' : '';
      return {
          left: getCSSNum(`${name}Left${suffix}`, style),
          right: getCSSNum(`${name}Right${suffix}`, style),
          top: getCSSNum(`${name}Top${suffix}`, style),
          bottom: getCSSNum(`${name}Bottom${suffix}`, style)
      };
  }
  /**
   * Set a style using the properly prefixed name
   */
  function setStyle(elem, name, value) {
      elem.style[getPrefixedName(name)] = value;
  }
  /**
   * Constructs the transition from panzoom options
   * and takes care of prefixing the transition and transform
   */
  function setTransition(elem, options) {
      const transform = getPrefixedName('transform');
      setStyle(elem, 'transition', `${transform} ${options.duration}ms ${options.easing}`);
  }
  /**
   * Set the transform using the proper prefix
   *
   * Override the transform setter.
   * This is exposed mostly so the user could
   * set other parts of a transform
   * aside from scale and translate.
   * Default is defined in src/css.ts.
   *
   * ```js
   * // This example always sets a rotation
   * // when setting the scale and translation
   * const panzoom = Panzoom(elem, {
   *   setTransform: (elem, { scale, x, y }) => {
   *     panzoom.setStyle('transform', `rotate(0.5turn) scale(${scale}) translate(${x}px, ${y}px)`)
   *   }
   * })
   * ```
   */
  function setTransform(elem, { x, y, scale, isSVG }, _options) {
      setStyle(elem, 'transform', `scale(${scale}) translate(${x}px, ${y}px)`);
      if (isSVG && isIE) {
          const matrixValue = window.getComputedStyle(elem).getPropertyValue('transform');
          elem.setAttribute('transform', matrixValue);
      }
  }
  /**
   * Dimensions used in containment and focal point zooming
   */
  function getDimensions(elem) {
      let parent = elem.parentNode;
      if (!parent || parent.nodeType !== 1) {
          parent = document.documentElement;
      }
      const style = window.getComputedStyle(elem);
      const parentStyle = window.getComputedStyle(parent);
      const rectElem = elem.getBoundingClientRect();
      const rectParent = parent.getBoundingClientRect();
      return {
          elem: {
              style,
              width: rectElem.width,
              height: rectElem.height,
              top: rectElem.top,
              bottom: rectElem.bottom,
              left: rectElem.left,
              right: rectElem.right,
              margin: getBoxStyle(elem, 'margin', style),
              border: getBoxStyle(elem, 'border', style)
          },
          parent: {
              style: parentStyle,
              width: rectParent.width,
              height: rectParent.height,
              top: rectParent.top,
              bottom: rectParent.bottom,
              left: rectParent.left,
              right: rectParent.right,
              padding: getBoxStyle(parent, 'padding', parentStyle),
              border: getBoxStyle(parent, 'border', parentStyle)
          }
      };
  }

  let events = {
      down: 'mousedown',
      move: 'mousemove',
      up: 'mouseup mouseleave'
  };
  if (typeof window !== 'undefined') {
      if (typeof window.PointerEvent === 'function') {
          events = {
              down: 'pointerdown',
              move: 'pointermove',
              up: 'pointerup pointerleave pointercancel'
          };
      }
      else if (typeof window.TouchEvent === 'function') {
          events = {
              down: 'touchstart',
              move: 'touchmove',
              up: 'touchend touchcancel'
          };
      }
  }
  function onPointer(event, elem, handler, eventOpts) {
      events[event].split(' ').forEach((name) => {
          elem.addEventListener(name, handler, eventOpts);
      });
  }
  function destroyPointer(event, elem, handler) {
      events[event].split(' ').forEach((name) => {
          elem.removeEventListener(name, handler);
      });
  }

  /**
   * Utilites for working with multiple pointer events
   */
  function findEventIndex(pointers, event) {
      let i = pointers.length;
      while (i--) {
          if (pointers[i].pointerId === event.pointerId) {
              return i;
          }
      }
      return -1;
  }
  function addPointer(pointers, event) {
      let i;
      // Add touches if applicable
      if (event.touches) {
          i = 0;
          for (const touch of event.touches) {
              touch.pointerId = i++;
              addPointer(pointers, touch);
          }
          return;
      }
      i = findEventIndex(pointers, event);
      // Update if already present
      if (i > -1) {
          pointers.splice(i, 1);
      }
      pointers.push(event);
  }
  function removePointer(pointers, event) {
      // Add touches if applicable
      if (event.touches) {
          // Remove all touches
          while (pointers.length) {
              pointers.pop();
          }
          return;
      }
      const i = findEventIndex(pointers, event);
      if (i > -1) {
          pointers.splice(i, 1);
      }
  }
  /**
   * Calculates a center point between
   * the given pointer events, for panning
   * with multiple pointers.
   */
  function getMiddle(pointers) {
      // Copy to avoid changing by reference
      pointers = pointers.slice(0);
      let event1 = pointers.pop();
      let event2;
      while ((event2 = pointers.pop())) {
          event1 = {
              clientX: (event2.clientX - event1.clientX) / 2 + event1.clientX,
              clientY: (event2.clientY - event1.clientY) / 2 + event1.clientY
          };
      }
      return event1;
  }
  /**
   * Calculates the distance between two points
   * for pinch zooming.
   * Limits to the first 2
   */
  function getDistance(pointers) {
      if (pointers.length < 2) {
          return 0;
      }
      const event1 = pointers[0];
      const event2 = pointers[1];
      return Math.sqrt(Math.pow(Math.abs(event2.clientX - event1.clientX), 2) +
          Math.pow(Math.abs(event2.clientY - event1.clientY), 2));
  }

  /**
   * Determine if an element is attached to the DOM
   * Panzoom requires this so events work properly
   */
  function isAttached(node) {
      let currentNode = node;
      while (currentNode && currentNode.parentNode) {
          if (currentNode.parentNode === document)
              return true;
          currentNode =
              currentNode.parentNode instanceof ShadowRoot
                  ? currentNode.parentNode.host
                  : currentNode.parentNode;
      }
      return false;
  }

  function getClass(elem) {
      return (elem.getAttribute('class') || '').trim();
  }
  function hasClass(elem, className) {
      return elem.nodeType === 1 && ` ${getClass(elem)} `.indexOf(` ${className} `) > -1;
  }
  function isExcluded(elem, options) {
      for (let cur = elem; cur != null; cur = cur.parentNode) {
          if (hasClass(cur, options.excludeClass) || options.exclude.indexOf(cur) > -1) {
              return true;
          }
      }
      return false;
  }

  /**
   * Determine if an element is SVG by checking the namespace
   * Exception: the <svg> element itself should be treated like HTML
   */
  const rsvg = /^http:[\w\.\/]+svg$/;
  function isSVGElement(elem) {
      return rsvg.test(elem.namespaceURI) && elem.nodeName.toLowerCase() !== 'svg';
  }

  function shallowClone(obj) {
      const clone = {};
      for (const key in obj) {
          if (obj.hasOwnProperty(key)) {
              clone[key] = obj[key];
          }
      }
      return clone;
  }

  /**
   * Panzoom for panning and zooming elements using CSS transforms
   * https://github.com/timmywil/panzoom
   *
   * Copyright Timmy Willison and other contributors
   * Released under the MIT license
   * https://github.com/timmywil/panzoom/blob/main/MIT-License.txt
   *
   */
  const defaultOptions = {
      animate: false,
      canvas: false,
      cursor: 'move',
      disablePan: false,
      disableZoom: false,
      disableXAxis: false,
      disableYAxis: false,
      duration: 200,
      easing: 'ease-in-out',
      exclude: [],
      excludeClass: 'panzoom-exclude',
      handleStartEvent: (e) => {
          e.preventDefault();
          e.stopPropagation();
      },
      maxScale: 4,
      minScale: 0.125,
      overflow: 'hidden',
      panOnlyWhenZoomed: false,
      pinchAndPan: false,
      relative: false,
      setTransform,
      startX: 0,
      startY: 0,
      startScale: 1,
      step: 0.3,
      touchAction: 'none'
  };
  function Panzoom(elem, options) {
      if (!elem) {
          throw new Error('Panzoom requires an element as an argument');
      }
      if (elem.nodeType !== 1) {
          throw new Error('Panzoom requires an element with a nodeType of 1');
      }
      if (!isAttached(elem)) {
          throw new Error('Panzoom should be called on elements that have been attached to the DOM');
      }
      options = Object.assign(Object.assign({}, defaultOptions), options);
      const isSVG = isSVGElement(elem);
      const parent = elem.parentNode;
      // Set parent styles
      parent.style.overflow = options.overflow;
      parent.style.userSelect = 'none';
      // This is important for mobile to
      // prevent scrolling while panning
      parent.style.touchAction = options.touchAction;
      (options.canvas ? parent : elem).style.cursor = options.cursor;
      // Set element styles
      elem.style.userSelect = 'none';
      elem.style.touchAction = options.touchAction;
      // The default for HTML is '50% 50%'
      // The default for SVG is '0 0'
      // SVG can't be changed in IE
      setStyle(elem, 'transformOrigin', typeof options.origin === 'string' ? options.origin : isSVG ? '0 0' : '50% 50%');
      function resetStyle() {
          parent.style.overflow = '';
          parent.style.userSelect = '';
          parent.style.touchAction = '';
          parent.style.cursor = '';
          elem.style.cursor = '';
          elem.style.userSelect = '';
          elem.style.touchAction = '';
          setStyle(elem, 'transformOrigin', '');
      }
      function setOptions(opts = {}) {
          for (const key in opts) {
              if (opts.hasOwnProperty(key)) {
                  options[key] = opts[key];
              }
          }
          // Handle option side-effects
          if (opts.hasOwnProperty('cursor') || opts.hasOwnProperty('canvas')) {
              parent.style.cursor = elem.style.cursor = '';
              (options.canvas ? parent : elem).style.cursor = options.cursor;
          }
          if (opts.hasOwnProperty('overflow')) {
              parent.style.overflow = opts.overflow;
          }
          if (opts.hasOwnProperty('touchAction')) {
              parent.style.touchAction = opts.touchAction;
              elem.style.touchAction = opts.touchAction;
          }
      }
      let x = 0;
      let y = 0;
      let scale = 1;
      let isPanning = false;
      zoom(options.startScale, { animate: false, force: true });
      // Wait for scale to update
      // for accurate dimensions
      // to constrain initial values
      setTimeout(() => {
          pan(options.startX, options.startY, { animate: false, force: true });
      });
      function trigger(eventName, detail, opts) {
          if (opts.silent) {
              return;
          }
          const event = new CustomEvent(eventName, { detail });
          elem.dispatchEvent(event);
      }
      function setTransformWithEvent(eventName, opts, originalEvent) {
          const value = { x, y, scale, isSVG, originalEvent };
          requestAnimationFrame(() => {
              if (typeof opts.animate === 'boolean') {
                  if (opts.animate) {
                      setTransition(elem, opts);
                  }
                  else {
                      setStyle(elem, 'transition', 'none');
                  }
              }
              opts.setTransform(elem, value, opts);
              trigger(eventName, value, opts);
              trigger('panzoomchange', value, opts);
          });
          return value;
      }
      function constrainXY(toX, toY, toScale, panOptions) {
          const opts = Object.assign(Object.assign({}, options), panOptions);
          const result = { x, y, opts };
          if (!(panOptions === null || panOptions === void 0 ? void 0 : panOptions.force) &&
              (opts.disablePan || (opts.panOnlyWhenZoomed && scale === opts.startScale))) {
              return result;
          }
          toX = parseFloat(toX);
          toY = parseFloat(toY);
          if (!opts.disableXAxis) {
              result.x = (opts.relative ? x : 0) + toX;
          }
          if (!opts.disableYAxis) {
              result.y = (opts.relative ? y : 0) + toY;
          }
          if (opts.contain) {
              const dims = getDimensions(elem);
              const realWidth = dims.elem.width / scale;
              const realHeight = dims.elem.height / scale;
              const scaledWidth = realWidth * toScale;
              const scaledHeight = realHeight * toScale;
              const diffHorizontal = (scaledWidth - realWidth) / 2;
              const diffVertical = (scaledHeight - realHeight) / 2;
              if (opts.contain === 'inside') {
                  const minX = (-dims.elem.margin.left - dims.parent.padding.left + diffHorizontal) / toScale;
                  const maxX = (dims.parent.width -
                      scaledWidth -
                      dims.parent.padding.left -
                      dims.elem.margin.left -
                      dims.parent.border.left -
                      dims.parent.border.right +
                      diffHorizontal) /
                      toScale;
                  result.x = Math.max(Math.min(result.x, maxX), minX);
                  const minY = (-dims.elem.margin.top - dims.parent.padding.top + diffVertical) / toScale;
                  const maxY = (dims.parent.height -
                      scaledHeight -
                      dims.parent.padding.top -
                      dims.elem.margin.top -
                      dims.parent.border.top -
                      dims.parent.border.bottom +
                      diffVertical) /
                      toScale;
                  result.y = Math.max(Math.min(result.y, maxY), minY);
              }
              else if (opts.contain === 'outside') {
                  const minX = (-(scaledWidth - dims.parent.width) -
                      dims.parent.padding.left -
                      dims.parent.border.left -
                      dims.parent.border.right +
                      diffHorizontal) /
                      toScale;
                  const maxX = (diffHorizontal - dims.parent.padding.left) / toScale;
                  result.x = Math.max(Math.min(result.x, maxX), minX);
                  const minY = (-(scaledHeight - dims.parent.height) -
                      dims.parent.padding.top -
                      dims.parent.border.top -
                      dims.parent.border.bottom +
                      diffVertical) /
                      toScale;
                  const maxY = (diffVertical - dims.parent.padding.top) / toScale;
                  result.y = Math.max(Math.min(result.y, maxY), minY);
              }
          }
          if (opts.roundPixels) {
              result.x = Math.round(result.x);
              result.y = Math.round(result.y);
          }
          return result;
      }
      function constrainScale(toScale, zoomOptions) {
          const opts = Object.assign(Object.assign({}, options), zoomOptions);
          const result = { scale, opts };
          if (!(zoomOptions === null || zoomOptions === void 0 ? void 0 : zoomOptions.force) && opts.disableZoom) {
              return result;
          }
          let minScale = options.minScale;
          let maxScale = options.maxScale;
          if (opts.contain) {
              const dims = getDimensions(elem);
              const elemWidth = dims.elem.width / scale;
              const elemHeight = dims.elem.height / scale;
              if (elemWidth > 1 && elemHeight > 1) {
                  const parentWidth = dims.parent.width - dims.parent.border.left - dims.parent.border.right;
                  const parentHeight = dims.parent.height - dims.parent.border.top - dims.parent.border.bottom;
                  const elemScaledWidth = parentWidth / elemWidth;
                  const elemScaledHeight = parentHeight / elemHeight;
                  if (options.contain === 'inside') {
                      maxScale = Math.min(maxScale, elemScaledWidth, elemScaledHeight);
                  }
                  else if (options.contain === 'outside') {
                      minScale = Math.max(minScale, elemScaledWidth, elemScaledHeight);
                  }
              }
          }
          result.scale = Math.min(Math.max(toScale, minScale), maxScale);
          return result;
      }
      function pan(toX, toY, panOptions, originalEvent) {
          const result = constrainXY(toX, toY, scale, panOptions);
          // Only try to set if the result is somehow different
          if (x !== result.x || y !== result.y) {
              x = result.x;
              y = result.y;
              return setTransformWithEvent('panzoompan', result.opts, originalEvent);
          }
          return { x, y, scale, isSVG, originalEvent };
      }
      function zoom(toScale, zoomOptions, originalEvent) {
          const result = constrainScale(toScale, zoomOptions);
          const opts = result.opts;
          if (!(zoomOptions === null || zoomOptions === void 0 ? void 0 : zoomOptions.force) && opts.disableZoom) {
              return;
          }
          const toScalePlanned = toScale;
          toScale = result.scale;
          let toX = x;
          let toY = y;
          if (opts.focal) {
              // The difference between the point after the scale and the point before the scale
              // plus the current translation after the scale
              // neutralized to no scale (as the transform scale will apply to the translation)
              const focal = opts.focal;
              toX = (focal.x / toScale - focal.x / scale + x * toScalePlanned) / toScalePlanned;
              toY = (focal.y / toScale - focal.y / scale + y * toScalePlanned) / toScalePlanned;
          }
          const panResult = constrainXY(toX, toY, toScale, { relative: false, force: true });
          x = panResult.x;
          y = panResult.y;
          scale = toScale;
          return setTransformWithEvent('panzoomzoom', opts, originalEvent);
      }
      function zoomInOut(isIn, zoomOptions) {
          const opts = Object.assign(Object.assign(Object.assign({}, options), { animate: true }), zoomOptions);
          return zoom(scale * Math.exp((isIn ? 1 : -1) * opts.step), opts);
      }
      function zoomIn(zoomOptions) {
          return zoomInOut(true, zoomOptions);
      }
      function zoomOut(zoomOptions) {
          return zoomInOut(false, zoomOptions);
      }
      function zoomToPoint(toScale, point, zoomOptions, originalEvent) {
          const dims = getDimensions(elem);
          // Instead of thinking of operating on the panzoom element,
          // think of operating on the area inside the panzoom
          // element's parent
          // Subtract padding and border
          const effectiveArea = {
              width: dims.parent.width -
                  dims.parent.padding.left -
                  dims.parent.padding.right -
                  dims.parent.border.left -
                  dims.parent.border.right,
              height: dims.parent.height -
                  dims.parent.padding.top -
                  dims.parent.padding.bottom -
                  dims.parent.border.top -
                  dims.parent.border.bottom
          };
          // Adjust the clientX/clientY to ignore the area
          // outside the effective area
          let clientX = point.clientX -
              dims.parent.left -
              dims.parent.padding.left -
              dims.parent.border.left -
              dims.elem.margin.left;
          let clientY = point.clientY -
              dims.parent.top -
              dims.parent.padding.top -
              dims.parent.border.top -
              dims.elem.margin.top;
          // Adjust the clientX/clientY for HTML elements,
          // because they have a transform-origin of 50% 50%
          if (!isSVG) {
              clientX -= dims.elem.width / scale / 2;
              clientY -= dims.elem.height / scale / 2;
          }
          // Convert the mouse point from it's position over the
          // effective area before the scale to the position
          // over the effective area after the scale.
          const focal = {
              x: (clientX / effectiveArea.width) * (effectiveArea.width * toScale),
              y: (clientY / effectiveArea.height) * (effectiveArea.height * toScale)
          };
          return zoom(toScale, Object.assign(Object.assign({}, zoomOptions), { animate: false, focal }), originalEvent);
      }
      function zoomWithWheel(event, zoomOptions) {
          // Need to prevent the default here
          // or it conflicts with regular page scroll
          event.preventDefault();
          const opts = Object.assign(Object.assign(Object.assign({}, options), zoomOptions), { animate: false });
          // Normalize to deltaX in case shift modifier is used on Mac
          const delta = event.deltaY === 0 && event.deltaX ? event.deltaX : event.deltaY;
          const wheel = delta < 0 ? 1 : -1;
          const toScale = constrainScale(scale * Math.exp((wheel * opts.step) / 3), opts).scale;
          return zoomToPoint(toScale, event, opts, event);
      }
      function reset(resetOptions) {
          const opts = Object.assign(Object.assign(Object.assign({}, options), { animate: true, force: true }), resetOptions);
          scale = constrainScale(opts.startScale, opts).scale;
          const panResult = constrainXY(opts.startX, opts.startY, scale, opts);
          x = panResult.x;
          y = panResult.y;
          return setTransformWithEvent('panzoomreset', opts);
      }
      let origX;
      let origY;
      let startClientX;
      let startClientY;
      let startScale;
      let startDistance;
      const pointers = [];
      function handleDown(event) {
          // Don't handle this event if the target is excluded
          if (isExcluded(event.target, options)) {
              return;
          }
          addPointer(pointers, event);
          isPanning = true;
          options.handleStartEvent(event);
          origX = x;
          origY = y;
          trigger('panzoomstart', { x, y, scale, isSVG, originalEvent: event }, options);
          // This works whether there are multiple
          // pointers or not
          const point = getMiddle(pointers);
          startClientX = point.clientX;
          startClientY = point.clientY;
          startScale = scale;
          startDistance = getDistance(pointers);
      }
      function handleMove(event) {
          if (!isPanning ||
              origX === undefined ||
              origY === undefined ||
              startClientX === undefined ||
              startClientY === undefined) {
              return;
          }
          addPointer(pointers, event);
          const current = getMiddle(pointers);
          const hasMultiple = pointers.length > 1;
          let toScale = scale;
          if (hasMultiple) {
              // A startDistance of 0 means
              // that there weren't 2 pointers
              // handled on start
              if (startDistance === 0) {
                  startDistance = getDistance(pointers);
              }
              // Use the distance between the first 2 pointers
              // to determine the current scale
              const diff = getDistance(pointers) - startDistance;
              toScale = constrainScale((diff * options.step) / 80 + startScale).scale;
              zoomToPoint(toScale, current, { animate: false }, event);
          }
          // Pan during pinch if pinchAndPan is true.
          // Note: some calculations may be off because the zoom
          // above has not yet rendered. However, the behavior
          // was removed before the new scale was used in the following
          // pan calculation.
          // See https://github.com/timmywil/panzoom/issues/512
          // and https://github.com/timmywil/panzoom/issues/606
          if (!hasMultiple || options.pinchAndPan) {
              pan(origX + (current.clientX - startClientX) / toScale, origY + (current.clientY - startClientY) / toScale, { animate: false }, event);
          }
      }
      function handleUp(event) {
          // Don't call panzoomend when panning with 2 touches
          // until both touches end
          if (pointers.length === 1) {
              trigger('panzoomend', { x, y, scale, isSVG, originalEvent: event }, options);
          }
          // Note: don't remove all pointers
          // Can restart without having to reinitiate all of them
          // Remove the pointer regardless of the isPanning state
          removePointer(pointers, event);
          if (!isPanning) {
              return;
          }
          isPanning = false;
          origX = origY = startClientX = startClientY = undefined;
      }
      let bound = false;
      function bind() {
          if (bound) {
              return;
          }
          bound = true;
          onPointer('down', options.canvas ? parent : elem, handleDown);
          onPointer('move', document, handleMove, { passive: true });
          onPointer('up', document, handleUp, { passive: true });
      }
      function destroy() {
          bound = false;
          destroyPointer('down', options.canvas ? parent : elem, handleDown);
          destroyPointer('move', document, handleMove);
          destroyPointer('up', document, handleUp);
      }
      if (!options.noBind) {
          bind();
      }
      return {
          bind,
          destroy,
          eventNames: events,
          getPan: () => ({ x, y }),
          getScale: () => scale,
          getOptions: () => shallowClone(options),
          handleDown,
          handleMove,
          handleUp,
          pan,
          reset,
          resetStyle,
          setOptions,
          setStyle: (name, value) => setStyle(elem, name, value),
          zoom,
          zoomIn,
          zoomOut,
          zoomToPoint,
          zoomWithWheel
      };
  }
  Panzoom.defaultOptions = defaultOptions;

  return Panzoom;

}));
