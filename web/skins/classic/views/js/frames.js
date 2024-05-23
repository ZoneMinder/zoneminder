var backBtn = $j('#backBtn');
var table = $j('#framesTable');

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
        console.log('Total: '+data.total);
        console.log('TotalnotFiltered: '+data.totalNotFiltered);
        params.success({total: data.total, totalNotFiltered: data.totalNotFiltered, rows: rows});
      })
      .fail(logAjaxFail);
}

function processRows(rows) {
  $j.each(rows, function(ndx, row) {
    // WIP: process each row here
    // VERIFY: Might not need to do anything here for the frames table
  });
  return rows;
}

function processClicks(event, field, value, row, $element) {
  if ( field == 'Score' ) {
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
  // Remove the thumbnail column from the DOM if thumbnails are off globally
  if ( !WEB_LIST_THUMBS ) $j('th[data-field="Thumbnail"]').remove();

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

  // Update table links each time after new data is loaded
  table.on('post-body.bs.table', function(data) {
    var type_ndx = $j('#framesTable tr th').filter(function() {
      return $j(this).text().trim() == 'Type';
    }).index();

    $j('#framesTable tr').each(function(ndx, row) {
      var row = $j(row);
      var type = row.find('td').eq(type_ndx).text().trim();
      row.addClass(type.toLowerCase());
    });

    var thumb_ndx = $j('#framesTable tr th').filter(function() {
      return $j(this).text().trim() == 'Thumbnail';
    }).index();
    table.find("tr td:nth-child(" + (thumb_ndx+1) + ")").addClass('colThumbnail');
  });
}

$j(document).ready(function() {
  initPage();
});
