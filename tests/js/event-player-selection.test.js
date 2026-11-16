'use strict';

// event.php renders no player for codec=auto; it hands the decision to
// EventStream.chooseEventPlayer(), which must play the MP4 natively only when
// the browser can decode the file's codec (canPlayCodec) and otherwise fall
// back to the zms <img id="evtStream"> the rest of event.js drives. These tests
// pin that branch and the DOM each branch builds. Isaac's H.265 log-flood was a
// browser that could play HEVC being forced down the zms path regardless.

const assert = require('assert');
const path = require('path');
const ES = require(path.join(__dirname, '../../web/js/EventStream.js'));
const EventStream = ES.EventStream;

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

// ---- Minimal DOM + videojs stubs -----------------------------------------

function fakeEl() {
  return {
    children: [],
    attrs: {},
    style: {},
    setAttribute: function(k, v) {
      this.attrs[k] = v;
    },
    appendChild: function(c) {
      this.children.push(c);
      c.parentNode = this;
      return c;
    },
    addEventListener: function() {},
    removeChild: function(c) {
      this.children = this.children.filter(function(x) {
        return x !== c;
      });
    }
  };
}

function installDom() {
  const created = [];
  const zoompan = fakeEl();
  zoompan.id = 'zoompan';
  global.document = {
    _created: created,
    _zoompan: zoompan,
    getElementById: function(id) {
      if (id === 'zoompan') return zoompan;
      // videoobj / evtStream live inside zoompan once built
      const hit = zoompan.children.filter(function(c) {
        return c.id === id;
      });
      return hit.length ? hit[0] : null;
    },
    createElement: function(tag) {
      const el = fakeEl();
      el.tagName = tag;
      created.push(el);
      return el;
    }
  };
  return {zoompan: zoompan, created: created};
}

function installVideojs() {
  const calls = [];
  global.videojs = function(id, opts) {
    calls.push({id: id, opts: opts});
    return {
      zoomrotate: function() {},
      on: function() {},
      one: function() {}
    };
  };
  global.videojs._calls = calls;
}

function cleanup() {
  delete global.document;
  delete global.videojs;
  delete global.canPlayCodec;
  delete global.rates;
}

const PLAYER_DATA = {
  codec: 'auto',
  mp4Src: 'index.php?view=view_video&eid=5&format=h264',
  hasHls: false,
  hlsSrc: '',
  zmsImgSrc: '/cgi-bin/nph-zms?source=event&mode=jpeg&event=5&connkey=123456',
  width: 1920,
  height: 1080,
  isLive: false
};
const EVENT_DATA = {Id: 5, Name: 'Event 5', DefaultVideo: '5-video.hevc.mp4'};

console.log('EventStream.chooseEventPlayer');

test('browser can decode the codec -> native video player', () => {
  const dom = installDom();
  installVideojs();
  global.rates = [-400, -200, 0, 100, 200, 400];
  global.canPlayCodec = function() {
    return true;
  };

  const choice = EventStream.chooseEventPlayer(PLAYER_DATA, EVENT_DATA);

  assert.strictEqual(choice.mode, 'video');
  assert.ok(choice.vid, 'a videojs player is returned');
  assert.strictEqual(global.videojs._calls.length, 1, 'videojs() was called once');
  assert.strictEqual(global.videojs._calls[0].id, 'videoobj');
  // Only non-negative rates, divided by 100.
  assert.deepStrictEqual(global.videojs._calls[0].opts.playbackRates, [0, 1, 2, 4]);
  const video = dom.zoompan.children.find(function(c) {
    return c.id === 'videoobj';
  });
  assert.ok(video, '#videoobj built into #zoompan');
  assert.strictEqual(dom.zoompan.children.filter(function(c) {
    return c.id === 'evtStream';
  }).length, 0, 'no zms img built');
  cleanup();
});

test('browser cannot decode the codec -> zms img fallback', () => {
  const dom = installDom();
  installVideojs();
  global.canPlayCodec = function() {
    return false;
  };

  const choice = EventStream.chooseEventPlayer(PLAYER_DATA, EVENT_DATA);

  assert.strictEqual(choice.mode, 'zms');
  assert.strictEqual(choice.vid, null);
  assert.strictEqual(global.videojs._calls.length, 0, 'videojs() not called');
  const img = dom.zoompan.children.find(function(c) {
    return c.id === 'evtStream';
  });
  assert.ok(img, '#evtStream img built into #zoompan');
  assert.strictEqual(img.src, PLAYER_DATA.zmsImgSrc);
  cleanup();
});

test('no canPlayCodec helper -> safe zms fallback', () => {
  const dom = installDom();
  installVideojs();
  // canPlayCodec intentionally not defined

  const choice = EventStream.chooseEventPlayer(PLAYER_DATA, EVENT_DATA);

  assert.strictEqual(choice.mode, 'zms');
  assert.ok(dom.zoompan.children.find(function(c) {
    return c.id === 'evtStream';
  }));
  cleanup();
});

test('canPlayFile only says yes when canPlayCodec does', () => {
  global.canPlayCodec = function(fn) {
    return fn === '5-video.h264.mp4';
  };
  assert.strictEqual(EventStream.canPlayFile('5-video.h264.mp4'), true);
  assert.strictEqual(EventStream.canPlayFile('5-video.hevc.mp4'), false);
  cleanup();
});

test('a non-auto codec is never routed through the native probe', () => {
  const dom = installDom();
  installVideojs();
  let probed = false;
  global.canPlayCodec = function() {
    probed = true;
    return true;
  };

  const choice = EventStream.chooseEventPlayer(
      Object.assign({}, PLAYER_DATA, {codec: 'MJPEG'}), EVENT_DATA);

  assert.strictEqual(choice.mode, 'zms');
  assert.strictEqual(probed, false, 'canPlayCodec not consulted for a forced codec');
  assert.ok(dom.zoompan.children.find(function(c) {
    return c.id === 'evtStream';
  }));
  cleanup();
});

test('live HLS auto event offers the HLS source before the mp4', () => {
  const dom = installDom();
  installVideojs();
  global.canPlayCodec = function() {
    return true;
  };

  EventStream.chooseEventPlayer(
      Object.assign({}, PLAYER_DATA, {hasHls: true, hlsSrc: 'x.m3u8', isLive: true}),
      EVENT_DATA);

  const video = dom.zoompan.children.find(function(c) {
    return c.id === 'videoobj';
  });
  const srcs = video.children.filter(function(c) {
    return c.tagName === 'source';
  });
  assert.strictEqual(srcs.length, 2, 'HLS + mp4');
  assert.strictEqual(srcs[0].type, 'application/x-mpegURL');
  assert.strictEqual(srcs[1].type, 'video/mp4');
  cleanup();
});

test('finished HLS auto event plays the mp4 directly, no HLS source', () => {
  const dom = installDom();
  installVideojs();
  global.canPlayCodec = function() {
    return true;
  };

  EventStream.chooseEventPlayer(
      Object.assign({}, PLAYER_DATA, {hasHls: true, hlsSrc: 'x.m3u8', isLive: false}),
      EVENT_DATA);

  const video = dom.zoompan.children.find(function(c) {
    return c.id === 'videoobj';
  });
  const srcs = video.children.filter(function(c) {
    return c.tagName === 'source';
  });
  assert.strictEqual(srcs.length, 1, 'mp4 only');
  assert.strictEqual(srcs[0].type, 'video/mp4');
  cleanup();
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
