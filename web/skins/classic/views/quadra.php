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

// Parse ni_rsrc_mon output (summary mode)
function parseQuadraSummary() {
  $output = [];
  $returnCode = 0;

  exec('ni_rsrc_mon 2>&1', $output, $returnCode);

  if ($returnCode != 0) {
    return ['error' => implode("\n", $output)];
  }

  $result = [
    'timestamp' => '',
    'version' => '',
    'uptime' => '',
    'devices' => [],
    'decoders' => [],
    'encoders' => [],
    'scalers' => [],
    'ais' => [],
  ];

  $currentSection = null;
  $headers = [];

  foreach ($output as $line) {
    $line = trim($line);

    if (empty($line) || preg_match('/^\*+$/', $line)) {
      continue;
    }

    // Parse timestamp and version line
    if (preg_match('/^(\w{3}\s+\w{3}\s+\d+\s+[\d:]+\s+\d{4})\s+up\s+([\d:]+)\s+v(.+)$/', $line, $matches)) {
      $result['timestamp'] = $matches[1];
      $result['uptime'] = $matches[2];
      $result['version'] = $matches[3];
      continue;
    }

    if (preg_match('/^(\d+) devices? retrieved/', $line)) {
      continue;
    }

    // Detect section changes
    if (preg_match('/^Num (decoders|encoders|scalers|AIs):\s*(\d+)/i', $line, $matches)) {
      $currentSection = strtolower($matches[1]);
      if ($currentSection == 'ais') $currentSection = 'ais';
      $headers = [];
      continue;
    }

    // Parse header line
    if (preg_match('/^INDEX\s+/', $line)) {
      $headers = preg_split('/\s+/', $line);
      if ($currentSection === null && in_array('TEMP', $headers)) {
        $currentSection = 'devices';
      }
      continue;
    }

    // Parse data rows
    if (!empty($headers) && $currentSection !== null && preg_match('/^\d+\s+/', $line)) {
      $values = preg_split('/\s+/', $line);
      $row = [];

      if ($currentSection == 'devices') {
        $row['INDEX'] = $values[0] ?? '';
        $row['TEMP'] = $values[1] ?? '';
        $row['POWER'] = $values[2] ?? '';
        $row['FLAVOR'] = $values[3] ?? '';
        $row['FR'] = $values[4] ?? '';
        $row['SN'] = isset($values[5]) ? implode(' ', array_slice($values, 5)) : '';
      } else {
        foreach ($headers as $i => $header) {
          $row[$header] = $values[$i] ?? '';
        }
      }

      $result[$currentSection][] = $row;
    }
  }

  return $result;
}

// Parse ni_rsrc_mon -d output (detailed mode)
function parseQuadraDetailed() {
  $output = [];
  $returnCode = 0;

  exec('ni_rsrc_mon -d 2>&1', $output, $returnCode);

  if ($returnCode != 0) {
    return ['error' => implode("\n", $output)];
  }

  $result = [
    'decoders' => [],
    'encoders' => [],
  ];

  $currentSection = null;
  $headers = [];

  foreach ($output as $line) {
    $line = trim($line);

    if (empty($line) || preg_match('/^\*+$/', $line)) {
      continue;
    }

    // Detect section changes
    if (preg_match('/^Num (decoders|encoders|scalers|AIs):\s*(\d+)/i', $line, $matches)) {
      $currentSection = strtolower($matches[1]);
      $headers = [];
      continue;
    }

    // Parse header line
    if (preg_match('/^INDEX\s+/', $line)) {
      $headers = preg_split('/\s+/', $line);
      continue;
    }

    // Parse data rows - only for decoders and encoders in detailed mode
    if (!empty($headers) && ($currentSection == 'decoders' || $currentSection == 'encoders') && preg_match('/^\d+\s+/', $line)) {
      $values = preg_split('/\s+/', $line);
      $row = [];

      foreach ($headers as $i => $header) {
        $row[$header] = $values[$i] ?? '';
      }

      $result[$currentSection][] = $row;
    }
  }

  return $result;
}

$quadraSummary = parseQuadraSummary();
$quadraDetailed = parseQuadraDetailed();

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
    <strong>Error:</strong> <?php echo htmlspecialchars($quadraSummary['error']) ?>
  </div>
