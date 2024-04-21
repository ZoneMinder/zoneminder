<?php     
//        
// ZoneMinder map view
// Copyright (C) 2022 ZoneMinder Inc
//      
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//        
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//
xhtmlHeaders(__FILE__, translate('Console'));
//<!--  leaflet imports -->
echo output_script_if_exists(array('js/leaflet/leaflet.js'), false);
echo output_link_if_exists(array('js/leaflet/leaflet.css'), false);
//<!--  leaflet draw imports -->
echo output_script_if_exists(array('js/leaflet/draw/src/Leaflet.draw.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/Leaflet.Draw.Event.js'), false);
echo output_link_if_exists(array('js/leaflet/draw/src/leaflet.draw.css'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/Toolbar.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/Tooltip.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/ext/GeometryUtil.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/ext/LatLngUtil.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/ext/LineUtil.Intersect.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/ext/Polygon.Intersect.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/ext/Polyline.Intersect.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/ext/TouchEvents.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/draw/DrawToolbar.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/draw/handler/Draw.Feature.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/draw/handler/Draw.SimpleShape.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/draw/handler/Draw.Polyline.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/draw/handler/Draw.Marker.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/draw/handler/Draw.Circle.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/draw/handler/Draw.CircleMarker.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/draw/handler/Draw.Polygon.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/draw/handler/Draw.Rectangle.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/edit/EditToolbar.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/edit/handler/EditToolbar.Edit.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/edit/handler/EditToolbar.Delete.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/Control.Draw.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/edit/handler/Edit.Poly.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/edit/handler/Edit.SimpleShape.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/edit/handler/Edit.Rectangle.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/edit/handler/Edit.Marker.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/edit/handler/Edit.CircleMarker.js'), false);
echo output_script_if_exists(array('js/leaflet/draw/src/edit/handler/Edit.Circle.js'), false);
//<!-- end leaflet imports -->

getBodyTopHTML();
$navbar = getNavBarHTML();
echo $navbar ?>
  <div id="content">
    <div id="statistics">
    <div class="progress">
            <div id="progress-connected-title" class="progress-bar" role="progressbar" style="width: 0%; background: #15ad10 !important; font-weight: bold; " aria-valuenow="15" aria-valuemin="0" aria-valuemax="100" title="Connected"></div>
            <div id="progress-disconnected-title" class="progress-bar bg-success" role="progressbar" style="width: 0%; background: #dd4042 !important; font-weight: bold; " aria-valuenow="30" aria-valuemin="0" aria-valuemax="100" title="Disconnected"></div>
            <div id="progress-error-title" class="progress-bar bg-info" role="progressbar" style="width: 0%; background: #797979 !important; font-weight: bold; " aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" title="No Connection"></div>
        </div>
        <div class="progress">
            <div id="progress-connected" class="progress-bar" role="progressbar" style="width: 0%; background: #15ad10 !important; font-weight: bold; font-size: medium;" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100" title="Connected"></div>
            <div id="progress-disconnected" class="progress-bar bg-success" role="progressbar" style="width: 0%; background: #dd4042 !important; font-weight: bold; font-size: medium;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100" title="Disconnected"></div>
            <div id="progress-error" class="progress-bar bg-info" role="progressbar" style="width: 0%; background: #797979 !important; font-weight: bold; font-size: medium;" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" title="No Connection"></div>
        </div>
    </div>
    <div id="leaflet-map"></div>
    <input type="button" value="Load Cameras Data" id="loadBtn"/>
    <!-- import floorplan.css  -->
    <link rel="stylesheet" href="css/floorplan.css">
    <script src="js/floorplan.js"></script>
<?php
xhtmlFooter()
?>
