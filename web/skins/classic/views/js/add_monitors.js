
var probeReq = new Request.JSON( { url:thisUrl, method: 'get', timeout: AJAX_TIMEOUT, link: 'cancel', onSuccess: getProbeResponse } );

function probe( url_e ) {
  probeReq.send( "request=add_monitors&action=probe&url="+url_e.value );
}

var ProbeResults;

function getProbeResponse( respObj, respText ) {
  if ( checkStreamForErrors( "getProbeResponse", respObj ) )
    return;
//alert(respText);

  if ( respObj.Streams ) {
    parseStreams( respObj.Streams );
  } else {
    alert("No Streams");
  }
} // end function getProbeResponse

function parseStreams( Streams ) {
    ProbeResults = Array();

    var results_div = $j('#url_results')[0];
    if ( ! results_div ) {
      console.log("No results div found.");
      return;
    }
    results_div.innerHTML = '';
    var html = '';
    
    for( i in Streams ) {
      var stream = Streams[i];
      if ( stream.url ) {
        html += '<p>'+stream.url;
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

  popup_url = '?view=monitor&newMonitor[Path]='+url;
  keys = Object.keys( Monitor );
  for ( i in Monitor ) {
    if ( ! Monitor[i] )
      continue;
    if ( Monitor[i] == 'null' )
      Monitor[i]='';
    popup_url += '&newMonitor['+i+']='+Monitor[i];
  }
  createPopup( popup_url, 'zmMonitor0', 'monitor' );
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
