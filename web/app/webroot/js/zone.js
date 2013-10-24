$(document).ready(function()
{
"use strict";
  var canvas = document.getElementById("c1"),
    oCanvas = $('#c1').offset(),
    ctx = canvas.getContext("2d"),
    zone = document.getElementById("imgZone"),
    vertices = [], coords = $("#ZoneCoords").val().split(" ");

  window.onload = function() {
    drawMonitor();
  }

  function drawMonitor() {
    canvas.width = zone.width;
    canvas.height = zone.height;
    ctx.drawImage(zone, 0, 0);

    if (coords) {
      drawZone(coords);
    }
  }

  function drawZone(coords) {
    var length = coords.length;
    for (var i = 0; i < length; i++) {
      var coord = coords[i].split(",");
      var left = coord[0];
      var top = coord[1];
      vertices.push([left, top]);
      drawVertex(vertices[vertices.length - 1]); // Draw the most recent click
    }
    finishZone();
  }

  function finishZone() {
    ctx.lineTo(vertices[0][0], vertices[0][1]);
    ctx.fillStyle = "rgba(158, 217, 50, 0.5)";
    ctx.fill();
    ctx.stroke();
    vertices = [];
  }

  function drawVertex(vertex) {
    if (vertices.length > 1) {
      ctx.lineTo(vertex[0], vertex[1]);
    } else {
      ctx.moveTo(vertex[0], vertex[1]);
    }
    ctx.strokeStyle = "blue";
    ctx.stroke();
  }

  $(canvas).click(function(e){
    vertices.push([e.pageX - oCanvas.left, e.pageY - oCanvas.top]); // Add the most recent click to the vertices array
    drawVertex(vertices[vertices.length - 1]); // Draw the most recent click
  });

  $("#done").click(function() {
    finishZone();
  });

  $("#reset").click(function() {
    vertices = [];
    canvas.width = canvas.width;
    drawMonitor();
  });
});
