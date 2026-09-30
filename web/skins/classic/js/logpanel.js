"use strict";
//
// Drives every .logPanel that skins/classic/includes/logpanel.php put in the
// page.  Each panel keeps its own request state in this closure, so more than
// one can live on a page without sharing an in-flight request or a selection.
//

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

function initLogPanel(panel) {
  const $panel = $j(panel);
  const table = $panel.find('table.logPanel-logs');
  const clearBtn = $panel.find('.logPanel-clear');
  // A panel locked to a set of components queries only those and never writes
  // the user's Log view selection back to the session.
  const lockedComponents = panel.dataset.components ? JSON.parse(panel.dataset.components) : null;
  // The panel's own strings; a view's views/js/<view>.js.php is not loaded here.
  const i18n = panel.dataset.i18n ? JSON.parse(panel.dataset.i18n) : {};

  let ajax = null;
  let allowRequest = false; // Allow unscheduled AJAX requests
  let requestMissed = false; // AJAX request was skipped.
  let btnAutoRefresh = null;
  let autoRefresh = null;
  let deleteProgressBar = null;
  let idsLength = 0;

  function readRequestStatus() {
    return $panel.find('.logPanel-status').text().replace(/(\[|\])/g, '').trim();
  }

  function updateRequestStatus(text) {
    $panel.find('.logPanel-status').text(' ['+text+']');
  }

  function getIdSelections() {
    return $j.map(table.bootstrapTable('getSelections'), function(row) {
      return row.Id;
    });
  }

  function paginationInfoToLocaleString() {
    const block = $panel.find('.pagination-info');
    if (block.length) {
      block.html(stringToLocaleString(block.html()));
    }
  }

  function updateHeaderStats(data) {
    const pageNum = table.bootstrapTable('getOptions').pageNumber;
    const pageSize = table.bootstrapTable('getOptions').pageSize;
    const startRow = (data.total > 0) ? (((pageNum - 1) * pageSize) + 1) : 0;
    const stopRow = (data.total > 0) ? Math.min(data.total, pageNum * pageSize) : 0;
    const newClass = (data.logstate == 'ok') ? 'text-success' : (data.logstate == 'alert' ? 'text-warning' : ((data.logstate == 'alarm' ? 'text-danger' : '')));
    const state = $panel.find('.logPanel-state');

    state.text(data.logstate);
    state.removeClass('text-success text-warning text-danger');
    state.addClass(newClass);

    $panel.find('.logPanel-total').text(Number(data.total).toLocaleString());
    $panel.find('.logPanel-available').text(Number(data.totalNotFiltered).toLocaleString());
    $panel.find('.logPanel-updated').text(data.updated);
    $panel.find('.logPanel-displaying').text(Number(startRow).toLocaleString() + ' to ' + Number(stopRow).toLocaleString());
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

  // Called by bootstrap-table to retrieve zm log data
  function ajaxRequest(params) {
    if (deferTableRequestWhileHidden(table)) return;
    if (allowRequest !== true && autoRefresh === false) return;
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
        return;
      }
    }

    const serverId = $panel.find('.logPanel-server').val();
    if (serverId) {
      params.data.ServerId = serverId;
    }
    // The level and component controls are multi-selects; val() returns an array
    // of chosen values (or null when nothing is selected, which the server
    // treats as "All").
    const levels = $panel.find('.logPanel-level').val();
    if (levels && levels.length) {
      params.data.level = levels;
    }
    if (lockedComponents) {
      params.data.Component = lockedComponents;
      params.data.embedded = 1;
    } else {
      const components = $panel.find('.logPanel-component').val();
      if (components && components.length) {
        params.data.Component = components;
      }
    }
    if ($panel.find('.logPanel-start').val()) {
      params.data.StartDateTime = $panel.find('.logPanel-start').val();
    }
    if ($panel.find('.logPanel-end').val()) {
      params.data.EndDateTime = $panel.find('.logPanel-end').val();
    }

    const startTime = Date.now();
    updateRequestStatus("in process");
    allowRequest = false;
    requestMissed = false;

    ajax = $j.ajax({
      url: thisUrl + '?view=request&request=log&task=query',
      data: params.data,
      timeout: 600000,
      success: function(data) {
        updateRequestStatus(secsToTime((Date.now() - startTime)/1000, 1));
        updateHeaderStats(data);
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
      },
      error: function(jqxhr) {
        if (jqxhr.statusText === "plannedAbort") {
          updateRequestStatus("stopped");
        } else {
          updateRequestStatus("error");
          zmAlert(i18n["Reason"] + ": " + jqxhr.statusText + "~~" + i18n["ErrorUpdatingLogTable"], i18n["AJAXRequestError"]);
        }
        table.bootstrapTable('hideLoading');
        logAjaxFail(jqxhr);
      }
    });
  }

  function manageClearButtonAvailability(enable = null) {
    const selections = table.bootstrapTable('getSelections');
    if (clearBtn.length) {
      if (enable === false || !selections.length) {
        clearBtn.prop('disabled', true);
      } else if (enable === true || selections.length) {
        clearBtn.prop('disabled', false);
      }
    }

    if (selections.length) {
      allowRequest = false; // We'll prevent table updates from interfering with the user who has selected rows.
      updateRequestStatus("stopped");
      if (ajax && (ajax.readyState !== 4 && ajax.readyState !== 0)) {
        ajax.abort("plannedAbort"); // There may already be a previous request that hasn't completed.
      }
    } else {
      if (readRequestStatus() === "stopped") updateRequestStatus("awaiting");
      if (requestMissed === true) {
        // A scheduled AJAX update was missed. We'll execute it out of order.
        allowRequest = true;
        console.debug("Unscheduled AJAX request.");
        table.bootstrapTable('refresh');
        table.bootstrapTable('showLoading');
        updateRequestStatus("in process");
      }
    }
  }

  function filterLog() {
    manageClearButtonAvailability(false);
    allowRequest = true;

    table.bootstrapTable('refresh');
    table.bootstrapTable('showLoading');
  }

  function setDeleteProgressBarValue(val) {
    if (deleteProgressBar) deleteProgressBar.querySelector('.progress-fill').style.width = val+'%';
  }

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

      idsLength = log_ids.length;
      handlerAlert = zmAlert(i18n["DeletingRowsFromTable"]);

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
          updateRequestStatus("in process");
          if (handlerAlert) {
            // A delay is required to allow the browser to render the progress bar at 100%.
            setTimeout(function() {
              closeZmAlert(handlerAlert);
            }, 500);
          }
        } else {
          setDeleteProgressBarValue((idsLength - log_ids.length) / idsLength*100);
          deleteLogs(log_ids, handlerAlert);
        }
      },
      error: function(jqxhr) {
        logAjaxFail(jqxhr);
        allowRequest = true;
        table.bootstrapTable('refresh');
        updateRequestStatus("error");
        if (handlerAlert) closeZmAlert(handlerAlert);
        zmAlert(i18n["Reason"] + ": " + jqxhr.statusText + "~~" + i18n["ErrorDeletingRowFromLogTable"], i18n["AJAXRequestError"]);
      }
    });
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

    autoRefresh = !icon.classList.contains(classBtn);
  }

  table.on('click', function(event) {
    if (event.target.classList.contains('sortable')) {
      manageClearButtonAvailability(false);
      allowRequest = true;
      table.bootstrapTable('showLoading');
    }
  });

  table.one('pre-body.bs.table', function(e, arg1, arg2, arg3) {
    btnAutoRefresh = panel.querySelector('button[name="autoRefresh"]');
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

  // Assign inf, err, fat, dbg color classes to the rows in the table
  table.on('post-body.bs.table', function(data) {
    const lvl_ndx = table.find('tr th').filter(function() {
      return $j(this).attr('data-field') == "Code";
    }).index();

    table.find('tr').each(function(ndx, row) {
      const $row = $j(row);
      const level = $row.find('td').eq(lvl_ndx).text().trim();

      if ((level == 'FAT') || (level == 'PNC')) {
        $row.addClass('log-fat');
      } else if (level == 'ERR') {
        $row.addClass('log-err');
      } else if (level == 'WAR') {
        $row.addClass('log-war');
      } else if (level == 'DBG') {
        $row.addClass('log-dbg');
      }
    });
  });

  table.one('post-header.bs.table', function(data) {
    // We'll replace the "Refresh" button to unbind all bootstrapTable listeners, as bootstrapTable doesn't have an event triggered when the button is clicked.
    // We need to perform additional actions before sending the request.
    // Manage the REFRESH table Button
    let btnRefresh = $panel.find('button[name="refresh"]');
    if (btnRefresh.length > 0) btnRefresh.replaceWith(btnRefresh.clone());
    btnRefresh = panel.querySelector('button[name="refresh"]');

    if (btnRefresh) {
      btnRefresh.addEventListener("click", function onRefreshTableClick(evt) {
        manageClearButtonAvailability(false);
        allowRequest = true;
        table.bootstrapTable('refresh');
        table.bootstrapTable('showLoading');
        updateRequestStatus("in process");
      });
    }
  });

  // Init the bootstrap-table with custom icons
  table.bootstrapTable({icons: icons, ajax: ajaxRequest});

  // Don't enable the back button if there is no previous zm page to go back to
  const backBtn = $panel.find('.logPanel-back');
  backBtn.prop('disabled', !document.referrer.length);
  backBtn.on('click', function onBackClick(evt) {
    evt.preventDefault();
    window.history.back();
  });

  $panel.find('.logPanel-reload').on('click', function onReloadClick(evt) {
    evt.preventDefault();
    window.location.reload(true);
  });

  clearBtn.on('click', function onClearLogsClick(evt) {
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

  // Enable or disable clear button based on selection
  table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', manageClearButtonAvailability);

  table.on('load-success.bs.table', function() {
    paginationInfoToLocaleString();
    manageClearButtonAvailability();
  });

  $panel.find('.logPanel-start, .logPanel-end')
      .datetimepicker({timeFormat: "HH:mm:ss", dateFormat: "yy-mm-dd", maxDate: 0, constrainInput: false, onClose: filterLog});
  $panel.find('.logPanel-filter').on('change', filterLog);
}

$j(document).ready(function() {
  $j('.logPanel').each(function() {
    initLogPanel(this);
  });
});
