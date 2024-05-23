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
#

require_once('includes/Event.php');
require_once('includes/Filter.php');
require_once('includes/Report.php');

$report_id = isset($_REQUEST['id']) ? validInt($_REQUEST['id']) : '';
$report = new ZM\Report($report_id);

xhtmlHeaders(__FILE__, translate('Reports'));
getBodyTopHTML();
   echo getNavBarHTML();
?>
  <div id="page">
    <div id="content">
      <div id="inner-content">
      <form name="report" id="reportForm" method="post" action="?view=report&id=<?php echo $report_id ?>">
      <!-- Toolbar button placement and styling handled by bootstrap-tables -->
      <div id="toolbar">
        <button id="backBtn" type="button" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
        <!--<button id="filterBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Filter') ?>"><i class="fa fa-filter"></i></button>-->
        <!--<button id="exportBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Export') ?>" disabled><i class="fa fa-external-link"></i></button>-->
        <button id="saveBtn" name="action" value="save" type="submit" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Save') ?>"><i class="fa fa-save"></i></button>
        <button id="deleteBtn" name="action" value="delete" type="submit" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Delete') ?>" disabled><i class="fa fa-trash"></i></button>
      </div>
        <table class="major table table-sm">
          <tbody>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Name') ?></th>
              <td><input type="text" name="Report[Name]" value="<?php echo $report->Name() ?>"/></td>
            </tr>
            <tr>
              <th class="text-right " scope="row"><?php echo translate('Filter') ?></th>
              <td>
<?php
  $FilterById = array();
  foreach (ZM\Filter::find() as $F) {
    $FiltersById[$F->Id()] = $F;
  }
  echo htmlSelect('Report[FilterId]', array(''=>translate('select')) + $FiltersById, $report->FilterId()) 
?></td>
            </tr>
<!--
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Starting') ?></th>
              <td><input type="text" name="Report[StartDateTime]" value="<?php echo $report->StartDateTime() ?>"/></td>
            </tr>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Ending') ?></th>
              <td><input type="text" name="Report[EndDateTime]" value="<?php echo $report->EndDateTime() ?>"/></td>
            </tr>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Interval') ?></th>
              <td><input type="text" name="Report[Interval]" value="<?php echo $report->Interval() ?>"/></td>
            </tr>
-->
          </tbody>
        </table>
      </form>
    <canvas id="bar-chart" width=300" height="150"></canvas>
    </div>
  </div>

  <script src="/skins/classic/js/Chart.min.js"></script>
<script nonce="<?php echo $cspNonce; ?>">
var events = Array();

<?php
require_once('includes/Filter.php');
if (!$report->FilterId()) return;

$filter = new ZM\Filter($report->FilterId());
if (count($user->unviewableMonitorIds())) {
  $filter = $filter->addTerm(array('cnj'=>'and', 'attr'=>'MonitorId', 'op'=>'IN', 'val'=>$user->viewableMonitorIds()));
}
$events = $filter->Events();
foreach ($events as $event) {
  echo 'events[events.length] = '.$event->to_json().PHP_EOL;
}
?>

time_labels = Array();
datasets = Array();
dataset_indexes = {}; // Associative array from a date String like July 20 to an index into the datasets.
for (i=0; i < 24; i++) {
  time_labels[time_labels.length] = `${i}:00`;
}
months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

for (event_index=0; event_index < events.length; event_index++) {
  const event = events[event_index];
  const event_start = new Date(event.StartDateTime);
  const day = event_start.getDate();
  const date_key = months[event_start.getMonth()] + ' ' + day;
  if (! (date_key in dataset_indexes)) {
    dataset_indexes[date_key] = datasets.length;
  }
  const dataset_index = dataset_indexes[date_key];

  if (!(dataset_index in datasets)) {
    datasets[dataset_index] = {
      label: date_key,
      fill: false,
      borderColor: 'rgb('+parseInt(255*Math.random())+', '+parseInt(255*Math.random())+', '+parseInt(255*Math.random())+')',
      tension: 0.1,
      data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
    };
  }

  datasets[dataset_index].data[event_start.getHours()] += parseFloat(event.Length);
}
/*
for (i=0; i < datasets.length; i++) {
  if (!datasets[i]) {
    datasets[i] = {
      label: '',
      fill: false,
      borderColor: 'rgb(192, 192, 192)',
      tension: 0.1,
      data: []
    };
  }
}
 */
console.log(datasets);

const data = {
    labels: time_labels,
    datasets: datasets,
};

new Chart(document.getElementById("bar-chart"), {
    type: 'line',
    data: data
});
/*
{
    options: {
      legend: { display: false },
      title: {
        display: true,
        text: report.Name
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
 */
</script>
  </div>
<?php xhtmlFooter() ?>
