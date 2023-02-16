var backBtn = $j('#backBtn');
var viewBtn = $j('#viewBtn');
var archiveBtn = $j('#archiveBtn');
var unarchiveBtn = $j('#unarchiveBtn');
var editBtn = $j('#editBtn');
var exportBtn = $j('#exportBtn');
var downloadBtn = $j('#downloadBtn');
var deleteBtn = $j('#deleteBtn');
var table = $j('#eventTable');

/*
This is the format of the json object sent by bootstrap-table

var params =
{
"type":"get",
"data":
  {
  "search":"some search text",
  "sort":"StartDateTime",
  "order":"asc",
  "offset":0,
  "limit":25
  "filter":
    {
    "Name":"some advanced search text"
    "StartDateTime":"some more advanced search text"
    }
  },
"cache":true,
"contentType":"application/json",
"dataType":"json"
};
*/

// Called by bootstrap-table to retrieve zm event data
function ajaxRequest(params) {
  if (params.data && params.data.filter) {
    params.data.advsearch = params.data.filter;
    delete params.data.filter;
  }
  $j.ajax({
    url: thisUrl + '?view=request&request=events&task=query'+filterQuery,
    data: params.data,
    timeout: 0,
    success: function(data) {
      if (data.result == 'Error') {
        alert(data.message);
        return;
      }
      var rows = processRows(data.rows);
      // rearrange the result into what bootstrap-table expects
      params.success({total: data.total, totalNotFiltered: data.totalNotFiltered, rows: rows});
    },
    error: function(jqXHR) {
      console.log("error", jqXHR);
      //logAjaxFail(jqXHR);
      //$j('#eventTable').bootstrapTable('refresh');
    }
  });
}

function processRows(rows) {
  $j.each(rows, function(ndx, row) {
    var eid = row.Id;
    var archived = row.Archived == yesString ? archivedString : '';
    var emailed = row.Emailed == yesString ? emailedString : '';

    row.Id = '<a href="?view=event&amp;eid=' + eid + filterQuery + sortQuery + '&amp;page=1">' + eid + '</a>';
    row.Name = '<a href="?view=event&amp;eid=' + eid + filterQuery + sortQuery + '&amp;page=1">' + row.Name + '</a>' +
        '<br/><div class="small text-muted">' + archived + emailed + '</div>';
    if ( canEdit.Monitors ) row.Monitor = '<a href="?view=event&amp;eid=' + eid + '">' + row.Monitor + '</a>';
    if ( canEdit.Events ) row.Cause = '<a href="#" title="' + row.Notes + '" class="eDetailLink" data-eid="' + eid + '">' + row.Cause + '</a>';
    if ( row.Notes.indexOf('detected:') >= 0 ) {
      row.Cause = row.Cause + '<a href="#" class="objDetectLink" data-eid=' +eid+ '><div class="small text-muted"><u>' + row.Notes + '</u></div></div></a>';
    } else if ( row.Notes != 'Forced Web: ' ) {
      row.Cause = row.Cause + '<br/><div class="small text-muted">' + row.Notes + '</div>';
    }
    row.Frames = '<a href="?view=frames&amp;eid=' + eid + '">' + row.Frames + '</a>';
    row.AlarmFrames = '<a href="?view=frames&amp;eid=' + eid + '">' + row.AlarmFrames + '</a>';
    row.MaxScore = '<a href="?view=frame&amp;eid=' + eid + '&amp;fid=0">' + row.MaxScore + '</a>';

    const date = new Date(0); // Have to init it fresh.  setSeconds seems to add time, not set it.
    date.setSeconds(row.Length);
    row.Length = date.toISOString().substr(11, 8);

    if ( WEB_LIST_THUMBS ) row.Thumbnail = '<a href="?view=event&amp;eid=' + eid + filterQuery + sortQuery + '&amp;page=1">' + row.imgHtml + '</a>';
  });

  return rows;
}

// Returns the event id's of the selected rows
function getIdSelections() {
  var table = $j('#eventTable');

  return $j.map(table.bootstrapTable('getSelections'), function(row) {
    return row.Id.replace(/(<([^>]+)>)/gi, ''); // strip the html from the element before sending
  });
}

// Returns a boolen to indicate at least one selected row is archived
function getArchivedSelections() {
  var table = $j('#eventTable');
  var selection = $j.map(table.bootstrapTable('getSelections'), function(row) {
    return row.Archived;
  });
  return selection.includes("Yes");
}

