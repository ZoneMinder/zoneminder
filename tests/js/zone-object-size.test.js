'use strict';

const assert = require('assert');
const path = require('path');
const ZM = require(path.join(__dirname,
    '../../web/skins/classic/views/js/zone-object-size.js'));

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

// A real case, in capture pixels throughout: a 2560x1920 camera, a zone
// covering 34.76% of it, and a box drawn round a person at the gate.
const ZONE_PX = 1708524;
const BOX_PX = 116 * 235; // 27260

// The shipped defaults, as rendered by $rectangle_ratios in zone.php. Mirrored
// here so the derivations below can be checked against the documentation; the
// module itself holds no defaults.
const RATIOS = {fraction: 0.25, filter: 0.7, blob: 0.86};

console.log('objectThresholdPixels');
test('alarm threshold is a quarter of the object', () => {
  // definezone.rst [J]: "a good starting point ... is 25% of the area that an
  // object of interest takes up in the Zone region". A bounding box is mostly
  // not the object - corners of sky and fence - and the pixels that are the
  // object only count when they clear MinPixelThreshold.
  assert.strictEqual(ZM.objectThresholdPixels(BOX_PX, RATIOS).alarm, 6815);
});
test('each stage steps down from the one it is drawn from', () => {
  // Filtered is a fraction of alarmed; blob is a fraction of FILTERED, not of
  // alarmed, because a blob is built from pixels that survived the filter.
  const px = ZM.objectThresholdPixels(BOX_PX, RATIOS);
  assert.ok(Math.abs(px.filter - 6815 * 0.7) < 1e-9);
  assert.ok(Math.abs(px.blob - px.filter * 0.86) < 1e-9);
});
test('blob can never exceed filter, whatever is typed', () => {
  // Structural rather than clamped: a ratio of at most 1 applied to the
  // filtered count cannot land above it. validateForm rejects the zone
  // outright if MinBlob > MinFilter.
  [0.1, 0.5, 1].forEach((r) => {
    const px = ZM.objectThresholdPixels(BOX_PX, Object.assign({}, RATIOS, {blob: r}));
    assert.ok(px.blob <= px.filter, 'blob ratio ' + r);
    assert.ok(px.filter <= px.alarm, 'filter ratio ' + r);
  });
});
test('kept unrounded, so percentages can round exactly once', () => {
  const px = ZM.objectThresholdPixels(BOX_PX, RATIOS);
  assert.ok(Math.abs(px.alarm - BOX_PX * 0.25) < 1e-9);
  assert.ok(Math.abs(px.filter - px.alarm * 0.7) < 1e-9);
  assert.ok(Math.abs(px.blob - px.filter * 0.86) < 1e-9);
});
test('honours a chosen fraction of the rectangle', () => {
  // The Alarmed area field, so the 25% rule is a starting point rather than a
  // hardcoded law.
  assert.strictEqual(ZM.objectThresholdPixels(1000, Object.assign({}, RATIOS, {fraction: 0.5})).alarm, 500);
  assert.strictEqual(ZM.objectThresholdPixels(1000, Object.assign({}, RATIOS, {fraction: 0.1})).alarm, 100);
});
test('honours chosen filter and blob ratios', () => {
  const px = ZM.objectThresholdPixels(1000, {fraction: 0.5, filter: 0.9, blob: 0.2});
  assert.strictEqual(px.alarm, 500);
  assert.strictEqual(px.filter, 450);
  assert.strictEqual(px.blob, 90, 'blob is 0.2 of FILTERED, not of alarmed');
});
test('null on a degenerate box', () => {
  assert.strictEqual(ZM.objectThresholdPixels(0, RATIOS), null);
  assert.strictEqual(ZM.objectThresholdPixels(-5, RATIOS), null);
});

