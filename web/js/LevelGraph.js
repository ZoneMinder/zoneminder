'use strict';

//
// Builds the level graph under the event video: motion score and audio level
// over the length of the event, on one time axis.
//
// This replaces the old #alarmCues strip, which drew one flat red band per
// alarm period. That strip tried to encode the score as a bar height
// ('height: '+frame.Score+'px') but never could: the frames ajax whitelist in
// web/ajax/status.php did not return Score, so every bar got 'height:
// undefinedpx' and fell back to the stylesheet's height: 100%. The alarm
// periods are kept here as shaded bands; the scores are now actually drawn.
//
// The two series do not share a scale. Audio level is 0-100 by construction
// (see AudioDetector::LevelFromRms), but a motion score is a sum over zones
// with no upper bound, so pinning both to 0-100 would flatten the audio line
// against the floor on any event that scored above 100. Each is scaled to its
// own maximum and the exact values are read out on hover, which is what the
// numbers are actually wanted for.
//

// Nothing is drawn below this many pixels of graph; smaller than this and the
// lines are indistinguishable from the axis.
const LEVEL_GRAPH_MIN_HEIGHT = 12;

// Room above the top sample so a peak's stroke is not clipped by the viewport.
const LEVEL_GRAPH_TOP_PAD = 2;

// Reserved along the bottom for the clock labels. The lines stop above it, so
// a series sitting near its floor -- which is where a quiet audio track lives
// for most of an event -- does not run through the text.
const LEVEL_GRAPH_LABEL_GUTTER = 10;

/**
 * Normalises the rows the frames ajax returns into plottable samples.
 *
 * Everything arrives as strings from JSON, and Delta is a decimal(8,2). Rows
 * with an unparseable Delta are dropped rather than plotted at 0, which would
 * drag a line back to the start of the event.
 *
 * @param {Array} frames Rows from ?request=status&entity=frames.
 * @param {number} eventLength Event length in seconds.
 * @return {Object} samples, motionMax, audioMax, hasAudio and hasScores.
 */
function levelGraphSeries(frames, eventLength) {
  const samples = [];
  let motionMax = 0;
  let audioMax = 0;

  const length = (typeof eventLength === 'number' && eventLength > 0) ? eventLength : 0;

  for (let i = 0; i < (frames ? frames.length : 0); i++) {
    const frame = frames[i];
    const t = parseFloat(frame.Delta);
    if (!isFinite(t)) continue;

    // A Delta past the end of the event would otherwise plot off the right
    // edge. Clamping is right rather than dropping: the frame happened.
    const clamped = length ? Math.min(Math.max(t, 0), length) : Math.max(t, 0);

    const score = Math.max(0, parseInt(frame.Score, 10) || 0);
    const audio = Math.min(100, Math.max(0, parseInt(frame.AudioLevel, 10) || 0));

    if (score > motionMax) motionMax = score;
    if (audio > audioMax) audioMax = audio;

    samples.push({t: clamped, score: score, audio: audio, type: frame.Type});
  }

  samples.sort((a, b) => a.t - b.t);

  return {
    samples: samples,
    motionMax: motionMax,
    audioMax: audioMax,
    // An event recorded before Frames.AudioLevel existed, or by a monitor with
    // no audio stream, has 0 in every row. That is not a measurement of
    // silence, so no line is drawn for it -- a flat line along the floor would
    // claim the audio was listened to and found quiet. Note that having
    // AudioDetection off is not one of these cases: the level is recorded
    // either way, precisely so the graph can be used to pick a threshold.
    hasAudio: audioMax > 0,
    hasScores: motionMax > 0,
  };
}

/**
 * Alarm periods, as intervals to shade behind the lines.
 *
 * A frame's Type describes the frame, so it is taken to cover from its own
 * Delta until the next frame's, and the last one until the end of the event.
 * Adjacent alarm intervals are merged so a run of alarm frames is one band.
 *
 * @param {Array} samples From levelGraphSeries.
 * @param {number} eventLength Event length in seconds.
 * @return {Array} {start, end} in seconds, in order, non-overlapping.
 */
