'use strict';

// The event view's cue strip became a line graph of motion score and audio
// level. These cover the arithmetic behind it: what the frame rows mean once
// parsed, where a point in time lands in pixels, and what the hover readout
// says. The DOM parts (reading the styled height, writing into #indicator) are
// not covered here.

const assert = require('assert');
const path = require('path');
const LG = require(path.join(__dirname, '../../web/js/LevelGraph.js'));

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + e.message);
    failed++;
  }
}

// Rows arrive from JSON exactly like this: every value a string, Delta a
// decimal(8,2), Type one of Normal/Bulk/Alarm.
function row(delta, type, score, audio) {
  return {Delta: String(delta), Type: type, Score: String(score), AudioLevel: String(audio)};
}

console.log('levelGraphSeries');

test('parses the string values the frames ajax returns', () => {
  const s = LG.levelGraphSeries([row(0, 'Normal', 0, 3), row('1.50', 'Alarm', 42, 61)], 10);
  assert.strictEqual(s.samples.length, 2);
  assert.deepStrictEqual(s.samples[1], {t: 1.5, score: 42, audio: 61, type: 'Alarm'});
  assert.strictEqual(s.motionMax, 42);
  assert.strictEqual(s.audioMax, 61);
});

test('a score above 100 sets the motion scale, it is not clamped', () => {
  // Score is a sum over zones with no upper bound. Clamping it to the audio
  // range would silently flatten the top of the motion line.
  const s = LG.levelGraphSeries([row(0, 'Alarm', 640, 0)], 10);
  assert.strictEqual(s.motionMax, 640);
  assert.strictEqual(s.samples[0].score, 640);
});

test('audio level is clamped to 0-100, the range the detector produces', () => {
  const s = LG.levelGraphSeries([row(0, 'Normal', 0, 500), row(1, 'Normal', 0, -7)], 10);
  assert.strictEqual(s.samples[0].audio, 100);
  assert.strictEqual(s.samples[1].audio, 0);
});

test('all-zero audio is reported as no audio, not as measured silence', () => {
  // Every row of every event recorded before Frames.AudioLevel existed, and
  // every row from a monitor with no audio, is 0. Drawing a flat line along
  // the floor for those would claim silence was measured. A monitor with
  // AudioDetection off is not one of these: it still records levels.
  const s = LG.levelGraphSeries([row(0, 'Normal', 5, 0), row(1, 'Alarm', 9, 0)], 10);
  assert.strictEqual(s.hasAudio, false);
  assert.strictEqual(s.hasScores, true);
});

test('a single non-zero audio row is enough to draw the line', () => {
  const s = LG.levelGraphSeries([row(0, 'Normal', 0, 0), row(1, 'Normal', 0, 1)], 10);
  assert.strictEqual(s.hasAudio, true);
});

test('a missing AudioLevel column reads as 0 rather than NaN', () => {
  // An upgraded install serving a cached ajax response, or a skin that asks
  // for a narrower element list.
  const s = LG.levelGraphSeries([{Delta: '2.00', Type: 'Alarm', Score: '7'}], 10);
  assert.strictEqual(s.samples[0].audio, 0);
  assert.strictEqual(s.hasAudio, false);
  assert.strictEqual(s.samples[0].score, 7);
});

test('rows with an unparseable Delta are dropped, not plotted at zero', () => {
  const s = LG.levelGraphSeries([row(0, 'Normal', 1, 1), {Delta: null, Score: '9', AudioLevel: '9'}], 10);
  assert.strictEqual(s.samples.length, 1);
});

test('a Delta past the end of the event is clamped to the end', () => {
  const s = LG.levelGraphSeries([row(99, 'Alarm', 1, 1)], 10);
  assert.strictEqual(s.samples[0].t, 10);
});

