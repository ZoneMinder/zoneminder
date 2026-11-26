'use strict';
// Undefined names are errors here, so a renamed or removed variable fails lint
// instead of the page. These are the zone view's globals, from zone.js,
// zone.js.php, skin.js and skin.js.php.
/* eslint no-undef: "error" */
/* global $j, applyCheckMethod, constrainValue, getCoordString, imagePrefix,
   logAjaxFail, maxX, maxY, module, monitorArea, monitorData, monitorPixelArea,
   monitors, pauseBtn, playBtn, setCookie, thisUrl, updateAllPixelDisplays,
   zmAuth, zone, zoneObjectSizeStrings */
//
// The zone editor's detection tools. The object box derives a zone's
// thresholds from a rectangle drawn round the smallest object that should
// alarm (ratios and their defaults are in zone.php). The test panel runs zma
// over a recorded event with the form's settings and replays the result over
// the event's video or JPEG frames. Coordinates use the editor's percent
// space (0..100 per axis). The pure functions up top are exported for
// tests/js/zone-object-size.test.js.
//
(function(global) {
  // definezone.rst [G]. Above the Default preset's 25.
  const DEFAULT_PIXEL_THRESHOLD = 40;

  // limitFilter clamps FilterX/Y odd within 3..15, so 3 is the smallest legal
  // kernel. Also the Default preset and Zone's constructor (zm_zone.h:140).
  const MIN_FILTER_KERNEL = 3;

  // Written and snapshotted for undo. ExtendAlarmFrames is excluded because
  // applyZoneType disables it outside Preclusive zones, so it cannot submit.
  const MANAGED_FIELDS = [
    'MinPixelThreshold', 'MaxPixelThreshold',
    'FilterX', 'FilterY',
    'MinAlarmPixels', 'MaxAlarmPixels',
    'MinFilterPixels', 'MaxFilterPixels',
    'MinBlobPixels', 'MaxBlobPixels',
    'MinBlobs', 'MaxBlobs',
    'OverloadFrames',
  ];


  // 2dp to match the DECIMAL(10,2) columns. Floor at 0.01 rather than allowing
  // 0, which CheckAlarms treats as "no minimum at all".
  function asPercentField(pct) {
    return Math.min(100, Math.max(0.01, Math.round(pct * 100) / 100));
  }

  // Unrounded pixel counts, so each stored percentage rounds exactly once.
  // Blob is a share of filter, not of alarm: a blob is built from pixels that
  // survived filtering, so blob <= filter <= alarm holds by construction,
  // which is what validateForm requires before a zone can be saved.
  function objectThresholdPixels(boxPx, ratios) {
    if (!(boxPx > 0)) return null;
    const alarm = ratios.fraction * boxPx;
    const filter = alarm * ratios.filter;
    return {alarm: alarm, filter: filter, blob: filter * ratios.blob};
  }

  // Storage unit: Zone::Load multiplies this back by polygon.Area()
  // (zm_zone.cpp:1020) to recover the pixel count.
  function pixelsToPercent(px, zonePx) {
    if (!(px > 0) || !(zonePx > 0)) return null;
    return asPercentField(px / zonePx * 100);
  }

  function keepOr(current, fallback) {
    return (current === undefined || current === null || String(current) === '') ?
        fallback : current;
  }

  // Size only: MinPixelThreshold and FilterX/Y are kept when already set.
  // Every Max stays blank - they set overload_count (zm_zone.cpp:304, 388,
  // 671) or delete the blob (:620), and one box cannot imply a ceiling when
  // the same object is far bigger up close.
  function zoneSettingsForObject(boxPx, zonePx, current, ratios) {
    const px = objectThresholdPixels(boxPx, ratios);
    if (!px || !(zonePx > 0)) return null;
    current = current || {};
    return {
      CheckMethod: 'Blobs',
      MinPixelThreshold: keepOr(current.MinPixelThreshold, DEFAULT_PIXEL_THRESHOLD),
      MaxPixelThreshold: '',
      FilterX: keepOr(current.FilterX, MIN_FILTER_KERNEL),
      FilterY: keepOr(current.FilterY, MIN_FILTER_KERNEL),
      MinAlarmPixels: pixelsToPercent(px.alarm, zonePx),
      MaxAlarmPixels: '',
      MinFilterPixels: pixelsToPercent(px.filter, zonePx),
      MaxFilterPixels: '',
      MinBlobPixels: pixelsToPercent(px.blob, zonePx),
      MaxBlobPixels: '',
      MinBlobs: 1,
      MaxBlobs: '',
      OverloadFrames: 0,
    };
  }

  // The filter keeps a pixel only if a fully-white FilterX by FilterY window
  // covers it (zm_zone.cpp:338), so a box narrower than the kernel on either
  // axis is erased entirely and the zone silently stops alarming. A box that
  // exactly fits the kernel survives, hence < rather than <=.
  // Only catches the certain case - real limbs are thinner than their box.
  function objectTooNarrowToFilter(rectWpx, rectHpx, filterX, filterY) {
    return rectWpx < Math.max(filterX || 0, MIN_FILTER_KERNEL) ||
        rectHpx < Math.max(filterY || 0, MIN_FILTER_KERNEL);
  }

  // zone.php reads these cookies back through validInt(), which strips
  // everything that is not a digit: 70.5 returns as 705 and 1e2 as 12. The
  // live calculation takes the same field through parseFloat, so a ratio that
  // is not a whole number in range means one thing now and another after a
  // reload. Settle it here, before it is stored or used.
  function normalizeRatio(value) {
    const n = Math.round(parseFloat(value));
    if (!(n >= 1) || !(n <= 100)) return null;
    return n;
  }

  // A box of the given capture-pixel size, centred in the frame, in the
  // editor's percent space. Lets the tool be driven by typing a size as well
  // as by dragging, and gives the drag somewhere to write its result back to.
  function boxPercentForPixels(wPx, hPx, frame) {
    if (!(wPx > 0) || !(hPx > 0)) return null;
    if (!frame || !(frame.width > 0) || !(frame.height > 0)) return null;
    const w = Math.min(frame.maxX, wPx / frame.width * frame.maxX);
    const h = Math.min(frame.maxY, hPx / frame.height * frame.maxY);
    const x = (frame.maxX - w) / 2;
    const y = (frame.maxY - h) / 2;
    return {a: {x: x, y: y}, b: {x: x + w, y: y + h}};
  }

  // The inverse of objectThresholdPixels' first step: the object a saved
  // MinAlarmPixels implies. Only the area survives storage, not the shape or
  // where it was drawn.
  function savedObjectPixels(minAlarmPct, zonePx, fraction) {
    if (!(minAlarmPct > 0) || !(zonePx > 0) || !(fraction > 0)) return null;
    return minAlarmPct / 100 * zonePx / fraction;
  }

  // Sides of a box of the given area and width/height ratio.
  function boxSidesForArea(areaPx, aspect) {
    if (!(areaPx > 0) || !(aspect > 0)) return null;
    return {w: Math.sqrt(areaPx * aspect), h: Math.sqrt(areaPx / aspect)};
  }

  // The box scaled about its centre to cover share of its area. Schematic:
  // alarmed pixels are scattered, this only shows how many of them must change.
  function innerBox(box, share) {
    if (!(share > 0)) return null;
    const k = Math.sqrt(Math.min(share, 1));
    const cx = (box.a.x + box.b.x) / 2;
    const cy = (box.a.y + box.b.y) / 2;
    const hw = Math.abs(box.b.x - box.a.x) * k / 2;
    const hh = Math.abs(box.b.y - box.a.y) * k / 2;
    return {a: {x: cx - hw, y: cy - hh}, b: {x: cx + hw, y: cy + hh}};
  }

  // The box moved by dx, dy, stopped at the frame edges so its size is kept.
  function shiftBox(box, dx, dy, maxX, maxY) {
    const x0 = Math.min(box.a.x, box.b.x);
    const y0 = Math.min(box.a.y, box.b.y);
    const w = Math.abs(box.b.x - box.a.x);
    const h = Math.abs(box.b.y - box.a.y);
    const x = Math.min(Math.max(x0 + dx, 0), Math.max(maxX - w, 0));
    const y = Math.min(Math.max(y0 + dy, 0), Math.max(maxY - h, 0));
    return {a: {x: x, y: y}, b: {x: x + w, y: y + h}};
  }

  const STAGES = ['alarm', 'filter', 'blob'];
  const CHECK_METHODS = ['AlarmedPixels', 'FilteredPixels', 'Blobs'];
  const PIXEL_FIELDS = {
    minAlarm: 'MinAlarmPixels', maxAlarm: 'MaxAlarmPixels',
    minFilter: 'MinFilterPixels', maxFilter: 'MaxFilterPixels',
    minBlob: 'MinBlobPixels', maxBlob: 'MaxBlobPixels',
  };

  // The form's values as CheckAlarms sees them: percent of the zone turned
  // into pixels the way Zone::LoadRow does (zm_zone.cpp:1180), 0 for "none".
  function zoneThresholds(values, area) {
    const t = {
      checkMethod: CHECK_METHODS.indexOf(values.CheckMethod) + 1,
      overloadFrames: parseInt(values.OverloadFrames, 10) || 0,
      minBlobs: parseInt(values.MinBlobs, 10) || 0,
      maxBlobs: parseInt(values.MaxBlobs, 10) || 0,
    };
    Object.keys(PIXEL_FIELDS).forEach(function(k) {
      const pct = parseFloat(values[PIXEL_FIELDS[k]]);
      t[k] = pct > 0 ? Math.floor(pct * area / 100 + 0.5) : 0;
    });
    return t;
  }

  // CheckAlarms' test for one count: something there, and within min/max
  // where those are set.
  function within(n, min, max) {
    return n > 0 && !(min && n < min) && !(max && n > max);
  }

  // Would the frame zma measured under `ran` pass under `now`? The counts
  // zma reports do not depend on the Min/Max fields, so those can change
  // without a re-run - except that CheckAlarms stops at the first stage that
  // fails, so a stage zma never reached was never measured (unknown). Blob
  // count depends on Min/MaxBlobPixels, so after changing those only the
  // largest blob is known, which settles "at least one blob" and nothing more.
  // ponytail: ignores overload frames after a Max is exceeded; re-run for those.
  function whatIfVerdict(fz, ran, now) {
    let reached = true;
    for (let i = 0; i < Math.max(now.checkMethod, 1); i++) {
      const stage = STAGES[i];
      if (!reached || i >= ran.checkMethod) return {failedAt: null, unknown: stage, scored: false};
      let pass;
      // A count over its maximum puts the zone into overload (applyOverload).
      let over = false;
      if (stage === 'alarm') {
        pass = within(fz.alarm, now.minAlarm, now.maxAlarm);
        over = now.maxAlarm > 0 && fz.alarm > now.maxAlarm;
        reached = within(fz.alarm, ran.minAlarm, ran.maxAlarm);
      } else if (stage === 'filter') {
        pass = within(fz.filter, now.minFilter, now.maxFilter);
        over = now.maxFilter > 0 && fz.filter > now.maxFilter;
        reached = within(fz.filter, ran.minFilter, ran.maxFilter);
      } else if (now.minBlob === ran.minBlob && now.maxBlob === ran.maxBlob) {
        pass = within(fz.blobs, now.minBlobs, now.maxBlobs);
        over = now.maxBlobs > 0 && fz.blobs > now.maxBlobs;
      } else if (now.minBlobs <= 1 && !now.maxBlob && !now.maxBlobs) {
        pass = within(fz.largestBlob, now.minBlob, 0);
      } else {
        return {failedAt: null, unknown: stage, scored: false};
      }
      if (!pass) return {failedAt: stage, unknown: null, scored: false, over: over};
    }
    return {failedAt: null, unknown: null, scored: true};
  }

  // CheckAlarms skips the overloadFrames frames after one that went over a
  // maximum. zma measured with the maximums off, so this replays that here.
  // frames: [{frame, verdict}] in frame order; skipped frames get an
  // 'overload' verdict.
  function applyOverload(frames, overloadFrames) {
    let until = -1;
    frames.forEach(function(f) {
      if (f.frame <= until) {
        f.verdict = {failedAt: 'overload', unknown: null, scored: false};
      } else if (f.verdict.over && overloadFrames > 0) {
        until = f.frame + overloadFrames;
      }
    });
  }

  // Per stage: true passed, false failed, undefined not reached, not tested
  // or not known.
  function stageResults(verdict, checkMethod) {
    const out = {};
    if (verdict.failedAt === 'overload') return out;
    let stop = false;
    STAGES.forEach(function(stage, i) {
      if (checkMethod < i + 1 || stop) return;
      if (verdict.unknown === stage) {
        stop = true;
      } else if (verdict.failedAt === stage) {
        out[stage] = false;
        stop = true;
      } else {
        out[stage] = true;
      }
    });
    return out;
  }

  // frames: [{frame, verdict}] in frame order, only those zma reported (the
  // rest had no change, so did not score). An alarm needs alarmFrameCount
  // scored frames in a row: zma.cpp's PREALARM count resets on any miss.
  // alarmFrames: the frame numbers of every run long enough to alarm.
  function tallyVerdicts(frames, alarmFrameCount) {
    const out = {scored: 0, unknown: 0, wouldAlarm: false, alarmFrames: []};
    const need = Math.max(alarmFrameCount, 1);
    let run = [];
    let prev = null;
    frames.forEach(function(f) {
      const v = f.verdict;
      if (v.unknown) out.unknown++;
      if (!v.scored || prev === null || f.frame !== prev + 1) run = [];
      if (v.scored) run.push(f.frame);
      prev = f.frame;
      if (v.scored) out.scored++;
      if (run.length === need) {
        out.alarmFrames = out.alarmFrames.concat(run);
        out.wouldAlarm = true;
      } else if (run.length > need) {
        out.alarmFrames.push(f.frame);
      }
    });
    return out;
  }

  // Why frames fail, for a "do I need to change something" answer: the stage
  // most failing frames stop at, and the closest any of them came to its
  // minimum. Frames failing on a maximum are not near misses and are skipped.
  // null when nothing fell short of a minimum.
  function failureSummary(frames, t) {
    const measure = {alarm: 'alarm', filter: 'filter', blob: 'largestBlob'};
    const need = {alarm: t.minAlarm, filter: t.minFilter, blob: t.minBlob};
    const counts = {};
    const best = {};
    frames.forEach(function(f) {
      const stage = f.verdict.failedAt;
      if (!stage) return;
      const got = f.fz[measure[stage]] || 0;
      if (need[stage] && got >= need[stage]) return;
      counts[stage] = (counts[stage] || 0) + 1;
      best[stage] = Math.max(best[stage] || 0, got);
    });
    const stage = Object.keys(counts).sort(function(a, b) {
      return counts[b] - counts[a];
    })[0];
    return stage ? {stage: stage, frames: counts[stage], best: best[stage], need: need[stage]} : null;
  }

  // The frame closest to t seconds, if one is within tol. frames sorted by t.
  function frameAt(frames, t, tol) {
    let lo = 0;
    let hi = frames.length - 1;
    if (hi < 0) return null;
    while (lo < hi) {
      const mid = (lo + hi) >> 1;
      if (frames[mid].t < t) lo = mid + 1; else hi = mid;
    }
    let best = lo;
    if (lo > 0 && Math.abs(frames[lo - 1].t - t) <= Math.abs(frames[lo].t - t)) best = lo - 1;
    return Math.abs(frames[best].t - t) <= tol ? frames[best] : null;
  }

  // zma reports boxes as inclusive capture-pixel corners; an all-zero box is
  // one that was never set.
  function pixelBoxToPercent(box, width, height) {
    if (!box || box.length !== 4 || !(width > 0) || !(height > 0)) return null;
    if (!box[0] && !box[1] && !box[2] && !box[3]) return null;
    return {
      a: {x: box[0] / width * 100, y: box[1] / height * 100},
      b: {x: (box[2] + 1) / width * 100, y: (box[3] + 1) / height * 100},
    };
  }

  // Zones.ObjectCoords: "x1,y1 x2,y2" in percent, corners normalised.
  function boxToCoords(box) {
    if (!box) return '';
    const f = function(n) {
      return n.toFixed(2);
    };
    return f(Math.min(box.a.x, box.b.x)) + ',' + f(Math.min(box.a.y, box.b.y)) + ' ' +
        f(Math.max(box.a.x, box.b.x)) + ',' + f(Math.max(box.a.y, box.b.y));
  }

  function coordsToBox(coords) {
    const m = /^(\d+(?:\.\d+)?),(\d+(?:\.\d+)?) (\d+(?:\.\d+)?),(\d+(?:\.\d+)?)$/.exec(coords || '');
    if (!m) return null;
    const v = m.slice(1).map(parseFloat);
    if (!(v[2] > v[0]) || !(v[3] > v[1]) || v[2] > 100 || v[3] > 100) return null;
    return {a: {x: v[0], y: v[1]}, b: {x: v[2], y: v[3]}};
  }

  const api = {
    MIN_FILTER_KERNEL: MIN_FILTER_KERNEL,
    zoneThresholds: zoneThresholds,
    whatIfVerdict: whatIfVerdict,
    tallyVerdicts: tallyVerdicts,
    applyOverload: applyOverload,
    failureSummary: failureSummary,
    stageResults: stageResults,
    frameAt: frameAt,
    pixelBoxToPercent: pixelBoxToPercent,
    boxToCoords: boxToCoords,
    coordsToBox: coordsToBox,
    shiftBox: shiftBox,
    savedObjectPixels: savedObjectPixels,
    boxSidesForArea: boxSidesForArea,
    innerBox: innerBox,
    normalizeRatio: normalizeRatio,
    boxPercentForPixels: boxPercentForPixels,
    MANAGED_FIELDS: MANAGED_FIELDS,
    objectThresholdPixels: objectThresholdPixels,
    pixelsToPercent: pixelsToPercent,
    zoneSettingsForObject: zoneSettingsForObject,
    objectTooNarrowToFilter: objectTooNarrowToFilter,
  };

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = api;
    return;
  }

  //
  // Browser-only below here.
  //

  let armed = false;
  let dragging = false;
  let startPct = null;
  let undoSnapshot = null;
  // The last box measured, so a change to the fraction can re-derive from it.
  let lastBox = null;
  // lastBox is also what gets saved, as Zones.ObjectCoords.
  function setLastBox(box) {
    lastBox = box;
    const el = document.zoneForm && document.zoneForm.elements['newZone[ObjectCoords]'];
    if (el) el.value = boxToCoords(box);
    // A hidden field raises no event, so tell zone.js the form changed.
    if (typeof global.checkDirty === 'function') global.checkDirty();
  }

  // A box saved with the zone is the measurement itself; without one, show
  // the estimate rebuilt from the thresholds.
  function showInitialBox() {
    const el = document.zoneForm && document.zoneForm.elements['newZone[ObjectCoords]'];
    const stored = el ? coordsToBox(el.value) : null;
    if (stored && zoneIsMeasurable()) {
      lastBox = stored;
      drawRect(stored.a, stored.b);
      drawThresholds(stored);
    } else {
      showSavedObject();
    }
  }

  // The box on screen, measured or rebuilt from the saved thresholds.
  let shownBox = null;

  const RATIO_INPUTS = [
    {key: 'fraction', id: 'objectFraction', cookie: 'zmZoneObjectFraction'},
    {key: 'filter', id: 'objectFilterRatio', cookie: 'zmZoneFilterRatio'},
    {key: 'blob', id: 'objectBlobRatio', cookie: 'zmZoneBlobRatio'},
  ];

  // A blank or out-of-range field falls back to defaultValue, which zone.php
  // rendered. Never 0 - CheckAlarms reads that as no minimum at all.
  function currentRatios() {
    const out = {};
    RATIO_INPUTS.forEach(function(spec) {
      const el = document.getElementById(spec.id);
      let pct = parseFloat(el.value);
      if (!(pct > 0) || !(pct <= 100)) pct = parseFloat(el.defaultValue);
      out[spec.key] = pct / 100;
    });
    return out;
  }

  function imageFeed() {
    return document.getElementById('imageFeed' + zone.MonitorId);
  }

  // A still can stand in for the live view. Read as a data URL - nothing is
  // uploaded. Kept hidden rather than removed so toggling reuses it.
  let stillShown = false;

  // The element id inside the feed, made hidden on first use. underSvg puts a
  // picture after the stream, so it covers the live view, but before the SVG,
  // so the zone outline and boxes still paint on top of it.
  function feedChild(tag, id, cls, underSvg) {
    let el = document.getElementById(id);
    if (el) return el;
    const feed = imageFeed();
    if (!feed) return null;
    el = document.createElement(tag);
    el.id = id;
    if (cls) el.className = cls;
    el.style.display = 'none';
    const svg = document.getElementById('zoneSVG');
    if (underSvg && svg && svg.parentNode === feed) {
      feed.insertBefore(el, svg);
    } else {
      feed.appendChild(el);
    }
    return el;
  }

  function stillElement() {
    return feedChild('img', 'objectStill', '', true);
  }

  function hasStill() {
    const img = document.getElementById('objectStill');
    return !!(img && img.getAttribute('src'));
  }

  function updateStillButton() {
    const btn = document.getElementById('objectImageBtn');
    if (btn) {
      btn.classList.toggle('btn-primary', stillShown);
      btn.title = stillShown ?
          zoneObjectSizeStrings.liveView : zoneObjectSizeStrings.useImage;
    }
    const replace = document.getElementById('objectImageReplaceBtn');
    if (replace) replace.style.display = stillShown ? '' : 'none';
  }

  function loadStill(dataUrl) {
    const img = stillElement();
    if (!img) return;
    img.src = dataUrl;
    showStill();
  }

  function showStill() {
    const img = stillElement();
    if (!img || !hasStill()) return;
    img.style.display = '';
    stillShown = true;
    // Stopped like under a replay: a paused stream kept its fps moving.
    liveStream(false);
    liveButtons(false, zoneObjectSizeStrings.stillLiveOff);
    updateStillButton();
  }

  // Leaves the stream alone: the Play button's own handler already plays it.
  function hideStill() {
    const img = document.getElementById('objectStill');
    if (img) img.style.display = 'none';
    stillShown = false;
    updateStillButton();
  }

  function showLive() {
    hideStill();
    // A replay still up keeps the stream off; its Exit restarts it.
    if (replay) return;
    liveButtons(true);
    liveStream(true);
  }

  function zoneIsMeasurable() {
    const type = document.zoneForm.elements['newZone[Type]'].value;
    return type !== 'Inactive' && type !== 'Privacy';
  }

  // Inverse of the mapping drawZonePoints uses, via zone.js's own maxX/maxY
  // and constrainValue so the box shares the vertices' coordinate space.
  function toPercent(evt, feed) {
    const box = feed.getBoundingClientRect();
    return {
      x: constrainValue((evt.clientX - box.left) / box.width * maxX, 0, maxX),
      y: constrainValue((evt.clientY - box.top) / box.height * maxY, 0, maxY),
    };
  }

  // Not SVG text: the SVG uses preserveAspectRatio="none", which would
  // stretch it out of shape.
  function boxLabel(id) {
    return feedChild('div', id, 'boxLabel', false);
  }

  // Above the box's top-left corner, or inside it near the top edge.
  function placeLabel(id, text, x, y) {
    const el = boxLabel(id);
    if (!el) return null;
    el.textContent = text;
    el.style.left = x + '%';
    el.style.top = y + '%';
    el.classList.toggle('below', y < 6);
    el.style.display = '';
    return el;
  }

  // The capture frame, as boxPercentForPixels takes it.
  function captureFrame() {
    return {width: monitorData[0].width, height: monitorData[0].height, maxX: maxX, maxY: maxY};
  }

  // A box's sides in capture pixels - the units CheckAlarms counts in, so a
  // size means the same at any window size.
  function boxPx(a, b) {
    return {
      w: Math.abs(b.x - a.x) / maxX * monitorData[0].width,
      h: Math.abs(b.y - a.y) / maxY * monitorData[0].height,
    };
  }

  function drawSizeLabel(x, y, w, h, saved) {
    const px = boxPx({x: x, y: y}, {x: x + w, y: y + h});
    const wPx = Math.round(px.w);
    const hPx = Math.round(px.h);
    placeLabel('objectSizeLabel', (saved ? zoneObjectSizeStrings.labelEstimate :
        zoneObjectSizeStrings.labelObject) + ' ' + wPx + '×' + hPx, x, y);
  }

  // Threshold rects drawn inside the object box, and the field each is sized by.
  const THRESHOLD_RECTS = [
    {id: 'objectAlarmRect', field: 'MinAlarmPixels'},
    {id: 'objectFilterRect', field: 'MinFilterPixels'},
    {id: 'objectBlobRect', field: 'MinBlobPixels'},
  ];

  // The last zma test, re-judged against the form's thresholds as they change.
  // Null once a setting the thresholds cannot cover has changed.
  let test = null;
  const INNER_RECT_IDS = THRESHOLD_RECTS.map(function(t) {
    return t.id;
  }).concat(['objectKernelRect']);

  function zonePixels() {
    return zone.Area / monitorArea * monitorPixelArea;
  }

  function setRect(id, a, b) {
    const r = document.getElementById(id);
    if (!r) return null;
    r.setAttribute('x', Math.min(a.x, b.x));
    r.setAttribute('y', Math.min(a.y, b.y));
    r.setAttribute('width', Math.abs(b.x - a.x));
    r.setAttribute('height', Math.abs(b.y - a.y));
    r.style.display = '';
    return r;
  }

  // Hides overlay elements by id: rects, labels, the resize handle.
  function hideRects(ids) {
    ids.forEach(function(id) {
      const r = document.getElementById(id);
      if (r) r.style.display = 'none';
    });
  }

  // A div rather than an SVG rect, for the same reason as the label: the
  // stretched viewBox would distort it.
  function resizeHandle() {
    return feedChild('div', 'objectResizeHandle', '', false);
  }

  // saved: the box was rebuilt from stored thresholds rather than drawn.
  function drawRect(a, b, saved) {
    const r = setRect('objectRect', a, b);
    if (!r) return;
    r.classList.toggle('saved', !!saved);
    shownBox = {
      a: {x: Math.min(a.x, b.x), y: Math.min(a.y, b.y)},
      b: {x: Math.max(a.x, b.x), y: Math.max(a.y, b.y)},
      saved: !!saved,
    };
    // Stale until drawThresholds runs for this box.
    hideRects(INNER_RECT_IDS);
    drawSizeLabel(shownBox.a.x, shownBox.a.y,
        shownBox.b.x - shownBox.a.x, shownBox.b.y - shownBox.a.y, shownBox.saved);
    // Whichever box is on screen, the size fields describe it.
    const px = boxPx(shownBox.a, shownBox.b);
    showBoxSize(px.w, px.h);
    const handle = resizeHandle();
    if (handle) {
      handle.style.left = shownBox.b.x + '%';
      handle.style.top = shownBox.b.y + '%';
      handle.style.display = '';
    }
  }

  function hideRect() {
    hideRects(['objectRect'].concat(INNER_RECT_IDS));
    shownBox = null;
    hideRects(['objectSizeLabel', 'objectResizeHandle']);
    updateLegend();
  }

  // The legend lists only what is on the image now. data-rect names the
  // element; data-saved, when present, must match its dashed state.
  function updateLegend() {
    document.querySelectorAll('#boxLegend [data-rect]').forEach(function(item) {
      const el = document.getElementById(item.dataset.rect);
      let shown = !!el && el.style.display !== 'none';
      if (shown && item.dataset.saved !== undefined) {
        shown = el.classList.contains('saved') === (item.dataset.saved === '1');
      }
      item.style.display = shown ? '' : 'none';
    });
    // A row with nothing on the image goes, heading and all.
    document.querySelectorAll('#boxLegend .legendRow').forEach(function(row) {
      const any = Array.prototype.some.call(row.querySelectorAll('[data-rect]'), function(item) {
        return item.style.display !== 'none';
      });
      row.style.display = any ? '' : 'none';
    });
  }

  // Each Min threshold as a share of the box, from whatever the fields hold
  // now, so hand edits show up too. Disabled fields (CheckMethod) are skipped:
  // they take no part in detection.
  function drawThresholds(box) {
    const form = document.zoneForm;
    const sides = boxPx(box.a, box.b);
    const areaPx = sides.w * sides.h;
    const zonePx = zonePixels();
    // Each must fit inside the one before - validateForm refuses the save
    // otherwise - so say so now, with its message, rather than at Save.
    const rules = [null, global.minFilterLtMinAlarmString, global.minBlobLtMinFilterString];
    let outer = Infinity;
    THRESHOLD_RECTS.forEach(function(t, i) {
      const el = form.elements['newZone[' + t.field + ']'];
      const pct = el && !el.disabled ? parseFloat(el.value) : NaN;
      const tooBig = pct > outer;
      if (el) {
        el.classList.toggle('outOfOrder', tooBig);
        el.title = tooBig ? rules[i] : '';
      }
      if (pct > 0) outer = pct;
      const inner = (areaPx > 0) ? innerBox(box, pct / 100 * zonePx / areaPx) : null;
      if (inner) {
        setRect(t.id, inner.a, inner.b).classList.toggle('outOfOrder', tooBig);
      } else {
        hideRects([t.id]);
      }
    });
    // The filter kernel to scale, in the box's corner: anything thinner than
    // this is erased before blobs are counted.
    const fx = form.elements['newZone[FilterX]'];
    const fy = form.elements['newZone[FilterY]'];
    const kx = fx && !fx.disabled ? parseInt(fx.value, 10) : NaN;
    const ky = fy && !fy.disabled ? parseInt(fy.value, 10) : NaN;
    if (kx > 1 || ky > 1) {
      const x = Math.min(box.a.x, box.b.x);
      const y = Math.min(box.a.y, box.b.y);
      setRect('objectKernelRect', {x: x, y: y},
          {x: x + (kx || 1) / monitorData[0].width * maxX, y: y + (ky || 1) / monitorData[0].height * maxY});
    } else {
      hideRects(['objectKernelRect']);
    }
    updateLegend();
  }

  // With no measurement in hand, show the object the saved settings imply, at
  // the monitor's aspect ratio and centred - shape and position are not stored.
  function showSavedObject() {
    hideRect();
    if (!zoneIsMeasurable()) return;
    const form = document.zoneForm;
    const el = form.elements['newZone[MinAlarmPixels]'];
    const area = savedObjectPixels(el && !el.disabled ? parseFloat(el.value) : NaN,
        zonePixels(), currentRatios().fraction);
    const frame = captureFrame();
    const sides = boxSidesForArea(area, frame.width / frame.height);
    if (!sides) return;
    const box = boxPercentForPixels(sides.w, sides.h, frame);
    if (!box) return;
    drawRect(box.a, box.b, true);
    drawThresholds(box);
  }

  // The box itself too, not just its thresholds: whatever was drawn before
  // (the estimate, say, while lastBox was briefly cleared) must not linger.
  function refreshOverlay() {
    if (lastBox) {
      drawRect(lastBox.a, lastBox.b);
      drawThresholds(lastBox);
    } else {
      showSavedObject();
    }
  }

  // Compared against the pre-drag snapshot: mid-drag the field already holds
  // our value, so comparing live would clear the mark on the second mousemove.
  function markChanged(field, changed) {
    const el = document.zoneForm.elements['newZone[' + field + ']'];
    if (el) el.classList.toggle('objectSizeChanged', changed);
    const px = document.getElementById(field + '_px');
    if (px) px.classList.toggle('objectSizeChanged', changed);
  }

  function clearChangeMarks() {
    MANAGED_FIELDS.concat(['CheckMethod']).forEach(function(field) {
      markChanged(field, false);
    });
  }

  // For when something else takes ownership of these fields - the marks would
  // then be a lie and undo would revert work done after us.
  function forgetMeasurement() {
    clearChangeMarks();
    undoSnapshot = null;
    setLastBox(null);
    const btn = document.getElementById('objectSizeUndoBtn');
    if (btn) btn.style.display = 'none';
    refreshOverlay();
  }

  function setArmed(on) {
    armed = on;
    const btn = document.getElementById('objectSizeBtn');
    if (btn) btn.classList.toggle('btn-primary', on);
    // Follows the button. A saved box always exists now, so it cannot be what
    // keeps the panel up, or the button could never close it.
    const panel = document.getElementById('rectangleSettings');
    if (panel) panel.style.display = on ? '' : 'none';
    const feed = imageFeed();
    if (feed) {
      feed.style.cursor = on ? 'crosshair' : '';
      // Without this the browser claims a touch drag for panning and the
      // pointermove events stop arriving after the first few pixels.
      feed.style.touchAction = on ? 'none' : '';
    }
    // Stop the zone vertices swallowing the drag while measuring.
    const points = document.querySelectorAll('.zonePoint');
    for (let i = 0; i < points.length; i++) {
      points[i].style.pointerEvents = on ? 'none' : '';
    }
  }

  function undo() {
    if (!undoSnapshot) return;
    const form = document.zoneForm;
    form.elements['newZone[CheckMethod]'].value = undoSnapshot.CheckMethod;
    applyCheckMethod();
    MANAGED_FIELDS.forEach(function(field) {
      const el = form.elements['newZone[' + field + ']'];
      if (el) el.value = undoSnapshot[field];
    });
    updateAllPixelDisplays();
    const box = undoSnapshot.box;
    forgetMeasurement();
    // Back to the box the measurement started from, saved one included.
    if (box) {
      setLastBox(box);
      refreshOverlay();
    }
  }

  // Runs on every mousemove, so nothing here may block - no alert or confirm.
  function apply(a, b) {
    const form = document.zoneForm;
    const px = boxPx(a, b);
    const zonePx = zonePixels();
    // A box this small is normal mid-drag. Change nothing, say nothing.
    if (!(px.w * px.h > 0) || !(zonePx > 0)) return false;

    // Every box in one armed session is measured against the state before the
    // first, so undo and the highlighting stay anchored there.
    if (!undoSnapshot) {
      undoSnapshot = formValues();
      undoSnapshot.box = lastBox;
      const undoBtn = document.getElementById('objectSizeUndoBtn');
      if (undoBtn) undoBtn.style.display = '';
    }

    setLastBox({a: {x: a.x, y: a.y}, b: {x: b.x, y: b.y}});

    // Sensitivity is read from the snapshot, not the live fields: mid-drag
    // those already hold our own writes.
    const values = zoneSettingsForObject(
        px.w * px.h, zonePx, undoSnapshot, currentRatios());
    if (!values) return false;

    // applyCheckMethod is required: assigning to a select does not fire
    // onchange, and the blob fields stay disabled - and disabled inputs never
    // submit. Read the old method from the snapshot; the live field already
    // says Blobs after the first mousemove.
    const switched = undoSnapshot.CheckMethod !== values.CheckMethod;
    form.elements['newZone[CheckMethod]'].value = values.CheckMethod;
    applyCheckMethod();
    markChanged('CheckMethod', switched);

    MANAGED_FIELDS.forEach(function(field) {
      const el = form.elements['newZone[' + field + ']'];
      if (!el) return;
      el.value = values[field];
      markChanged(field, String(values[field]) !== String(undoSnapshot[field]));
    });

    // Refresh the pixel readouts beside each percentage field.
    updateAllPixelDisplays();
    rejudgeTest();
    drawThresholds(lastBox);
    return true;
  }

  // The typed size and the drawn box are two views of one measurement, so
  // whichever was used last writes to the other.
  function showBoxSize(wPx, hPx) {
    const w = document.getElementById('objectWidthPx');
    const h = document.getElementById('objectHeightPx');
    if (w) w.value = (wPx > 0) ? Math.round(wPx) : '';
    if (h) h.value = (hPx > 0) ? Math.round(hPx) : '';
  }

  // Warnings live here, not in apply(), which runs on every pointermove.
  function finishDrag(a, b) {
    if (!apply(a, b)) return;
    const form = document.zoneForm;
    const px = boxPx(a, b);
    const wPx = px.w;
    const hPx = px.h;
    showBoxSize(wPx, hPx);
    const fx = Math.max(
        parseInt(form.elements['newZone[FilterX]'].value, 10) || 0,
        MIN_FILTER_KERNEL);
    const fy = Math.max(
        parseInt(form.elements['newZone[FilterY]'].value, 10) || 0,
        MIN_FILTER_KERNEL);
    if (objectTooNarrowToFilter(wPx, hPx, fx, fy)) {
      alert(zoneObjectSizeStrings.filterTooSmall
          .replace('%s', fx).replace('%s', fy));
    }
  }

  function fmt(n) {
    return Math.round(n).toLocaleString();
  }

  function showTestResult() {
    const out = document.getElementById('zoneTestResult');
    if (!out || !test) return;
    const t = test.tally;
    const str = zoneObjectSizeStrings;
    // The answer first, then why: would it alarm, how many frames qualify,
    // and if not, what blocked most frames and how close the best one came.
    out.textContent = '';
    // Frames not known could hold the run that alarms: unless one is proven,
    // that is a "can't tell", not a no.
    const unsure = !t.wouldAlarm && t.unknown > 0;
    out.appendChild(checkMark(unsure ? undefined : t.wouldAlarm));
    const verdict = document.createElement('b');
    verdict.textContent = ' ' + (t.wouldAlarm ? str.testWouldAlarm :
        unsure ? str.testUnsure : str.testWouldNotAlarm);
    out.appendChild(verdict);
    const parts = [str.testQualify.replace('%1', t.scored).replace('%2', test.done.frames)];
    if (!t.wouldAlarm && !unsure) {
      const why = failureSummary(test.frames, test.now);
      if (t.scored) {
        parts.push(str.testNotInARow.replace('%1', test.done.alarmFrameCount));
      } else if (why) {
        parts.push(str.testClosest.replace('%1', stageName(why.stage))
            .replace('%2', fmt(why.best)).replace('%3', fmt(why.need)));
      }
    }
    if (t.unknown) parts.push(str.testUnknown.replace('%1', t.unknown));
    if (test.noVideo) parts.push(str.testNoVideo);
    out.appendChild(document.createTextNode(' · ' + parts.join(' · ')));
  }

  function stageName(stage) {
    const str = zoneObjectSizeStrings;
    return {alarm: str.replayAlarmed, filter: str.replayFiltered, blob: str.replayBlob,
      overload: str.replayOverload}[stage];
  }

  // Values the test was run with, or that the form holds now.
  function formValues() {
    const form = document.zoneForm;
    const values = {
      CheckMethod: form.elements['newZone[CheckMethod]'].value,
      Coords: getCoordString(),
    };
    MANAGED_FIELDS.forEach(function(field) {
      const el = form.elements['newZone[' + field + ']'];
      values[field] = el ? el.value : '';
    });
    return values;
  }

  // Settings that change what zma measures, not just how it is judged.
  function measuredKey(values) {
    return [values.MinPixelThreshold, values.MaxPixelThreshold, values.FilterX,
      values.FilterY, values.Coords].join('|');
  }

  // After any form change: re-judge every frame against the new thresholds,
  // or drop the result if zma has to measure again.
  function rejudgeTest() {
    if (!test) return;
    if (!zoneIsMeasurable()) {
      staleTest();
      const out = document.getElementById('zoneTestResult');
      if (out) out.textContent = zoneObjectSizeStrings.testNotDetecting;
      return;
    }
    const values = formValues();
    if (measuredKey(values) !== test.measured) {
      staleTest();
      return;
    }
    test.now = zoneThresholds(values, test.zone.area);
    test.frames.forEach(function(f) {
      f.verdict = whatIfVerdict(f.fz, test.ran, test.now);
    });
    applyOverload(test.frames, test.now.overloadFrames);
    test.tally = tallyVerdicts(test.frames, test.done.alarmFrameCount);
    const inAlarm = new Set(test.tally.alarmFrames);
    test.frames.forEach(function(f) {
      f.inAlarm = inAlarm.has(f.frame);
    });
    showTestResult();
    refreshOverlay();
    if (replay) showReplayFrame();
  }

  function staleTest() {
    closeReplay();
    if (!test) return;
    test = null;
    const out = document.getElementById('zoneTestResult');
    if (out) out.textContent = zoneObjectSizeStrings.testStale;
    refreshOverlay();
  }

  function runTest() {
    const btn = document.getElementById('zoneTestBtn');
    const select = document.getElementById('zoneTestEvent');
    const typed = document.getElementById('zoneTestEventId');
    const out = document.getElementById('zoneTestResult');
    if (!zoneIsMeasurable()) {
      out.textContent = zoneObjectSizeStrings.testNotDetecting;
      return;
    }
    const form = document.zoneForm;
    // A typed event id wins over the list; the server checks it is this
    // monitor's. Parsed here, as it also goes into the replay's image URLs.
    const eid = parseInt(typed && typed.value.trim() !== '' ? typed.value : select.value, 10);
    const data = {command: 'test', mid: zone.MonitorId, zid: form.elements['zid'].value, eid: eid};
    // zma measures with the form's pixel threshold, filter and blob size, but
    // with every alarmed/filtered limit off and all three stages on, so it
    // measures every stage of every frame instead of stopping at the first
    // that fails. rejudgeTest then applies the form's limits.
    // MaxBlobs is off too: exceeding it puts zma into overload, and frames lost
    // to overload cannot be judged again.
    // ponytail: blob size stays on - with none, noise blobs can exhaust
    // CheckAlarms' blob slots; so blob size changes judge from the largest blob.
    const sent = formValues();
    sent.CheckMethod = 'Blobs';
    ['MinAlarmPixels', 'MaxAlarmPixels', 'MinFilterPixels', 'MaxFilterPixels', 'MaxBlobs'].forEach(function(f) {
      sent[f] = '0';
    });
    Object.keys(sent).forEach(function(field) {
      data['newZone[' + field + ']'] = sent[field];
    });
    btn.disabled = true;
    closeReplay();
    test = null;
    refreshOverlay();
    // Bootstrap's spinner-grow, as export.js and the video view use.
    out.textContent = '';
    const spinner = document.createElement('span');
    spinner.className = 'spinner-grow spinner-grow-sm';
    spinner.setAttribute('role', 'status');
    spinner.setAttribute('aria-hidden', 'true');
    out.appendChild(spinner);
    out.appendChild(document.createTextNode(' ' + zoneObjectSizeStrings.testRunning));
    // Not getJSON: that takes the global ZM_WEB_AJAX_TIMEOUT (a few seconds)
    // and zma needs longer than that for any real event.
    $j.ajax({
      url: zmAuth.appendTo(thisUrl + '?view=request&request=zone'),
      data: data,
      dataType: 'json',
      timeout: 0,
    })
        .done(function(resp) {
          if (resp.result !== 'Ok') {
            out.textContent = resp.message;
            return;
          }
          test = buildTest(resp, data.zid, sent);
          if (!test) {
            out.textContent = zoneObjectSizeStrings.testNotDetecting;
            return;
          }
          rejudgeTest();
          // A setting zma measures changed while it ran: the result is stale.
          if (!test) return;
          // Closed while zma ran: its Exit button would be hidden with it.
          const panel = document.getElementById('zoneTestPanel');
          // The server sends the video for the event it ran, so a pick changed
          // mid-run cannot pair the wrong one.
          const canReplay = resp.video || resp.jpegs;
          if (canReplay && !(panel && panel.style.display === 'none')) openReplay(resp, eid);
          // Neither a video nor stored frames: say why there is no replay.
          test.noVideo = !canReplay;
          showTestResult();
        })
        .fail(function(jqxhr, textStatus, error) {
          out.textContent = textStatus + (error ? ': ' + error : '');
          logAjaxFail(jqxhr, textStatus, error);
        })
        .always(function() {
          btn.disabled = false;
        });
  }

  //
  // Replay: the event video in place of the live view, with zma's verdict for
  // the frame on screen drawn over it and a timeline of every frame.
  //
  let replay = null;
  // Alarm, qualifies, fails, not known - which check failed is in the panel on
  // the image. The colours are zone.css's, so the timeline and its legend cannot drift.
  function timelineColours() {
    const css = getComputedStyle(document.getElementById('zoneForm'));
    const v = function(name) {
      return css.getPropertyValue(name).trim();
    };
    return {pass: v('--zmPass'), fail: v('--zmFail'), unknown: v('--zmUnknown'), alarm: v('--zmInAlarm')};
  }

  // What the replay plays on: the event's mp4 in a <video>, or jpegPlayer over
  // its stored frames. Both offer the video calls the replay uses.
  let media = null;

  // A <video> or <img> filling the feed under the overlay, made once.
  function replayElement(tag, id) {
    const el = feedChild(tag, id, 'replayMedia', true);
    if (el && tag === 'video') {
      el.muted = true;
      el.playsInline = true;
      el.preload = 'auto';
    }
    return el;
  }

  // Plays an event saved as JPEGs, one image per FrameId from view=image, in
  // an <img>, behind the same calls and events as a <video>: currentTime,
  // duration, paused, play(), pause(), and loadedmetadata / timeupdate /
  // seeked / play / pause. Seconds map to frames at the event's own rate.
  // ponytail: loads one frame at a time, so a slow server plays slower than
  // real time; preload ahead if that matters.
  function jpegPlayer(img, eid, total, fps) {
    const events = new EventTarget();
    let id = 1;
    let timer = null;
    const fire = function(type) {
      events.dispatchEvent(new Event(type));
    };
    const show = function(n, type) {
      id = Math.min(Math.max(n, 1), total);
      // A missing frame is skipped over like a loaded one.
      img.onload = img.onerror = function() {
        fire(type);
        if (!player.paused) timer = setTimeout(advance, 1000 / fps);
      };
      img.src = zmAuth.appendTo(thisUrl + imagePrefix + eid + '&fid=' + id);
    };
    const advance = function() {
      if (id >= total) {
        player.pause();
      } else {
        show(id + 1, 'timeupdate');
      }
    };
    const player = {
      paused: true,
      duration: total / fps,
      addEventListener: events.addEventListener.bind(events),
      play: function() {
        if (!player.paused) return;
        player.paused = false;
        fire('play');
        advance();
      },
      pause: function() {
        clearTimeout(timer);
        if (player.paused) return;
        player.paused = true;
        fire('pause');
      },
    };
    Object.defineProperty(player, 'currentTime', {
      get: function() {
        return (id - 1) / fps;
      },
      set: function(t) {
        show(Math.round(t * fps) + 1, 'seeked');
      },
    });
    show(1, 'loadedmetadata');
    return player;
  }

  // The replay's listeners, once per player.
  function bindMedia(m) {
    if (m.zmBound) return;
    m.zmBound = true;
    m.addEventListener('loadedmetadata', function() {
      if (replay && m.duration && replay.totalFrames) {
        replay.tol = m.duration / replay.totalFrames / 2;
      }
      drawTimeline();
    });
    ['timeupdate', 'seeked'].forEach(function(type) {
      m.addEventListener(type, showReplayFrame);
    });
    const playBtn = document.getElementById('replayPlayBtn');
    const setPlayIcon = function() {
      if (playBtn) playBtn.firstElementChild.textContent = m.paused ? 'play_arrow' : 'pause';
    };
    m.addEventListener('play', setPlayIcon);
    m.addEventListener('pause', setPlayIcon);
  }

  // One colour per frame, each meaning one thing: in an alarm, qualifying but
  // too few in a row to alarm, failing, or not known.
  function frameColour(f, colours) {
    if (f.verdict.scored) return f.inAlarm ? colours.alarm : colours.pass;
    return f.verdict.unknown ? colours.unknown : colours.fail;
  }

  function drawTimeline() {
    const canvas = document.getElementById('replayTimeline');
    if (!canvas || !replay || !media) return;
    const dpr = window.devicePixelRatio || 1;
    const w = canvas.clientWidth * dpr;
    const h = canvas.clientHeight * dpr;
    if (canvas.width !== w) canvas.width = w;
    if (canvas.height !== h) canvas.height = h;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, w, h);
    ctx.fillStyle = 'rgba(128,128,128,0.25)';
    ctx.fillRect(0, 0, w, h);
    const duration = media.duration || replay.lastT || 1;
    const barW = Math.max(1, w / Math.max(replay.totalFrames, 1));
    // The legend lists only colours on the strip. Grey is the background.
    const seen = {legendNone: true};
    const colours = timelineColours();
    replay.frames.forEach(function(f) {
      const v = f.verdict;
      seen[v.scored ? (f.inAlarm ? 'legendInAlarm' : 'legendScored') :
          v.unknown ? 'legendRerun' : 'legendFail'] = true;
      ctx.fillStyle = frameColour(f, colours);
      ctx.fillRect(f.t / duration * w, 0, barW, h);
    });
    ctx.fillStyle = '#000000';
    ctx.fillRect(media.currentTime / duration * w - dpr, 0, 2 * dpr, h);
    document.querySelectorAll('#replayLegend span').forEach(function(item) {
      item.style.display = seen[item.className] ? '' : 'none';
    });
  }

  const REPLAY_RECTS = ['replayAlarmRect', 'replayFilterRect', 'replayBlobRect'];

  function drawReplayBox(id, box) {
    const pct = pixelBoxToPercent(box, monitorData[0].width, monitorData[0].height);
    if (pct) setRect(id, pct.a, pct.b); else hideRects([id]);
  }

  // Top left of the image, one row per check: the minimum set, what this frame
  // measured, and a tick or cross. rows: [[swatch class, name, min, got, ok]].
  function showReplayStats(rows) {
    const el = boxLabel('replayStats');
    if (!el) return;
    el.classList.add('replayStats');
    el.textContent = '';
    rows.forEach(function(r) {
      const row = document.createElement('div');
      row.className = r[0];
      row.appendChild(document.createTextNode(r[1] + '  ' + zoneObjectSizeStrings.replayMin + ' ' +
          fmt(r[2]) + ' · ' + zoneObjectSizeStrings.replayGot + ' ' + fmt(r[3]) + ' '));
      row.appendChild(checkMark(r[4]));
      el.appendChild(row);
    });
    el.style.display = '';
  }

  // Green tick, red cross, or ? for a check not reached - on the image and in
  // the frame line alike.
  function checkMark(v) {
    const mark = document.createElement('span');
    mark.className = 'checkMark' + (v === true ? ' ok' : v === false ? ' bad' : '');
    mark.textContent = v === true ? '\u2713' : v === false ? '\u2717' : '?';
    return mark;
  }

  function showReplayFrame() {
    if (!replay || !media) return;
    const f = frameAt(replay.frames, media.currentTime, replay.tol);
    const info = document.getElementById('replayFrameInfo');
    if (f) {
      const str = zoneObjectSizeStrings;
      // What ZM would do: checks stop at the first that fails, so the ones
      // after it - outline and panel row - are not shown, though zma measured
      // them.
      const replayStages = stageResults(f.verdict, test.now.checkMethod);
      const shown = function(stage) {
        return replayStages[stage] !== undefined || f.verdict.unknown === stage;
      };
      const z = test.now;
      drawReplayBox('replayAlarmRect', f.fz.alarmBox);
      drawReplayBox('replayFilterRect', shown('filter') ? f.fz.filterBox : null);
      drawReplayBox('replayBlobRect', shown('blob') ? f.fz.blobBox : null);
      showReplayStats([
        ['swatchAlarm', str.replayAlarmed, z.minAlarm, f.fz.alarm, replayStages.alarm, 'alarm'],
        ['swatchFilter', str.replayFiltered, z.minFilter, f.fz.filter, replayStages.filter, 'filter'],
        ['swatchBlob', str.replayBlob, z.minBlob, f.fz.largestBlob, replayStages.blob, 'blob'],
      ].filter(function(r) {
        return shown(r[5]);
      }));
      if (info) {
        // The numbers are in the panel on the image; this says what they add up to.
        info.textContent = str.replayFrame + ' ' + f.frame;
        const add = function(text) {
          info.appendChild(document.createTextNode(' · ' + text));
        };
        if (f.verdict.unknown) {
          add(zoneObjectSizeStrings.testUnknownFrame);
        } else if (f.verdict.scored) {
          add(f.inAlarm ? str.replayInAlarm : str.replayScored);
        } else {
          add(str.replayFailsAt.replace('%1', stageName(f.verdict.failedAt)));
        }
      }
    } else {
      hideRects(REPLAY_RECTS);
      hideRects(['replayStats']);
      if (info) info.textContent = media.currentTime.toFixed(2) + 's - ' + zoneObjectSizeStrings.replayNoMotion;
    }
    // Not refreshOverlay: the box does not change with the frame, and it would
    // rewrite the size fields on every frame of playback.
    updateLegend();
    drawTimeline();
  }

  // Kept so closeReplay can cancel it: a pending callback would otherwise
  // start a second chain on the next replay's video.
  let frameCallback = null;
  function onVideoFrame() {
    showReplayFrame();
    if (replay && media && media.requestVideoFrameCallback) {
      frameCallback = media.requestVideoFrameCallback(onVideoFrame);
    }
  }

  // This zone's frames from zma's output, judged as zma judged them until the
  // form changes.
  function buildTest(resp, zoneId, sent) {
    const tested = (resp.zones || []).filter(function(z) {
      return z.id == zoneId;
    })[0];
    if (!tested || !resp.done) return null;
    const ran = zoneThresholds(sent, tested.area);
    const frames = [];
    (resp.frames || []).forEach(function(f) {
      const fz = (f.zones || []).filter(function(z) {
        return z.id == zoneId;
      })[0];
      if (fz) frames.push({t: f.t, frame: f.frame, fz: fz});
    });
    frames.sort(function(x, y) {
      return x.frame - y.frame;
    });
    return {zone: tested, done: resp.done, frames: frames, ran: ran,
      measured: measuredKey(sent)};
  }

  // resp: the test response - a video to play, or JPEG frames of event eid.
  function openReplay(resp, eid) {
    if (!test) return;
    const total = test.done.frames;
    let video;
    if (resp.video) {
      video = replayElement('video', 'replayVideo');
      if (!video) return;
      video.src = resp.video;
    } else {
      const img = replayElement('img', 'replayImage');
      if (!img) return;
      // Frames sit at their FrameId at the event's average rate, the clock the
      // JPEG player keeps, rather than at zma's timestamps.
      const fps = resp.length > 0 ? total / resp.length : 10;
      test.frames.forEach(function(f) {
        f.t = (f.frame - 1) / fps;
      });
      video = jpegPlayer(img, eid, total, fps);
    }
    media = video;
    bindMedia(video);
    const frames = test.frames.filter(function(f) {
      return f.t !== undefined;
    });
    frames.sort(function(x, y) {
      return x.t - y.t;
    });
    const lastT = frames.length ? frames[frames.length - 1].t : 0;
    replay = {
      frames: frames, totalFrames: total, lastT: lastT,
      // Half a frame either side; refined once the video's length is known.
      tol: lastT && total ? lastT / total / 2 : 0.025,
    };
    if (stillShown) hideStill();
    liveStream(false);
    liveButtons(false, zoneObjectSizeStrings.liveOff);
    (resp.video ? video : document.getElementById('replayImage')).style.display = '';
    const controls = document.getElementById('replayControls');
    if (controls) controls.style.display = '';
    if (video.requestVideoFrameCallback) frameCallback = video.requestVideoFrameCallback(onVideoFrame);
    showReplayFrame();
  }

  // Stopped, not paused, under a recording: a paused zms keeps reporting
  // status, so the fps line kept moving. Restarted the way changePlayer does.
  function liveStream(on) {
    monitors.forEach(function(m) {
      if (on) m.start(); else m.stop();
    });
    pauseBtn.toggle(on);
    playBtn.toggle(!on);
  }

  // The live stream's buttons do nothing to the recording, so grey them out
  // while it is up.
  // offTitle: how to get back to live, shown on the greyed buttons.
  function liveButtons(on, offTitle) {
    const feed = imageFeed();
    if (feed) feed.classList.toggle('replaying', !on);
    ['playBtn', 'pauseBtn', 'analyseBtn'].forEach(function(id) {
      const b = document.getElementById(id);
      if (!b) return;
      if (on) {
        b.disabled = false;
        if (b.dataset.liveTitle !== undefined) b.title = b.dataset.liveTitle;
      } else {
        // Keep the live title from the first time, not an off one.
        if (!b.disabled) b.dataset.liveTitle = b.title;
        b.disabled = true;
        b.title = offTitle;
      }
    });
  }

  function closeReplay() {
    if (!replay) return;
    replay = null;
    if (frameCallback !== null && media && media.cancelVideoFrameCallback) {
      media.cancelVideoFrameCallback(frameCallback);
    }
    frameCallback = null;
    if (media) media.pause();
    media = null;
    const video = document.getElementById('replayVideo');
    if (video) {
      video.removeAttribute('src');
      video.load();
      video.style.display = 'none';
    }
    const img = document.getElementById('replayImage');
    if (img) {
      img.onload = img.onerror = null;
      img.removeAttribute('src');
      img.style.display = 'none';
    }
    const controls = document.getElementById('replayControls');
    if (controls) controls.style.display = 'none';
    hideRects(REPLAY_RECTS);
    hideRects(['replayStats']);
    updateLegend();
    liveButtons(true);
    liveStream(true);
    refreshOverlay();
  }

  function stepReplay(direction) {
    if (!replay || !media || !replay.frames.length) return;
    const now = media.currentTime;
    const eps = replay.tol / 2;
    const list = replay.frames;
    let target = null;
    if (direction > 0) {
      target = list.filter(function(f) {
        return f.t > now + eps;
      })[0];
    } else {
      const before = list.filter(function(f) {
        return f.t < now - eps;
      });
      target = before[before.length - 1];
    }
    if (target) {
      media.pause();
      media.currentTime = target.t;
    }
  }

  // The controls act on whichever player the replay is on.
  function initReplay() {
    const playBtn = document.getElementById('replayPlayBtn');
    if (playBtn) {
      playBtn.addEventListener('click', function() {
        if (!media) return;
        if (media.paused) media.play(); else media.pause();
      });
    }
    const prev = document.getElementById('replayPrevBtn');
    if (prev) {
      prev.addEventListener('click', function() {
        stepReplay(-1);
      });
    }
    const next = document.getElementById('replayNextBtn');
    if (next) {
      next.addEventListener('click', function() {
        stepReplay(1);
      });
    }
    const close = document.getElementById('replayCloseBtn');
    if (close) close.addEventListener('click', closeReplay);
    const canvas = document.getElementById('replayTimeline');
    if (canvas) {
      canvas.addEventListener('click', function(evt) {
        if (!replay || !media) return;
        const rect = canvas.getBoundingClientRect();
        const duration = media.duration || replay.lastT;
        media.pause();
        media.currentTime = (evt.clientX - rect.left) / rect.width * duration;
      });
    }
    window.addEventListener('resize', drawTimeline);
  }

  const NOT_SETTINGS = ['zoneTestEvent', 'zoneTestEventId', 'objectWidthPx', 'objectHeightPx'];

  function init() {
    const testBtn = document.getElementById('zoneTestBtn');
    if (testBtn) testBtn.addEventListener('click', runTest);
    // Opens the test under the image, like the other tools in that row.
    const testToggle = document.getElementById('zoneTestToggleBtn');
    const testPanel = document.getElementById('zoneTestPanel');
    if (testToggle && testPanel) {
      testToggle.addEventListener('click', function() {
        const open = testPanel.style.display === 'none';
        testPanel.style.display = open ? '' : 'none';
        // Same on/off look as the object size button.
        testToggle.classList.toggle('btn-primary', open);
        if (!open) closeReplay();
      });
    }
    if (document.zoneForm) {
      ['input', 'change'].forEach(function(type) {
        document.zoneForm.addEventListener(type, function(evt) {
          // Picking another event is not a settings change.
          // Picking an event, or typing a size not yet applied (applyTypedSize
          // rejudges once it is), is not a settings change.
          if (!NOT_SETTINGS.includes(evt.target.id)) rejudgeTest();
        });
      });
    }

    const btn = document.getElementById('objectSizeBtn');
    const feed = imageFeed();
    if (!btn || !feed) return;

    btn.addEventListener('click', function() {
      if (!armed && !zoneIsMeasurable()) {
        alert(zoneObjectSizeStrings.inactive);
        return;
      }
      // Arming starts a fresh measurement: the next drag must snapshot the
      // state as it is now, not as it was before the previous one.
      if (!armed) {
        // The box survives: drawing a new one replaces it, not arming.
        const keep = lastBox;
        forgetMeasurement();
        if (keep) {
          setLastBox(keep);
          refreshOverlay();
        }
      }
      setArmed(!armed);
    });

    const undoBtn = document.getElementById('objectSizeUndoBtn');
    if (undoBtn) undoBtn.addEventListener('click', undo);

    const imageBtn = document.getElementById('objectImageBtn');
    const replaceBtn = document.getElementById('objectImageReplaceBtn');
    const fileInput = document.getElementById('objectImageFile');
    updateStillButton();
    // First press asks for a file; after that it toggles still against live.
    if (imageBtn && fileInput) {
      imageBtn.addEventListener('click', function() {
        if (!hasStill()) {
          fileInput.click();
        } else if (stillShown) {
          showLive();
        } else {
          showStill();
        }
      });
    }
    if (replaceBtn && fileInput) {
      replaceBtn.addEventListener('click', function() {
        fileInput.click();
      });
    }
    if (fileInput) {
      fileInput.addEventListener('change', function() {
        const file = fileInput.files && fileInput.files[0];
        // Lets the same file be picked again later.
        fileInput.value = '';
        if (!file) return;
        if (file.type.indexOf('image/') !== 0) {
          alert(zoneObjectSizeStrings.notAnImage);
          return;
        }
        const reader = new FileReader();
        reader.onload = function() {
          // The MIME type only says what the file claims to be. Decode it
          // first, so a corrupt one complains instead of pausing the stream
          // to show a broken image.
          const probe = new Image();
          probe.onload = function() {
            loadStill(reader.result);
          };
          probe.onerror = function() {
            alert(zoneObjectSizeStrings.notAnImage);
          };
          probe.src = reader.result;
        };
        reader.onerror = function() {
          alert(zoneObjectSizeStrings.notAnImage);
        };
        reader.readAsDataURL(file);
      });
    }

    // Re-derives from the box already drawn, so the effect is immediate.
    RATIO_INPUTS.forEach(function(spec) {
      const el = document.getElementById(spec.id);
      if (!el) return;
      el.addEventListener('change', function() {
        // Normalized first: zone.php reads the cookie back through validInt(),
        // so anything but a whole number in range would come back as a
        // different ratio. Write it into the field too, so what is shown, what
        // is calculated and what is stored are the same number.
        const pct = normalizeRatio(el.value);
        if (pct === null) {
          el.value = el.defaultValue;
        } else {
          el.value = pct;
        }
        // Written here, read back in zone.php when the page next renders, the
        // way the player selector handles zmZonePlayer. No expiry argument, so
        // skin.js stores it with max-age to 2038 as most callers do.
        setCookie(spec.cookie, el.value);
        if (lastBox) {
          apply(lastBox.a, lastBox.b);
        } else {
          showSavedObject();
        }
      });
    });

    // Reset restores every field from initialValues and redraws the polygon,
    // so the measurement it would undo is already gone. Wrapping the handler
    // rather than adding a listener because ours has to run first: resetChanges
    // calls updateArea, which re-derives from lastBox (below), and that would
    // put the measurement straight back.
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
      const resetHandler = resetBtn.onclick;
      resetBtn.onclick = function(evt) {
        if (armed) setArmed(false);
        forgetMeasurement();
        showBoxSize(0, 0);
        const result = resetHandler ? resetHandler.call(this, evt) : undefined;
        // forgetMeasurement drew from the pre-reset fields; Reset has now put
        // the saved ObjectCoords back too.
        showInitialBox();
        return result;
      };
    }

    // The stored thresholds are percentages of the zone, so moving, adding or
    // removing a vertex changes what they mean in pixels. updateArea is the
    // one funnel every such edit goes through, so re-derive from the box that
    // is still drawn and the numbers keep describing the same object.
    // apply() only reaches updateAllPixelDisplays, never back here.
    const areaUpdater = global.updateArea;
    if (typeof areaUpdater === 'function') {
      global.updateArea = function() {
        const result = areaUpdater.apply(this, arguments);
        // Only a box measured in this session re-derives the settings; a
        // restored one is shown, so a vertex move cannot rewrite thresholds,
        // maxima or CheckMethod tuned by hand and saved with it.
        if (lastBox && undoSnapshot) {
          apply(lastBox.a, lastBox.b);
        } else {
          refreshOverlay();
        }
        rejudgeTest();
        return result;
      };
    }
    // Adding or deleting a point redraws the points without updateArea.
    const pointsDrawer = global.drawZonePoints;
    if (typeof pointsDrawer === 'function') {
      global.drawZonePoints = function() {
        const result = pointsDrawer.apply(this, arguments);
        rejudgeTest();
        return result;
      };
    }

    // A preset rewrites every field we manage. addEventListener rather than
    // patching applyPreset, which skin.js binds by reference at load time.
    const presetSelector = document.getElementById('presetSelector');
    // skin.js delegates data-on-change from the document, so applyPreset runs
    // after this listener; redraw once it has written the preset's values.
    if (presetSelector) {
      presetSelector.addEventListener('change', function() {
        forgetMeasurement();
        setTimeout(refreshOverlay, 0);
      });
    }

    const form = document.zoneForm;

    // applyZoneType disables every field we write once the zone stops being
    // measurable, so stand down rather than re-enable them behind it.
    form.elements['newZone[Type]'].addEventListener('change', function() {
      // Deferred like the preset: applyZoneType may not have run yet.
      if (zoneIsMeasurable()) {
        setTimeout(refreshOverlay, 0);
        return;
      }
      rejudgeTest();
      if (armed) setArmed(false);
      forgetMeasurement();
    });

    // Hands one field back to the user: unmark it, and move the snapshot on
    // so undo cannot revert an edit made after us. Our own writes assign
    // .value directly and raise no input event.
    function releaseField(field) {
      markChanged(field, false);
      const el = form.elements['newZone[' + field + ']'];
      if (undoSnapshot && el) undoSnapshot[field] = el.value;
      refreshOverlay();
    }

    MANAGED_FIELDS.forEach(function(field) {
      const el = form.elements['newZone[' + field + ']'];
      if (el) {
        el.addEventListener('input', function() {
          releaseField(field);
        });
      }
      // The pixel twin writes the percentage field from zone.js's own oninput,
      // which runs first, so the value is already there to snapshot.
      const px = document.getElementById(field + '_px');
      if (px) {
        px.addEventListener('input', function() {
          releaseField(field);
        });
      }
    });
    const checkMethod = form.elements['newZone[CheckMethod]'];
    if (checkMethod) {
      checkMethod.addEventListener('change', function() {
        releaseField('CheckMethod');
        // applyCheckMethod enables or disables the fields drawThresholds reads.
        setTimeout(refreshOverlay, 0);
      });
    }

    // Typing a size is the keyboard-operable equivalent of the drag, and is
    // also the easier way in when the object is not in front of the camera
    // right now. Same derivation, same warnings - only the box differs, being
    // centred in the frame rather than drawn where the object stood.
    function applyTypedSize() {
      if (!zoneIsMeasurable()) {
        alert(zoneObjectSizeStrings.inactive);
        return;
      }
      const w = document.getElementById('objectWidthPx');
      const h = document.getElementById('objectHeightPx');
      if (!w || !h) return;
      let box = boxPercentForPixels(parseFloat(w.value), parseFloat(h.value), captureFrame());
      if (!box) {
        alert(zoneObjectSizeStrings.sizeNeeded);
        return;
      }
      // Resize in place: keep the centre of the box already on screen.
      if (shownBox) {
        box = shiftBox(box, (shownBox.a.x + shownBox.b.x - box.a.x - box.b.x) / 2,
            (shownBox.a.y + shownBox.b.y - box.a.y - box.b.y) / 2, maxX, maxY);
      }
      drawRect(box.a, box.b);
      finishDrag(box.a, box.b);
    }

    // A typed size takes effect when the field is left, like the other zone
    // fields; saving it is still the Save button's job.
    ['objectWidthPx', 'objectHeightPx'].forEach(function(id) {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('change', function() {
        // Half-typed is not a mistake yet: wait for both sides.
        const w = document.getElementById('objectWidthPx');
        const h = document.getElementById('objectHeightPx');
        if (w && h && w.value !== '' && h.value !== '') applyTypedSize();
      });
      // Enter in a number field would otherwise submit the zone.
      el.addEventListener('keydown', function(evt) {
        if (evt.key !== 'Enter') return;
        evt.preventDefault();
        applyTypedSize();
      });
    });

    // Pointer rather than mouse events, so a touch or a pen draws the box too.
    // Capture keeps the moves coming when the finger leaves the image, which
    // is what the old window-level mouseup was for.
    feed.addEventListener('pointerdown', function(evt) {
      if (!armed) return;
      // Secondary buttons scroll or open menus; leave them alone.
      if (evt.pointerType === 'mouse' && evt.button !== 0) return;
      // Armed state can outlive measurability: applyPreset writes Type
      // directly and fires no change event, so the type listener never runs.
      // apply() would then re-enable the fields applyZoneType just disabled.
      if (!zoneIsMeasurable()) {
        setArmed(false);
        forgetMeasurement();
        return;
      }
      evt.preventDefault();
      dragging = true;
      if (feed.setPointerCapture) feed.setPointerCapture(evt.pointerId);
      startPct = toPercent(evt, feed);
      drawRect(startPct, startPct);
    });

    feed.addEventListener('pointermove', function(evt) {
      if (!armed || !dragging) return;
      const at = toPercent(evt, feed);
      drawRect(startPct, at);
      // Live: the thresholds track the box as it is drawn.
      apply(startPct, at);
    });

    function endDrag(evt) {
      if (!armed || !dragging) return;
      dragging = false;
      const at = toPercent(evt, feed);
      // Redraw before measuring: the pointer drifts between the last move and
      // the release, and the visible box must be the one measured.
      drawRect(startPct, at);
      finishDrag(startPct, at);
    }

    feed.addEventListener('pointerup', endDrag);
    // A cancelled pointer - the browser taking over for a scroll or a gesture
    // - never sends pointerup, and would otherwise leave the drag running.
    feed.addEventListener('pointercancel', endDrag);

    // Moving and resizing the box on screen. Moving keeps the area, so the
    // thresholds stand; resizing is a new measurement, anchored at the
    // opposite corner, and goes through apply() like a drawn box.
    let edit = null;
    function startEdit(evt, mode) {
      if (!shownBox) return false;
      if (evt.pointerType === 'mouse' && evt.button !== 0) return false;
      if (mode === 'resize' && !zoneIsMeasurable()) return false;
      evt.preventDefault();
      evt.stopPropagation();
      evt.currentTarget.setPointerCapture(evt.pointerId);
      edit = {mode: mode, from: toPercent(evt, feed), box: shownBox};
      return true;
    }
    function moveEdit(evt, done) {
      if (!edit) return;
      const at = toPercent(evt, feed);
      if (edit.mode === 'move') {
        const box = shiftBox(edit.box, at.x - edit.from.x, at.y - edit.from.y,
            maxX, maxY);
        // Placing the estimate makes it your box, so where you put it is
        // saved with the zone like a drawn one. Same area, same thresholds.
        drawRect(box.a, box.b);
        setLastBox({a: box.a, b: box.b});
        drawThresholds(box);
      } else {
        drawRect(edit.box.a, at);
        if (done) {
          finishDrag(edit.box.a, at);
        } else {
          apply(edit.box.a, at);
        }
      }
      if (done) edit = null;
    }

    // Armed or not, a press on the box moves it and one on the handle resizes
    // it; startEdit stops it reaching the feed, so only a press outside the
    // box starts a new one.
    [[document.getElementById('objectRect'), 'move'], [resizeHandle(), 'resize']].forEach(function(t) {
      const el = t[0];
      if (!el) return;
      el.addEventListener('pointerdown', function(evt) {
        startEdit(evt, t[1]);
      });
      el.addEventListener('pointermove', function(evt) {
        moveEdit(evt, false);
      });
      ['pointerup', 'pointercancel'].forEach(function(type) {
        el.addEventListener(type, function(evt) {
          moveEdit(evt, true);
        });
      });
    });

    initReplay();
    showInitialBox();
  }

  if (typeof document !== 'undefined' && document.addEventListener) {
    document.addEventListener('DOMContentLoaded', init);
  }
})(typeof window !== 'undefined' ? window : globalThis);
