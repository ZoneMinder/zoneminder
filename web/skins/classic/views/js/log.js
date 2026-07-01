"use strict";
const table = $j('#logTable');
var ajax = null;
var allowRequest; // Allow unscheduled AJAX requests
var requestMissed; // AJAX request was skipped.
var btnAutoRefresh = null;
var autoRefresh = null;
var optionsBootstrapTable = null;
var deleteProgressBar = null;

/*
This is the format of the json object sent by bootstrap-table

var params =
{
"type":"get",
"data":
  {
  "search":"some search text",
  "sort":"DateTime",
  "order":"asc",
  "offset":0,
  "limit":25
  "filter":
    {
    "message":"some advanced search text"
    "level":"some more advanced search text"
    }
  },
"cache":true,
"contentType":"application/json",
"dataType":"json"
};
*/

// Called by bootstrap-table to retrieve zm log data
function ajaxRequest(params) {
  if (document.visibilityState == 'hidden' || (allowRequest !== true && autoRefresh === false)) return;
  if (getIdSelections().length && allowRequest === false) {
    // We won't automatically refresh to avoid disturbing the user who has selected rows.
    console.debug("The user selected rows in the table, and the AJAX request was rejected.");
    requestMissed = true;
    return;
  }
  if (ajax && (ajax.readyState !== 4 && ajax.readyState !== 0)) {
    // AJAX request in progress
    if (allowRequest === true) {
      // User decided to change the filter or update the table.
      ajax.abort("plannedAbort");
      table.bootstrapTable('showLoading'); // This needs to be displayed, for example, when navigating to another page while the previous request is still in progress.
    } else {
      // An autoRefresh request has been received.
      console.debug("We're skipping the current AJAX table update request because the previous one hasn't completed.", ajax.readyState);
      //requestMissed = true; // We won't take this into account for now, since, for example, if a request takes more than approximately 50% of WEB_REFRESH_LOGS to complete, then there's a high probability that a new request will arrive while the previous request is still pending and will be skipped, and as soon as the previous request completes, the next unscheduled request will begin executing without a pause.
      return;
    }
  }

  if ($j('#filterServerId').val()) {
    params.data.ServerId = $j('#filterServerId').val();
  }
  // #filterLevel is a multi-select; val() returns an array of chosen levels (or
  // null when nothing is selected, which the server treats as "All").
  const levels = $j('#filterLevel').val();
  if (levels && levels.length) {
    params.data.level = levels;
  }
  // #filterComponent is a multi-select; val() returns an array of chosen
  // components (or null when nothing is selected, treated as "All").
  const components = $j('#filterComponent').val();
  if (components && components.length) {
    params.data.Component = components;
  }
  if ($j('#filterStartDateTime').val()) {
    params.data.StartDateTime = $j('#filterStartDateTime').val();
  }
  if ($j('#filterEndDateTime').val()) {
    params.data.EndDateTime = $j('#filterEndDateTime').val();
  }

  const startTime = Date.now();
  updateHeaderRequestStatus("in process");
  allowRequest = false;
  requestMissed = false;

  ajax = $j.ajax({
    url: thisUrl + '?view=request&request=log&task=query',
    data: params.data,
    timeout: 600000,
    success: function(data) {
      updateHeaderRequestStatus(secsToTime((Date.now() - startTime)/1000, 1));
      table.bootstrapTable('hideLoading');
      if (!data.rows.length && data.total > 0) {
        // The requested page is out of range; reset to page 1.
        table.bootstrapTable('selectPage', 1);
        return;
      }
      // rearrange the result into what bootstrap-table expects
      params.success({
        total: data.total,
        totalNotFiltered: data.totalNotFiltered,
        rows: processRows(data.rows)
      });
      updateHeaderStats(data);
    },
    error: function(jqxhr) {
      if (jqxhr.statusText === "plannedAbort") {
        updateHeaderRequestStatus("stopped");
      } else {
        updateHeaderRequestStatus("error");
        zmAlert(translate["Reason"] + ": " + jqxhr.statusText + "~~" + translate["ErrorUpdatingLogTable"], translate["AJAXRequestError"]);
      }
      table.bootstrapTable('hideLoading');
      logAjaxFail(jqxhr);
    }
  });
}

