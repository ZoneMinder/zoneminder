// initial setup
var xmlns = "http://www.w3.org/2000/svg";

var cameras = new Array();
var floorplans = {};
const urlParams = new URLSearchParams(window.location.search);

if (urlParams.has('floorplanid')) {
currentFloorplanID = urlParams.get('floorplanid');
}
init();

async function init() {
  response = await fetch("api/monitors.json");
  responseJson = await response.json();
  monitors = responseJson.monitors;

  response = await fetch("api/floorplans.json");
  responseJson = await response.json();
  floorplansobject = responseJson.Floorplans;
  for (flpln of floorplansobject) { // convert the API response into more sane object
    floorplans[flpln.Floorplan.id] = flpln.Floorplan.url
  }

  document.getElementById('floorView').style.backgroundImage = "url('" + floorplans[currentFloorplanID] + "')";  //set map background

  for (const cameranum in monitors) {
    var camera = monitors[cameranum];
    if (camera.Monitor.FloorplanID == currentFloorplanID) {
      newCamera = drawCamera(camera.Monitor.FloorplanX, camera.Monitor.FloorplanY, camera.Monitor.FloorplanPoint);
      newCamera.Id = camera.Monitor.Id;
      document.getElementById("floorView").appendChild(newCamera);
      cameras.push(newCamera);
    }
  }


//loop through the known cameras checking state




//<polygon points=​"5,5 5,10 10,5" style=​"fill:​yellow;​stroke:​yellow;​stroke-width:​1;​fill-rule:​nonzero;​">​</polygon>​
//$0.appendChild(svg);

updateCameras();

setInterval(updateCameras, 5000);
}

// Run every 5 seconds
async function updateCameras() {
  for (cameraNum in cameras) {
    camera = cameras[cameraNum];
    cameraStateResponse = await fetch("api/monitors/alarm/id:" + camera.Id + "/command:status.json");
    cameraState = await cameraStateResponse.json();
    if (cameraState.status == "false") {
      camera.polygon.setAttribute("style", "fill:black;");
    } else if (cameraState.status == 0) {
      camera.polygon.setAttribute("style", "fill:green;");
    } else if (cameraState.status == 2) {
      camera.polygon.setAttribute("style", "fill:red;");
    } else if (cameraState.status == 3) {
      camera.polygon.setAttribute("style", "fill:orange;");
    }
  }
}

function drawCamera(X, Y, Point) {
    cameraDiv = document.createElement("div");
    cameraDiv.classList.add("camera");
    cameraDiv.style.left = X.toString() + "px";
    cameraDiv.style.top = Y.toString() + "px";
    cameraDiv.style.rotate = Point.toString() + "deg";
    cameraDiv.svg = document.createElementNS(xmlns, "svg");
    cameraDiv.polygon = document.createElementNS(xmlns, "polygon");
    cameraDiv.polygon.setAttribute("points", "33,20 60,10 60,30")
    cameraDiv.polygon.setAttribute("style","fill:yellow;stroke:yellow;stroke-width:1;fill-rule:nonzero;")
    cameraDiv.svg.appendChild(cameraDiv.polygon);
    cameraDiv.appendChild(cameraDiv.svg);

    return cameraDiv;
}