console.log('pixelsToPercent');
test('converts against zone area, not frame area', () => {
  // The stored percentage is of the ZONE (zm_zone.cpp:1020 multiplies it by
  // polygon.Area()), so the same object needs a bigger percentage in a
  // smaller zone.
  assert.strictEqual(ZM.pixelsToPercent(6815, ZONE_PX), 0.4);
  assert.strictEqual(ZM.pixelsToPercent(6815, ZONE_PX / 2), 0.8);
});
test('rounds to 2dp to match the DECIMAL(10,2) column', () => {
  // 6815/1708524*100 = 0.39888...
  const v = ZM.pixelsToPercent(6815, ZONE_PX);
  assert.strictEqual(v, Math.round(v * 100) / 100);
});
test('clamps to 100 when the box is larger than the zone', () => {
  assert.strictEqual(ZM.pixelsToPercent(ZONE_PX * 8, ZONE_PX), 100);
});
test('floors at 0.01 rather than rounding a tiny box to zero', () => {
  // 0 would disable the threshold entirely - CheckAlarms skips the check when
  // the minimum is 0, turning "small objects only" into "no minimum at all".
  assert.strictEqual(ZM.pixelsToPercent(1, ZONE_PX), 0.01);
});
test('null on a degenerate zone or count', () => {
  assert.strictEqual(ZM.pixelsToPercent(6815, 0), null);
  assert.strictEqual(ZM.pixelsToPercent(0, ZONE_PX), null);
});