function processRows(rows) {
  $j.each(rows, function(ndx, row) {
    try {
      row.Message = decodeURIComponent(row.Message.replace(/%(?![0-9A-Fa-f]{2})/g, '%25'))
          .replace(/</g, "&lt;").replace(/>/g, "&gt;") // Replace link tags
          .replace(/event (\d+)/g, "<a href=\"?view=event&eid=$1\">event $1</a>");
    } catch (e) {
      console.log("Error decoding ", row.Message, e);
      // ignore errors
    }
  });
  return rows;
}

function filterLog() {
  manageClearButtonAvailability(false);
  allowRequest = true;

  table.bootstrapTable('refresh');
  table.bootstrapTable('showLoading');
}

function updateHeaderStats(data) {
  var pageNum = table.bootstrapTable('getOptions').pageNumber;
  var pageSize = table.bootstrapTable('getOptions').pageSize;
  var startRow = (data.total > 0) ? (( (pageNum - 1 ) * pageSize ) + 1) : 0;
  var stopRow = (data.total > 0) ? Math.min(data.total, pageNum * pageSize) : 0;
  var newClass = (data.logstate == 'ok') ? 'text-success' : (data.logstate == 'alert' ? 'text-warning' : ((data.logstate == 'alarm' ? 'text-danger' : '')));

  $j('#logState').text(data.logstate);
  $j('#logState').removeClass('text-success');
  $j('#logState').removeClass('text-warning');
  $j('#logState').removeClass('text-danger');
  $j('#logState').addClass(newClass);

  $j('#totalLogs').text(Number(data.total).toLocaleString());
  $j('#availLogs').text(Number(data.totalNotFiltered).toLocaleString());
  $j('#lastUpdate').text(data.updated);
  $j('#displayLogs').text(Number(startRow).toLocaleString() + ' to ' + Number(stopRow).toLocaleString());
}

function manageClearLogsModalBtns() {
  document.getElementById('clearLogsConfirmBtn').addEventListener('click', function onClearLogsConfirmClick(evt) {
    evt.preventDefault();
    $j('#clearLogsConfirm').modal('hide');
    document.getElementById('clearLogsConfirmBtn').disabled = true;
    deleteLogs(getIdSelections());
  });
  $j('#clearLogsConfirm').on('hide.bs.modal', function onClearLogsConfirmHidden(evt) {
    const idRelatedTarget = $j(document.activeElement).attr('id');
    // When executing deleteLogs(), we always call a table update
    // after which manageClearButtonAvailability() is always executed, so there is no need to execute manageClearButtonAvailability() here
    if (idRelatedTarget != "clearLogsConfirmBtn") manageClearButtonAvailability();
  });
  document.getElementById('clearLogsCancelBtn').addEventListener('click', function onClearLogsCancelClick(evt) {
    evt.preventDefault();
    $j('#clearLogsConfirm').modal('hide');
  });
}

/*
* action = 'toggle' or 'off' or 'on' class
*/
function manageAutoRefreshBtn(action) {
  const classBtn = "slash";
  const icon = btnAutoRefresh.querySelector('i.fa');
  if (action == 'toggle') {
    $j(icon).toggleClass(classBtn);
  } else if (action == 'off') {
    $j(icon).addClass(classBtn);
  } else if (action == 'on') {
    $j(icon).removeClass(classBtn);
  }

  if (icon.classList.contains(classBtn)) {
    autoRefresh = false;
  } else {
    autoRefresh = true;
  }
}

function getIdSelections() {
  return $j.map(table.bootstrapTable('getSelections'), function(row) {
    return row.Id;
  });
}

function setDeleteProgressBarValue(val) {
  if (deleteProgressBar) deleteProgressBar.querySelector('.progress-fill').style.width = val+'%';
}

