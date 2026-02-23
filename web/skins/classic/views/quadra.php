<?php
//
// ZoneMinder web Quadra status view file
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

// Resolve the full path to ni_rsrc_mon.  The web server's PATH may not
// include /usr/local/bin where the tool is typically installed.
function findNiRsrcMon() {
  static $path = null;
  if ($path !== null) return $path;

  // Check common locations
  $candidates = [
    '/usr/local/bin/ni_rsrc_mon',
    '/usr/bin/ni_rsrc_mon',
  ];
  foreach ($candidates as $candidate) {
    if (is_executable($candidate)) {
      $path = $candidate;
      return $path;
    }
  }

  // Fall back to bare command (relies on PATH)
  $path = 'ni_rsrc_mon';
  return $path;
}

// ni_rsrc_mon -o json outputs non-standard JSON: unquoted hex strings
// (e.g. "SID": 9e48), unquoted barewords ("Format": YUV), and trailing
// commas after the last array element.  Fix these before json_decode.
function fixQuadraJson($text) {
  // Remove trailing commas before ] or }
  $text = preg_replace('/,(\s*[}\]])/', '$1', $text);

  // Quote unquoted values (barewords and hex strings).
  // Matches "KEY": VALUE where VALUE is not already quoted, not an array/object,
  // and not a pure integer or float.
  $text = preg_replace_callback(
    '/("[\w]+"\s*:\s*)([^\s"\[\]{},][^\s,\]\}]*)(\s*[,}\]])/',
    function ($matches) {
      $value = trim($matches[2]);
      if (preg_match('/^-?\d+$/', $value)) return $matches[0];
      if (preg_match('/^-?\d+\.\d+$/', $value)) return $matches[0];
      if (in_array($value, ['true', 'false', 'null'])) return $matches[0];
      return $matches[1].'"'.$value.'"'.$matches[3];
    },
    $text
  );

  return $text;
}

// Parse ni_rsrc_mon JSON output.  The tool emits a timestamp header
// followed by multiple separate JSON objects { "section": [...] },
// delimited by **** lines.
function parseQuadraJsonOutput($args) {
  $bin = findNiRsrcMon();
  $command = $bin.' '.$args;

  // Check if exec() is available
  if (!function_exists('exec') || in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
    ZM\Warning("Quadra: exec() is disabled in PHP configuration");
    return ['error' => 'exec() is disabled in PHP configuration. Cannot run ni_rsrc_mon.'];
  }

  $output = [];
  $returnCode = -1;
  exec($command.' 2>&1', $output, $returnCode);

  $outputText = implode("\n", $output);
  $lineCount = count($output);

  if ($returnCode != 0) {
    ZM\Warning("Quadra: '$command' failed with return code $returnCode: $outputText");
    if ($returnCode == 127 || (empty($output) && $returnCode != 0)) {
      // Command not found - provide helpful diagnostics
      $diag = "Command '$command' not found (return code $returnCode).";
      if ($bin === 'ni_rsrc_mon') {
        $diag .= ' ni_rsrc_mon was not found in /usr/local/bin or /usr/bin.'
                 .' Ensure the NetInt tools are installed.';
      }
      return ['error' => $diag];
    }
    return ['error' => "Command failed (return code $returnCode): $outputText"];
  }

  if (empty($output)) {
    ZM\Warning("Quadra: '$command' returned no output (return code $returnCode)");
    return ['error' => "ni_rsrc_mon returned no output. Command: $command"];
  }

  ZM\Debug("Quadra: '$command' returned $lineCount lines (rc=$returnCode)");

  $result = ['timestamp' => '', 'uptime' => '', 'version' => ''];
  $rawText = $outputText;

  if (preg_match('/(\w{3}\s+\w{3}\s+\d+\s+[\d:]+\s+\d{4})\s+up\s+([\d:]+)\s+v(.+)/', $rawText, $matches)) {
    $result['timestamp'] = $matches[1];
    $result['uptime'] = $matches[2];
    $result['version'] = $matches[3];
  }

  // Each section is { "key": [...] } with the outer } at column 0.
  // Inner object braces are tab-indented, so ^\} only matches the outer close.
  $sectionCount = 0;
  $parseErrors = [];
  if (preg_match_all('/^\{\s*"(\w+)"\s*:.*?^\}/ms', $rawText, $jsonBlocks, PREG_SET_ORDER)) {
    foreach ($jsonBlocks as $block) {
      $key = $block[1];
      $json = fixQuadraJson($block[0]);
      $parsed = json_decode($json, true);
      if ($parsed !== null && isset($parsed[$key])) {
        $result[$key] = $parsed[$key];
        $sectionCount++;
      } else {
        $jsonError = json_last_error_msg();
        $parseErrors[] = "$key: $jsonError";
        ZM\Warning("Quadra: JSON parse error for section '$key': $jsonError");
      }
    }
  } else {
    ZM\Warning("Quadra: no JSON sections found in output of '$command'. "
           ."Output ($lineCount lines): ".substr($rawText, 0, 500));
    return ['error' => "ni_rsrc_mon output could not be parsed. "
           ."No JSON sections found in $lineCount lines of output. "
           ."First 200 chars: ".htmlspecialchars(substr($rawText, 0, 200))];
  }

  if (!empty($parseErrors)) {
    ZM\Warning('Quadra: failed to parse '.count($parseErrors).' section(s): '.implode('; ', $parseErrors));
  }
  if ($sectionCount > 0) {
    ZM\Debug("Quadra: parsed $sectionCount sections from '$command'");
  }

  return $result;
}