console.log('zoneSettingsForObject');
test('reproduces the worked example in definezone.rst [J]', () => {
  // "if a subject moving through the frame takes up approximately 30% of the
  // Zone region, then a good starting minimum Alarmed Area is about 7.5%".
  const s = ZM.zoneSettingsForObject(300000, 1000000, null, RATIOS);
  assert.strictEqual(s.MinAlarmPixels, 7.5);
});
test('a person-sized box on a real zone', () => {
  const s = ZM.zoneSettingsForObject(BOX_PX, ZONE_PX, null, RATIOS);
  assert.strictEqual(s.MinAlarmPixels, 0.4);
  assert.strictEqual(s.MinFilterPixels, 0.28);
  assert.strictEqual(s.MinBlobPixels, 0.24);
  assert.strictEqual(s.MinBlobs, 1);
});
test('derived values round once, not twice', () => {
  // The point of computing in pixels. With boxPx 1104 in a 100000px zone the
  // raw alarm percentage is 0.276, which rounds to 0.28. Deriving the filter
  // from that rounded 0.28 gives 0.196 -> 0.20, whereas deriving it from the
  // exact 0.276 gives 0.1932 -> 0.19. The second is correct.
  const s = ZM.zoneSettingsForObject(1104, 100000, null, RATIOS);
  assert.strictEqual(s.MinAlarmPixels, 0.28);
  assert.strictEqual(s.MinFilterPixels, 0.19, 'double rounding would give 0.20');
});
test('strictly decreasing, so validateForm accepts them', () => {
  // validateForm errors when MinAlarm < MinFilter or MinFilter < MinBlob.
  const s = ZM.zoneSettingsForObject(BOX_PX, ZONE_PX, null, RATIOS);
  assert.ok(s.MinAlarmPixels > s.MinFilterPixels);
  assert.ok(s.MinFilterPixels > s.MinBlobPixels);
});
test('ordering still holds once everything hits the 0.01 floor', () => {
  // They collapse to equal rather than inverting. Equal is what validateForm
  // permits; inverted would be rejected.
  const s = ZM.zoneSettingsForObject(4, ZONE_PX, null, RATIOS);
  assert.ok(s.MinAlarmPixels >= s.MinFilterPixels, 'alarm >= filter');
  assert.ok(s.MinFilterPixels >= s.MinBlobPixels, 'filter >= blob');
});
test('forces Blobs, the only per-object method', () => {
  // MinAlarmPixels and MinFilterPixels are zone-wide sums, so several small
  // movers can reach them together. MinBlobPixels is tested per blob.
  assert.strictEqual(ZM.zoneSettingsForObject(BOX_PX, ZONE_PX, null, RATIOS).CheckMethod, 'Blobs');
});
test('every Max is blank, because each one suppresses detection', () => {
  // MaxAlarmPixels/MaxFilterPixels/MaxBlobs set overload_count, blinding the
  // zone for OverloadFrames frames. MaxBlobPixels deletes the blob outright.
  // A ceiling from one box would reject the same object seen closer.
  const s = ZM.zoneSettingsForObject(BOX_PX, ZONE_PX, null, RATIOS);
  ['MaxAlarmPixels', 'MaxFilterPixels', 'MaxBlobPixels', 'MaxBlobs',
    'MaxPixelThreshold'].forEach((f) => assert.strictEqual(s[f], '', f));
});
test('nothing can trigger overload, so OverloadFrames is 0', () => {
  assert.strictEqual(ZM.zoneSettingsForObject(BOX_PX, ZONE_PX, null, RATIOS).OverloadFrames, 0);
});
test('writes every managed field, so nothing is left half-applied', () => {
  const s = ZM.zoneSettingsForObject(BOX_PX, ZONE_PX, null, RATIOS);
  ZM.MANAGED_FIELDS.forEach((f) => assert.ok(f in s, f + ' missing'));
});
test('does not touch ExtendAlarmFrames', () => {
  // applyZoneType disables it on anything but a Preclusive zone, so a write
  // could not submit anyway.
  const s = ZM.zoneSettingsForObject(BOX_PX, ZONE_PX, null, RATIOS);
  assert.ok(!('ExtendAlarmFrames' in s));
  assert.ok(ZM.MANAGED_FIELDS.indexOf('ExtendAlarmFrames') < 0);
});
test('passes the chosen ratios through to the thresholds', () => {
  // Doubling the fraction doubles all three, since filter and blob are derived
  // from the alarm figure.
  const base = ZM.zoneSettingsForObject(BOX_PX, ZONE_PX, null, RATIOS);
  const dbl = ZM.zoneSettingsForObject(BOX_PX, ZONE_PX, null, Object.assign({}, RATIOS, {fraction: 0.5}));
  assert.strictEqual(base.MinAlarmPixels, 0.4);
  assert.strictEqual(dbl.MinAlarmPixels, 0.8);
  assert.strictEqual(dbl.MinBlobPixels, 0.48);
});
test('filter and blob ratios reach the stored fields', () => {
  const s = ZM.zoneSettingsForObject(BOX_PX, ZONE_PX, null,
      {fraction: 0.25, filter: 0.9, blob: 0.5});
  assert.strictEqual(s.MinAlarmPixels, 0.4);
  assert.strictEqual(s.MinFilterPixels, 0.36);
  assert.strictEqual(s.MinBlobPixels, 0.18, 'half of filtered, not of alarmed');
});
test('the stored ordering holds for any pair of ratios', () => {
  // validateForm rejects MinBlob > MinFilter or MinFilter > MinAlarm, which
  // would leave the zone unsaveable.
  [[0.3, 0.9], [0.9, 0.3], [1, 1], [0.05, 1]].forEach((pair) => {
    const s = ZM.zoneSettingsForObject(BOX_PX, ZONE_PX, null,
        {fraction: 0.25, filter: pair[0], blob: pair[1]});
    assert.ok(s.MinAlarmPixels >= s.MinFilterPixels, 'alarm >= filter ' + pair);
    assert.ok(s.MinFilterPixels >= s.MinBlobPixels, 'filter >= blob ' + pair);
  });
});
test('null on degenerate geometry', () => {
  assert.strictEqual(ZM.zoneSettingsForObject(0, ZONE_PX, null, RATIOS), null);
  assert.strictEqual(ZM.zoneSettingsForObject(BOX_PX, 0, null, RATIOS), null);
});

