'use strict';

// A monitor whose Device Class is Speaker is drawn without the Timestamp tab
// and without the reference image blend fields, so newMonitor[LabelX],
// newMonitor[LabelY] and newMonitor[RefBlendPerc] are not in the form.
// validateForm() read .value from each of them unconditionally, so it threw a
// TypeError and Save and Save and Close did nothing at all for that monitor.
//
// validateForm lives in a php file, so it is cut out by name and the
// translate() echoes are replaced by the key they would print.
//
// Run: node tests/js/monitor-validate-speaker.test.js

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/monitor.js.php'), 'utf8');
const start = src.indexOf('function validateForm(form) {');
const end = src.indexOf('function updateMethods(element) {');
assert(start > 0 && end > start, 'validateForm not found');
const code = src.substring(start, end)
    .replace(/<\?php echo translate\('([^']*)'\) \?>/g, '$1');

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

const cameraValues = {
  'newMonitor[Name]': 'Door',
  'newMonitor[Type]': 'Ffmpeg',
  'newMonitor[Path]': 'rtsp://camera/stream',
  'newMonitor[VideoWriter]': '0',
  'newMonitor[EncoderParameters]': '',
  'newMonitor[AnalysisFPSLimit]': '',
  'newMonitor[MaxFPS]': '',
  'newMonitor[AlarmMaxFPS]': '',
  'newMonitor[RefBlendPerc]': '6',
  'newMonitor[Colours]': '4',
  'newMonitor[Width]': '640',
  'newMonitor[Height]': '480',
  'newMonitor[LabelX]': '0',
  'newMonitor[LabelY]': '0',
  'newMonitor[ImageBufferCount]': '3',
  'newMonitor[WarmupCount]': '0',
  'newMonitor[PreEventCount]': '5',
  'newMonitor[PostEventCount]': '5',
  'newMonitor[StreamReplayBuffer]': '0',
  'newMonitor[MaxImageBufferCount]': '0',
  'newMonitor[AlarmFrameCount]': '1',
  'newMonitor[SectionLength]': '600',
  'newMonitor[AnalysisUpdateDelay]': '0',
  'newMonitor[FPSReportInterval]': '100',
  'newMonitor[MotionFrameSkip]': '0',
  'newMonitor[WebColour]': '#ff0000',
  'newMonitor[RTSPStreamName]': '',
};

function run(omit, overrides) {
  const alerts = [];
  const ctx = vm.createContext({
    monitorNames: {},
    rtspStreamNames: {},
    ZM_FfmpegOptions: {badEntries: () => []},
    alert: (m) => alerts.push(m),
    confirm: () => true,
  });
  vm.runInContext(code, ctx);
  const values = Object.assign({}, cameraValues, overrides || {});
  const elements = [];
  Object.keys(values).forEach((name) => {
    if (omit.includes(name)) return;
    const el = {name: name, nodeName: 'INPUT', value: values[name]};
    elements.push(el);
    elements[name] = el;
  });
  const result = vm.runInContext('validateForm', ctx)({elements: elements});
  return {result, alerts};
}

const speakerOmit = [
  'newMonitor[RefBlendPerc]', 'newMonitor[LabelX]', 'newMonitor[LabelY]',
];

test('a camera form with every field validates', () => {
  assert.strictEqual(run([]).result, true);
});

test('a speaker form without the timestamp and blend fields validates', () => {
  assert.strictEqual(run(speakerOmit).result, true);
});

test('a speaker form is still checked for the fields it has', () => {
  const r = run(speakerOmit, {'newMonitor[Width]': ''});
  assert.strictEqual(r.result, false);
  assert(r.alerts.join('\n').includes('BadWidth'));
});

test('a camera form still rejects a missing blend percentage', () => {
  const r = run([], {'newMonitor[RefBlendPerc]': ''});
  assert.strictEqual(r.result, false);
  assert(r.alerts.join('\n').includes('BadRefBlendPerc'));
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