function formatBitrate($bps) {
  $bps = intval($bps);
  if ($bps >= 1000000) return number_format($bps / 1000000, 1).' Mbps';
  if ($bps >= 1000) return number_format($bps / 1000, 1).' kbps';
  return number_format($bps).' bps';
}

$quadraSummary = parseQuadraJsonOutput('-o json');
$quadraDetailed = parseQuadraJsonOutput('-d -o json');

// Extract device info from the first available section entry
$deviceInfo = null;
foreach (['decoder', 'encoder', 'scaler', 'AI', 'uploader', 'nvme'] as $sect) {
  if (!empty($quadraSummary[$sect][0])) {
    $deviceInfo = $quadraSummary[$sect][0];
    break;
  }
}

$resourceSections = [
  'decoder'  => ['label' => 'Decoder',  'icon' => 'input'],
  'encoder'  => ['label' => 'Encoder',  'icon' => 'output'],
  'uploader' => ['label' => 'Uploader', 'icon' => 'cloud_upload'],
  'scaler'   => ['label' => 'Scaler',   'icon' => 'aspect_ratio'],
  'AI'       => ['label' => 'AI',       'icon' => 'psychology'],
];

$infraSections = [
  'nvme' => ['label' => 'NVMe',      'icon' => 'storage'],
  'tp'   => ['label' => 'Transport', 'icon' => 'swap_horiz'],
  'pcie' => ['label' => 'PCIe',      'icon' => 'settings_ethernet'],
];

xhtmlHeaders(__FILE__, 'Quadra Status');
getBodyTopHTML();
echo getNavBarHTML();
?>
<div id="page" class="container-fluid">
  <h2><i class="material-icons md-18">memory</i> NetInt Quadra Status</h2>

  <div id="toolbar" class="pb-2">
    <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
    <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>"><i class="fa fa-refresh"></i></button>
  </div>

  <div id="content">
<?php if (isset($quadraSummary['error'])): ?>
  <div class="alert alert-danger">
    <strong>Error running ni_rsrc_mon:</strong><br>
    <?php echo htmlspecialchars($quadraSummary['error']) ?>
  </div>
<?php elseif (!$deviceInfo && empty($quadraSummary['decoder']) && empty($quadraSummary['encoder'])): ?>
  <div class="alert alert-warning">
    <strong>No data:</strong> ni_rsrc_mon returned no resource data.
    Check that a NetInt Quadra device is installed and that the web server user has permission to access it.
  </div>
<?php else: ?>

  <!-- Device Information -->
  <div class="card mb-3">
    <div class="card-header">
      <i class="material-icons md-18">info</i> Device Information
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3">
          <strong>Timestamp:</strong> <?php echo htmlspecialchars($quadraSummary['timestamp'] ?: 'N/A') ?>
        </div>
        <div class="col-md-2">
          <strong>Uptime:</strong> <?php echo htmlspecialchars($quadraSummary['uptime'] ?: 'N/A') ?>
        </div>
        <div class="col-md-2">
          <strong>Firmware:</strong> v<?php echo htmlspecialchars($quadraSummary['version'] ?: 'N/A') ?>
        </div>
<?php if ($deviceInfo): ?>
        <div class="col-md-2">
          <strong>Device:</strong> <?php echo htmlspecialchars($deviceInfo['DEVICE'] ?? 'N/A') ?>
        </div>
        <div class="col-md-3">
          <strong>PCIe:</strong> <?php echo htmlspecialchars($deviceInfo['PCIE_ADDR'] ?? 'N/A') ?>
          (NUMA <?php echo htmlspecialchars($deviceInfo['NUMA_NODE'] ?? '?') ?>)
        </div>
