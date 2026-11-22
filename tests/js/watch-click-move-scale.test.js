'use strict';

// handleClick() already converts a click on the live image into monitor
// pixels, so controlCmdImage() must not also send the page's display scale:
// the server divides x and y by it again (640px turned 120,160 into 18,25) and
// a non-numeric scale such as fit_to_width throws a TypeError there.

const assert = require('assert');
const {loadWatch, runner} = require('./watch-sandbox');
const {test, done} = runner();

console.log('watch.js click-to-move scale');

for (const pageScale of ['0', '100', 'fit_to_width', '640px']) {
  test(`sends the click unscaled when the page scale is ${pageScale}`, () => {
    let sent = null;
    const getJSON = (url, data) => {
      sent = data;
      const chain = {done: () => chain, fail: () => chain};
      return chain;
    };
    const g = loadWatch({
      zmAuth: {},
      SCALE_BASE: 100,
      scale: pageScale,
      imageControlMode: 'moveMap',
      streamMode: 'jpeg',
    });
    g.$j = {getJSON};
    g.controlCmdImage(120, 160);
    assert.ok(sent, 'no control request sent');
    assert.strictEqual(sent.control, 'moveMap');
    assert.strictEqual(sent.x, 120);
    assert.strictEqual(sent.y, 160);
    assert.strictEqual(Number(sent.scale), 100);
  });
}

done();
