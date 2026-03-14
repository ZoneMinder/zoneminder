"use strict";

function editClicked() {
  if (mode == EDITING) {
    mode = VIEWING;
    //$j('#columnsControlInner').css('visibility', 'hidden');
    // save layout
    saveSort();
    monitors_ul.sortable('disable');
    $j('#editBtn').show();
    $j('#saveBtn').hide();
  } else {
    mode = EDITING;
    //$j('#columnsControlInner').css('visibility', 'visible');
    monitors_ul.sortable('enable');
    $j('#editBtn ').hide();
    $j('#saveBtn').show();
  }
}

function saveSort() {
  const monitor_ids = [];
  $j('#monitors .monitor').each(function(index, element) {
    monitor_ids[monitor_ids.length] = element.getAttribute('data-id');
  });

  const selectedGroups = $j('[name="GroupId\\[\\]"]');
  if (!selectedGroups.length) {
    alert("GroupIds not found");
    return;
  }

  const layout_user_preference = {
    "UserId": user.Id,
    "Name": 'MontageSort'+selectedGroups.val().join(','),
    "Value": monitor_ids.join(',')
  };

  const server = Servers[serverId];
  if (!server) {
    Error("Unknown server "+serverId);
    return;
  }
  $j.ajax({
    url: server.urlToApi() + '/user_preference.json?'+auth_relay,
    method: 'POST',
    dataType: 'json',
    data: layout_user_preference,
    timeout: 0,
    success: function(data) {
      if (data.result == 'Error') {
        alert(data.message);
        return;
      }
    },
    error: function(jqXHR) {
      console.log("error", jqXHR);
      //logAjaxFail(jqXHR);
      //$j('#eventTable').bootstrapTable('refresh');
    }
  });
} // end function saveSort

/// handles packing different size/aspect monitors on screen

function maxfit2(divW, divH) {
  let bestFitX = []; // how we arranged the so-far best match
  let bestFitX2 = [];
  let bestFitY = [];
  let bestFitY2 = [];

  let minScale = 0.05;
  let maxScale = 5.00;
  let bestFitArea = 0;
  let borders_width=-1;
  let borders_height=-1;

  while (1) {
    if ( maxScale - minScale < 0.01 ) break;
    const thisScale = (maxScale + minScale) / 2;
    let allFit = 1;
    let thisArea = 0;
    const thisX = []; // top left
    const thisY = [];
    const thisX2 = []; // bottom right
    const thisY2 = [];

    for ( let m = 0; m < numMonitors; m++ ) {
      // this loop places each monitor (if it can)
      const monId = monitorPtr[m];

      function doesItFit(x, y, w, h, d) { // does block (w,h) fit at position (x,y) relative to edge and other nodes already done (0..d)
        if ((x+w>=divW) || (y+h>=divH)) return 0;
        for (let i=0; i <= d; i++) {
          if (!(thisX[i]>x+w-1 || thisX2[i] < x || thisY[i] > y+h-1 || thisY2[i] < y)) return 0;
        }
        return 1; // it's OK
      }

      const monitor_div = $j('#Monitor'+monId);
      if (borders_width <= 0) {
        borders_width = parseInt(monitor_div.css('border-left-width')) + parseInt(monitor_div.css('border-right-width'));
      }
      if (borders_height <= 0) {
        borders_height = parseInt(monitor_div.css('border-top-width')) + parseInt(monitor_div.css('border-bottom-width'));
      } // assume fixed size border, and added to both sides and top/bottom

      // try fitting over first, then down.  Each new one must land at either
      // upper right or lower left corner of last (try in that order)
      // Pick the one with the smallest Y, then smallest X if Y equal
      let fitX = 999999999;
      let fitY = 999999999;
      for (let adjacent = 0; adjacent < m; adjacent ++ ) {
        // try top right of adjacent
        if (doesItFit(thisX2[adjacent]+1, thisY[adjacent],
            monitorWidth[monId] * thisScale * monitorNormalizeScale[monId] * monitorZoomScale[monId] + borders_width,
            monitorHeight[monId] * thisScale * monitorNormalizeScale[monId] * monitorZoomScale[monId] + borders_height,
            m-1) == 1) {
          if ( thisY[adjacent]<fitY || ( thisY[adjacent] == fitY && thisX2[adjacent]+1 < fitX ) ) {
            fitX = thisX2[adjacent] + 1;
            fitY = thisY[adjacent];
          }
        }
        // try bottom left
        if (doesItFit(thisX[adjacent], thisY2[adjacent]+1,
            monitorWidth[monId] * thisScale * monitorNormalizeScale[monId] * monitorZoomScale[monId] + borders_width,
            monitorHeight[monId] * thisScale * monitorNormalizeScale[monId] * monitorZoomScale[monId] + borders_height,
            m-1) == 1) {
          if ( thisY2[adjacent]+1 < fitY || ( thisY2[adjacent]+1 == fitY && thisX[adjacent] < fitX ) ) {
            fitX = thisX[adjacent];
            fitY = thisY2[adjacent] + 1;
          }
        }
      } // end for adjacent < m

      if (m == 0) { // note for the very first one there were no adjacents so the above loop didn't run
        if (doesItFit(
            0, 0,
            monitorWidth[monId] * thisScale * monitorNormalizeScale[monId] * monitorZoomScale[monId] + borders_width,
            monitorHeight[monId] * thisScale * monitorNormalizeScale[monId] * monitorZoomScale[monId] + borders_height,
            -1) == 1) {
          fitX = 0;
          fitY = 0;
        }
      }

      if (fitX == 999999999) {
        allFit = 0;
        break; // break out of monitor loop flagging we didn't fit
      }
      thisX[m] =fitX;
      thisX2[m]=fitX + monitorWidth[monitorPtr[m]] * thisScale * monitorNormalizeScale[monitorPtr[m]] * monitorZoomScale[monitorPtr[m]] + borders_width;
      thisY[m] =fitY;
      thisY2[m]=fitY + monitorHeight[monitorPtr[m]] * thisScale * monitorNormalizeScale[monitorPtr[m]] * monitorZoomScale[monitorPtr[m]] + borders_height;
      thisArea += (thisX2[m] - thisX[m])*(thisY2[m] - thisY[m]);
    } // end foreach monitor
    if (allFit == 1) {
      minScale=thisScale;
      if (bestFitArea<thisArea) {
        bestFitArea=thisArea;
        bestFitX=thisX;
        bestFitY=thisY;
        bestFitX2=thisX2;
        bestFitY2=thisY2;
      }
    } else {
      // didn't fit
      maxScale=thisScale;
    }
  }
  if (bestFitArea > 0) { // only rearrange if we could fit -- otherwise just do nothing, let them start coming out, whatever
    // Find bounding box of all positioned monitors and center horizontally
    let maxRight = 0;
    for (let m=0; m < numMonitors; m++) {
      if (bestFitX2[m] > maxRight) maxRight = bestFitX2[m];
    }
    const offsetX = Math.max(0, Math.floor((divW - maxRight) / 2));

    for (let m=0; m < numMonitors; m++) {
      const c = document.getElementById('Monitor' + monitorPtr[m]);
      c.style.position = 'absolute';
      c.style.left = (bestFitX[m] + offsetX).toString() + "px";
      c.style.top = bestFitY[m].toString() + "px";
      c.width = bestFitX2[m] - bestFitX[m] + 1 - borders_width;
      c.height = bestFitY2[m] - bestFitY[m] + 1 - borders_height;
    }
    return 1;
  } else {
    return 0;
  }
}
