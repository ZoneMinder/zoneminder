'use strict';

// A failed video generation request only called logAjaxFail(), so the
// "Generating video..." spinner and text-warning class stayed on screen for
// ever. The fail handler must show videoGenFailedString in red as well.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    process.env.VIDEO_JS ||
        path.join(__dirname, '../../web/skins/classic/views/js/video.js'),
    'utf8');

const responseSrc = src.match(/function generateVideoResponse\([\s\S]*?\n}\n/)[0];
const generateSrc = src.match(/function generateVideo\(\) \{[\s\S]*?\n}\n/)[0];

const elements = {};
function element(sel) {
  if (!elements[sel]) elements[sel] = {classes: new Set(), text: '', html: ''};
  return elements[sel];
}

let captured = null;
const ajaxFails = [];

const $j = function(sel) {
  const el = element(sel);
  const chain = {
    addClass(c) {
      c.split(' ').forEach((x) => el.classes.add(x));
      return this;
    },
    removeClass(c) {
      c.split(' ').forEach((x) => el.classes.delete(x));
      return this;
    },
    text(v) {
      el.text = String(v);
      return this;
    },
    html(v) {
      el.html = String(v);
      return this;
    },
    append(n) {
      el.html += (n && n.textContent !== undefined) ? n.textContent : String(n);
      return this;
    },
    serialize() {
      return 'id=7';
    },
    load() {
      return this;
    },
  };
  return chain;
};
$j.ajaxSetup = function() {};
$j.getJSON = function() {
  return {
    done() {
      return this;
    },
    fail(cb) {
      captured = cb;
      return this;
    },
  };
};

const ctx = {
  $j: $j,
  thisUrl: 'index.php',
  currentView: 'video',
  eventId: '7',
  videoGenProgressString: 'Generating video...',
  videoGenSuccessString: 'Video generation succeeded!',
  videoGenFailedString: 'Video generation failed',
  logAjaxFail(jqxhr, textStatus, error) {
    assert.ok(jqxhr, 'logAjaxFail needs the jqxhr');
    ajaxFails.push({jqxhr: jqxhr, textStatus: textStatus, error: error});
  },
  document: {createTextNode: (t) => ({textContent: t})},
  console: {log() {}},
};

vm.createContext(ctx);
vm.runInContext(responseSrc + '\n' + generateSrc, ctx);

ctx.generateVideo();

const jqxhr = {status: 500, statusText: 'Internal Server Error'};
assert.strictEqual(typeof captured, 'function', 'generateVideo did not register a fail handler');
captured(jqxhr, 'error', 'x');

const progress = element('#videoProgress');
assert.ok(progress.classes.has('text-danger'), 'missing text-danger: ' +
    JSON.stringify([...progress.classes]));
assert.ok(!progress.classes.has('text-warning'), 'text-warning was not removed');
assert.strictEqual(progress.text, ctx.videoGenFailedString);
assert.strictEqual(ajaxFails.length, 1);
assert.strictEqual(ajaxFails[0].jqxhr, jqxhr);

console.log('  ok video generation failure replaces the spinner with an error');
