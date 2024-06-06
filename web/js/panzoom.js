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

  init: function(param={}) {
    const objString = param.objString;
    const contain = (param.contain) ? param.contain : (objString) ? null : "outside";
    const disablePan = (param.disablePan) ? param.disablePan : (contain != "outside") ? true : false;

    if (panZoomEnabled) {
      const _this = this;
      const object = (objString) ? $j(objString) : $j('.zoompan');

      object.each( function() {
        const params = {
          obj: this,
          contain: contain,
          disablePan: disablePan
        };
        _this.action('enable', params);
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
  * param = param['obj'] : DOM object
  * param = param['id'] : monitor id
  * param = param['contain'] : "inside" | "outside", default="outside"
  * param = param['disablePan'] : true || false
  */
  action: function(action, param) {
    const _this = this;
    const contain = param['contain'];
    const disablePan = param['disablePan'];
    const minScale = (contain != "outside") ? 0.1 : 1.0;
    if (action == "enable") {
      var id;

      if (typeof eventData != 'undefined') {
        id = eventData.MonitorId; //Event page
      } else {
        const obj = $j(param['obj']).find('[id ^= "liveStream"]')[0];
        if (obj) {
          id = stringToNumber(obj.id); //Montage page
        } 
      }
      if (!id) {
        console.log("The for panZoom action object was not found.", param);
        return;
      }
      $j('.btn-zoom-in').removeClass('hidden');
      $j('.btn-zoom-out').removeClass('hidden');
      this.panZoom[id] = Panzoom(param['obj'], {
        minScale: minScale,
        step: this.panZoomStep,
        maxScale: this.panZoomMaxScale,
        contain: contain, //"inside" | "outside" | null
        cursor: 'inherit',
        disablePan: disablePan,
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

      param['obj'].addEventListener('panzoomchange', (event) => {
        if (typeof panZoomEventPanzoomchange !== 'undefined' && $j.isFunction(panZoomEventPanzoomchange)) panZoomEventPanzoomchange(param['obj'], event);
        //console.log('panzoomchange', event.detail) // => { x: 0, y: 0, scale: 1 }
      })
      param['obj'].addEventListener('panzoomzoom', (event) => {
        if (typeof panZoomEventPanzoomzoom !== 'undefined' && $j.isFunction(panZoomEventPanzoomzoom)) panZoomEventPanzoomzoom(param['obj'], event);
      })
      param['obj'].addEventListener('panzoomstart', (event) => {
        if (typeof panZoomEventPanzoomstart !== 'undefined' && $j.isFunction(panZoomEventPanzoomstart)) panZoomEventPanzoomstart(param['obj'], event);
      })
      param['obj'].addEventListener('panzoompan', (event) => {
        if (typeof panZoomEventPanzoompan !== 'undefined' && $j.isFunction(panZoomEventPanzoompan)) panZoomEventPanzoompan(param['obj'], event);
      })
      param['obj'].addEventListener('panzoomend', (event) => {
        if (typeof panZoomEventPanzoomend !== 'undefined' && $j.isFunction(panZoomEventPanzoomend)) panZoomEventPanzoomend(param['obj'], event);
      })
      param['obj'].addEventListener('panzoomreset', (event) => {
        if (typeof panZoomEventPanzoomreset !== 'undefined' && $j.isFunction(panZoomEventPanzoomreset)) panZoomEventPanzoomreset(param['obj'], event);
      })
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
    if (!this.panZoom[id]) {
      console.log(`PanZoom for monitor ID=${id} is not initialized.`);
      return;
    }
    var obj;
    var obj_btn;
    const disablePan = this.panZoom[id].getOptions().disablePan;
    const disableZoom = this.panZoom[id].getOptions().disableZoom;

    obj = document.getElementById('liveStream'+id);
    if (obj) { //Montage & Watch page
      obj_btn = document.getElementById('button_zoom'+id); //Change the cursor when you hover over the block of buttons at the top of the image. Not required on Event page
    } else { //Event page
      obj = document.getElementById('videoFeedStream'+id);
    }
    const currentScale = this.panZoom[id].getScale().toFixed(1);
    if (this.shifted && this.ctrled) {
      const cursor = (disableZoom) ? 'auto' : 'zoom-out';
      obj.style['cursor'] = cursor;
      if (obj_btn) {
        obj_btn.style['cursor'] = cursor;
      }
    } else if (this.shifted) {
      const cursor = (disableZoom) ? 'auto' : 'zoom-in';
      obj.style['cursor'] = cursor;
      if (obj_btn) {
        obj_btn.style['cursor'] = cursor;
      }
    } else if (this.ctrled) {
      if (currentScale == 1.0) {
        obj.style['cursor'] = 'auto';
        if (obj_btn) {
          obj_btn.style['cursor'] = 'auto';
        }
      } else {
       const cursor = (disableZoom) ? 'auto' : 'zoom-out';
       obj.style['cursor'] = cursor;
        if (obj_btn) {
          obj_btn.style['cursor'] = cursor;
        }
      }
    } else { //No ctrled & no shifted
      if (currentScale == 1.0) {
        obj.style['cursor'] = 'auto';
        if (obj_btn) {
          obj_btn.style['cursor'] = 'auto';
        }
      } else {
        const cursor = (disablePan) ? 'auto' : 'move';
        obj.style['cursor'] = cursor;
        if (obj_btn) {
          obj_btn.style['cursor'] = cursor;
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
