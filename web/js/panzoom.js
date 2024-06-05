/*
 * Copyright (C) 2024 ZoneMinder
 * This file is for managing jquery.panzoom.js
 */

var zmPanZoom = {
  panZoomMaxScale: 10,
  panZoomStep: 0.3,
  panZoom: [],
  shifted: null,
  ctrled: null,

  init: function() {
    if (panZoomEnabled) {
      const _this = this;
      $j('.zoompan').each( function() {
        _this.action('enable', {obj: this});
        const stream = this.querySelector("[id^='liveStream']");
        const id = (stream) ? stringToNumber(stream.id) /* Montage & Watch page */ : eventData.MonitorId; /* Event page */
        $j(document).on('keyup.panzoom keydown.panzoom', function(e) {
          _this.shifted = e.shiftKey ? e.shiftKey : e.shift;
          _this.ctrled = e.ctrlKey;
          _this.manageCursor(id);
        });
        this.addEventListener('mousemove', function(e) {
          //Temporarily not use
        });
      });
    }
  },

  /*
  param = param['obj'] : DOM object
  param = param['id'] : monitor id
  */
  action: function(action, param) {
    const _this = this;
    if (action == "enable") {
      var id;
      if ($j(param['obj']).children('[id ^= "liveStream"]')[0]) {
        id = stringToNumber($j(param['obj']).children('[id ^= "liveStream"]')[0].id); //Montage page
      } else {
        id = eventData.MonitorId; //Event page
      }

      $j('.btn-zoom-in').removeClass('hidden');
      $j('.btn-zoom-out').removeClass('hidden');
      this.panZoom[id] = Panzoom(param['obj'], {
        minScale: 1,
        step: this.panZoomStep,
        maxScale: this.panZoomMaxScale,
        contain: 'outside',
        cursor: 'inherit',
      });
      //panZoom[id].pan(10, 10);
      //panZoom[id].zoom(1, {animate: true});
      // Binds to shift + wheel
      param['obj'].parentElement.addEventListener('wheel', function(event) {
        if (!_this.shifted) {
          return;
        }
        _this.panZoom[id].zoomWithWheel(event);
        _this.setTriggerChangedMonitors(id);
      });
    } else if (action == "disable") { //Disable a specific object
      if (!this.panZoom[param['id']]) {
        console.log(`PanZoom for monitor "${param['id']}" was not initialized.`);
        return;
      }
      $j(document).off('keyup.panzoom keydown.panzoom');
      $j('.btn-zoom-in').addClass('hidden');
      $j('.btn-zoom-out').addClass('hidden');
      this.panZoom[param['id']].reset();
      this.panZoom[param['id']].resetStyle();
      this.panZoom[param['id']].setOptions({disablePan: true, disableZoom: true});
      this.panZoom[param['id']].destroy();
    }
  },

  zoomIn: function(clickedElement) {
    if (clickedElement.target.id) {
      var id = stringToNumber(clickedElement.target.id);
    } else { //There may be an element without ID inside the button
      var id = stringToNumber(clickedElement.target.parentElement.id);
    }
    if (clickedElement.ctrlKey) {
      // Double the zoom step.
      this.panZoom[id].zoom(this.panZoom[id].getScale() * Math.exp(this.panZoomStep*2), {animate: true});
    } else {
      this.panZoom[id].zoomIn();
    }
    this.setTriggerChangedMonitors(id);
    this.manageCursor(id);
  },

  zoomOut: function(clickedElement) {
    const id = stringToNumber(clickedElement.target.id ? clickedElement.target.id : clickedElement.target.parentElement.id);
    if (clickedElement.ctrlKey) {
      // Reset zoom
      this.panZoom[id].zoom(1, {animate: true});
    } else {
      this.panZoom[id].zoomOut();
    }
    this.setTriggerChangedMonitors(id);
    this.manageCursor(id);
  },

  /*
  * id - Monitor ID
  * !!! On Montage & Watch page, when you hover over a block of buttons (in the empty space between the buttons themselves), the cursor changes, but no action occurs, you need to review "monitors[i]||monitorStream.setup_onclick(handleClick)"
  */
  manageCursor: function(id) {
    var obj;
    var obj_btn;
    obj = document.getElementById('liveStream'+id);
    if (obj) { //Montage & Watch page
      obj_btn = document.getElementById('button_zoom'+id); //Change the cursor when you hover over the block of buttons at the top of the image. Not required on Event page
    } else { //Event page
      obj = document.getElementById('videoFeedStream'+id);
    }
    const currentScale = this.panZoom[id].getScale().toFixed(1);
    if (this.shifted && this.ctrled) {
      obj.style['cursor'] = 'zoom-out';
      if (obj_btn) {
        obj_btn.style['cursor'] = 'zoom-out';
      }
    } else if (this.shifted) {
      obj.style['cursor'] = 'zoom-in';
      if (obj_btn) {
        obj_btn.style['cursor'] = 'zoom-in';
      }
    } else if (this.ctrled) {
      if (currentScale == 1.0) {
        obj.style['cursor'] = 'auto';
        if (obj_btn) {
          obj_btn.style['cursor'] = 'auto';
        }
      } else {
        obj.style['cursor'] = 'zoom-out';
        if (obj_btn) {
          obj_btn.style['cursor'] = 'zoom-out';
        }
      }
    } else { //No ctrled & no shifted
      if (currentScale == 1.0) {
        obj.style['cursor'] = 'auto';
        if (obj_btn) {
          obj_btn.style['cursor'] = 'auto';
        }
      } else {
        obj.style['cursor'] = 'move';
        if (obj_btn) {
          obj_btn.style['cursor'] = 'move';
        }
      }
    }
  },

  click: function(id) {
    if (this.ctrled && this.shifted) {
      this.panZoom[id].zoom(1, {animate: true});
    } else if (this.ctrled) {
      this.panZoom[id].zoomOut();
    } else if (this.shifted) {
      const scale = this.panZoom[id].getScale() * Math.exp(this.panZoomStep);
      const point = {clientX: event.clientX, clientY: event.clientY};
      this.panZoom[id].zoomToPoint(scale, point, {focal: {x: event.clientX, y: event.clientY}});
    }
    if (this.ctrled || this.shifted) {
      this.setTriggerChangedMonitors(id);
    }
  },

  setTriggerChangedMonitors: function(id) {
    if (typeof setTriggerChangedMonitors !== 'undefined' && $j.isFunction(setTriggerChangedMonitors)) {
      //Montage page
      setTriggerChangedMonitors(id);
    } else {
      // Event page
      updateScale = true;
    }
  }
};