<?php else: ?>

  <!-- Status Summary -->
  <div class="card mb-3">
    <div class="card-header">
      <i class="material-icons md-18">info</i> Device Information
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4">
          <strong>Timestamp:</strong> <?php echo htmlspecialchars($quadraSummary['timestamp'] ?? 'N/A') ?>
        </div>
        <div class="col-md-4">
          <strong>Uptime:</strong> <?php echo htmlspecialchars($quadraSummary['uptime'] ?? 'N/A') ?>
        </div>
        <div class="col-md-4">
          <strong>Firmware:</strong> v<?php echo htmlspecialchars($quadraSummary['version'] ?? 'N/A') ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Device Table -->
  <?php if (!empty($quadraSummary['devices'])): ?>
  <div class="card mb-3">
    <div class="card-header">
      <i class="material-icons md-18">developer_board</i> Devices (<?php echo count($quadraSummary['devices']) ?>)
    </div>
    <div class="card-body table-responsive">
      <table class="table table-sm table-striped table-hover">
        <thead class="thead-highlight text-left">
          <tr>
            <th>Index</th>
            <th>Temperature</th>
            <th>Power</th>
            <th>Flavor</th>
            <th>Firmware</th>
            <th>Serial Number</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($quadraSummary['devices'] as $device): ?>
          <tr>
            <td><?php echo htmlspecialchars($device['INDEX']) ?></td>
            <td><?php echo htmlspecialchars($device['TEMP']) ?>&deg;C</td>
            <td><?php echo htmlspecialchars($device['POWER']) ?></td>
            <td><?php echo htmlspecialchars($device['FLAVOR']) ?></td>
            <td><?php echo htmlspecialchars($device['FR']) ?></td>
            <td><code><?php echo htmlspecialchars($device['SN']) ?></code></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Resource Summary Tables -->
  <div class="row">
    <!-- Decoders Summary -->
    <?php if (!empty($quadraSummary['decoders'])): ?>
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header">
          <i class="material-icons md-18">input</i> Decoder Summary
        </div>
        <div class="card-body table-responsive">
          <table class="table table-sm table-striped table-hover">
            <thead class="thead-highlight text-left">
              <tr>
                <th>Index</th>
                <th>Load</th>
                <th>Model Load</th>
                <th>Instances</th>
                <th>Memory</th>
                <th>Shared Mem</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($quadraSummary['decoders'] as $decoder): ?>
              <tr>
                <td><?php echo htmlspecialchars($decoder['INDEX']) ?></td>
                <td><?php echo htmlspecialchars($decoder['LOAD'] ?? '') ?>%</td>
                <td><?php echo htmlspecialchars($decoder['MODEL_LOAD'] ?? '') ?></td>
                <td><?php echo htmlspecialchars($decoder['INST'] ?? '') ?></td>
                <td><?php echo htmlspecialchars($decoder['MEM'] ?? '') ?> MB</td>
                <td><?php echo htmlspecialchars($decoder['SHARE_MEM'] ?? '') ?> MB</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Encoders Summary -->
    <?php if (!empty($quadraSummary['encoders'])): ?>
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header">
          <i class="material-icons md-18">output</i> Encoder Summary
        </div>
        <div class="card-body table-responsive">
          <table class="table table-sm table-striped table-hover">
            <thead class="thead-highlight text-left">
              <tr>
                <th>Index</th>
                <th>Load</th>
                <th>Model Load</th>
                <th>Instances</th>
                <th>Memory</th>
                <th>Shared Mem</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($quadraSummary['encoders'] as $encoder): ?>
              <tr>
                <td><?php echo htmlspecialchars($encoder['INDEX']) ?></td>
                <td><?php echo htmlspecialchars($encoder['LOAD'] ?? '') ?>%</td>
                <td><?php echo htmlspecialchars($encoder['MODEL_LOAD'] ?? '') ?></td>
                <td><?php echo htmlspecialchars($encoder['INST'] ?? '') ?></td>
                <td><?php echo htmlspecialchars($encoder['MEM'] ?? '') ?> MB</td>
                <td><?php echo htmlspecialchars($encoder['SHARE_MEM'] ?? '') ?> MB</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="row">
    <!-- Scalers Summary -->
    <?php if (!empty($quadraSummary['scalers'])): ?>
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header">
          <i class="material-icons md-18">aspect_ratio</i> Scaler Summary
        </div>
        <div class="card-body table-responsive">
          <table class="table table-sm table-striped table-hover">
            <thead class="thead-highlight text-left">
              <tr>
                <th>Index</th>
                <th>Load</th>
                <th>Model Load</th>
                <th>Instances</th>
                <th>Memory</th>
                <th>Shared Mem</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($quadraSummary['scalers'] as $scaler): ?>
              <tr>
                <td><?php echo htmlspecialchars($scaler['INDEX']) ?></td>
                <td><?php echo htmlspecialchars($scaler['LOAD'] ?? '') ?>%</td>
                <td><?php echo htmlspecialchars($scaler['MODEL_LOAD'] ?? '') ?></td>
                <td><?php echo htmlspecialchars($scaler['INST'] ?? '') ?></td>
                <td><?php echo htmlspecialchars($scaler['MEM'] ?? '') ?> MB</td>
                <td><?php echo htmlspecialchars($scaler['SHARE_MEM'] ?? '') ?> MB</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- AI Summary -->
    <?php if (!empty($quadraSummary['ais'])): ?>
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header">
          <i class="material-icons md-18">psychology</i> AI Summary
        </div>
        <div class="card-body table-responsive">
          <table class="table table-sm table-striped table-hover">
            <thead class="thead-highlight text-left">
              <tr>
                <th>Index</th>
                <th>Load</th>
                <th>Model Load</th>
                <th>Instances</th>
                <th>Memory</th>
                <th>Shared Mem</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($quadraSummary['ais'] as $ai): ?>
              <tr>
                <td><?php echo htmlspecialchars($ai['INDEX']) ?></td>
                <td><?php echo htmlspecialchars($ai['LOAD'] ?? '') ?>%</td>
                <td><?php echo htmlspecialchars($ai['MODEL_LOAD'] ?? '') ?></td>
                <td><?php echo htmlspecialchars($ai['INST'] ?? '') ?></td>
                <td><?php echo htmlspecialchars($ai['MEM'] ?? '') ?> MB</td>
                <td><?php echo htmlspecialchars($ai['SHARE_MEM'] ?? '') ?> MB</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Detailed Session Tables -->
  <?php if (!empty($quadraDetailed['decoders'])): ?>
  <div class="card mb-3">
    <div class="card-header">
      <i class="material-icons md-18">input</i> Active Decoder Sessions (<?php echo count($quadraDetailed['decoders']) ?>)
    </div>
    <div class="card-body table-responsive">
      <table class="table table-sm table-striped table-hover">
        <thead class="thead-highlight text-left">
          <tr>
            <th>Index</th>
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
          foreach ($quadraDetailed['decoders'] as $decoder):
            $totalDecoderFps += floatval($decoder['FrameRate'] ?? 0);
            $totalDecoderInFrames += intval($decoder['InFrame'] ?? 0);
            $totalDecoderOutFrames += intval($decoder['OutFrame'] ?? 0);
          ?>
          <tr>
            <td><?php echo htmlspecialchars($decoder['INDEX']) ?></td>
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

  <?php if (!empty($quadraDetailed['encoders'])): ?>
  <div class="card mb-3">
    <div class="card-header">
      <i class="material-icons md-18">output</i> Active Encoder Sessions (<?php echo count($quadraDetailed['encoders']) ?>)
    </div>
    <div class="card-body table-responsive">
      <table class="table table-sm table-striped table-hover">
        <thead class="thead-highlight text-left">
          <tr>
            <th>Index</th>
            <th>Resolution</th>
            <th>Format</th>
            <th>Frame Rate</th>
            <th>Bitrate</th>
            <th>Avg Bitrate</th>
            <th>Avg Cost</th>
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
          foreach ($quadraDetailed['encoders'] as $encoder):
            $totalEncoderFps += floatval($encoder['FrameRate'] ?? 0);
            $totalEncoderBitrate += intval($encoder['BR'] ?? 0);
            $totalEncoderAvgBitrate += intval($encoder['AvgBR'] ?? 0);
            $totalEncoderInFrames += intval($encoder['InFrame'] ?? 0);
            $totalEncoderOutFrames += intval($encoder['OutFrame'] ?? 0);
          ?>
          <tr>
            <td><?php echo htmlspecialchars($encoder['INDEX']) ?></td>
            <td><?php echo htmlspecialchars($encoder['Width'] ?? '') ?>x<?php echo htmlspecialchars($encoder['Height'] ?? '') ?></td>
            <td><?php echo htmlspecialchars($encoder['Format'] ?? '') ?></td>
            <td><?php echo htmlspecialchars($encoder['FrameRate'] ?? '') ?> fps</td>
            <td><?php echo number_format(intval($encoder['BR'] ?? 0)) ?> bps</td>
            <td><?php echo number_format(intval($encoder['AvgBR'] ?? 0)) ?> bps</td>
            <td><?php echo htmlspecialchars($encoder['AvgCost'] ?? '') ?></td>
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
            <th><?php echo number_format($totalEncoderBitrate) ?> bps</th>
            <th><?php echo number_format($totalEncoderAvgBitrate) ?> bps</th>
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