function levelGraphBands(samples, eventLength) {
  const bands = [];
  if (!samples || !samples.length) return bands;

  for (let i = 0; i < samples.length; i++) {
    if (samples[i].type !== 'Alarm') continue;

    const start = samples[i].t;
    const end = (i + 1 < samples.length) ? samples[i + 1].t : eventLength;
    if (!(end > start)) continue;

    const last = bands.length ? bands[bands.length - 1] : null;
    if (last && last.end >= start) {
      last.end = Math.max(last.end, end);
    } else {
      bands.push({start: start, end: end});
    }
  }
  return bands;
}

/**
 * The coordinate mapping for one rendering, at one container width.
 *
 * Kept separate from the drawing so the hover readout and the polylines agree
 * on where a given second sits, and so the arithmetic is testable without a
 * DOM.
 *
 * @param {Object} opts width, height, eventLength, motionMax.
 * @return {Object} x(), yMotion(), yAudio() and tAt().
 */
function levelGraphScale(opts) {
  const width = Math.max(0, opts.width || 0);
  const height = Math.max(LEVEL_GRAPH_MIN_HEIGHT, opts.height || 0);
  const length = (opts.eventLength > 0) ? opts.eventLength : 1;
  // A motionMax of 0 would divide by zero; with no scores there is no line to
  // draw anyway, so the value only has to be safe.
  const motionMax = (opts.motionMax > 0) ? opts.motionMax : 1;
  // The floor the lines sit on, above the label gutter. On a graph too short
  // to give the gutter away, the labels overlap rather than leaving no plot.
  const baseline = (height > LEVEL_GRAPH_LABEL_GUTTER * 2) ?
      (height - LEVEL_GRAPH_LABEL_GUTTER) : height;
  const plot = Math.max(1, baseline - LEVEL_GRAPH_TOP_PAD);

  return {
    width: width,
    height: height,
    baseline: baseline,
    x: function(t) {
      const clamped = Math.min(Math.max(t, 0), length);
      return (clamped / length) * width;
    },
    yMotion: function(score) {
      const fraction = Math.min(Math.max(score, 0), motionMax) / motionMax;
      return baseline - (fraction * plot);
    },
    yAudio: function(level) {
      const fraction = Math.min(Math.max(level, 0), 100) / 100;
      return baseline - (fraction * plot);
    },
    // Inverse of x(), for turning a mouse position back into an event time.
    tAt: function(px) {
      if (!width) return 0;
      const clamped = Math.min(Math.max(px, 0), width);
      return (clamped / width) * length;
    },
  };
}

/**
 * The sample nearest a point in time, for the hover readout.
 *
 * Nearest rather than preceding: rows are written well below the capture rate,
 * so the gap between them is often seconds, and snapping backwards would
 * report a stale value for most of the width of the graph.
 *
 * @param {Array} samples From levelGraphSeries, sorted by t.
 * @param {number} t Seconds into the event.
 * @return {Object} The nearest sample, or null when there are none.
 */
function levelGraphSampleAt(samples, t) {
  if (!samples || !samples.length) return null;

  let lo = 0;
  let hi = samples.length - 1;
  while (lo < hi) {
    const mid = (lo + hi) >> 1;
    if (samples[mid].t < t) {
      lo = mid + 1;
    } else {
      hi = mid;
    }
  }

  const after = samples[lo];
  const before = (lo > 0) ? samples[lo - 1] : null;
  if (!before) return after;
  return (Math.abs(after.t - t) < Math.abs(t - before.t)) ? after : before;
}

/**
 * Turns samples into the points attribute of an SVG polyline.
 *
 * @param {Array} samples From levelGraphSeries.
 * @param {Object} scale From levelGraphScale.
 * @param {string} key 'score' or 'audio'.
 * @return {string} Space separated "x,y" pairs.
 */
function levelGraphPoints(samples, scale, key) {
  const points = [];
  const y = (key === 'audio') ? scale.yAudio : scale.yMotion;
  for (let i = 0; i < samples.length; i++) {
    points.push(round2(scale.x(samples[i].t)) + ',' + round2(y(samples[i][key])));
  }
  return points.join(' ');
}

