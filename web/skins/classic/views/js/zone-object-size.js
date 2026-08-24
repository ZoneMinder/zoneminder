'use strict';
//
// Derives a zone's detection thresholds from a rectangle drawn round the
// smallest object that should alarm. Ratios and their defaults are in
// zone.php. Coordinates use the editor's percent space (0..100 per axis).
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

  // The filter keeps a pixel only if a fully-white kernel-sized window covers
  // it (zm_zone.cpp:338), so once the kernel reaches the box's narrow side the
  // object is erased entirely and the zone silently stops alarming.
  // Only catches the certain case - real limbs are thinner than their box.
  function objectTooNarrowToFilter(rectWpx, rectHpx, kernel) {
    return Math.min(rectWpx, rectHpx) <= Math.max(kernel || 0, MIN_FILTER_KERNEL);
  }

  const api = {
    MIN_FILTER_KERNEL: MIN_FILTER_KERNEL,
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
  global.ZM_ZoneObjectSize = api;

  //
  // Browser-only below here.
  //

  let armed = false;
  let dragging = false;
  let startPct = null;
  let undoSnapshot = null;
  // The last box measured, so a change to the fraction can re-derive from it.
  let lastBox = null;

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
  let snapshotTaken = false;

  function imageFeed() {
    return document.getElementById('imageFeed' + zone.MonitorId);
  }

  // A still can stand in for the live view. Read as a data URL - nothing is
  // uploaded. Kept hidden rather than removed so toggling reuses it.
  let stillShown = false;

  function stillElement() {
    let img = document.getElementById('objectStill');
    if (img) return img;
    const feed = imageFeed();
    if (!feed) return null;
    img = document.createElement('img');
    img.id = 'objectStill';
    img.style.display = 'none';
    const svg = document.getElementById('zoneSVG');
    // After the stream so it covers the live view, before the SVG so the zone
    // outline still paints on top.
    if (svg && svg.parentNode === feed) {
      feed.insertBefore(img, svg);
    } else {
      feed.appendChild(img);
    }
    return img;
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
    // Not monitors[i].pause(), so the play/pause buttons stay in sync.
    streamCmdPause();
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
    streamCmdPlay();
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
  function sizeLabel() {
    let el = document.getElementById('objectSizeLabel');
    if (!el) {
      const feed = imageFeed();
      if (!feed) return null;
      el = document.createElement('div');
      el.id = 'objectSizeLabel';
      feed.appendChild(el);
    }
    return el;
  }

  // Capture pixels, so the figure means the same at any window size.
  function drawSizeLabel(x, y, w, h) {
    const el = sizeLabel();
    if (!el) return;
    const wPx = Math.round(w / maxX * monitorData[0].width);
    const hPx = Math.round(h / maxY * monitorData[0].height);
    el.textContent = wPx + '×' + hPx + ' = ' + (wPx * hPx).toLocaleString() + ' px';
    el.style.left = x + '%';
    el.style.top = y + '%';
    // Flips inside the box when it is near the top edge.
    el.classList.toggle('below', y < 6);
    el.style.display = '';
  }

  function drawRect(a, b) {
    const r = document.getElementById('objectRect');
    if (!r) return;
    const x = Math.min(a.x, b.x);
    const y = Math.min(a.y, b.y);
    const w = Math.abs(b.x - a.x);
    const h = Math.abs(b.y - a.y);
    r.setAttribute('x', x);
    r.setAttribute('y', y);
    r.setAttribute('width', w);
    r.setAttribute('height', h);
    r.style.display = '';
    drawSizeLabel(x, y, w, h);
  }

  function hideRect() {
    const r = document.getElementById('objectRect');
    if (r) r.style.display = 'none';
    const el = document.getElementById('objectSizeLabel');
    if (el) el.style.display = 'none';
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
    snapshotTaken = false;
    lastBox = null;
    const btn = document.getElementById('objectSizeUndoBtn');
    if (btn) btn.style.display = 'none';
    const panel = document.getElementById('rectangleSettings');
    if (panel && !armed) panel.style.display = 'none';
    hideRect();
  }

  function setArmed(on) {
    armed = on;
    const btn = document.getElementById('objectSizeBtn');
    if (btn) btn.classList.toggle('btn-primary', on);
    // Stays up after a measurement so the ratios can be tuned against the box.
    const panel = document.getElementById('rectangleSettings');
    if (panel) panel.style.display = (on || lastBox) ? '' : 'none';
    const feed = imageFeed();
    if (feed) feed.style.cursor = on ? 'crosshair' : '';
    // Stop the zone vertices swallowing the drag while measuring.
    const points = document.querySelectorAll('.zonePoint');
    for (let i = 0; i < points.length; i++) {
      points[i].style.pointerEvents = on ? 'none' : '';
    }
  }

  function takeSnapshot(form) {
    undoSnapshot = {};
    MANAGED_FIELDS.forEach(function(field) {
      const el = form.elements['newZone[' + field + ']'];
      undoSnapshot[field] = el ? el.value : '';
    });
    undoSnapshot.CheckMethod = form.elements['newZone[CheckMethod]'].value;
    const btn = document.getElementById('objectSizeUndoBtn');
    if (btn) btn.style.display = '';
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
    forgetMeasurement();
  }

  // Runs on every mousemove, so nothing here may block - no alert or confirm.
  function apply(a, b) {
    const form = document.zoneForm;
    // Capture pixels: the units CheckAlarms counts in.
    const wPx = Math.abs(b.x - a.x) / maxX * monitorData[0].width;
    const hPx = Math.abs(b.y - a.y) / maxY * monitorData[0].height;
    const zonePx = zone.Area / monitorArea * monitorPixelArea;
    // A box this small is normal mid-drag. Change nothing, say nothing.
    if (!(wPx * hPx > 0) || !(zonePx > 0)) return false;

    if (!snapshotTaken) {
      takeSnapshot(form);
      snapshotTaken = true;
    }

    lastBox = {a: {x: a.x, y: a.y}, b: {x: b.x, y: b.y}};

    // Sensitivity is read from the snapshot, not the live fields: mid-drag
    // those already hold our own writes.
    const values = zoneSettingsForObject(
        wPx * hPx, zonePx, undoSnapshot, currentRatios());
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
    return true;
  }

  // Warnings live here, not in apply(), which runs on every mousemove.
  function finishDrag(a, b) {
    if (!apply(a, b)) return;
    const form = document.zoneForm;
    const wPx = Math.abs(b.x - a.x) / maxX * monitorData[0].width;
    const hPx = Math.abs(b.y - a.y) / maxY * monitorData[0].height;
    const kernel = Math.max(
        parseInt(form.elements['newZone[FilterX]'].value, 10) || 0,
        parseInt(form.elements['newZone[FilterY]'].value, 10) || 0);
    if (objectTooNarrowToFilter(wPx, hPx, kernel)) {
      alert(zoneObjectSizeStrings.filterTooSmall.replace(/%s/g, kernel));
    }
  }

  function init() {
    const btn = document.getElementById('objectSizeBtn');
    const feed = imageFeed();
    if (!btn || !feed) return;

    btn.addEventListener('click', function() {
      if (!armed && !zoneIsMeasurable()) {
        alert(zoneObjectSizeStrings.inactive);
        return;
      }
      if (!armed) {
        // Arming starts a fresh measurement, so drop the previous one's marks.
        hideRect();
        clearChangeMarks();
      }
      setArmed(!armed);
    });

    const undoBtn = document.getElementById('objectSizeUndoBtn');
    if (undoBtn) undoBtn.addEventListener('click', undo);

    // zone.js already binds streamCmdPlay here, so this only drops the still.
    const playButton = document.getElementById('playBtn');
    if (playButton) {
      playButton.addEventListener('click', function() {
        if (stillShown) hideStill();
      });
    }

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
          loadStill(reader.result);
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
        // Written here, read back in zone.php when the page next renders, the
        // way the player selector handles zmZonePlayer. No expiry argument, so
        // skin.js stores it with max-age to 2038 as most callers do.
        setCookie(spec.cookie, el.value);
        if (lastBox) apply(lastBox.a, lastBox.b);
      });
    });

    // A preset rewrites every field we manage. addEventListener rather than
    // patching applyPreset, which skin.js binds by reference at load time.
    const presetSelector = document.getElementById('presetSelector');
    if (presetSelector) presetSelector.addEventListener('change', forgetMeasurement);

    // Unmarks just that field; the rest of the measurement stands. Our own
    // writes assign .value directly and raise no input event.
    const form = document.zoneForm;
    MANAGED_FIELDS.forEach(function(field) {
      const el = form.elements['newZone[' + field + ']'];
      if (el) {
        el.addEventListener('input', function() {
          markChanged(field, false);
        });
      }
      // The pixel twin writes back to the percentage field without an event.
      const px = document.getElementById(field + '_px');
      if (px) {
        px.addEventListener('input', function() {
          markChanged(field, false);
        });
      }
    });
    const checkMethod = form.elements['newZone[CheckMethod]'];
    if (checkMethod) {
      checkMethod.addEventListener('change', function() {
        markChanged('CheckMethod', false);
      });
    }

    feed.addEventListener('mousedown', function(evt) {
      if (!armed) return;
      evt.preventDefault();
      dragging = true;
      // snapshotTaken is deliberately not reset: every box in one armed
      // session is measured against the state before the first, so undo and
      // the highlighting stay anchored there.
      startPct = toPercent(evt, feed);
      drawRect(startPct, startPct);
    });

    feed.addEventListener('mousemove', function(evt) {
      if (!armed || !dragging) return;
      const at = toPercent(evt, feed);
      drawRect(startPct, at);
      // Live: the thresholds track the box as it is drawn.
      apply(startPct, at);
    });

    // On window, not the feed, so releasing outside the image still finishes.
    window.addEventListener('mouseup', function(evt) {
      if (!armed || !dragging) return;
      dragging = false;
      const at = toPercent(evt, feed);
      // Redraw before measuring: the pointer drifts between the last mousemove
      // and the release, and the visible box must be the one measured.
      drawRect(startPct, at);
      finishDrag(startPct, at);
    });
  }

  if (typeof document !== 'undefined' && document.addEventListener) {
    document.addEventListener('DOMContentLoaded', init);
  }
})(typeof window !== 'undefined' ? window : globalThis);