console.log('zoneSettingsForObject: sensitivity is the user\'s, not the box\'s');
test('keeps a preset sensitivity instead of overwriting it', () => {
  // "Best, low sensitivity" is threshold 60 and a 7x7 filter. Measuring an
  // object must set the size from the box and leave that choice intact.
  const s = ZM.zoneSettingsForObject(BOX_PX, ZONE_PX,
      {MinPixelThreshold: '60', FilterX: '7', FilterY: '7'}, RATIOS);
  assert.strictEqual(s.MinPixelThreshold, '60');
  assert.strictEqual(s.FilterX, '7');
  assert.strictEqual(s.FilterY, '7');
  assert.strictEqual(s.MinAlarmPixels, 0.4, 'size still comes from the box');
});
test('falls back only where the field is blank', () => {
  // A blank MinPixelThreshold or FilterX/Y blocks the save (validateForm:22,
  // :27), so a fresh zone still has to be filled in. 40 is [G]'s starting
  // point, above the Default preset's 25.
  const s = ZM.zoneSettingsForObject(BOX_PX, ZONE_PX,
      {MinPixelThreshold: '', FilterX: ''}, RATIOS);
  assert.strictEqual(s.MinPixelThreshold, 40);
  assert.strictEqual(s.FilterX, ZM.MIN_FILTER_KERNEL);
});
test('no prior state behaves as all blank', () => {
  const s = ZM.zoneSettingsForObject(BOX_PX, ZONE_PX, null, RATIOS);
  assert.strictEqual(s.MinPixelThreshold, 40);
  assert.strictEqual(s.FilterY, ZM.MIN_FILTER_KERNEL);
});
test('a hand-tuned threshold survives too', () => {
  // The rule is "already set", not "matches a preset".
  const s = ZM.zoneSettingsForObject(BOX_PX, ZONE_PX, {MinPixelThreshold: '33'}, RATIOS);
  assert.strictEqual(s.MinPixelThreshold, '33');
});

console.log('objectTooNarrowToFilter');
test('an object narrower than the kernel cannot be detected', () => {
  // No fully-white window of that size fits, so the opening blacks out every
  // pixel and the zone can never alarm.
  assert.strictEqual(ZM.objectTooNarrowToFilter(2, 140, 3, 3), true);
});
test('an object that exactly fits the kernel survives', () => {
  assert.strictEqual(ZM.objectTooNarrowToFilter(3, 140, 3, 3), false);
});
test('a normally-sized object is fine', () => {
  assert.strictEqual(ZM.objectTooNarrowToFilter(60, 140, 3, 3), false);
});
test('judged against the kernel actually in use', () => {
  // The regression this guards: a sensitivity preset sets 7x7, the tool now
  // leaves that alone, and a 5px object is erased. Assuming 3x3 would miss it.
  assert.strictEqual(ZM.objectTooNarrowToFilter(5, 140, 7, 7), true);
  assert.strictEqual(ZM.objectTooNarrowToFilter(5, 140, 3, 3), false);
});
test('each side is judged against its own filter dimension', () => {
  // A 15x3 kernel fits inside a 100x10 object, so nothing is erased.
  assert.strictEqual(ZM.objectTooNarrowToFilter(100, 10, 15, 3), false);
  // Turn the kernel on its side and the same object no longer fits it.
  assert.strictEqual(ZM.objectTooNarrowToFilter(100, 10, 3, 15), true);
});
test('never reports safer than the 3x3 minimum', () => {
  // A kernel below the legal minimum cannot make a 2px object detectable.
  assert.strictEqual(ZM.objectTooNarrowToFilter(2, 140, 0, 0), true);
});
test('a wide flat object is erased when its height is the problem', () => {
  assert.strictEqual(ZM.objectTooNarrowToFilter(400, 2, 3, 3), true);
});

