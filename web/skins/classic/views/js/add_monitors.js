var ProbeResults = {};
var table = null;

function ajaxRequest(params) {
  return probe(params);
}

function probe(params) {
  if (params.data && params.data.filter) {
    params.data.advsearch = params.data.filter;
    delete params.data.filter;
  }
  $j('#toolbar input, #toolbar select').each(function(index) {
    const el = $j(this);
    const name = el.attr('name');
    if (name) {
      params.data[name] = el.val();
      setCookie('addMonitors'+name, el.val(), 3600);
    }
  });
  if (auth_relay) params.data['auth_relay'] = auth_relay;

  $j.ajax({
    url: thisUrl + '?view=request&request=add_monitors&action=probe',
    data: params.data,
    timeout: 0,
    success: function(data) {
      if (data.result == 'Error') {
        alert(data.message);
        return;
      }

      if (data.Streams && data.Streams.length) {
        for ( i in data.Streams ) {
          const stream = data.Streams[i];
          if (stream.Monitor) {
            stream.buttons = '<input type="button" value="Edit" data-on-click-this="addMonitor" data-url="'+stream.url+'"/>';
          } else if (user.Monitors == 'Create') {
            stream.buttons = '<input type="button" value="Add" data-on-click-this="addMonitor" data-url="'+stream.url+'"/>';
          }
          if (ZM_WEB_LIST_THUMBS && stream.camera.mjpegstream) {
            console.log("Setting thumbnail stream to " + stream.camera.mjpegstream);
            //stream.Thumbnail = '<img src="'+stream.camera.mjpegstream+'"/>';
            stream.Thumbnail = '<img src="?view=image&proxy='+stream.camera.mjpegstream+'" width="'+ZM_WEB_LIST_THUMB_WIDTH+'"/>';
          } else {
            console.log(stream.camera);
          }
          ProbeResults[stream.url] = stream;
        } // end for each Stream

        const rows = data.Streams;
        // rearrange the result into what bootstrap-table expects
        params.success({total: rows.length, totalNotFiltered: rows.length, rows: rows});
      }
    },
    error: function(jqXHR) {
      console.log("error", jqXHR);
      //logAjaxFail(jqXHR);
      //$j('#eventTable').bootstrapTable('refresh');
    }
  });
}

function onvif_probe() {
}

function getProbeResponse( respObj, respText ) {
  if ( respObj.Streams && respObj.Streams.length ) {
    parseStreams( respObj.Streams );
  } else {
    var results_div = $j('#results')[0];
    if ( ! results_div ) {
      console.log("No results div found.");
      return;
    }
    results_div.innerHTML = 'No streams found.';
    //console.log("No streams: " + respText);
  }
} // end function getProbeResponse

function parseStreams( Streams ) {
  ProbeResults = Array();

  const results_div = $j('#results')[0];
  if ( ! results_div ) {
    console.log("No results div found.");
    return;
  }
  results_div.innerHTML = '';
  var html = '';

  for ( i in Streams ) {
    const stream = Streams[i];
    if ( stream.url ) {
      html += '<p>'+stream.description;
      if ( stream.Monitor ) {
        html += ' is already entered into the system by Monitor ' + stream.Monitor.Id + ' ' + stream.Monitor.Name + '<br/>';
        html += '<input type="button" value="Edit" data-on-click-this="addMonitor" data-url="'+stream.url+'"/>';
      } else {
        html += '<input type="button" value="Add" data-on-click-this="addMonitor" data-url="'+stream.url+'"/>';
      }
      html += '</p>';
      ProbeResults[stream.url] = stream;
    } else {
      //console.log(stream);
    }
  } // end for each Stream

  results_div.innerHTML = html;
}

function addMonitor(btn) {
  url = btn.getAttribute('data-url');
  if (!url) {
    alert('No url in button');
    return;
  }
  if (!ProbeResults[url]) {
    alert("Monitor for url " + url + " not found in probe results." );
    return;
  }
  const Stream = ProbeResults[url];
  if (Stream.Monitor) {
    const Monitor = Stream.Monitor;
    urlString = '?view=monitor&mid='+Monitor.Id;
  } else {
    const Monitor = Stream.camera.monitor;
    urlString = '?view=monitor&newMonitor[Path]='+url;
    keys = Object.keys( Monitor );
    for (i in Monitor) {
      if (!Monitor[i]) continue;
      if (Monitor[i] == 'null') {
        Monitor[i]='';
      }
      urlString += '&newMonitor['+i+']='+Monitor[i];
    }
  }
  window.location.assign(urlString);
}

function import_csv() {
  const form = $j('#contentForm');
  var formData = new FormData( form );
  console.log(formData);

  $j.ajax({
    url: thisUrl+"?request=add_monitors&action=import",
    type: 'POST',
    data: formData,
    processData: false, // tell jQuery not to process the data
    contentType: false, // tell jQuery not to set contentType
    done: function(data) {
      const json = JSON.parse(data);
      parseStreams(json.Streams);
    }
  });
}

function importMonitors() {
}

function initPage() {
  table = $j('#AddMonitorsTable');
  // Init the bootstrap-table
  table.bootstrapTable({icons: icons,
    onPostBody: function() {
      dataOnClickThis();
    }
  });
  table.bootstrapTable('resetSearch');
  // The table is initially given a hidden style, so now that we are done rendering, show it
  table.show();
  $j('#url').on('input', probe);

  // Manage the EDIT button
  document.getElementById('importBtn').addEventListener('click', function onEditClick(evt) {
    evt.preventDefault();
    $j.ajax({
      method: 'GET',
      timeout: 0,
      url: thisUrl + '?request=modal&modal=add_monitors_import',
      success: function(data) {
        insertModalHtml('ImportMonitorsModal', data.html);
        $j('#ImportMonitorsModal').modal('show');
        // Manage the Save button
        $j('#importSubmitBtn').click(function(evt) {
          evt.preventDefault();
          import_csv();
        });
      },
      error: logAjaxFail
    });
  });
  $j('#toolbar input').on('change', function() {
    table.bootstrapTable('resetSearch');
    table.bootstrapTable('refresh');
  });
  $j('#toolbar select').on('change', function() {
    table.bootstrapTable('resetSearch');
    table.bootstrapTable('refresh');
  });
}

$j(document).ready(initPage);