// Two decimals is under a thousandth of a pixel at any width a browser will
// render, and keeps the markup from being mostly float noise.
function round2(n) {
  return Math.round(n * 100) / 100;
}

function escapeXml(text) {
  return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
}

/**
 * Renders the whole graph as an SVG string.
 *
 * Pixel coordinates rather than a unit viewBox with preserveAspectRatio=none:
 * the graph is re-rendered on every scale change anyway, and a stretched
 * viewBox would scale the stroke widths and the text with it.
 *
 * @param {Object} opts samples, bands, width, height, eventLength, motionMax,
 *     hasAudio, hasScores, labels (array of {t, text}).
 * @return {string} SVG markup.
 */
function renderLevelGraph(opts) {
  const scale = levelGraphScale(opts);
  const samples = opts.samples || [];
  const parts = [];

  parts.push('<svg class="level-graph" width="' + round2(scale.width) + '"' +
             ' height="' + round2(scale.height) + '"' +
             ' viewBox="0 0 ' + round2(scale.width) + ' ' + round2(scale.height) + '"' +
             ' preserveAspectRatio="none" aria-hidden="true">');

  const bands = opts.bands || [];
  for (let i = 0; i < bands.length; i++) {
    const x1 = scale.x(bands[i].start);
    const x2 = scale.x(bands[i].end);
    parts.push('<rect class="level-graph-band" x="' + round2(x1) + '" y="0"' +
               ' width="' + round2(Math.max(1, x2 - x1)) + '"' +
               ' height="' + round2(scale.baseline) + '"/>');
  }

  // The floor the lines are read against.
  parts.push('<line class="level-graph-axis" x1="0" y1="' + round2(scale.baseline) +
             '" x2="' + round2(scale.width) + '" y2="' + round2(scale.baseline) + '"/>');

  const labels = opts.labels || [];
  for (let i = 0; i < labels.length; i++) {
    const lx = scale.x(labels[i].t);
    // A short mark in the gutter rather than a full height rule: the graph is
    // only tens of pixels tall and ten rules through it drown the lines.
    parts.push('<line class="level-graph-tick" x1="' + round2(lx) +
               '" y1="' + round2(scale.baseline) +
               '" x2="' + round2(lx) + '" y2="' + round2(scale.height) + '"/>');
    // Nudged off the tick, and anchored at the start so the last label cannot
    // run past the right edge.
    parts.push('<text class="level-graph-label" x="' + round2(lx + 2) + '"' +
               ' y="' + round2(scale.height - 1) + '">' + escapeXml(labels[i].text) + '</text>');
  }

  if (opts.hasAudio && samples.length) {
    parts.push('<polyline class="level-graph-audio" fill="none" points="' +
               levelGraphPoints(samples, scale, 'audio') + '"/>');
  }
  if (opts.hasScores && samples.length) {
    parts.push('<polyline class="level-graph-motion" fill="none" points="' +
               levelGraphPoints(samples, scale, 'score') + '"/>');
  }

  parts.push('</svg>');
  return parts.join('');
}

/**
 * The hover readout for a point in time, as plain text.
 *
 * Returns the parts rather than a formatted string for the time, because the
 * caller already has the localised clock time from the seek indicator.
 *
 * @param {Object} series From levelGraphSeries.
 * @param {number} t Seconds into the event.
 * @return {string} e.g. "motion 42, audio 17", or '' when there is nothing.
 */
function levelGraphReadout(series, t) {
  if (!series) return '';
  const sample = levelGraphSampleAt(series.samples, t);
  if (!sample) return '';

  const parts = [];
  if (series.hasScores) parts.push('motion ' + sample.score);
  if (series.hasAudio) parts.push('audio ' + sample.audio);
  return parts.join(', ');
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    LEVEL_GRAPH_MIN_HEIGHT,
    LEVEL_GRAPH_TOP_PAD,
    LEVEL_GRAPH_LABEL_GUTTER,
    levelGraphSeries,
    levelGraphBands,
    levelGraphScale,
    levelGraphSampleAt,
    levelGraphPoints,
    levelGraphReadout,
    renderLevelGraph,
  };
}
