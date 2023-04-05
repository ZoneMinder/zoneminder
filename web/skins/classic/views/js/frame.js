const table = $j('#frameStatsTable');
const backBtn = $j('#backBtn');
const statsBtn = $j('#statsBtn');

function changeScale() {
  const scale = $j('#scale').val();
  const img = $j('#frameImg');
  const controlsLinks = {
    next: $j('#nextLink'),
    prev: $j('#prevLink'),
    first: $j('#firstLink'),
    last: $j('#lastLink')
  };

  if (img) {
    const baseWidth = $j('#base_width').val();
    const baseHeight = $j('#base_height').val();
    if (!parseInt(scale)) {
      const newSize = scaleToFit(baseWidth, baseHeight, img, $j('#controls'));
      newWidth = newSize.width;
      newHeight = newSize.height;
      autoScale = newSize.autoScale;
    } else {
      $j(window).off('resize', endOfResize); // remove resize handler when Scale to Fit is not active
      newWidth = baseWidth * scale / SCALE_BASE;
      newHeight = baseHeight * scale / SCALE_BASE;
    }
    img.css('width', newWidth + 'px');
    img.css('height', newHeight + 'px');
  }
  setCookie('zmWatchScale', scale, 3600);
  $j.each(controlsLinks, function(k, anchor) { // Make frames respect scale choices
    if (anchor) {
      anchor.prop('href', anchor.prop('href').replace(/scale=.*&/, 'scale=' + scale + '&'));
    }
  });

  // After a resize, check if we still have room to display the event stats table
  onStatsResize(newWidth);
}

function getFrameStatsCookie() {
  const cookie = 'zmFrameStats';
  let stats = getCookie(cookie);

  if (!stats) {
    stats = 'on';
    setCookie(cookie, stats, 10*365);
  }
  return stats;
}

function getStat(params) {
  $j.getJSON(thisUrl + '?view=request&request=stats&raw=true', params)
      .done(function(data) {
        $j('#frameStatsTable').empty().append('<tbody>');
        if (!data.raw.length) {
          statsBtn.prop('disabled', true);
          statsBtn.prop('title', 'No statistics available for this frame');
          return;
        }
        for (const stat of data.raw) {
          $j.each(statHeaderStrings, function(key) {
            const th = $j('<th>').addClass('text-right').text(statHeaderStrings[key]);
            let tdString = '';

            switch (stat ? key : 'n/a') {
              case 'FrameId':
                tdString = '<a href="?view=frame&amp;eid=' + params.eid + '&amp;fid=' + params.fid + '">' + stat[key] + '</a>';
                break;
              case 'EventId':
                tdString = '<a href="?view=event&amp;eid=' + params.eid + '">' + stat[key] + '</a>';
                break;
              case 'n/a':
                tdString = 'n/a';
                break;
              default:
                tdString = stat[key];
            }

            const td = $j('<td>').html(tdString);
            const row = $j('<tr>').append(th, td);

            $j('#frameStatsTable tbody').append(row);
          });
        } // end foreach stat
      })
      .fail(logAjaxFail);
}

function onStatsResize(vidwidth) {
  var minWidth = 300; // An arbitrary value in pixels used to hide the stats table
  var width = $j(window).width() - vidwidth;

  // Hide the stats table if we have run out of room to show it properly
  if (width < minWidth) {
    statsBtn.prop('disabled', true);
    if (table.is(':visible')) {
      table.toggle(false);
      wasHidden = true;
    }
  // Show the stats table if we hid it previously and sufficient room becomes available
  } else if (width >= minWidth) {
    statsBtn.prop('disabled', false);
    if (!table.is(':visible') && wasHidden) {
      table.toggle(true);
      wasHidden = false;
    }
  }
}

function initPage() {
  if (scale == '0' || scale == 'auto') changeScale();

  // Don't enable the back button if there is no previous zm page to go back to
  backBtn.prop('disabled', !document.referrer.length);

  // Manage the BACK button
  document.getElementById("backBtn").addEventListener("click", function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  // Manage the REFRESH Button
  document.getElementById("refreshBtn").addEventListener("click", function onRefreshClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });

  // Manage the Frame STATISTICS Button
  document.getElementById("statsBtn").addEventListener("click", function onStatsClick(evt) {
    evt.preventDefault();

    // Toggle the visiblity of the stats table and write an appropriate cookie
    if (table.is(':visible')) {
      setCookie('zmFrameStats', 'off', 10*365);
      table.toggle(false);
    } else {
      setCookie('zmFrameStats', 'on', 10*365);
      table.toggle(true);
    }
  });

  // Manage the Frame STATISTICS VIEW button
  document.getElementById("statsViewBtn").addEventListener("click", function onViewClick(evt) {
    evt.preventDefault();
    window.location.href = thisUrl+'?view=stats&eid='+eid+'&fid='+fid;
  });

  // Load the frame stats
  getStat({eid: eid, fid: fid});

  if (getFrameStatsCookie() != 'on') {
    table.toggle(false);
  } else {
    onStatsResize($j('#base_width').val() * scale / SCALE_BASE);
  }

  // Manage the FRAMES Button
  bindButton('#framesBtn', 'click', null, function onFramesClick(evt) {
    evt.preventDefault();
    window.location.assign('?view=frames&eid='+eid);
  });
}

// Kick everything off
$j(document).ready(initPage);