console.log('normalizeRatio');
test('a whole number in range is kept', () => {
  assert.strictEqual(ZM.normalizeRatio('25'), 25);
  assert.strictEqual(ZM.normalizeRatio(1), 1);
  assert.strictEqual(ZM.normalizeRatio(100), 100);
});
test('a fraction is rounded rather than truncated by validInt later', () => {
  // zone.php reads the cookie back through validInt(), which strips the dot:
  // 70.5 would return as 705 and mean something else entirely.
  assert.strictEqual(ZM.normalizeRatio('70.5'), 71);
  assert.strictEqual(ZM.normalizeRatio('70.4'), 70);
});
test('out of range and unreadable values are refused', () => {
  // The caller falls back to the field's rendered default for these.
  [0, -5, 101, '', 'abc', null, undefined, NaN].forEach((v) => {
    assert.strictEqual(ZM.normalizeRatio(v), null, 'value ' + v);
  });
});
test('exponent notation cannot smuggle a value through', () => {
  // validInt('1e2') is 12, not 100. 100 is in range, so it has to round-trip
  // as the digits that will be read back.
  assert.strictEqual(ZM.normalizeRatio('1e2'), 100);
});

console.log('boxPercentForPixels');
// The frame the drag maps into: a 2560x1920 camera in the editor's 0..100
// percent space on both axes.
const FRAME = {width: 2560, height: 1920, maxX: 100, maxY: 100};
test('a typed size becomes a box of the same capture pixels', () => {
  const box = ZM.boxPercentForPixels(256, 192, FRAME);
  // A tenth of the frame on each axis.
  assert.ok(Math.abs((box.b.x - box.a.x) - 10) < 1e-9);
  assert.ok(Math.abs((box.b.y - box.a.y) - 10) < 1e-9);
});
test('the box is centred, so it is visible wherever the zone is', () => {
  const box = ZM.boxPercentForPixels(256, 192, FRAME);
  assert.ok(Math.abs(box.a.x - 45) < 1e-9);
  assert.ok(Math.abs(box.a.y - 45) < 1e-9);
});
test('an object larger than the frame is clamped to it', () => {
  // Nothing bigger than the image can be measured against it, and a box that
  // ran off the edge would be drawn outside the drag's own coordinate space.
  const box = ZM.boxPercentForPixels(9999, 9999, FRAME);
  assert.strictEqual(box.a.x, 0);
  assert.strictEqual(box.a.y, 0);
  assert.strictEqual(box.b.x, 100);
  assert.strictEqual(box.b.y, 100);
});
test('a missing or zero size measures nothing', () => {
  [[0, 100], [100, 0], [NaN, 100], [-5, 5]].forEach((wh) => {
    assert.strictEqual(ZM.boxPercentForPixels(wh[0], wh[1], FRAME), null,
        'size ' + wh);
  });
});
test('an unmeasured frame is refused rather than dividing by zero', () => {
  // monitorData is not populated until the stream reports its size.
  assert.strictEqual(ZM.boxPercentForPixels(50, 50, null), null);
  assert.strictEqual(
      ZM.boxPercentForPixels(50, 50, {width: 0, height: 0, maxX: 100, maxY: 100}),
      null);
});

console.log('savedObjectPixels');
test('a saved MinAlarmPixels gives back the box that produced it', () => {
  // Round trip through storage: pixels -> 2dp percent -> pixels. The column
  // holds the alarm area to +-0.005% of the zone, so the box comes back to
  // within that divided by the fraction - 342px here (27336 for 27260).
  const pct = ZM.pixelsToPercent(
      ZM.objectThresholdPixels(BOX_PX, RATIOS).alarm, ZONE_PX);
  const back = ZM.savedObjectPixels(pct, ZONE_PX, RATIOS.fraction);
  const slack = 0.005 / 100 * ZONE_PX / RATIOS.fraction;
  assert.ok(Math.abs(back - BOX_PX) <= slack, 'got ' + back);
});
test('nothing saved, nothing drawn', () => {
  [NaN, 0, -1].forEach((pct) => {
    assert.strictEqual(ZM.savedObjectPixels(pct, ZONE_PX, 0.25), null);
  });
  assert.strictEqual(ZM.savedObjectPixels(1, 0, 0.25), null);
  assert.strictEqual(ZM.savedObjectPixels(1, ZONE_PX, 0), null);
});