// Load the Delete Confirmation Modal HTML via Ajax call
function getDelConfirmModal() {
  $j.getJSON(thisUrl + '?request=modal&modal=delconfirm')
      .done(function(data) {
        insertModalHtml('deleteConfirm', data.html);
        manageDelConfirmModalBtns();
      })
      .fail(function(jqXHR) {
        console.log("error getting delconfirm", jqXHR);
        logAjaxFail(jqXHR);
      });
}

// Manage the DELETE CONFIRMATION modal button
function manageDelConfirmModalBtns() {
  document.getElementById("delConfirmBtn").addEventListener("click", function onDelConfirmClick(evt) {
    if ( ! canEdit.Events ) {
      enoperm();
      return;
    }
    evt.preventDefault();

    const selections = getIdSelections();
    deleteEvents(selections);
  });

  // Manage the CANCEL modal button
  document.getElementById("delCancelBtn").addEventListener("click", function onDelCancelClick(evt) {
    $j('#deleteConfirm').modal('hide');
  });
}

function deleteEvents(event_ids) {
  const ticker = document.getElementById('deleteProgressTicker');
  const chunk = event_ids.splice(0, 10);
  console.log("Deleting " + chunk.length + " selections.  " + event_ids.length);

  $j.ajax({
    method: 'get',
    timeout: 0,
    url: thisUrl + '?request=events&task=delete',
    data: {'eids[]': chunk},
    success: function(data) {
      if (!event_ids.length) {
        $j('#eventTable').bootstrapTable('refresh');
        $j('#deleteConfirm').modal('hide');
      } else {
        if ( ticker.innerHTML.length < 1 || ticker.innerHTML.length > 10 ) {
          ticker.innerHTML = '.';
        } else {
          ticker.innerHTML = ticker.innerHTML + '.';
        }
        deleteEvents(event_ids);
      }
    },
    fail: function(jqxhr) {
      logAjaxFail(jqxhr);
      $j('#eventTable').bootstrapTable('refresh');
      $j('#deleteConfirm').modal('hide');
    }
  });
}

function getEventDetailModal(eid) {
  $j.getJSON(thisUrl + '?request=modal&modal=eventdetail&eids[]=' + eid)
      .done(function(data) {
        insertModalHtml('eventDetailModal', data.html);
        $j('#eventDetailModal').modal('show');
        // Manage the Save button
        $j('#eventDetailSaveBtn').click(function(evt) {
          evt.preventDefault();
          $j('#eventDetailForm').submit();
        });
      })
      .fail(function(jqxhr) {
        console.log("Fail get event details");
        logAjaxFail(jqxhr);
      });
}

function getObjdetectModal(eid) {
  $j.getJSON(thisUrl + '?request=modal&modal=objdetect&eid=' + eid)
      .done(function(data) {
        insertModalHtml('objdetectModal', data.html);
        $j('#objdetectModal').modal('show');
      })
      .fail(function(jqxhr) {
        console.log("Fail get objdetect details");
        logAjaxFail(jqxhr);
      });
}