var log_idsLength;
function deleteLogs(log_ids, handlerAlert = null) {
  if (handlerAlert === null) {
    // Creating a progress bar.
    deleteProgressBar = document.createElement('div');
    deleteProgressBar.id = 'deleteProgress';
    deleteProgressBar.classList.add('progress-container');

    const fill = document.createElement('div');
    fill.classList.add('progress-fill');
    fill.style.width = '0%';
    deleteProgressBar.appendChild(fill);

    log_idsLength = log_ids.length;
    handlerAlert = zmAlert(translate["DeletingRowsFromTable"]);

    waitUntil(() => (document.querySelector('#' + handlerAlert + ' .modal-body')), 10000).then((result) => {
      // We're waiting for the modal information block to appear.
      const block = document.querySelector('#' + handlerAlert + ' .modal-body');
      if (block) {
        block.appendChild(deleteProgressBar);
      } else {
        console.warn("Modal information block not found.");
      }
      console.log(result);
    }).catch((error) => {
      console.error(error);
    });
  }

  const chunk = log_ids.splice(0, 100);

  console.log('Deleting ' + chunk.length + ' log entries. ' + log_ids.length + ' remaining.');
  $j.ajax({
    method: 'post',
    timeout: 0,
    url: thisUrl + '?request=log&task=delete',
    data: {'ids[]': chunk},
    success: function(data) {
      if (!log_ids.length) {
        allowRequest = true;
        setDeleteProgressBarValue(100);
        table.bootstrapTable('refresh');
        updateHeaderRequestStatus("in process");
        if (handlerAlert) {
          // A delay is required to allow the browser to render the progress bar at 100%.
          setTimeout(function() {
            closeZmAlert(handlerAlert);
          }, 500);
        }
      } else {
        setDeleteProgressBarValue((log_idsLength - log_ids.length) / log_idsLength*100);
        deleteLogs(log_ids, handlerAlert);
      }
    },
    error: function(jqxhr) {
      logAjaxFail(jqxhr);
      allowRequest = true;
      table.bootstrapTable('refresh');
      updateHeaderRequestStatus("error");
      if (handlerAlert) closeZmAlert(handlerAlert);
      zmAlert(translate["Reason"] + ": " + jqxhr.statusText + "~~" + translate["ErrorDeletingRowFromLogTable"], translate["AJAXRequestError"]);
    }
  });
}