console.log('boxSidesForArea');
test('sides keep the area and the aspect', () => {
  const s = ZM.boxSidesForArea(4800, 4 / 3);
  assert.ok(Math.abs(s.w - 80) < 1e-9 && Math.abs(s.h - 60) < 1e-9);
});
test('no area or aspect gives no box', () => {
  assert.strictEqual(ZM.boxSidesForArea(0, 1), null);
  assert.strictEqual(ZM.boxSidesForArea(100, 0), null);
});

console.log('innerBox');
const OUTER = {a: {x: 10, y: 20}, b: {x: 50, y: 60}};
test('a quarter of the area is half of each side, about the centre', () => {
  assert.deepStrictEqual(ZM.innerBox(OUTER, 0.25),
      {a: {x: 20, y: 30}, b: {x: 40, y: 50}});
});
test('the threshold chain nests', () => {
  // 25% -> 70% -> 86%: each rect must sit inside the one before.
  const alarm = ZM.innerBox(OUTER, 0.25);
  const filter = ZM.innerBox(OUTER, 0.25 * 0.7);
  const blob = ZM.innerBox(OUTER, 0.25 * 0.7 * 0.86);
  [[alarm, filter], [filter, blob]].forEach((pair) => {
    assert.ok(pair[1].a.x > pair[0].a.x && pair[1].b.x < pair[0].b.x);
  });
});
test('a threshold bigger than the object is capped at the box', () => {
  // A hand-set MinAlarmPixels can exceed the measured box; show the box full.
  assert.deepStrictEqual(ZM.innerBox(OUTER, 3), OUTER);
});
test('an empty threshold draws nothing', () => {
  [0, NaN, -1].forEach((v) => assert.strictEqual(ZM.innerBox(OUTER, v), null));
});

console.log('shiftBox');
test('a move inside the frame moves by exactly that much', () => {
  assert.deepStrictEqual(ZM.shiftBox(OUTER, 5, -10, 100, 100),
      {a: {x: 15, y: 10}, b: {x: 55, y: 50}});
});
test('a move past an edge stops at it and keeps the size', () => {
  // Size must survive: the thresholds were derived from the area.
  assert.deepStrictEqual(ZM.shiftBox(OUTER, 200, -200, 100, 100),
      {a: {x: 60, y: 0}, b: {x: 100, y: 40}});
});
test('a reversed box is moved as its normalised self', () => {
  const rev = {a: OUTER.b, b: OUTER.a};
  assert.deepStrictEqual(ZM.shiftBox(rev, 0, 0, 100, 100), OUTER);
});

console.log('zoneThresholds');
test('percent of the zone becomes pixels the way Zone::LoadRow rounds it', () => {
  const t = ZM.zoneThresholds({CheckMethod: 'Blobs', MinAlarmPixels: '2.5',
    MaxAlarmPixels: '', MinFilterPixels: '1.75', MinBlobPixels: '0.01',
    MinBlobs: '1', MaxBlobs: ''}, 1000003);
  assert.deepStrictEqual(t, {checkMethod: 3, overloadFrames: 0, minBlobs: 1, maxBlobs: 0,
    minAlarm: 25000, maxAlarm: 0, minFilter: 17500, maxFilter: 0,
    minBlob: 100, maxBlob: 0});
});

console.log('whatIfVerdict');
// Zone 11 of event 432536, as zma --json reported it.
const RAN = {checkMethod: 3, minAlarm: 26131, maxAlarm: 0, minFilter: 18242,
  maxFilter: 0, minBlob: 15613, maxBlob: 0, minBlobs: 1, maxBlobs: 0};