test('samples come back in time order whatever order the rows were in', () => {
  const s = LG.levelGraphSeries([row(5, 'Normal', 1, 1), row(1, 'Normal', 2, 2)], 10);
  assert.deepStrictEqual(s.samples.map((x) => x.t), [1, 5]);
});

test('no frames gives an empty, drawable series', () => {
  const s = LG.levelGraphSeries([], 10);
  assert.deepStrictEqual(s.samples, []);
  assert.strictEqual(s.hasAudio, false);
  assert.strictEqual(s.hasScores, false);
});

console.log('levelGraphBands');

test('an alarm frame covers the time until the next frame', () => {
  const s = LG.levelGraphSeries([row(0, 'Normal', 0, 0), row(2, 'Alarm', 9, 0), row(3, 'Normal', 0, 0)], 10);
  assert.deepStrictEqual(LG.levelGraphBands(s.samples, 10), [{start: 2, end: 3}]);
});

test('a run of alarm frames is one band, not one per frame', () => {
  const s = LG.levelGraphSeries(
      [row(1, 'Alarm', 9, 0), row(2, 'Alarm', 9, 0), row(3, 'Alarm', 9, 0), row(4, 'Normal', 0, 0)], 10);
  assert.deepStrictEqual(LG.levelGraphBands(s.samples, 10), [{start: 1, end: 4}]);
});

test('separate alarms stay separate bands', () => {
  const s = LG.levelGraphSeries(
      [row(1, 'Alarm', 9, 0), row(2, 'Normal', 0, 0), row(5, 'Alarm', 9, 0), row(6, 'Normal', 0, 0)], 10);
  assert.deepStrictEqual(LG.levelGraphBands(s.samples, 10), [{start: 1, end: 2}, {start: 5, end: 6}]);
});

test('a trailing alarm frame runs to the end of the event', () => {
  const s = LG.levelGraphSeries([row(1, 'Normal', 0, 0), row(8, 'Alarm', 9, 0)], 10);
  assert.deepStrictEqual(LG.levelGraphBands(s.samples, 10), [{start: 8, end: 10}]);
});

test('no alarm frames means no bands', () => {
  const s = LG.levelGraphSeries([row(1, 'Normal', 0, 0), row(2, 'Bulk', 0, 0)], 10);
  assert.deepStrictEqual(LG.levelGraphBands(s.samples, 10), []);
});

console.log('levelGraphScale');

test('time maps across the full width', () => {
  const sc = LG.levelGraphScale({width: 800, height: 48, eventLength: 20, motionMax: 100});
  assert.strictEqual(sc.x(0), 0);
  assert.strictEqual(sc.x(10), 400);
  assert.strictEqual(sc.x(20), 800);
});

test('times outside the event are clamped to the edges', () => {
  const sc = LG.levelGraphScale({width: 800, height: 48, eventLength: 20, motionMax: 100});
  assert.strictEqual(sc.x(-5), 0);
  assert.strictEqual(sc.x(999), 800);
});

test('y is inverted: zero sits on the baseline, the max near the top', () => {
  const sc = LG.levelGraphScale({width: 800, height: 48, eventLength: 20, motionMax: 200});
  assert.strictEqual(sc.yMotion(0), sc.baseline);
  assert.strictEqual(sc.yMotion(200), LG.LEVEL_GRAPH_TOP_PAD);
  assert.ok(sc.yMotion(100) < sc.yMotion(0) && sc.yMotion(100) > sc.yMotion(200));
});

test('the baseline leaves the clock labels a gutter to sit in', () => {
  // Audio idles near its floor for most of an event, so lines that ran to the
  // bottom edge would cross the times written there.
  const sc = LG.levelGraphScale({width: 800, height: 48, eventLength: 20, motionMax: 200});
  assert.strictEqual(sc.baseline, 48 - LG.LEVEL_GRAPH_LABEL_GUTTER);
  assert.ok(sc.yAudio(0) < sc.height, 'the floor must sit above the bottom edge');
});

