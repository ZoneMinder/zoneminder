<?php
//
// ZoneMinder web Intel GPU status view file
// Copyright (C) 2024 ZoneMinder Inc.
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

if (!canView('System')) {
  $view = 'error';
  return;
}

// Parse intel_gpu_top JSON output
function parseIntelGpuTop() {
  $output = [];
  $returnCode = 0;

  // Run intel_gpu_top for one sample with JSON output
  // -s 1000 = 1 second sample, -J = JSON output
  exec('timeout 2 intel_gpu_top -J -s 1000 -o - 2>&1', $output, $returnCode);

  $jsonOutput = implode("\n", $output);

  // Check for permission error
  if (strpos($jsonOutput, 'Permission denied') !== false || strpos($jsonOutput, 'CAP_PERFMON') !== false) {
    return ['error' => 'Permission denied. intel_gpu_top requires CAP_PERFMON capability or root access. See "man 7 capabilities" for details.'];
  }

  if ($returnCode != 0 && empty($output)) {
    return ['error' => 'Failed to run intel_gpu_top. Is it installed? (apt install intel-gpu-tools)'];
  }

  // intel_gpu_top outputs multiple JSON objects (one per sample), we want the last complete one
  // Find the last complete JSON object
  $jsonObjects = [];
  $depth = 0;
  $start = -1;

  for ($i = 0; $i < strlen($jsonOutput); $i++) {
    $char = $jsonOutput[$i];
    if ($char === '{') {
      if ($depth === 0) {
        $start = $i;
      }
      $depth++;
    } else if ($char === '}') {
      $depth--;
      if ($depth === 0 && $start >= 0) {
        $jsonObjects[] = substr($jsonOutput, $start, $i - $start + 1);
        $start = -1;
      }
    }
  }

  if (empty($jsonObjects)) {
    return ['error' => 'No valid JSON output from intel_gpu_top. Raw output: ' . substr($jsonOutput, 0, 500)];
  }

  // Use the last complete JSON object
  $lastJson = end($jsonObjects);
  $data = json_decode($lastJson, true);

  if ($data === null) {
    return ['error' => 'Failed to parse JSON from intel_gpu_top: ' . json_last_error_msg()];
  }

  return $data;
}

// Get list of Intel GPU devices
function getIntelGpuDevices() {
  $output = [];
  $returnCode = 0;

  exec('intel_gpu_top -L 2>&1', $output, $returnCode);

  if ($returnCode != 0 || empty($output)) {
    return [];
  }

  $devices = [];
  foreach ($output as $line) {
    $line = trim($line);
    if (!empty($line) && strpos($line, 'card') !== false) {
      $devices[] = $line;
    }
  }

  return $devices;
}

// Get additional GPU info from sysfs/lspci
function getIntelGpuInfo() {
  $info = [
    'model' => 'Unknown',
    'driver' => 'Unknown',
    'pci_id' => 'Unknown',
  ];

  // Try to get GPU model from lspci
  $output = [];
  exec('lspci -nn 2>/dev/null | grep -i "VGA\|Display\|3D" | grep -i intel', $output);
  if (!empty($output)) {
    $info['model'] = trim($output[0]);
    if (preg_match('/\[([0-9a-f]{4}:[0-9a-f]{4})\]/i', $output[0], $matches)) {
      $info['pci_id'] = $matches[1];
    }
  }

  // Try to get driver version
  $output = [];
  exec('cat /sys/module/i915/version 2>/dev/null', $output);
  if (!empty($output)) {
    $info['driver'] = trim($output[0]);
  } else {
    // Try modinfo
    $output = [];
    exec('modinfo i915 2>/dev/null | grep "^version:"', $output);
    if (!empty($output)) {
      $info['driver'] = trim(str_replace('version:', '', $output[0]));
    }
  }

  return $info;
}

// Format percentage with color coding
function formatPercentage($value, $inverse = false) {
  $value = floatval($value);
  if ($inverse) {
    // For RC6, higher is better (GPU is idle)
    if ($value >= 80) {
      $class = 'text-success';
    } else if ($value >= 50) {
      $class = 'text-warning';
    } else {
      $class = 'text-danger';
    }
  } else {
    // For usage, lower is better (less loaded)
    if ($value >= 80) {
      $class = 'text-danger';
    } else if ($value >= 50) {
      $class = 'text-warning';
    } else {
      $class = 'text-success';
    }
  }
  return '<span class="' . $class . '">' . number_format($value, 1) . '%</span>';
}

$gpuData = parseIntelGpuTop();
$gpuDevices = getIntelGpuDevices();
$gpuInfo = getIntelGpuInfo();

xhtmlHeaders(__FILE__, 'Intel GPU Status');
getBodyTopHTML();
echo getNavBarHTML();
?>
<div id="page" class="container-fluid">
  <h2><i class="material-icons md-18">memory</i> Intel GPU Status</h2>

  <div id="toolbar" class="pb-2">
    <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
    <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>"><i class="fa fa-refresh"></i></button>
  </div>

  <div id="content">