const with_ = (o) => Object.assign({}, RAN, o);
// Passes every stage.
const PASS = {alarm: 144989, filter: 133210, blobs: 3, maxBlob: 37972, largestBlob: 37972};
// Through filtering, but its only blob was under MinBlobPixels and removed.
const SMALL = {alarm: 30000, filter: 20000, blobs: 0, maxBlob: 0, largestBlob: 12000};
// Short at the first stage, so zma measured nothing after it.
const QUIET = {alarm: 900, filter: 0, blobs: 0, maxBlob: 0, largestBlob: 0};
test('unchanged settings give zma\'s own verdict', () => {
  assert.deepStrictEqual(ZM.whatIfVerdict(PASS, RAN, RAN), {failedAt: null, unknown: null, scored: true});
  assert.strictEqual(ZM.whatIfVerdict(SMALL, RAN, RAN).failedAt, 'blob');
  assert.strictEqual(ZM.whatIfVerdict(QUIET, RAN, RAN).failedAt, 'alarm');
});
test('raising a minimum fails frames that passed', () => {
  assert.strictEqual(ZM.whatIfVerdict(PASS, RAN, with_({minFilter: 140000})).failedAt, 'filter');
  assert.strictEqual(ZM.whatIfVerdict(PASS, RAN, with_({minBlob: 40000})).failedAt, 'blob');
});
test('lowering MinBlobPixels passes a blob zma removed, from its size', () => {
  assert.strictEqual(ZM.whatIfVerdict(SMALL, RAN, with_({minBlob: 10000})).scored, true);
});
test('a stage zma never reached is unknown, not failed', () => {
  const v = ZM.whatIfVerdict(QUIET, RAN, with_({minAlarm: 500}));
  assert.deepStrictEqual(v, {failedAt: null, unknown: 'filter', scored: false});
  assert.strictEqual(ZM.whatIfVerdict(PASS, with_({checkMethod: 1}), RAN).unknown, 'filter');
});
test('blob count after changing blob size is unknown when MinBlobs > 1', () => {
  assert.strictEqual(ZM.whatIfVerdict(PASS, RAN, with_({minBlob: 100, minBlobs: 2})).unknown, 'blob');
  // Same blob size: zma's count stands.
  assert.strictEqual(ZM.whatIfVerdict(PASS, RAN, with_({minBlobs: 4})).failedAt, 'blob');
});
test('a Max now exceeded fails the stage', () => {
  assert.strictEqual(ZM.whatIfVerdict(PASS, RAN, with_({maxAlarm: 100000})).failedAt, 'alarm');
});
test('stageResults stops at an unknown stage', () => {
  assert.deepStrictEqual(ZM.stageResults({failedAt: null, unknown: 'filter'}, 3), {alarm: true});
  assert.deepStrictEqual(ZM.stageResults({failedAt: 'blob'}, 3),
      {alarm: true, filter: true, blob: false});
});

console.log('tallyVerdicts');
const v = (scored, extra) => Object.assign({failedAt: scored ? null : 'alarm', unknown: null, scored: scored}, extra);
test('an alarm needs the count of scored frames in a row', () => {
  const frames = [{frame: 1, verdict: v(true)}, {frame: 2, verdict: v(true)},
    {frame: 4, verdict: v(true)}];
  const t = ZM.tallyVerdicts(frames, 3);
  assert.strictEqual(t.scored, 3);
  assert.strictEqual(t.wouldAlarm, false); // frame 3 had no change
  frames.splice(2, 0, {frame: 3, verdict: v(true)});
  assert.strictEqual(ZM.tallyVerdicts(frames, 3).wouldAlarm, true);
  frames.push({frame: 5, verdict: v(true)}, {frame: 6, verdict: v(false)});
  assert.deepStrictEqual(ZM.tallyVerdicts(frames, 3).alarmFrames, [1, 2, 3, 4, 5]);
});
test('unknown frames are counted and not scored', () => {
  const t = ZM.tallyVerdicts([{frame: 1, verdict: {failedAt: null, unknown: 'blob', scored: false}}], 1);
  assert.deepStrictEqual([t.unknown, t.scored], [1, 0]);
});

