'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/video.js'), 'utf8');

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

function respond(result) {
  const texts = {};
  const jq = function(sel) {
    return {
      addClass() {
        return this;
      },
      removeClass() {
        return this;
      },
      text(v) {
        texts[sel] = v;
        return this;
      },
      load() {},
      ready() {},
    };
  };
  const ctx = {
    $j: jq,
    console: {log() {}},
    thisUrl: 'index.php',
    document: {},
    currentView: 'video',
    eventId: '7',
    // skin.js.php strings, present on every page
    exportSucceededString: 'Export Succeeded',
    exportFailedString: 'Export Failed',
    // video.js.php strings
    videoGenSuccessString: 'Video Generation Succeeded!',
    videoGenFailedString: 'Video Generation Failed!',
  };
  vm.createContext(ctx);
  vm.runInContext(src + '\n;this.generateVideoResponse = generateVideoResponse;', ctx);
  ctx.generateVideoResponse({result: result});
  return texts['#videoProgress'];
}

test('a generated video reports video generation success', () => {
  assert.strictEqual(respond('Ok'), 'Video Generation Succeeded!');
});

test('a failed video reports video generation failure', () => {
  assert.strictEqual(respond('Error'), 'Video Generation Failed!');
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
