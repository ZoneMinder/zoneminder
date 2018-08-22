
var probeReq = new Request.JSON( { url:thisUrl, method: 'get', timeout: AJAX_TIMEOUT, link: 'cancel', onSuccess: getProbeResponse } );

function probe( url_e ) {
  probeReq.send( "request=add_monitors&action=probe&url="+url_e.value );
}

var ProbeResults;

function getProbeResponse( respObj, respText ) {
  if ( checkStreamForErrors( "getProbeResponse", respObj ) ) {
    console.log(respText);
    return;
  }

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

    var results_div = $j('#results')[0];
    if ( ! results_div ) {
      console.log("No results div found.");
      return;
    }
    results_div.innerHTML = '';
    var html = '';
    
    for( i in Streams ) {
      var stream = Streams[i];
      if ( stream.url ) {
        html += '<p>'+stream.Monitor.Name + ' at ' + stream.url;
        if ( stream.Monitor.Id ) {
          html += ' is already entered into the system by Monitor ' + stream.Monitor.Id + ' ' + stream.Monitor.Name + '<br/>';
          html += '<input type="button" value="Edit" onclick="addMonitor(\''+stream.url+'\');"/>';
        } else {
          html += '<input type="button" value="Add" onclick="addMonitor(\''+stream.url+'\');"/>';
        }
        html += '</p>';
        ProbeResults[stream.url] = stream;
      } else {
        //console.log(stream);
      }
    } // end for eah Stream
      
    results_div.innerHTML = html;
}

function addMonitor(url) {
  if ( ! ProbeResults[url] ) {
    alert("Monitor for url " + url + " not found in probe results." );
    return;
  }
  var Stream = ProbeResults[url];
  var Monitor = Stream.Monitor;

  var mid = Monitor.Id ? Monitor.Id : '';
  popup_url = '?view=monitor&mid='+mid+'&newMonitor[Path]='+url;
  keys = Object.keys( Monitor );
  for ( i in Monitor ) {
    if ( ! Monitor[i] )
      continue;
    if ( Monitor[i] == 'null' )
      Monitor[i]='';
    popup_url += '&newMonitor['+i+']='+Monitor[i];
  }
  createPopup( popup_url, 'zmMonitor'+mid, 'monitor' );
}
  
function import_csv( form ) {
  var formData = new FormData( form );
  console.log(formData);
  //formData.append('file', $('#file')[0].files[0]);

  $j.ajax({
    url : thisUrl+"?request=add_monitors&action=import",
         type : 'POST',
         data : formData,
         processData: false,  // tell jQuery not to process the data
         contentType: false,  // tell jQuery not to set contentType
         success : function(data) {
            var json = JSON.parse(data);
            parseStreams( json.Streams );
         }
  });
}
function initPage() {
  url = $j('#Url')[0];
  if ( url.value ) {
    probe(url);
  }
}
window.addEvent( 'domready', initPage );