<?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Resource Utilization -->
<?php
$hasResources = false;
foreach ($resourceSections as $key => $meta) {
  if (!empty($quadraSummary[$key])) { $hasResources = true; break; }
}
if ($hasResources):
?>
  <div class="card mb-3">
    <div class="card-header">
      <i class="material-icons md-18">speed</i> Resource Utilization
    </div>
    <div class="card-body table-responsive">
      <table class="table table-sm table-striped table-hover">
        <thead class="thead-highlight text-left">
          <tr>
            <th>Resource</th>
            <th>Load</th>
            <th>Model Load</th>
            <th>FW Load</th>
            <th>Instances</th>
            <th>Memory</th>
            <th>Critical Mem</th>
            <th>Shared Mem</th>
          </tr>
        </thead>
        <tbody>
<?php foreach ($resourceSections as $key => $meta):
  if (empty($quadraSummary[$key])) continue;
  foreach ($quadraSummary[$key] as $entry):
?>
          <tr>
            <td><i class="material-icons md-18"><?php echo $meta['icon'] ?></i> <?php echo $meta['label'] ?></td>
            <td><?php echo htmlspecialchars($entry['LOAD'] ?? '0') ?>%</td>
            <td><?php echo htmlspecialchars($entry['MODEL_LOAD'] ?? '0') ?>%</td>
            <td><?php echo htmlspecialchars($entry['FW_LOAD'] ?? '0') ?>%</td>
            <td><?php echo htmlspecialchars($entry['INST'] ?? '0') ?> / <?php echo htmlspecialchars($entry['MAX_INST'] ?? '0') ?></td>
            <td><?php echo htmlspecialchars($entry['MEM'] ?? '0') ?> MB</td>
            <td><?php echo htmlspecialchars($entry['CRITICAL_MEM'] ?? '0') ?> MB</td>
            <td><?php echo htmlspecialchars($entry['SHARE_MEM'] ?? '0') ?> MB</td>
          </tr>
<?php endforeach; endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

  <!-- Infrastructure -->
<?php
$hasInfra = false;
foreach ($infraSections as $key => $meta) {
  if (!empty($quadraSummary[$key])) { $hasInfra = true; break; }
}
if ($hasInfra):
?>
  <div class="card mb-3">
    <div class="card-header">
      <i class="material-icons md-18">developer_board</i> Infrastructure
    </div>
    <div class="card-body table-responsive">
      <table class="table table-sm table-striped table-hover">
        <thead class="thead-highlight text-left">
          <tr>
            <th>Component</th>
            <th>FW Load</th>
            <th>Shared Mem</th>
            <th>PCIe Throughput</th>
          </tr>
        </thead>
        <tbody>
<?php foreach ($infraSections as $key => $meta):
  if (empty($quadraSummary[$key])) continue;
  foreach ($quadraSummary[$key] as $entry):
?>
          <tr>
            <td><i class="material-icons md-18"><?php echo $meta['icon'] ?></i> <?php echo $meta['label'] ?></td>
            <td><?php echo htmlspecialchars($entry['FW_LOAD'] ?? '0') ?>%</td>
            <td><?php echo htmlspecialchars($entry['SHARE_MEM'] ?? '0') ?> MB</td>
            <td><?php echo isset($entry['PCIE_THROUGHPUT']) ? htmlspecialchars($entry['PCIE_THROUGHPUT']) : '-' ?></td>
          </tr>
<?php endforeach; endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

  <!-- Active Decoder Sessions -->
<?php if (!empty($quadraDetailed['decoder'])): ?>
  <div class="card mb-3">
    <div class="card-header">
      <i class="material-icons md-18">input</i> Active Decoder Sessions (<?php echo count($quadraDetailed['decoder']) ?>)
    </div>
    <div class="card-body table-responsive">
      <table class="table table-sm table-striped table-hover">
        <thead class="thead-highlight text-left">
          <tr>
            <th>#</th>
            <th>Resolution</th>
            <th>Frame Rate</th>
            <th>Avg Cost</th>
            <th>IDR</th>
            <th>In Frames</th>
            <th>Out Frames</th>
            <th>Session ID</th>
          </tr>
        </thead>
        <tbody>
<?php
$totalDecoderFps = 0;
$totalDecoderInFrames = 0;
$totalDecoderOutFrames = 0;
foreach ($quadraDetailed['decoder'] as $decoder):
  $totalDecoderFps += floatval($decoder['FrameRate'] ?? 0);
  $totalDecoderInFrames += intval($decoder['InFrame'] ?? 0);
  $totalDecoderOutFrames += intval($decoder['OutFrame'] ?? 0);
