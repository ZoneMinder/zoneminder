<?php
//
// ZoneMinder web reports view file
// Copyright (C) 2022 Isaac Connor
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

#if (!canView('Reports')) {
  #$view = 'error';
  #return;
#} else if (!ZM_FEATURES_SNAPSHOTS) {
  #$view = 'console';
  #return;
#}

require_once('includes/Event.php');
require_once('includes/Filter.php');
#require_once('includes/Report.php');

xhtmlHeaders(__FILE__, translate('Reports'));
getBodyTopHTML();
   echo getNavBarHTML();
?>
  <div id="page" class="container-fluid p-3">
    <!-- Toolbar button placement and styling handled by bootstrap-tables -->
    <div id="toolbar">
      <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
      <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
      <!--<button id="filterBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Filter') ?>"><i class="fa fa-filter"></i></button>-->
      <!--<button id="exportBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Export') ?>" disabled><i class="fa fa-external-link"></i></button>-->
      <button id="deleteBtn" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Delete') ?>" disabled><i class="fa fa-trash"></i></button>
    </div>
    <canvas id="bar-chart" width=300" height="150"></canvas>

  <script src="/skins/classic/js/Chart.min.js"></script>
<script>
var events = Array();
<?php
require_once('includes/Filter.php');
$filter = new ZM\Filter($_REQUEST['filter_id']);
if ($user['MonitorIds']) {
  $filter = $filter->addTerm(array('cnj'=>'and', 'attr'=>'MonitorId', 'op'=>'IN', 'val'=>$user['MonitorIds']));
}
$events = $filter->Events();
foreach ($events as $event) {
  echo 'events[] = '.$event->to_json().PHP_EOL;
}
?>
var start = (new Date()).getDate();
console.log(start);
for (let i=0; i < 24; i++) {
  let current = start;
  current.setTime(i*60 * 60 * 1000);
  console.log(current);

}

new Chart(document.getElementById("bar-chart"), {
    type: 'line',
    data: {
      labels: ["North America", "Latin America", "Europe", "Asia", "Africa"],
      datasets: [
        {
          label: "Sleeping",
          backgroundColor: ["red", "blue","yellow","green","pink"],
          data: [7,4,6,9,3]
        }
      ]
    },
    options: {
      legend: { display: false },
      title: {
        display: true,
        text: 'Number of Developers in Every Continent'
      },

      scales: {
            yAxes: [{
                ticks: {
                    beginAtZero:true
                }
            }]
        }

    }
});
</script>
  </div>
<?php xhtmlFooter() ?>