console.log('applyOverload');
test('frames after one over a maximum are skipped for OverloadFrames', () => {
  const over = {failedAt: 'alarm', unknown: null, scored: false, over: true};
  const ok = {failedAt: null, unknown: null, scored: true};
  const frames = [{frame: 1, verdict: over}, {frame: 2, verdict: ok}, {frame: 3, verdict: ok},
    {frame: 4, verdict: ok}];
  ZM.applyOverload(frames, 2);
  assert.deepStrictEqual(frames.map((f) => f.verdict.failedAt), ['alarm', 'overload', 'overload', null]);
});
test('a maximum exceeded is marked as over', () => {
  assert.strictEqual(ZM.whatIfVerdict(PASS, RAN, with_({maxAlarm: 100000})).over, true);
  assert.strictEqual(ZM.whatIfVerdict(QUIET, RAN, RAN).over, false);
});

console.log('failureSummary');
const TH = {minAlarm: 1000, minFilter: 800, minBlob: 500};
const fr = (failedAt, fz) => ({verdict: {failedAt: failedAt}, fz: fz});
test('names the stage most frames stop at, and the closest one came', () => {
  const s = ZM.failureSummary([
    fr('alarm', {alarm: 300}), fr('alarm', {alarm: 900}), fr('blob', {largestBlob: 450}),
    fr(null, {alarm: 5000}),
  ], TH);
  assert.deepStrictEqual(s, {stage: 'alarm', frames: 2, best: 900, need: 1000});
});
test('a frame over a maximum is not a near miss', () => {
  assert.strictEqual(ZM.failureSummary([fr('alarm', {alarm: 5000})], TH), null);
  assert.strictEqual(ZM.failureSummary([], TH), null);
});

console.log('frameAt');
const FR = [{t: 0.1}, {t: 0.2}, {t: 0.5}];
test('nearest frame within tolerance, either side', () => {
  assert.strictEqual(ZM.frameAt(FR, 0.21, 0.05), FR[1]);
  assert.strictEqual(ZM.frameAt(FR, 0.48, 0.05), FR[2]);
  assert.strictEqual(ZM.frameAt(FR, 0.0, 0.1), FR[0]);
});
test('a gap with no motion gives no frame', () => {
  assert.strictEqual(ZM.frameAt(FR, 0.35, 0.05), null);
  assert.strictEqual(ZM.frameAt([], 1, 1), null);
});

console.log('pixelBoxToPercent');
test('inclusive pixel corners become percent of the frame', () => {
  assert.deepStrictEqual(ZM.pixelBoxToPercent([0, 0, 99, 49], 200, 100),
      {a: {x: 0, y: 0}, b: {x: 50, y: 50}});
});
test('an unset box draws nothing', () => {
  assert.strictEqual(ZM.pixelBoxToPercent([0, 0, 0, 0], 200, 100), null);
  assert.strictEqual(ZM.pixelBoxToPercent(undefined, 200, 100), null);
});

console.log('boxToCoords / coordsToBox');
test('a box round-trips through ObjectCoords, corners normalised', () => {
  const s = ZM.boxToCoords({a: {x: 40.5, y: 60}, b: {x: 10, y: 20.25}});
  assert.strictEqual(s, '10.00,20.25 40.50,60.00');
  assert.deepStrictEqual(ZM.coordsToBox(s), {a: {x: 10, y: 20.25}, b: {x: 40.5, y: 60}});
});
test('no box, or a malformed or empty one, reads as none', () => {
  assert.strictEqual(ZM.boxToCoords(null), '');
  ['', null, '1,2', '10,10 5,20', '0,0 101,50', '1,2 3,4 5,6', 'a,b c,d'].forEach((v) => {
    assert.strictEqual(ZM.coordsToBox(v), null, String(v));
  });
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
