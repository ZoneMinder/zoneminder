<?php
ini_set('display_errors', '0');

// Training features require the option to be enabled
if (!defined('ZM_OPT_TRAINING') or !ZM_OPT_TRAINING) {
  ZM\Warning('Training: access denied — ZM_OPT_TRAINING is not enabled');
  ajaxError('Training features are not enabled');
  return;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
ZM\Debug('Training: action='.validHtmlStr($action).(isset($_REQUEST['eid']) ? ' eid='.validHtmlStr($_REQUEST['eid']) : ''));

require_once('includes/Event.php');

/**
 * Get the training data directory path from config.
 */
function getTrainingDataDir() {
  if (defined('ZM_TRAINING_DATA_DIR') && ZM_TRAINING_DATA_DIR != '') {
    return ZM_TRAINING_DATA_DIR;
  }
  return '';
}

/**
 * Ensure the training directory structure exists.
 * Creates images/all/ and labels/all/ subdirectories.
 */
function ensureTrainingDirs() {
  $base = getTrainingDataDir();
  if ($base === '') {
    ajaxError('ZM_TRAINING_DATA_DIR is not configured. Please set it in Options or run zmupdate.pl --freshen.');
    return false;
  }
  $dirs = [$base, $base.'/images', $base.'/images/all', $base.'/labels', $base.'/labels/all'];
  foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
      ZM\Debug('Training: creating directory '.$dir);
      if (!mkdir($dir, 0755, true)) {
        ZM\Error('Training: failed to create directory '.$dir);
        ajaxError('Failed to create training directory');
        return false;
      }
    }
  }
  return true;
}

/**
 * Validate a frame ID: must be a known special name or a positive integer.
 */
function validFrameId($fid) {
  return in_array($fid, ['alarm', 'snapshot']) || ctype_digit($fid);
}

/**
 * Get current class labels from data.yaml in the training directory.
 * Returns array of label strings ordered by class ID.
 */
function getClassLabels() {
  $base = getTrainingDataDir();
  if ($base === '') return [];
  $yamlFile = $base.'/data.yaml';
  if (!file_exists($yamlFile)) return [];

  $labels = [];
  $inNames = false;
  foreach (file($yamlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (preg_match('/^names:\s*$/', $line)) {
      $inNames = true;
      continue;
    }
    if ($inNames) {
      if (preg_match('/^\s+(\d+):\s*(.+)$/', $line, $m)) {
        $labels[intval($m[1])] = trim($m[2]);
      } else {
        break; // End of names block
      }
    }
  }
  ksort($labels);
  return array_values($labels);
}

/**
 * Write data.yaml in the training directory with the given labels.
 */
function writeDataYaml($labels) {
  $base = getTrainingDataDir();
  $yaml = "path: .\n";
  $yaml .= "train: images/all\n";
  $yaml .= "val: images/all\n";
  $yaml .= "names:\n";
  foreach ($labels as $i => $label) {
    $yaml .= "  $i: $label\n";
  }
  file_put_contents($base.'/data.yaml', $yaml);
  ZM\Debug('Training: wrote data.yaml with '.count($labels).' classes: '.implode(', ', $labels));
}

/**
 * Collect training dataset statistics:
 * total images, total classes, images per class.
 */
function getTrainingStats() {
  $base = getTrainingDataDir();
  if ($base === '') return ['total_images' => 0, 'total_classes' => 0, 'images_per_class' => [], 'class_labels' => []];
  $labelsDir = $base.'/labels/all';
  $labels = getClassLabels();
  $stats = [
    'total_images' => 0,
    'total_classes' => 0,
    'images_per_class' => [],
    'class_labels' => $labels,
  ];

  if (!is_dir($labelsDir)) return $stats;

  $classCounts = array_fill(0, count($labels), 0);

  $files = glob($labelsDir.'/*.txt');
  $annotatedCount = 0;

  $backgroundCount = 0;
  foreach ($files as $file) {
    $seenClasses = [];
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($lines)) {
      $backgroundCount++;
      continue;
    }
    $annotatedCount++;
    foreach ($lines as $line) {
      $parts = explode(' ', trim($line));
      if (count($parts) >= 5) {
        $classId = intval($parts[0]);
        if (!isset($seenClasses[$classId])) {
          $seenClasses[$classId] = true;
          if (isset($classCounts[$classId])) {
            $classCounts[$classId]++;
          }
        }
      }
    }
  }
  $stats['total_images'] = $annotatedCount;
  $stats['background_images'] = $backgroundCount;

  foreach ($labels as $i => $label) {
    $stats['images_per_class'][$label] = $classCounts[$i];
  }
  $stats['total_classes'] = count(array_filter($classCounts, function($c) { return $c > 0; }));

  return $stats;
}