<?php if (isset($gpuData['error'])): ?>
  <div class="alert alert-danger">
    <strong>Error:</strong> <?php echo htmlspecialchars($gpuData['error']) ?>
  </div>
<?php else: ?>

  <!-- GPU Information -->
  <div class="card mb-3">
    <div class="card-header">
      <i class="material-icons md-18">info</i> GPU Information
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <strong>Device:</strong> <?php echo htmlspecialchars($gpuInfo['model']) ?>
        </div>
        <div class="col-md-3">
          <strong>Driver:</strong> <?php echo htmlspecialchars($gpuInfo['driver']) ?>
        </div>
        <div class="col-md-3">
          <strong>PCI ID:</strong> <?php echo htmlspecialchars($gpuInfo['pci_id']) ?>
        </div>
      </div>
      <?php if (!empty($gpuDevices)): ?>
      <div class="row mt-2">
        <div class="col-12">
          <strong>Available Devices:</strong>
          <ul class="mb-0">
            <?php foreach ($gpuDevices as $device): ?>
            <li><code><?php echo htmlspecialchars($device) ?></code></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="row">
    <!-- Frequency & Power -->
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header">
          <i class="material-icons md-18">speed</i> Frequency & Power
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            <?php if (isset($gpuData['frequency'])): ?>
            <tr>
              <td><strong>Requested Frequency:</strong></td>
              <td><?php echo htmlspecialchars($gpuData['frequency']['requested'] ?? 'N/A') ?> <?php echo htmlspecialchars($gpuData['frequency']['unit'] ?? 'MHz') ?></td>
            </tr>
            <tr>
              <td><strong>Actual Frequency:</strong></td>
              <td><?php echo htmlspecialchars($gpuData['frequency']['actual'] ?? 'N/A') ?> <?php echo htmlspecialchars($gpuData['frequency']['unit'] ?? 'MHz') ?></td>
            </tr>
            <?php endif; ?>
            <?php if (isset($gpuData['power'])): ?>
            <?php if (isset($gpuData['power']['GPU'])): ?>
            <tr>
              <td><strong>GPU Power:</strong></td>
              <td><?php echo number_format(floatval($gpuData['power']['GPU']), 2) ?> <?php echo htmlspecialchars($gpuData['power']['unit'] ?? 'W') ?></td>
            </tr>
            <?php endif; ?>
            <?php if (isset($gpuData['power']['Package'])): ?>
            <tr>
              <td><strong>Package Power:</strong></td>
              <td><?php echo number_format(floatval($gpuData['power']['Package']), 2) ?> <?php echo htmlspecialchars($gpuData['power']['unit'] ?? 'W') ?></td>
            </tr>
            <?php endif; ?>
            <?php endif; ?>
            <?php if (isset($gpuData['rc6'])): ?>
            <tr>
              <td><strong>RC6 (Idle):</strong></td>
              <td><?php echo formatPercentage($gpuData['rc6']['value'] ?? 0, true) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (isset($gpuData['interrupts'])): ?>
            <tr>
              <td><strong>Interrupts:</strong></td>
              <td><?php echo number_format(intval($gpuData['interrupts']['count'] ?? 0)) ?> <?php echo htmlspecialchars($gpuData['interrupts']['unit'] ?? '/s') ?></td>
            </tr>
            <?php endif; ?>
          </table>
        </div>
      </div>
    </div>

    <!-- Memory Bandwidth -->
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header">
          <i class="material-icons md-18">swap_horiz</i> Memory Bandwidth (IMC)
        </div>
        <div class="card-body">
          <?php if (isset($gpuData['imc-bandwidth'])): ?>
          <table class="table table-sm table-borderless mb-0">
            <tr>
              <td><strong>Reads:</strong></td>
              <td><?php echo number_format(floatval($gpuData['imc-bandwidth']['reads'] ?? 0), 1) ?> <?php echo htmlspecialchars($gpuData['imc-bandwidth']['unit'] ?? 'MiB/s') ?></td>
            </tr>
            <tr>
              <td><strong>Writes:</strong></td>
              <td><?php echo number_format(floatval($gpuData['imc-bandwidth']['writes'] ?? 0), 1) ?> <?php echo htmlspecialchars($gpuData['imc-bandwidth']['unit'] ?? 'MiB/s') ?></td>
            </tr>
            <tr>
              <td><strong>Total:</strong></td>
              <td><?php echo number_format(floatval($gpuData['imc-bandwidth']['reads'] ?? 0) + floatval($gpuData['imc-bandwidth']['writes'] ?? 0), 1) ?> <?php echo htmlspecialchars($gpuData['imc-bandwidth']['unit'] ?? 'MiB/s') ?></td>
            </tr>
          </table>
          <?php else: ?>
          <p class="text-muted mb-0">IMC bandwidth data not available</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Engine Utilization -->
  <?php if (isset($gpuData['engines']) && is_array($gpuData['engines'])): ?>
  <div class="card mb-3">
    <div class="card-header">
      <i class="material-icons md-18">developer_board</i> Engine Utilization
    </div>
    <div class="card-body table-responsive">
      <table class="table table-sm table-striped table-hover">
        <thead class="thead-highlight text-left">
          <tr>
            <th>Engine</th>
            <th>Busy</th>
            <th>Semaphore Wait</th>
            <th>Memory Wait</th>
            <th>Utilization Bar</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $totalBusy = 0;
          $engineCount = 0;
          foreach ($gpuData['engines'] as $engineName => $engineData):
            if (!is_array($engineData)) continue;
            $busy = floatval($engineData['busy'] ?? 0);
            $sema = floatval($engineData['sema'] ?? 0);
            $wait = floatval($engineData['wait'] ?? 0);
            $totalBusy += $busy;
            $engineCount++;
          ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($engineName) ?></strong></td>
            <td><?php echo formatPercentage($busy) ?></td>
            <td><?php echo number_format($sema, 1) ?>%</td>
            <td><?php echo number_format($wait, 1) ?>%</td>
            <td style="width: 30%;">
              <div class="progress" style="height: 20px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $busy ?>%;" aria-valuenow="<?php echo $busy ?>" aria-valuemin="0" aria-valuemax="100">
                  <?php if ($busy >= 10): ?><?php echo number_format($busy, 0) ?>%<?php endif; ?>
                </div>
                <?php if ($sema > 0): ?>
                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $sema ?>%;" aria-valuenow="<?php echo $sema ?>" aria-valuemin="0" aria-valuemax="100"></div>
                <?php endif; ?>
                <?php if ($wait > 0): ?>
                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $wait ?>%;" aria-valuenow="<?php echo $wait ?>" aria-valuemin="0" aria-valuemax="100"></div>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <?php if ($engineCount > 0): ?>
        <tfoot class="table-secondary text-left">
          <tr>
            <th>Average</th>
            <th colspan="3"><?php echo formatPercentage($totalBusy / $engineCount) ?></th>
            <th>
              <div class="progress" style="height: 20px;">
                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $totalBusy / $engineCount ?>%;">
                  <?php echo number_format($totalBusy / $engineCount, 0) ?>%
                </div>
              </div>
            </th>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Clients/Processes (if available) -->
  <?php if (isset($gpuData['clients']) && is_array($gpuData['clients']) && !empty($gpuData['clients'])): ?>
  <div class="card mb-3">
    <div class="card-header">
      <i class="material-icons md-18">apps</i> GPU Clients (<?php echo count($gpuData['clients']) ?>)
    </div>
    <div class="card-body table-responsive">
      <table class="table table-sm table-striped table-hover">
        <thead class="thead-highlight text-left">
          <tr>
            <th>PID</th>
            <th>Name</th>
            <?php
            // Get engine columns from first client
            $firstClient = reset($gpuData['clients']);
            if (isset($firstClient['engine-classes']) && is_array($firstClient['engine-classes'])):
              foreach (array_keys($firstClient['engine-classes']) as $engine):
            ?>
            <th><?php echo htmlspecialchars($engine) ?></th>
            <?php
              endforeach;
            endif;
            ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($gpuData['clients'] as $client): ?>
          <tr>
            <td><?php echo htmlspecialchars($client['pid'] ?? 'N/A') ?></td>
            <td><?php echo htmlspecialchars($client['name'] ?? 'Unknown') ?></td>
            <?php if (isset($client['engine-classes']) && is_array($client['engine-classes'])): ?>
              <?php foreach ($client['engine-classes'] as $usage): ?>
              <td><?php echo number_format(floatval($usage['busy'] ?? 0), 1) ?>%</td>
              <?php endforeach; ?>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Legend -->
  <div class="card mb-3">
    <div class="card-header">
      <i class="material-icons md-18">help_outline</i> Legend
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4">
          <h6>Engines</h6>
          <ul class="small mb-0">
            <li><strong>Render/3D:</strong> 3D rendering workloads</li>
            <li><strong>Blitter:</strong> 2D copy operations</li>
            <li><strong>Video:</strong> Hardware video decode (VAAPI)</li>
            <li><strong>VideoEnhance:</strong> Video post-processing</li>
          </ul>
        </div>
        <div class="col-md-4">
          <h6>Utilization</h6>
          <ul class="small mb-0">
            <li><strong>Busy:</strong> Engine actively processing</li>
            <li><strong>Sema:</strong> Waiting on semaphore</li>
            <li><strong>Wait:</strong> Waiting on memory</li>
          </ul>
        </div>
        <div class="col-md-4">
          <h6>Power States</h6>
          <ul class="small mb-0">
            <li><strong>RC6:</strong> Render C-state 6 (deep idle)</li>
            <li>Higher RC6 = More power efficient</li>
            <li><strong>IMC:</strong> Integrated Memory Controller</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

<?php endif; ?>
  </div><!-- content -->
</div>

<script>
document.getElementById('refreshBtn').addEventListener('click', function() {
  location.reload();
});

document.getElementById('backBtn').addEventListener('click', function() {
  window.history.back();
});

// Enable back button if there's history
if (window.history.length > 1) {
  document.getElementById('backBtn').disabled = false;
}
</script>

<?php xhtmlFooter() ?>
