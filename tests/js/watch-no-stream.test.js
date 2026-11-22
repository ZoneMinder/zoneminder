'use strict';

// A WebSite monitor has no MonitorStream, so monitorStream stays false. The
// idle timeout still calls streamCmdStop() and, once the modal closes,
// streamCmdPlay(true); both threw on the missing stream.

const assert = require('assert');
const {loadWatch, runner} = require('./watch-sandbox');
const {test, done} = runner();

console.log('watch.js without a stream');

test('streamCmdStop does nothing when there is no stream', () => {
  const g = loadWatch();
  g.monitorStream = false;
  g.currentMonitor = {monitorStreamReplayBuffer: 0};
  g.setButtonState = g.setButtonStateWatch = () => {};
  g.streamCmdStop();
});

test('streamCmdPlay does nothing when there is no stream', () => {
  const g = loadWatch();
  g.monitorStream = false;
  g.currentMonitor = {monitorStreamReplayBuffer: 0};
  g.setButtonState = g.setButtonStateWatch = () => {};
  g.streamCmdPlay(true);
});

done();
