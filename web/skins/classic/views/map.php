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
    <div id="map-template"></div>
    <script src="js/Map.js"></script>
<?php
echo output_script_if_exists(array('js/leaflet/leaflet.js'), false);
echo output_link_if_exists(array('js/leaflet/leaflet.css'), false);
xhtmlFooter()
?>
