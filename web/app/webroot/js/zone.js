$(document).ready(function()
{
"use strict";
  var canvas = document.getElementById("c1"),
    oCanvas = $('#c1').offset(),
    ctx = canvas.getContext("2d"),
    zone = document.getElementById("imgZone"),
    vertices = [];


  function drawMonitor() {
    canvas.width = zone.width;
    canvas.height = zone.height;
    ctx.drawImage(zone, 0, 0);
  }

  function drawZone(vertex) {
    if (vertices.length > 1) {
      ctx.lineTo(vertex[0], vertex[1]);
    } else {
      ctx.moveTo(vertex[0], vertex[1]);
    }
    ctx.strokeStyle = "blue";
    ctx.stroke();
  }

  drawMonitor();

  $(canvas).click(function(e){
    vertices.push([e.pageX - oCanvas.left, e.pageY - oCanvas.top]);
    drawZone(vertices[vertices.length - 1]);
  });

  $("#done").click(function() {
    ctx.lineTo(vertices[0][0], vertices[0][1]);
    ctx.fillStyle = "rgba(158, 217, 50, 0.5)";
    ctx.fill();
    ctx.stroke();
    vertices = [];
  });

  $("#reset").click(function() {
    vertices = [];
    canvas.width = canvas.width;
    drawMonitor();
  });
});