test('a graph too short for a gutter keeps its plot instead', () => {
  // Better to have the labels overlap than to have no room left to draw in.
  const sc = LG.levelGraphScale({width: 800, height: 14, eventLength: 20, motionMax: 200});
  assert.strictEqual(sc.baseline, sc.height);
  assert.ok(sc.yMotion(200) < sc.yMotion(0), 'no plot area left');
});

test('the two series use their own scales', () => {
  // A motion max of 200 and an audio level of 100 must not draw at the same
  // height, or the graph would imply they are comparable numbers.
  const sc = LG.levelGraphScale({width: 800, height: 48, eventLength: 20, motionMax: 200});
  assert.strictEqual(sc.yAudio(100), LG.LEVEL_GRAPH_TOP_PAD);
  assert.notStrictEqual(sc.yMotion(100), sc.yAudio(100));
});

test('a zero motion max does not divide by zero', () => {
  const sc = LG.levelGraphScale({width: 800, height: 48, eventLength: 20, motionMax: 0});
  assert.ok(isFinite(sc.yMotion(0)));
  assert.strictEqual(sc.yMotion(0), sc.baseline);
});

test('a zero length event does not divide by zero', () => {
  const sc = LG.levelGraphScale({width: 800, height: 48, eventLength: 0, motionMax: 10});
  assert.ok(isFinite(sc.x(0)));
});

test('tAt inverts x', () => {
  const sc = LG.levelGraphScale({width: 800, height: 48, eventLength: 20, motionMax: 100});
  assert.strictEqual(sc.tAt(400), 10);
  assert.strictEqual(sc.tAt(-10), 0);
  assert.strictEqual(sc.tAt(9999), 20);
});

console.log('levelGraphSampleAt');

const hoverSeries = LG.levelGraphSeries(
    [row(0, 'Normal', 1, 10), row(5, 'Alarm', 50, 80), row(10, 'Normal', 2, 20)], 10);

test('snaps to the nearest sample, not the preceding one', () => {
  // Rows are written well below the capture rate, so snapping backwards would
  // report a stale value across most of the width of the graph.
  assert.strictEqual(LG.levelGraphSampleAt(hoverSeries.samples, 4.9).t, 5);
  assert.strictEqual(LG.levelGraphSampleAt(hoverSeries.samples, 5.1).t, 5);
});

test('past the last sample gives the last sample', () => {
  assert.strictEqual(LG.levelGraphSampleAt(hoverSeries.samples, 100).t, 10);
});

test('before the first sample gives the first sample', () => {
  assert.strictEqual(LG.levelGraphSampleAt(hoverSeries.samples, -100).t, 0);
});

test('an empty series has no sample rather than throwing', () => {
  assert.strictEqual(LG.levelGraphSampleAt([], 1), null);
  assert.strictEqual(LG.levelGraphSampleAt(null, 1), null);
});

console.log('levelGraphReadout');

test('reports both levels when both were measured', () => {
  assert.strictEqual(LG.levelGraphReadout(hoverSeries, 5), 'motion 50, audio 80');
});

test('omits audio entirely when the event has none', () => {
  const s = LG.levelGraphSeries([row(0, 'Alarm', 12, 0)], 10);
  assert.strictEqual(LG.levelGraphReadout(s, 0), 'motion 12');
});

test('says nothing at all when there is no series', () => {
  assert.strictEqual(LG.levelGraphReadout(null, 5), '');
  assert.strictEqual(LG.levelGraphReadout(LG.levelGraphSeries([], 10), 5), '');
});

console.log('renderLevelGraph');

function render(frames, opts) {
  const series = LG.levelGraphSeries(frames, 10);
  return LG.renderLevelGraph(Object.assign({
    samples: series.samples,
    bands: LG.levelGraphBands(series.samples, 10),
    labels: [],
    width: 800,
    height: 48,
    eventLength: 10,
    motionMax: series.motionMax,
    hasAudio: series.hasAudio,
    hasScores: series.hasScores,
  }, opts || {}));
}

