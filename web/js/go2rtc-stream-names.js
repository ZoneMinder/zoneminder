"use strict";

(function(root, factory) {
  if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    root.ZMGo2RTCStreamNames = factory();
  }
}(typeof globalThis !== 'undefined' ? globalThis : this, function() {
  function getGo2RTCStreamBase(monitorId, monitorName) {
    if (typeof monitorName === 'string' && monitorName.length) {
      return monitorName;
    }
    if (monitorId === undefined || monitorId === null) {
      return '';
    }
    return String(monitorId);
  }

  function getGo2RTCStreamName(monitorId, monitorName, suffix) {
    return getGo2RTCStreamBase(monitorId, monitorName) + (suffix || '');
  }

  return {
    getGo2RTCStreamBase,
    getGo2RTCStreamName,
  };
}));