?>
          <tr>
            <td><?php echo htmlspecialchars($decoder['INDEX'] ?? '') ?></td>
            <td><?php echo htmlspecialchars($decoder['Width'] ?? '') ?>x<?php echo htmlspecialchars($decoder['Height'] ?? '') ?></td>
            <td><?php echo htmlspecialchars($decoder['FrameRate'] ?? '') ?> fps</td>
            <td><?php echo htmlspecialchars($decoder['AvgCost'] ?? '') ?></td>
            <td><?php echo htmlspecialchars($decoder['IDR'] ?? '') ?></td>
            <td><?php echo number_format(intval($decoder['InFrame'] ?? 0)) ?></td>
            <td><?php echo number_format(intval($decoder['OutFrame'] ?? 0)) ?></td>
            <td><code><?php echo htmlspecialchars($decoder['SID'] ?? '') ?></code></td>
          </tr>
<?php endforeach; ?>
        </tbody>
        <tfoot class="table-secondary text-left">
          <tr>
            <th colspan="2">Total</th>
            <th><?php echo number_format($totalDecoderFps, 1) ?> fps</th>
            <th></th>
            <th></th>
            <th><?php echo number_format($totalDecoderInFrames) ?></th>
            <th><?php echo number_format($totalDecoderOutFrames) ?></th>
            <th></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
<?php endif; ?>

  <!-- Active Encoder Sessions -->
<?php if (!empty($quadraDetailed['encoder'])): ?>
  <div class="card mb-3">
    <div class="card-header">
      <i class="material-icons md-18">output</i> Active Encoder Sessions (<?php echo count($quadraDetailed['encoder']) ?>)
    </div>
    <div class="card-body table-responsive">
      <table class="table table-sm table-striped table-hover">
        <thead class="thead-highlight text-left">
          <tr>
            <th>#</th>
            <th>Resolution</th>
            <th>Format</th>
            <th>Frame Rate</th>
            <th>Bitrate</th>
            <th>Avg Bitrate</th>
            <th>Avg Cost</th>
            <th>IDR</th>
            <th>In Frames</th>
            <th>Out Frames</th>
            <th>Session ID</th>
          </tr>
        </thead>
        <tbody>
<?php
$totalEncoderFps = 0;
$totalEncoderBitrate = 0;
$totalEncoderAvgBitrate = 0;
$totalEncoderInFrames = 0;
$totalEncoderOutFrames = 0;
foreach ($quadraDetailed['encoder'] as $encoder):
  $totalEncoderFps += floatval($encoder['FrameRate'] ?? 0);
  $totalEncoderBitrate += intval($encoder['BR'] ?? 0);
  $totalEncoderAvgBitrate += intval($encoder['AvgBR'] ?? 0);
  $totalEncoderInFrames += intval($encoder['InFrame'] ?? 0);
  $totalEncoderOutFrames += intval($encoder['OutFrame'] ?? 0);
?>
          <tr>
            <td><?php echo htmlspecialchars($encoder['INDEX'] ?? '') ?></td>
            <td><?php echo htmlspecialchars($encoder['Width'] ?? '') ?>x<?php echo htmlspecialchars($encoder['Height'] ?? '') ?></td>
            <td><?php echo htmlspecialchars($encoder['Format'] ?? '') ?></td>
            <td><?php echo htmlspecialchars($encoder['FrameRate'] ?? '') ?> fps</td>
            <td><?php echo formatBitrate($encoder['BR'] ?? 0) ?></td>
            <td><?php echo formatBitrate($encoder['AvgBR'] ?? 0) ?></td>
            <td><?php echo htmlspecialchars($encoder['AvgCost'] ?? '') ?></td>
            <td><?php echo htmlspecialchars($encoder['IDR'] ?? '') ?></td>
            <td><?php echo number_format(intval($encoder['InFrame'] ?? 0)) ?></td>
            <td><?php echo number_format(intval($encoder['OutFrame'] ?? 0)) ?></td>
            <td><code><?php echo htmlspecialchars($encoder['SID'] ?? '') ?></code></td>
          </tr>
<?php endforeach; ?>
        </tbody>
        <tfoot class="table-secondary text-left">
          <tr>
            <th colspan="3">Total</th>
            <th><?php echo number_format($totalEncoderFps, 1) ?> fps</th>
            <th><?php echo formatBitrate($totalEncoderBitrate) ?></th>
            <th><?php echo formatBitrate($totalEncoderAvgBitrate) ?></th>
            <th></th>
            <th></th>
            <th><?php echo number_format($totalEncoderInFrames) ?></th>
            <th><?php echo number_format($totalEncoderOutFrames) ?></th>
            <th></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php endif; ?>
  </div><!-- content -->
</div>
<?php xhtmlFooter() ?>
