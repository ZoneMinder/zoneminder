<?php
ini_set('display_errors', '0');

// Training features require the option to be enabled
if (!defined('ZM_OPT_TRAINING') or !ZM_OPT_TRAINING) {
  ajaxError('Training features are not enabled');
  return;
}

if (!canEdit('Events')) {
  ajaxError('Insufficient permissions');
  return;
}

require_once('includes/Event.php');

/**
 * Get the training data directory path.
 * Falls back to ZM_DIR_EVENTS/../training if ZM_TRAINING_DATA_DIR is empty.
 */
function getTrainingDataDir() {
  if (defined('ZM_TRAINING_DATA_DIR') && ZM_TRAINING_DATA_DIR != '') {
    return ZM_TRAINING_DATA_DIR;
  }
  return dirname(ZM_DIR_EVENTS) . '/training';
}

/**
 * Ensure the training directory structure exists.
 * Creates images/all/ and labels/all/ subdirectories.
 */
function ensureTrainingDirs() {
  $base = getTrainingDataDir();
  $dirs = [$base, $base.'/images', $base.'/images/all', $base.'/labels', $base.'/labels/all'];
  foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
      if (!mkdir($dir, 0755, true)) {
        ajaxError('Failed to create directory: '.$dir);
        return false;
      }
    }
  }
  return true;
}

/**
 * Get current class labels from data.yaml in the training directory.
 * Returns array of label strings ordered by class ID.
 */
function getClassLabels() {
  $base = getTrainingDataDir();
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
}

/**
 * Collect training dataset statistics:
 * total images, total classes, images per class.
 */
function getTrainingStats() {
  $base = getTrainingDataDir();
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

switch ($_REQUEST['action']) {

  case 'load':
    // Load detection data for an event
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

    if (file_exists($objectsFile)) {
      $json = file_get_contents($objectsFile);
      $detectionData = json_decode($json, true);
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
    $savedFile = $base.'/labels/all/event_'.$eid.'_frame_'.$fid.'.txt';
    $hasSavedAnnotation = file_exists($savedFile);

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

  case 'save':
    // Save annotation (image + YOLO label file)
    if (empty($_REQUEST['eid']) || !isset($_REQUEST['fid'])) {
      ajaxError('Event ID and Frame ID required');
      break;
    }

    $eid = validCardinal($_REQUEST['eid']);
    $fid = $_REQUEST['fid'];

    // Validate fid is either a known special name or a positive integer
    if (!in_array($fid, ['alarm', 'snapshot']) && !ctype_digit($fid)) {
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
      ajaxError('Source frame image not found: '.$fid);
      break;
    }

    $dstImage = $base.'/images/all/'.$stem.'.jpg';
    if (!copy($srcImage, $dstImage)) {
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
    ZM\Info('Saved '.$savedType.' for training event '.$eid.' frame '.$fid);

    $stats = getTrainingStats();

    ajaxResponse([
      'saved' => true,
      'annotations_count' => count($annotations),
      'stats' => $stats,
    ]);
    break;

  case 'labels':
    // Return current class label list
    $labels = getClassLabels();
    ajaxResponse(['labels' => $labels]);
    break;

  case 'delete':
    // Remove a saved annotation
    if (empty($_REQUEST['eid']) || !isset($_REQUEST['fid'])) {
      ajaxError('Event ID and Frame ID required');
      break;
    }

    $eid = validCardinal($_REQUEST['eid']);
    $fid = $_REQUEST['fid'];

    // Validate fid
    if (!in_array($fid, ['alarm', 'snapshot']) && !ctype_digit($fid)) {
      ajaxError('Invalid frame ID');
      break;
    }

    $base = getTrainingDataDir();
    $stem = 'event_'.$eid.'_frame_'.$fid;

    $imgFile = $base.'/images/all/'.$stem.'.jpg';
    $lblFile = $base.'/labels/all/'.$stem.'.txt';

    $deleted = false;
    if (file_exists($imgFile)) { unlink($imgFile); $deleted = true; }
    if (file_exists($lblFile)) { unlink($lblFile); $deleted = true; }

    if ($deleted) {
      ZM\Info('Removed training annotation for event '.$eid.' frame '.$fid);
    }

    ajaxResponse([
      'deleted' => $deleted,
      'stats' => getTrainingStats(),
    ]);
    break;

  case 'status':
    // Return training dataset statistics
    ajaxResponse(['stats' => getTrainingStats()]);
    break;

  case 'delete_all':
    // Delete ALL training data (images, labels, data.yaml)
    $base = getTrainingDataDir();
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

    ZM\Info('Deleted all training data ('.$deleted.' files)');
    ajaxResponse([
      'deleted' => $deleted,
      'stats' => getTrainingStats(),
    ]);
    break;

  case 'detect':
    // Run object detection script on a frame image
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
    if (!in_array($fid, ['alarm', 'snapshot']) && !ctype_digit($fid)) {
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
      ajaxError('Source frame image not found: '.$fid);
      break;
    }

    // Copy to temp file so the script can read it
    $tmpFile = tempnam(sys_get_temp_dir(), 'zm_detect_');
    rename($tmpFile, $tmpFile.'.jpg');
    $tmpFile = $tmpFile.'.jpg';
    copy($srcImage, $tmpFile);

    $script = ZM_TRAINING_DETECT_SCRIPT;
    if (!file_exists($script)) {
      unlink($tmpFile);
      ajaxError('Detection script not found: '.$script);
      break;
    }

    $monitorId = $Event->MonitorId();
    $cmd = escapeshellarg($script).' -f '.escapeshellarg($tmpFile).' -m '.escapeshellarg($monitorId).' 2>&1';
    exec($cmd, $outputLines, $exitCode);
    $output = implode("\n", $outputLines);
    unlink($tmpFile);

    if ($exitCode !== 0 && empty($output)) {
      ajaxError('Detection script failed to execute (exit code '.$exitCode.')');
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

    ajaxResponse([
      'detections' => $detections,
      'raw_output' => $output,
    ]);
    break;

  default:
    ajaxError('Unknown action: '.$_REQUEST['action']);
    break;
}
?>
