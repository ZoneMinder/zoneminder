'use strict';

// The PTZ arrows carry data-xtell/data-ytell. 0 means the click position on
// that axis does not matter (Up has data-xtell="0", Left has data-ytell="0").
// getAttribute() returns the string "0", which is truthy, so controlCmd() sent
// the raw click position as xge/yge for the unused axis. The control request
// then got a --panspeed for a straight tilt (or --tiltspeed for a straight pan)
// and, with an auto stop timeout set, that stray speed can drop --autostop.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const {runner} = require('./watch-sandbox');

const {test, done} = runner();

// A 100x100 button at (0,0), clicked at (95, 80).
function makeButton(value, xtell, ytell) {
  return {
    attrs: {'value': value, 'data-xtell': xtell, 'data-ytell': ytell},
    getAttribute(k) {
      return this.attrs[k];
    },
  };
}
const click = (button) => ({type: 'mousedown', target: button, currentTarget: button, pageX: 95, pageY: 80});

function fakeJquery(sent) {
  const box = {
    offset: () => ({left: 0, top: 0}), width: () => 100, height: () => 100, val: () => '1',
    on: () => box, ready: () => box, change: () => box,
  };
  const $j = function() {
    return box;
  };
  $j.getJSON = function(url, data) {
    sent.push(data);
    const p = {done: () => p, fail: () => p};
    return p;
  };
  return $j;
}

function controlSend(button) {
  const sent = [];
  const src = fs.readFileSync(
      path.join(__dirname, '../../web/skins/classic/views/js/control.js'), 'utf8');
  const ctx = {
    $j: fakeJquery(sent), zmAuth: {appendTo: (u) => u}, monitorUrl: '',
    parseInt, document: {}, logAjaxFail: () => {},
  };
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  ctx.controlCmd(click(button));
  return sent[0];
}

[['control.js', controlSend]].forEach(function([file, send]) {
  test(file + ': Up sends no pan factor', () => {
    const data = send(makeButton('moveConUp', '0', '-1'));
    assert.strictEqual(data.control, 'moveConUp');
    assert.strictEqual(data.xge, undefined, 'xge ' + data.xge);
    assert.strictEqual(data.yge, 20);
  });
  test(file + ': Left sends no tilt factor', () => {
    const data = send(makeButton('moveConLeft', '-1', '0'));
    assert.strictEqual(data.xge, 5);
    assert.strictEqual(data.yge, undefined, 'yge ' + data.yge);
  });
  test(file + ': a diagonal still sends both', () => {
    const data = send(makeButton('moveConDownRight', '1', '1'));
    assert.strictEqual(data.xge, 95);
    assert.strictEqual(data.yge, 80);
  });
});

done();
