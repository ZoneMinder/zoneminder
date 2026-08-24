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
test('an object no wider than the kernel cannot be detected', () => {
  // No fully-white window of that size fits, so the opening blacks out every
  // pixel and the zone can never alarm.
  assert.strictEqual(ZM.objectTooNarrowToFilter(3, 140, 3), true);
  assert.strictEqual(ZM.objectTooNarrowToFilter(2, 140, 3), true);
});
test('a normally-sized object is fine', () => {
  assert.strictEqual(ZM.objectTooNarrowToFilter(60, 140, 3), false);
});
test('judged against the kernel actually in use', () => {
  // The regression this guards: a sensitivity preset sets 7x7, the tool now
  // leaves that alone, and a 5px object is erased. Assuming 3x3 would miss it.
  assert.strictEqual(ZM.objectTooNarrowToFilter(5, 140, 7), true);
  assert.strictEqual(ZM.objectTooNarrowToFilter(5, 140, 3), false);
});
test('never reports safer than the 3x3 minimum', () => {
  // A kernel below the legal minimum cannot make a 2px object detectable.
  assert.strictEqual(ZM.objectTooNarrowToFilter(2, 140, 0), true);
});
test('measured against the narrower side, not the area', () => {
  // A wide flat object is still erased when its height is the problem.
  assert.strictEqual(ZM.objectTooNarrowToFilter(400, 2, 3), true);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
