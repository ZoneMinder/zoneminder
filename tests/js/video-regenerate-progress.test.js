'use strict';

// Generating a second video on the video view left the first result showing:
// the response replaced the spinner and message with text(), and generateVideo()
// only removed 'invisible', so the heading still said "succeeded" in green.

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

// #videoProgress as views/video.php renders it.
function load() {
  const progress = {
    classes: new Set(['text-warning', 'invisible']),
    content: '<span class="spinner-grow"></span> Generating video...',
  };
  let done = null;
  const jq = function(sel) {
    const obj = {
      addClass(c) {
        if (sel === '#videoProgress') c.split(' ').forEach((x) => progress.classes.add(x));
        return this;
      },
      removeClass(c) {
        if (sel === '#videoProgress') c.split(' ').forEach((x) => progress.classes.delete(x));
        return this;
      },
      text(v) {
        if (sel === '#videoProgress') progress.content = String(v);
        return this;
      },
      html(v) {
        if (sel === '#videoProgress') progress.content = String(v);
        return this;
      },
      append(n) {
        if (sel === '#videoProgress') progress.content += (n && n.textContent !== undefined) ? n.textContent : String(n);
        return this;
      },
      serialize() {
        return 'id=7';
      },
      load() {},
      ready() {},
    };
    return obj;
  };
  jq.ajaxSetup = function() {};
  jq.getJSON = function() {
    return {
      done(fn) {
        done = fn;
        return this;
      },
      fail() {
        return this;
      },
    };
  };
  const ctx = {
    $j: jq,
    console: {log() {}},
    thisUrl: 'index.php',
    document: {createTextNode: (t) => ({textContent: t})},
    currentView: 'video',
    eventId: '7',
    logAjaxFail() {},
    videoGenSuccessString: 'Video Generation Succeeded!',
    videoGenFailedString: 'Video Generation Failed!',
    videoGenProgressString: 'Generating Video',
  };
  vm.createContext(ctx);
  vm.runInContext(src + '\n;this.generateVideo = generateVideo;', ctx);
  return {ctx, progress, respond: (r) => done({result: r})};
}

test('a second generation shows progress again, not the last result', () => {
  const {ctx, progress, respond} = load();
  ctx.generateVideo();
  respond('Ok');
  assert.strictEqual(progress.content, 'Video Generation Succeeded!');
  ctx.generateVideo();
  assert.ok(progress.content.includes('spinner-grow'), 'spinner missing: ' + progress.content);
  assert.ok(progress.content.endsWith('Generating Video'), 'message: ' + progress.content);
  assert.ok(progress.classes.has('text-warning'));
  assert.ok(!progress.classes.has('text-success'));
});

test('a failure after a success is not also marked as success', () => {
  const {ctx, progress, respond} = load();
  ctx.generateVideo();
  respond('Ok');
  ctx.generateVideo();
  respond('Error');
  assert.strictEqual(progress.content, 'Video Generation Failed!');
  assert.ok(progress.classes.has('text-danger'));
  assert.ok(!progress.classes.has('text-success'));
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