function initPage() {
  var backBtn = $j('#backBtn');

  table.on('click', function(event) {
    if (event.target.classList.contains('sortable')) {
      manageClearButtonAvailability(false);
      allowRequest = true;
      table.bootstrapTable('showLoading');
    }
  });

  table.one('pre-body.bs.table', function(e, arg1, arg2, arg3) {
    btnAutoRefresh = document.querySelector('button[name="autoRefresh"]');
    if (btnAutoRefresh) {
      btnAutoRefresh.addEventListener("click", function onBtnAutoRefreshClick(evt) {
        manageAutoRefreshBtn('toggle');
      });
    }
  });

  table.on('page-change.bs.table', function() {
    paginationInfoToLocaleString();
    manageClearButtonAvailability(false);
    allowRequest = true;
    table.bootstrapTable('showLoading');
  });

  allowRequest = false;
  requestMissed = false;

  // Assign inf, err, fat, dbg color classes to the rows in the table
  table.on('post-body.bs.table', function(data) {
    var lvl_ndx = $j('#logTable tr th').filter(function() {
      return $j(this).attr('data-field') == "Code";
    }).index();

    $j('#logTable tr').each(function(ndx, row) {
      var row = $j(row);
      var level = row.find('td').eq(lvl_ndx).text().trim();

      if (( level == 'FAT' ) || ( level == 'PNC' )) {
        row.addClass('log-fat');
      } else if ( level == 'ERR' ) {
        row.addClass('log-err');
      } else if ( level == 'WAR' ) {
        row.addClass('log-war');
      } else if ( level == 'DBG' ) {
        row.addClass('log-dbg');
      }
    });
  });

  table.one('post-header.bs.table', function(data) {
    // We'll replace the "Refresh" button to unbind all bootstrapTable listeners, as bootstrapTable doesn't have an event triggered when the button is clicked.
    // We need to perform additional actions before sending the request.
    // Manage the REFRESH table Button
    let btnRefresh = $j('button[name="refresh"]');
    if (btnRefresh.length > 0) btnRefresh.replaceWith(btnRefresh.clone());
    btnRefresh = document.querySelector('button[name="refresh"]');

    if (btnRefresh) {
      btnRefresh.addEventListener("click", function onRefreshTableClick(evt) {
        manageClearButtonAvailability(false);
        allowRequest = true;
        table.bootstrapTable('refresh');
        table.bootstrapTable('showLoading');
        updateHeaderRequestStatus("in process");
      });
    }
  });

  // Init the bootstrap-table with custom icons
  table.bootstrapTable({icons: icons});

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

  // Manage the CLEAR LOGS button
  const clearLogsBtn = document.getElementById('clearLogsBtn');
  if (clearLogsBtn) {
    clearLogsBtn.addEventListener('click', function onClearLogsClick(evt) {
      manageClearButtonAvailability(false);
      evt.preventDefault();
      if (evt.ctrlKey) {
        // Bypass confirmation
        deleteLogs(getIdSelections());
      } else {
        if (!document.getElementById('clearLogsConfirm')) {
          $j.getJSON(thisUrl + '?request=modal&modal=clearlogsconfirm')
              .done(function(data) {
                insertModalHtml('clearLogsConfirm', data.html);
                manageClearLogsModalBtns();
                $j('#clearLogsConfirm').modal('show');
              })
              .fail(function(jqXHR) {
                manageClearButtonAvailability();
                console.log('error getting clearlogsconfirm', jqXHR);
                logAjaxFail(jqXHR);
              });
        } else {
          document.getElementById('clearLogsConfirmBtn').disabled = false;
          $j('#clearLogsConfirm').modal('show');
        }
      }
    });
  }

  // Enable or disable clear button based on selection
  table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', manageClearButtonAvailability);

  table.on('load-success.bs.table', function() {
    paginationInfoToLocaleString();
    manageClearButtonAvailability();
  });

  $j('#filterStartDateTime, #filterEndDateTime')
      .datetimepicker({timeFormat: "HH:mm:ss", dateFormat: "yy-mm-dd", maxDate: 0, constrainInput: false, onClose: filterLog});
  $j('#filterServerId')
      .on('change', filterLog);
}

function paginationInfoToLocaleString() {
  const block = $j('#logsTable').find('.pagination-info');
  if (block.length) {
    block.html(stringToLocaleString(block.html()));
  }
}

function manageClearButtonAvailability(enable = null) {
  const selections = table.bootstrapTable('getSelections');
  const clearLogsBtn = document.getElementById('clearLogsBtn');
  if (clearLogsBtn) {
    if (enable === false || !selections.length) {
      clearLogsBtn.disabled = true;
    } else if (enable === true || selections.length) {
      clearLogsBtn.disabled = false;
    }
  }

  if (selections.length) {
    allowRequest = false; // We'll prevent table updates from interfering with the user who has selected rows.
    updateHeaderRequestStatus("stopped");
    if (ajax && (ajax.readyState !== 4 && ajax.readyState !== 0)) {
      ajax.abort("plannedAbort"); // There may already be a previous request that hasn't completed.
    }
  } else {
    if (readHeaderRequestStatus() === "stopped") updateHeaderRequestStatus("awaiting");
    if (requestMissed === true) {
      // A scheduled AJAX update was missed. We'll execute it out of order.
      allowRequest = true;
      console.debug("Unscheduled AJAX request.");
      table.bootstrapTable('refresh');
      table.bootstrapTable('showLoading');
      updateHeaderRequestStatus("in process");
    }
  }
}

function readHeaderRequestStatus() {
  return $j('#requestStatus').text().replace(/(\[|\])/g, '').trim();
}

function updateHeaderRequestStatus(text) {
  $j('#requestStatus').text(" ["+text+"]");
}

$j(document).ready(function() {
  initPage();
});