/**
 * Recursively build a directory tree, with symlink and depth protection.
 */
function buildTree($dir, $base, $depth = 0) {
  $maxDepth = 5;
  $entries = [];
  if ($depth > $maxDepth || !is_dir($dir)) return $entries;
  $items = scandir($dir);
  sort($items);
  foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $fullPath = $dir.'/'.$item;
    // Skip symlinks
    if (is_link($fullPath)) continue;
    $relPath = ltrim(str_replace($base, '', $fullPath), '/');
    if (is_dir($fullPath)) {
      $entries[] = [
        'name' => $item,
        'path' => $relPath,
        'type' => 'dir',
        'children' => buildTree($fullPath, $base, $depth + 1),
      ];
    } else if (is_file($fullPath)) {
      $entries[] = [
        'name' => $item,
        'path' => $relPath,
        'type' => 'file',
        'size' => filesize($fullPath),
      ];
    }
  }
  return $entries;
}

switch ($action) {

  // ---- Read-only actions (canView) ----

  case 'load':
    // Load detection data for an event
    if (!canView('Events')) {
      ajaxError('Insufficient permissions');
      break;
    }
    if (empty($_REQUEST['eid'])) {
      ajaxError('Event ID required');
      break;
    }
    $eid = validCardinal($_REQUEST['eid']);
    $Event = ZM\Event::find_one(['Id' => $eid]);
    if (!$Event) {
      ajaxError('Event not found');
      break;
    }

    $eventPath = $Event->Path();
    $objectsFile = $eventPath.'/objects.json';
    $detectionData = null;

    ZM\Debug('Training: load event '.$eid.' path='.$eventPath);
    if (file_exists($objectsFile)) {
      $json = file_get_contents($objectsFile);
      $detectionData = json_decode($json, true);
      ZM\Debug('Training: found objects.json with '.(is_array($detectionData) ? count($detectionData) : 0).' entries');
    } else {
      ZM\Debug('Training: no objects.json found at '.$objectsFile);
    }

    // Determine default frame
    $defaultFrameId = null;
    if ($detectionData && isset($detectionData['frame_id'])) {
      $defaultFrameId = $detectionData['frame_id'];
    } else if (file_exists($eventPath.'/alarm.jpg')) {
      $defaultFrameId = 'alarm';
    } else if (file_exists($eventPath.'/snapshot.jpg')) {
      $defaultFrameId = 'snapshot';
    }

    // Check which special frames exist
    $availableFrames = [];
    foreach (['alarm', 'snapshot'] as $special) {
      if (file_exists($eventPath.'/'.$special.'.jpg')) {
        $availableFrames[] = $special;
      }
    }

    // Also check if we already have a saved annotation for this event
    $base = getTrainingDataDir();
    $fid = $defaultFrameId ?: 'alarm';
    $hasSavedAnnotation = false;
    if ($base !== '') {
      $savedFile = $base.'/labels/all/event_'.$eid.'_frame_'.$fid.'.txt';
      $hasSavedAnnotation = file_exists($savedFile);
    }

    $hasDetectScript = defined('ZM_TRAINING_DETECT_SCRIPT') && ZM_TRAINING_DETECT_SCRIPT != '';

    ajaxResponse([
      'detectionData' => $detectionData,
      'defaultFrameId' => $defaultFrameId,
      'availableFrames' => $availableFrames,
      'totalFrames' => $Event->Frames(),
      'eventPath' => $Event->Relative_Path(),
      'width' => $Event->Width(),
      'height' => $Event->Height(),
      'monitorId' => $Event->MonitorId(),
      'hasSavedAnnotation' => $hasSavedAnnotation,
      'hasDetectScript' => $hasDetectScript,
    ]);
    break;

  case 'labels':
    // Return current class label list
    if (!canView('Events')) {
      ajaxError('Insufficient permissions');
      break;
    }
    $labels = getClassLabels();
    ajaxResponse(['labels' => $labels]);
    break;

  case 'status':
    // Return training dataset statistics
    if (!canView('Events')) {
      ajaxError('Insufficient permissions');
      break;
    }
    ajaxResponse(['stats' => getTrainingStats()]);
    break;

  case 'browse':
    // Return recursive directory tree of training folder
    if (!canView('Events')) {
      ajaxError('Insufficient permissions');
      break;
    }
    $base = getTrainingDataDir();
    if ($base === '') {
      ajaxResponse(['tree' => []]);
      break;
    }

    $tree = buildTree($base, $base);

    ajaxResponse([
      'tree' => $tree,
    ]);
    break;

  case 'browse_file':
    // Serve an individual file from the training directory
    if (!canView('Events')) {
      ajaxError('Insufficient permissions');
      break;
    }
    if (empty($_REQUEST['path'])) {
      ajaxError('Path required');
      break;
    }

    $base = getTrainingDataDir();
    if ($base === '') {
      ajaxError('Training data directory not configured');
      break;
    }
    $reqPath = detaintPath($_REQUEST['path']);
    $fullPath = realpath($base.'/'.$reqPath);

    // Validate file is within the training directory
    if ($fullPath === false || strpos($fullPath, realpath($base)) !== 0 || !is_file($fullPath)) {
      ZM\Warning('Training: browse_file path rejected: '.validHtmlStr($_REQUEST['path']));
      ajaxError('File not found or access denied');
      break;
    }

    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
      // Serve raw image
      $mimeMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
      header('Content-Type: '.$mimeMap[$ext]);
      header('Content-Length: '.filesize($fullPath));
      header('Cache-Control: private, max-age=300');
      readfile($fullPath);
      exit;
    } else if (in_array($ext, ['txt', 'yaml', 'yml'])) {
      // Return text content as JSON
      ajaxResponse(['content' => file_get_contents($fullPath)]);
    } else {
      ajaxError('Unsupported file type');
    }
    break;

  // ---- Write actions (canEdit) ----

  case 'save':
    // Save annotation (image + YOLO label file)
    if (!canEdit('Events')) {
      ajaxError('Insufficient permissions');
      break;
    }
    if (empty($_REQUEST['eid']) || !isset($_REQUEST['fid'])) {
      ajaxError('Event ID and Frame ID required');
      break;
    }

    $eid = validCardinal($_REQUEST['eid']);
    $fid = $_REQUEST['fid'];

    if (!validFrameId($fid)) {
      ajaxError('Invalid frame ID');
      break;
    }

    $Event = ZM\Event::find_one(['Id' => $eid]);
    if (!$Event) {
      ajaxError('Event not found');
      break;
    }

    if (!ensureTrainingDirs()) break;

    $annotations = json_decode($_REQUEST['annotations'], true);
    if (!is_array($annotations)) {
      ajaxError('Invalid annotations data');
      break;
    }

    $imgWidth = intval($_REQUEST['width']);
    $imgHeight = intval($_REQUEST['height']);
    if ($imgWidth <= 0 || $imgHeight <= 0) {
      ajaxError('Invalid image dimensions');
      break;
    }

    $base = getTrainingDataDir();
    $stem = 'event_'.$eid.'_frame_'.$fid;

    // Copy the frame image to training dir
    $eventPath = $Event->Path();
    if (in_array($fid, ['alarm', 'snapshot'])) {
      $srcImage = $eventPath.'/'.$fid.'.jpg';
    } else {
      $srcImage = $eventPath.'/'.sprintf('%0'.ZM_EVENT_IMAGE_DIGITS.'d', $fid).'-capture.jpg';
    }

    if (!file_exists($srcImage)) {
      ZM\Warning('Training: source image not found: '.$srcImage);
      ajaxError('Source frame image not found');
      break;
    }

    $dstImage = $base.'/images/all/'.$stem.'.jpg';
    ZM\Debug('Training: copying '.$srcImage.' to '.$dstImage);
    if (!copy($srcImage, $dstImage)) {
      ZM\Error('Training: failed to copy image from '.$srcImage.' to '.$dstImage);
      ajaxError('Failed to copy image');
      break;
    }

    // Validate and sanitize annotation labels
    foreach ($annotations as $ann) {
      if (!isset($ann['label']) || !preg_match('/^[a-zA-Z0-9_-]+$/', $ann['label'])) {
        ajaxError('Invalid label: labels must contain only letters, numbers, hyphens, and underscores');
        break 2;
      }
    }

    // Get current labels from data.yaml, add any new ones
    $labels = getClassLabels();
    foreach ($annotations as $ann) {
      if (!in_array($ann['label'], $labels)) {
        $labels[] = $ann['label'];
      }
    }

    // Write YOLO label file (empty file = background/negative image)
    $labelLines = [];
    foreach ($annotations as $ann) {
      $classId = array_search($ann['label'], $labels);
      // Convert pixel coords [x1, y1, x2, y2] to YOLO normalized [cx, cy, w, h]
      $cx = max(0.0, min(1.0, (($ann['x1'] + $ann['x2']) / 2) / $imgWidth));
      $cy = max(0.0, min(1.0, (($ann['y1'] + $ann['y2']) / 2) / $imgHeight));
      $w  = max(0.0, min(1.0, ($ann['x2'] - $ann['x1']) / $imgWidth));
      $h  = max(0.0, min(1.0, ($ann['y2'] - $ann['y1']) / $imgHeight));
      $labelLines[] = sprintf('%d %.6f %.6f %.6f %.6f', $classId, $cx, $cy, $w, $h);
    }
    $dstLabel = $base.'/labels/all/'.$stem.'.txt';
    file_put_contents($dstLabel, empty($labelLines) ? '' : implode("\n", $labelLines)."\n");

    // Write data.yaml with the current label list (only if we have labels)
    if (!empty($labels)) {
      writeDataYaml($labels);
    }

    $savedType = empty($annotations) ? 'background' : count($annotations).' annotation(s)';
    ZM\Debug('Training: saved '.$savedType.' for event '.$eid.' frame '.$fid);

    $stats = getTrainingStats();

    ajaxResponse([
      'saved' => true,
      'annotations_count' => count($annotations),
      'stats' => $stats,
    ]);
    break;

  case 'delete':
    // Remove a saved annotation
    if (!canEdit('Events')) {
      ajaxError('Insufficient permissions');
      break;
    }
    if (empty($_REQUEST['eid']) || !isset($_REQUEST['fid'])) {
      ajaxError('Event ID and Frame ID required');
      break;
    }

    $eid = validCardinal($_REQUEST['eid']);
    $fid = $_REQUEST['fid'];

    if (!validFrameId($fid)) {
      ajaxError('Invalid frame ID');
      break;
    }

    $base = getTrainingDataDir();
    if ($base === '') {
      ajaxError('Training data directory not configured');
      break;
    }
    $stem = 'event_'.$eid.'_frame_'.$fid;

    $imgFile = $base.'/images/all/'.$stem.'.jpg';
    $lblFile = $base.'/labels/all/'.$stem.'.txt';

    $deleted = false;
    if (file_exists($imgFile)) { unlink($imgFile); $deleted = true; }
    if (file_exists($lblFile)) { unlink($lblFile); $deleted = true; }

    if ($deleted) {
      ZM\Debug('Training: removed annotation for event '.$eid.' frame '.$fid);
    }

    ajaxResponse([
      'deleted' => $deleted,
      'stats' => getTrainingStats(),
    ]);
    break;

  case 'delete_all':
    // Delete ALL training data (images, labels, data.yaml)
    if (!canEdit('Events')) {
      ajaxError('Insufficient permissions');
      break;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      ajaxError('POST method required for destructive operations');
      break;
    }

    $base = getTrainingDataDir();
    if ($base === '') {
      ajaxError('Training data directory not configured');
      break;
    }
    $deleted = 0;
    foreach (['images/all', 'labels/all'] as $sub) {
      $dir = $base.'/'.$sub;
      if (is_dir($dir)) {
        foreach (glob($dir.'/*') as $file) {
          if (is_file($file)) { unlink($file); $deleted++; }
        }
      }
    }
    $yamlFile = $base.'/data.yaml';
    if (file_exists($yamlFile)) { unlink($yamlFile); $deleted++; }

    ZM\Warning('Training: deleted ALL training data ('.$deleted.' files)');
    ajaxResponse([
      'deleted' => $deleted,
      'stats' => getTrainingStats(),
    ]);
    break;

  case 'browse_delete':
    // Delete an image/label pair by file path, then update data.yaml
    if (!canEdit('Events')) {
      ajaxError('Insufficient permissions');
      break;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      ajaxError('POST method required for destructive operations');
      break;
    }
    if (empty($_REQUEST['path'])) {
      ajaxError('Path required');
      break;
    }

    $base = getTrainingDataDir();
    if ($base === '') {
      ajaxError('Training data directory not configured');
      break;
    }
    $reqPath = detaintPath($_REQUEST['path']);
    $fullPath = realpath($base.'/'.$reqPath);

    if ($fullPath === false || strpos($fullPath, realpath($base)) !== 0 || !is_file($fullPath)) {
      ajaxError('File not found or access denied');
      break;
    }

    // Determine the stem and delete both image + label
    $stem = pathinfo(basename($fullPath), PATHINFO_FILENAME);
    $imgFile = $base.'/images/all/'.$stem.'.jpg';
    $lblFile = $base.'/labels/all/'.$stem.'.txt';

    $deletedFiles = [];
    ZM\Debug('Training: browse_delete stem='.$stem);
    if (file_exists($imgFile)) { unlink($imgFile); $deletedFiles[] = 'images/all/'.$stem.'.jpg'; }
    if (file_exists($lblFile)) { unlink($lblFile); $deletedFiles[] = 'labels/all/'.$stem.'.txt'; }

    // Rebuild data.yaml and remap class IDs in remaining label files
    $labelsDir = $base.'/labels/all';
    $usedClasses = [];
    if (is_dir($labelsDir)) {
      foreach (glob($labelsDir.'/*.txt') as $lf) {
        foreach (file($lf, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
          $parts = explode(' ', trim($line));
          if (count($parts) >= 5) {
            $usedClasses[intval($parts[0])] = true;
          }
        }
      }
    }

    // Build old->new class ID mapping, keeping only classes still in use
    $oldLabels = getClassLabels();
    $newLabels = [];
    $idMap = []; // oldId => newId
    if (!empty($oldLabels)) {
      $newId = 0;
      foreach ($oldLabels as $oldId => $label) {
        if (isset($usedClasses[$oldId])) {
          $idMap[$oldId] = $newId;
          $newLabels[] = $label;
          $newId++;
        }
      }
    }

    // Remap class IDs in all remaining label files if any IDs changed
    $needsRemap = false;
    foreach ($idMap as $old => $new) {
      if ($old !== $new) { $needsRemap = true; break; }
    }
    if ($needsRemap && is_dir($labelsDir)) {
      foreach (glob($labelsDir.'/*.txt') as $lf) {
        $lines = file($lf, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $newLines = [];
        foreach ($lines as $line) {
          $parts = explode(' ', trim($line));
          if (count($parts) >= 5) {
            $oldId = intval($parts[0]);
            $parts[0] = isset($idMap[$oldId]) ? $idMap[$oldId] : $parts[0];
            $newLines[] = implode(' ', $parts);
          }
        }
        file_put_contents($lf, empty($newLines) ? '' : implode("\n", $newLines)."\n");
      }
    }

    // Write updated data.yaml or remove if empty
    if (!empty($newLabels)) {
      writeDataYaml($newLabels);
    } else {
      $yamlFile = $base.'/data.yaml';
      if (file_exists($yamlFile)) unlink($yamlFile);
    }

    ajaxResponse([
      'deleted' => $deletedFiles,
      'stats' => getTrainingStats(),
    ]);
    break;

  case 'detect':
    // Run object detection script on a frame image
    if (!canEdit('Events')) {
      ajaxError('Insufficient permissions');
      break;
    }
    if (!defined('ZM_TRAINING_DETECT_SCRIPT') || ZM_TRAINING_DETECT_SCRIPT == '') {
      ajaxError('No detection script configured');
      break;
    }
    if (empty($_REQUEST['eid']) || !isset($_REQUEST['fid'])) {
      ajaxError('Event ID and Frame ID required');
      break;
    }

    $eid = validCardinal($_REQUEST['eid']);
    $fid = $_REQUEST['fid'];
    if (!validFrameId($fid)) {
      ajaxError('Invalid frame ID');
      break;
    }

    $Event = ZM\Event::find_one(['Id' => $eid]);
    if (!$Event) {
      ajaxError('Event not found');
      break;
    }

    $eventPath = $Event->Path();
    if (in_array($fid, ['alarm', 'snapshot'])) {
      $srcImage = $eventPath.'/'.$fid.'.jpg';
    } else {
      $srcImage = $eventPath.'/'.sprintf('%0'.ZM_EVENT_IMAGE_DIGITS.'d', $fid).'-capture.jpg';
    }

    if (!file_exists($srcImage)) {
      ajaxError('Source frame image not found');
      break;
    }

    $script = ZM_TRAINING_DETECT_SCRIPT;
    if (!file_exists($script) || !is_executable($script)) {
      ajaxError('Detection script not found or not executable');
      break;
    }

    // Copy to temp file so the script can read it
    $tmpFile = tempnam(sys_get_temp_dir(), 'zm_detect_').'.jpg';
    if (!copy($srcImage, $tmpFile)) {
      ajaxError('Failed to create temp file for detection');
      break;
    }

    $monitorId = $Event->MonitorId();
    $cmd = escapeshellarg($script).' -f '.escapeshellarg($tmpFile).' -m '.escapeshellarg($monitorId).' 2>&1';
    ZM\Debug('Training: running detect command: '.$cmd);
    exec($cmd, $outputLines, $exitCode);
    $output = implode("\n", $outputLines);
    if (file_exists($tmpFile)) unlink($tmpFile);

    ZM\Debug('Training: detect script exit code='.$exitCode.' output length='.strlen($output));
    if ($exitCode !== 0 && empty($output)) {
      ZM\Warning('Training: detect script failed with exit code '.$exitCode.' for event '.$eid.' frame '.$fid);
      ajaxError('Detection script failed (exit code '.$exitCode.')');
      break;
    }

    // Parse output: "PREFIX detected:labels--SPLIT--{JSON}"
    $detections = [];
    if (strpos($output, '--SPLIT--') !== false) {
      $parts = explode('--SPLIT--', $output, 2);
      $json = json_decode(trim($parts[1]), true);
      if ($json && isset($json['labels']) && isset($json['boxes'])) {
        for ($i = 0; $i < count($json['labels']); $i++) {
          $box = isset($json['boxes'][$i]) ? $json['boxes'][$i] : [0,0,0,0];
          $conf = isset($json['confidences'][$i]) ? $json['confidences'][$i] : 0;
          $detections[] = [
            'label' => $json['labels'][$i],
            'confidence' => $conf,
            'bbox' => $box,
          ];
        }
      }
    }

    ZM\Debug('Training: detect found '.count($detections).' objects for event '.$eid.' frame '.$fid);
    ajaxResponse([
      'detections' => $detections,
      'raw_output' => $output,
    ]);
    break;

  default:
    ajaxError('Unknown action');
    break;
}
?>