function initPage() {
  // Remove the thumbnail column from the DOM if thumbnails are off globally
  if ( !WEB_LIST_THUMBS ) $j('th[data-field="Thumbnail"]').remove();

  // Load the delete confirmation modal into the DOM
  getDelConfirmModal();

  // Init the bootstrap-table
  table.bootstrapTable({icons: icons});

  // Hide these columns on first run when no cookie is saved
  if ( !getCookie("zmEventsTable.bs.table.columns") ) {
    table.bootstrapTable('hideColumn', 'Archived');
    table.bootstrapTable('hideColumn', 'Emailed');
  }

  // enable or disable buttons based on current selection and user rights
  table.on('check.bs.table uncheck.bs.table ' +
  'check-all.bs.table uncheck-all.bs.table',
  function() {
    selections = table.bootstrapTable('getSelections');

    viewBtn.prop('disabled', !(selections.length && canView.Events));
    archiveBtn.prop('disabled', !(selections.length && canEdit.Events));
    unarchiveBtn.prop('disabled', !(getArchivedSelections()) && canEdit.Events);
    editBtn.prop('disabled', !(selections.length && canEdit.Events));
    exportBtn.prop('disabled', !(selections.length && canView.Events));
    downloadBtn.prop('disabled', !(selections.length && canView.Events));
    deleteBtn.prop('disabled', !(selections.length && canEdit.Events));
  });

  // Don't enable the back button if there is no previous zm page to go back to
  backBtn.prop('disabled', !document.referrer.length);

  // Setup the thumbnail video animation
  initThumbAnimation();

  // Some toolbar events break the thumbnail animation, so re-init eventlistener
  table.on('all.bs.table', initThumbAnimation);

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

  // Manage the TIMELINE Button
  document.getElementById("tlineBtn").addEventListener("click", function onTlineClick(evt) {
    evt.preventDefault();
    window.location.assign('?view=timeline'+filterQuery);
  });

  // Manage the FILTER Button
  document.getElementById("filterBtn").addEventListener("click", function onFilterClick(evt) {
    evt.preventDefault();
    window.location.assign('?view=filter'+filterQuery);
  });

  // Manage the VIEW button
  document.getElementById("viewBtn").addEventListener("click", function onViewClick(evt) {
    var selections = getIdSelections();

    evt.preventDefault();
    var filter = '&filter[Query][terms][0][attr]=Id&filter[Query][terms][0][op]=%3D%5B%5D&filter[Query][terms][0][val]='+selections.join('%2C');
    window.location.href = thisUrl+'?view=event&eid='+selections[0]+filter+sortQuery+'&page=1&play=1';
  });

  // Manage the ARCHIVE button
  document.getElementById("archiveBtn").addEventListener("click", function onArchiveClick(evt) {
    var selections = getIdSelections();

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=events&task=archive&eids[]='+selections.join('&eids[]='))
        .done( function(data) {
          $j('#eventTable').bootstrapTable('refresh');
        })
        .fail(logAjaxFail);
  });

  // Manage the UNARCHIVE button
  document.getElementById("unarchiveBtn").addEventListener("click", function onUnarchiveClick(evt) {
    if ( ! canEdit.Events ) {
      enoperm();
      return;
    }

    var selections = getIdSelections();
    //console.log(selections);

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=events&task=unarchive&eids[]='+selections.join('&eids[]='))
        .done( function(data) {
          $j('#eventTable').bootstrapTable('refresh');
        })
        .fail(logAjaxFail);
  });

  // Manage the EDIT button
  document.getElementById("editBtn").addEventListener("click", function onEditClick(evt) {
    if ( ! canEdit.Events ) {
      enoperm();
      return;
    }

    var selections = getIdSelections();

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=modal&modal=eventdetail&eids[]='+selections.join('&eids[]='))
        .done(function(data) {
          insertModalHtml('eventDetailModal', data.html);
          $j('#eventDetailModal').modal('show');
          // Manage the Save button
          $j('#eventDetailSaveBtn').click(function(evt) {
            evt.preventDefault();
            $j('#eventDetailForm').submit();
          });
        })
        .fail(logAjaxFail);
  });

  // Manage the EXPORT button
  document.getElementById("exportBtn").addEventListener("click", function onExportClick(evt) {
    var selections = getIdSelections();

    evt.preventDefault();
    window.location.assign('?view=export&eids[]='+selections.join('&eids[]='));
  });

  // Manage the DOWNLOAD VIDEO button
  document.getElementById("downloadBtn").addEventListener("click", function onDownloadClick(evt) {
    var selections = getIdSelections();

    evt.preventDefault();
    $j.getJSON(thisUrl + '?request=modal&modal=download&eids[]='+selections.join('&eids[]='))
        .done(function(data) {
          insertModalHtml('downloadModal', data.html);
          $j('#downloadModal').modal('show');
          // Manage the GENERATE DOWNLOAD button
          $j('#exportButton').click(exportEvent);
        })
        .fail(logAjaxFail);
  });

  // Manage the DELETE button
  document.getElementById("deleteBtn").addEventListener("click", function onDeleteClick(evt) {
    if ( ! canEdit.Events ) {
      enoperm();
      return;
    }

    evt.preventDefault();
    $j('#deleteConfirm').modal('show');
  });

  // Update table links each time after new data is loaded
  table.on('post-body.bs.table', function(data) {
    // Manage the Object Detection links in the events list
    $j(".objDetectLink").click(function(evt) {
      evt.preventDefault();
      var eid = $j(this).data('eid');
      getObjdetectModal(eid);
    });

    // Manage the eventdetail links in the events list
    $j(".eDetailLink").click(function(evt) {
      evt.preventDefault();
      var eid = $j(this).data('eid');
      getEventDetailModal(eid);
    });

    var thumb_ndx = $j('#eventTable tr th').filter(function() {
      return $j(this).text().trim() == 'Thumbnail';
    }).index();
    table.find("tr td:nth-child(" + (thumb_ndx+1) + ")").addClass('colThumbnail');
  });

  table.bootstrapTable('resetSearch');
  // The table is initially given a hidden style, so now that we are done rendering, show it
  table.show();
}

$j(document).ready(function() {
  initPage();
});