test('draws a motion polyline and, when there is audio, an audio one', () => {
  const svg = render([row(0, 'Normal', 5, 0), row(5, 'Alarm', 50, 70)]);
  assert.ok(svg.includes('class="level-graph-motion"'), 'no motion line');
  assert.ok(svg.includes('class="level-graph-audio"'), 'no audio line');
});

test('draws no audio line for an event with no audio data', () => {
  const svg = render([row(0, 'Normal', 5, 0), row(5, 'Alarm', 50, 0)]);
  assert.ok(svg.includes('class="level-graph-motion"'), 'no motion line');
  assert.ok(!svg.includes('class="level-graph-audio"'), 'drew an audio line for an event with none');
});

test('draws alarm bands behind the lines', () => {
  const svg = render([row(0, 'Normal', 0, 0), row(5, 'Alarm', 50, 0), row(6, 'Normal', 0, 0)]);
  assert.ok(svg.indexOf('level-graph-band') < svg.indexOf('level-graph-motion'),
      'band must come before the line so it paints behind it');
});

test('draws a baseline for the lines to be read against', () => {
  assert.ok(render([row(0, 'Normal', 1, 1)]).includes('class="level-graph-axis"'));
});

test('tick marks stay in the label gutter rather than crossing the plot', () => {
  const svg = render([row(0, 'Normal', 1, 1)], {labels: [{t: 5, text: '12:00:05'}]});
  const tick = svg.match(/<line class="level-graph-tick"[^>]*>/);
  assert.ok(tick, 'no tick drawn');
  const y1 = parseFloat(tick[0].match(/y1="([\d.]+)"/)[1]);
  const y2 = parseFloat(tick[0].match(/y2="([\d.]+)"/)[1]);
  assert.strictEqual(y1, 48 - LG.LEVEL_GRAPH_LABEL_GUTTER);
  assert.strictEqual(y2, 48);
});

test('an empty event still renders a well formed svg', () => {
  const svg = render([]);
  assert.ok(svg.startsWith('<svg'), 'not an svg');
  assert.ok(svg.endsWith('</svg>'), 'unterminated svg');
  assert.ok(!svg.includes('polyline'), 'drew a line with no samples');
});

test('label text is escaped', () => {
  // toLocaleTimeString is the only source today, but it is locale data going
  // straight into markup.
  const svg = render([row(0, 'Normal', 1, 1)], {labels: [{t: 0, text: '<script>&"'}]});
  assert.ok(!svg.includes('<script>'), 'unescaped markup in a label');
  assert.ok(svg.includes('&lt;script&gt;&amp;&quot;'), 'label not escaped as expected');
});

test('no NaN reaches the markup', () => {
  // A NaN coordinate makes the browser drop the whole polyline silently.
  const svg = render([row(0, 'Normal', 1, 1), row('2.50', 'Alarm', 3, 4)]);
  assert.ok(!/NaN/.test(svg), 'NaN in: ' + svg);
});

test('geometry matches the scale the hover readout uses', () => {
  // The line and the readout have to agree about where a second sits, or the
  // number under the cursor belongs to a different part of the graph.
  const series = LG.levelGraphSeries([row(0, 'Normal', 0, 0), row(5, 'Alarm', 100, 50)], 10);
  const sc = LG.levelGraphScale({width: 800, height: 48, eventLength: 10, motionMax: series.motionMax});
  const points = LG.levelGraphPoints(series.samples, sc, 'score');
  assert.strictEqual(points, '0,' + sc.baseline + ' 400,' + LG.LEVEL_GRAPH_TOP_PAD);
  assert.strictEqual(sc.tAt(400), 5);
  assert.strictEqual(LG.levelGraphSampleAt(series.samples, sc.tAt(400)).score, 100);
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
