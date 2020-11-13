// Called by bootstrap-table to retrieve zm frame data
function ajaxRequest(params) {
  if ( params.data && params.data.filter ) {
    params.data.advsearch = params.data.filter;
    delete params.data.filter;
  }
  $j.getJSON(thisUrl + '?view=request&request=frames&task=query&eid='+eid, params.data)
      .done(function(data) {
        var rows = processRows(data.rows);
        // rearrange the result into what bootstrap-table expects
        params.success({total: data.total, totalNotFiltered: data.totalNotFiltered, rows: rows});
      })
      .fail(logAjaxFail);
}

function processRows(rows) {
  $j.each(rows, function(ndx, row) {
    // WIP: process each row here
  });
  return rows;
}

function thumbnail_onmouseover(event) {
  var img = event.target;
  img.src = '';
  img.src = img.getAttribute('full_img_src');
}

function thumbnail_onmouseout(event) {
  var img = event.target;
  img.src = '';
  img.src = img.getAttribute('img_src');
}

function initThumbAnimation() {
  $j('.colThumbnail img').each(function() {
    this.addEventListener('mouseover', thumbnail_onmouseover, false);
    this.addEventListener('mouseout', thumbnail_onmouseout, false);
  });
}

function processClicks(event, field, value, row, $element) {
  if ( field == 'FrameScore' ) {
    window.location.assign('?view=stats&eid='+row.EventId+'&fid='+row.FrameId);
  } else {
    window.location.assign('?view=frame&eid='+row.EventId+'&fid='+row.FrameId);
  }
}

// This function handles when the user clicks a "+" link to retrieve stats for a frame
function detailFormatter(index, row, $detail) {
  $detail.html('Please wait. Loading from ajax request...');
  $j.get(thisUrl + '?request=stats&eid=' + row.EventId + '&fid=' + row.FrameId + '&row=' + index)
      .done(function(data) {
        $detail.html(data.html);
      })
      .fail(logAjaxFail);
}
function initPage() {
  var backBtn = $j('#backBtn');
  var table = $j('#framesTable');

  // Init the bootstrap-table
  table.bootstrapTable({icons: icons});

  // Hide these columns on first run when no cookie is saved
  if ( !getCookie("zmFramesTable.bs.table.columns") ) {
    table.bootstrapTable('hideColumn', 'EventId');
  }

  // Hide the stats tables on init
  $j(".contentStatsTable").hide();

  // Disable the back button if there is nothing to go back to
  backBtn.prop('disabled', !document.referrer.length);

  // Setup the thumbnail animation
  initThumbAnimation();

  // Some toolbar events break the thumbnail animation, so re-init eventlistener
  table.on('all.bs.table', initThumbAnimation);

  // Load the associated frame image when the user clicks on a row
  table.on('click-cell.bs.table', processClicks);

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
}

$j(document).ready(function() {
  initPage();
});
