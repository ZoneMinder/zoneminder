# Custom Model Training Annotation UI — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add an integrated bounding-box annotation editor to ZoneMinder's event view that lets users correct object detection results and save them in Roboflow-compatible YOLO format for custom model training via pyzm.

**Architecture:** New ZM Config options gate the feature. A Canvas-based annotation editor opens inline on the event view. A new AJAX handler (`web/ajax/training.php`) loads detection data and saves corrected annotations to a configurable directory in YOLO format. No C++ changes, no new dependencies.

**Tech Stack:** PHP 7+, HTML5 Canvas, vanilla JS + jQuery (existing ZM stack), MySQL Config table, YOLO annotation format.

**Design doc:** `docs/plans/2026-02-28-custom-model-training-design.md`

---

## Task 1: Add Config Options to ConfigData.pm.in

Add the three new ZM options that gate and configure the training feature.

**Files:**
- Modify: `scripts/ZoneMinder/lib/ZoneMinder/ConfigData.pm.in:4049` (before closing `);` of `@options`)

**Step 1: Add config entries**

Insert before line 4050 (the closing `);` of `@options`):

```perl
  {
    name        => 'ZM_OPT_TRAINING',
    default     => 'no',
    description => 'Enable custom model training features',
    help        => q`
      Enable annotation tools on the event view for correcting
      object detection results. Corrected annotations are saved
      in YOLO format for training custom models via pyzm.
      `,
    type        => $types{boolean},
    category    => 'config',
  },
  {
    name        => 'ZM_TRAINING_DATA_DIR',
    default     => '@ZM_CACHEDIR@/training',
    description => 'Training data directory',
    help        => q`
      Filesystem path where corrected annotation images and YOLO
      label files are stored. The directory will be created
      automatically if it does not exist. Uses Roboflow-compatible
      YOLO directory layout (images/all/, labels/all/, data.yaml).
      `,
    type        => $types{string},
    category    => 'config',
    requires    => [ { name => 'ZM_OPT_TRAINING', value => 'yes' } ],
  },
  {
    name        => 'ZM_TRAINING_LABELS',
    default     => '',
    description => 'Training class labels',
    help        => q`
      Comma-separated list of object class labels for annotation
      (e.g. person,car,dog). Auto-populated from detected objects.
      New labels can be added during annotation. The order of labels
      determines class IDs in YOLO files and must not be changed
      once training has started.
      `,
    type        => $types{string},
    category    => 'config',
    requires    => [ { name => 'ZM_OPT_TRAINING', value => 'yes' } ],
  },
```

**Step 2: Verify build generates the config**

Run:
```bash
cd build && cmake .. && cmake --build . --target generate_config
```

If `generate_config` target does not exist, a full `cmake --build .` will invoke `zmconfgen.pl` which reads ConfigData.pm.in and generates the SQL inserts into `db/zm_create.sql`. Verify the three new options appear:

```bash
grep -c ZM_OPT_TRAINING build/db/zm_create.sql
```
Expected: at least 1 match.

**Step 3: Commit**

```bash
git add scripts/ZoneMinder/lib/ZoneMinder/ConfigData.pm.in
git commit -m "feat: add ZM_OPT_TRAINING config options for custom model training

Adds three new Config entries:
- ZM_OPT_TRAINING: master toggle for training features
- ZM_TRAINING_DATA_DIR: configurable path for YOLO training data
- ZM_TRAINING_LABELS: comma-separated class label list

ZM_TRAINING_DATA_DIR defaults to @ZM_CACHEDIR@/training to respect
the install prefix. The latter two options require ZM_OPT_TRAINING."
```

---

## Task 2: Add Database Migration

Create the migration file for existing installations. The `zmconfgen.pl` handles fresh installs via ConfigData.pm.in, but existing installs need a migration to add the new Config rows.

**Files:**
- Create: `db/zm_update-1.39.2.sql`

**Step 1: Create migration file**

```sql
--
-- Add custom model training configuration options
--

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM Config WHERE Name='ZM_OPT_TRAINING') > 0,
  "SELECT 'ZM_OPT_TRAINING already exists'",
  "INSERT INTO Config SET
    Name='ZM_OPT_TRAINING',
    Value='0',
    Type='boolean',
    DefaultValue='0',
    Hint='yes|no',
    Prompt='Enable custom model training features',
    Help='Enable annotation tools on the event view for correcting object detection results. Corrected annotations are saved in YOLO format for training custom models via pyzm.',
    Category='config',
    Readonly='0',
    Private='0',
    System='0',
    Requires=''"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM Config WHERE Name='ZM_TRAINING_DATA_DIR') > 0,
  "SELECT 'ZM_TRAINING_DATA_DIR already exists'",
  "INSERT INTO Config SET
    Name='ZM_TRAINING_DATA_DIR',
    Value='',
    Type='string',
    DefaultValue='',
    Hint='',
    Prompt='Training data directory',
    Help='Filesystem path where corrected annotation images and YOLO label files are stored. The directory will be created automatically if it does not exist. Uses Roboflow-compatible YOLO directory layout (images/all/, labels/all/, data.yaml).',
    Category='config',
    Readonly='0',
    Private='0',
    System='0',
    Requires='ZM_OPT_TRAINING'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM Config WHERE Name='ZM_TRAINING_LABELS') > 0,
  "SELECT 'ZM_TRAINING_LABELS already exists'",
  "INSERT INTO Config SET
    Name='ZM_TRAINING_LABELS',
    Value='',
    Type='string',
    DefaultValue='',
    Hint='',
    Prompt='Training class labels',
    Help='Comma-separated list of object class labels for annotation (e.g. person,car,dog). Auto-populated from detected objects. New labels can be added during annotation. The order of labels determines class IDs in YOLO files and must not be changed once training has started.',
    Category='config',
    Readonly='0',
    Private='0',
    System='0',
    Requires='ZM_OPT_TRAINING'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
```

Note: The migration uses `ZM_TRAINING_DATA_DIR` with an empty default because the CMake variable `@ZM_CACHEDIR@` is not available at migration time. The PHP code will fall back to `ZM_DIR_EVENTS . '/../training'` or similar at runtime if the value is empty.

**Step 2: Commit**

```bash
git add db/zm_update-1.39.2.sql
git commit -m "feat: add database migration for training config options

Migration zm_update-1.39.2.sql adds ZM_OPT_TRAINING,
ZM_TRAINING_DATA_DIR, and ZM_TRAINING_LABELS to Config table
for existing installations. Uses IF/PREPARE pattern for
idempotent execution."
```

---

## Task 3: Add i18n Strings

Add all translatable strings for the annotation UI.

**Files:**
- Modify: `web/lang/en_gb.php` (primary language file with `$SLANG` and `$OLANG`)
- Modify: `web/lang/en_us.php` (US overrides, inherits from en_gb.php)

**Step 1: Add $SLANG entries to en_gb.php**

Find the `$SLANG` array (entries are in alphabetical order by convention) and add:

```php
$SLANG['AddLabel']             = 'Add Label';
$SLANG['Annotate']             = 'Annotate';
$SLANG['AnnotationEditor']     = 'Annotation Editor';
$SLANG['AnnotationSaved']      = 'Annotation saved to training set';
$SLANG['AnnotationsRemoved']   = 'Annotation removed from training set';
$SLANG['Cancel']               = 'Cancel';  // may already exist
$SLANG['ClassLabel']           = 'Label';
$SLANG['DeleteBox']            = 'Delete Box';
$SLANG['DrawBox']              = 'Draw a bounding box around the object';
$SLANG['GoToFrame']            = 'Go to Frame';
$SLANG['ImagesPerClass']       = 'Images per class';
$SLANG['NewLabel']             = 'New Label';
$SLANG['NextFrame']            = 'Next Frame';
$SLANG['NoDetectionData']      = 'No detection data available for this event';
$SLANG['PreviousFrame']        = 'Previous Frame';
$SLANG['SaveToTrainingSet']    = 'Save to Training Set';
$SLANG['SelectLabel']          = 'Select a label for this object';
$SLANG['TotalAnnotatedImages'] = 'Total annotated images';
$SLANG['TotalClasses']         = 'Total classes';
$SLANG['TrainingDataStats']    = 'Training Data Statistics';
$SLANG['TrainingGuidance']     = 'Training is generally possible with at least 50-100 images per class. For best results, aim for 200+ images per class with varied angles and lighting conditions.';
$SLANG['UnsavedAnnotations']   = 'You have unsaved annotations. Discard changes?';
```

Insert each entry at its alphabetical position within the existing `$SLANG` array.

**Step 2: Add $OLANG help entries to en_gb.php**

Find the `$OLANG` array and add entries for the three Config options:

```php
$OLANG['ZM_OPT_TRAINING'] = array(
  'Prompt' => 'Enable custom model training features',
  'Help' => 'Enable annotation tools on the event view for correcting object detection results.~~Corrected annotations are saved in YOLO format for training custom models via pyzm.~~When enabled, an Annotate button appears on the event view toolbar.'
);

$OLANG['ZM_TRAINING_DATA_DIR'] = array(
  'Prompt' => 'Training data directory',
  'Help' => 'Filesystem path where corrected annotation images and YOLO label files are stored.~~The directory will be created automatically if it does not exist.~~Uses Roboflow-compatible YOLO directory layout:~~images/all/ - annotated frame images~~labels/all/ - YOLO format label files~~data.yaml - class definitions'
);

$OLANG['ZM_TRAINING_LABELS'] = array(
  'Prompt' => 'Training class labels',
  'Help' => 'Comma-separated list of object class labels for annotation (e.g. person,car,dog).~~Auto-populated from detected objects. New labels can be added during annotation.~~IMPORTANT: The order of labels determines class IDs in YOLO files.~~Do not reorder labels once training has started.'
);
```

**Step 3: Verify no lint errors**

```bash
php -l web/lang/en_gb.php
php -l web/lang/en_us.php
```
Expected: `No syntax errors detected`

**Step 4: Commit**

```bash
git add web/lang/en_gb.php web/lang/en_us.php
git commit -m "feat: add i18n strings for annotation editor UI

Adds SLANG entries for all annotation editor UI strings and
OLANG help text for the three training Config options."
```

---

## Task 4: Create AJAX Backend (web/ajax/training.php)

The backend handles loading detection data, saving annotations, managing labels, and returning training dataset statistics.

**Files:**
- Create: `web/ajax/training.php`

**Step 1: Create the AJAX handler**

Follow the pattern in `web/ajax/event.php` — check permissions at entry, switch on action.

```php
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
 * Get current class labels from ZM_TRAINING_LABELS config.
 * Returns array of label strings.
 */
function getClassLabels() {
  if (defined('ZM_TRAINING_LABELS') && ZM_TRAINING_LABELS != '') {
    return array_map('trim', explode(',', ZM_TRAINING_LABELS));
  }
  return [];
}

/**
 * Regenerate data.yaml in the training directory.
 */
function regenerateDataYaml() {
  $base = getTrainingDataDir();
  $labels = getClassLabels();
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
  $stats = [
    'total_images' => 0,
    'total_classes' => 0,
    'images_per_class' => [],
    'class_labels' => getClassLabels(),
  ];

  if (!is_dir($labelsDir)) return $stats;

  $labels = getClassLabels();
  $classCounts = array_fill(0, count($labels), 0);

  $files = glob($labelsDir.'/*.txt');
  $stats['total_images'] = count($files);

  foreach ($files as $file) {
    $seenClasses = [];
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
    foreach (['alarm', 'snapshot', 'objdetect'] as $special) {
      if (file_exists($eventPath.'/'.$special.'.jpg')) {
        $availableFrames[] = $special;
      }
    }

    // Also check if we already have a saved annotation for this event
    $base = getTrainingDataDir();
    $fid = $defaultFrameId ?: 'alarm';
    $savedFile = $base.'/labels/all/event_'.$eid.'_frame_'.$fid.'.txt';
    $hasSavedAnnotation = file_exists($savedFile);

    ajaxResponse([
      'detectionData' => $detectionData,
      'defaultFrameId' => $defaultFrameId,
      'availableFrames' => $availableFrames,
      'totalFrames' => $Event->Frames(),
      'eventPath' => $Event->Relative_Path(),
      'width' => $Event->Width(),
      'height' => $Event->Height(),
      'hasSavedAnnotation' => $hasSavedAnnotation,
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
    if (!in_array($fid, ['alarm', 'snapshot', 'objdetect']) && !ctype_digit($fid)) {
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
    if (in_array($fid, ['alarm', 'snapshot', 'objdetect'])) {
      $srcImage = $eventPath.'/'.$fid.'.jpg';
    } else {
      $srcImage = $eventPath.'/'.sprintf('%06d', $fid).'-capture.jpg';
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

    // Get current labels, add any new ones
    $labels = getClassLabels();
    $labelsChanged = false;
    foreach ($annotations as $ann) {
      if (!in_array($ann['label'], $labels)) {
        $labels[] = $ann['label'];
        $labelsChanged = true;
      }
    }

    if ($labelsChanged) {
      // Update ZM_TRAINING_LABELS in database
      $newLabelsStr = implode(',', $labels);
      dbQuery('UPDATE Config SET Value=? WHERE Name=?', [$newLabelsStr, 'ZM_TRAINING_LABELS']);
    }

    // Write YOLO label file
    $labelLines = [];
    foreach ($annotations as $ann) {
      $classId = array_search($ann['label'], $labels);
      // Convert pixel coords [x1, y1, x2, y2] to YOLO normalized [cx, cy, w, h]
      $cx = (($ann['x1'] + $ann['x2']) / 2) / $imgWidth;
      $cy = (($ann['y1'] + $ann['y2']) / 2) / $imgHeight;
      $w  = ($ann['x2'] - $ann['x1']) / $imgWidth;
      $h  = ($ann['y2'] - $ann['y1']) / $imgHeight;
      $labelLines[] = sprintf('%d %.6f %.6f %.6f %.6f', $classId, $cx, $cy, $w, $h);
    }
    $dstLabel = $base.'/labels/all/'.$stem.'.txt';
    file_put_contents($dstLabel, implode("\n", $labelLines)."\n");

    // Regenerate data.yaml
    regenerateDataYaml();

    // Audit log
    ZM\AuditAction('update', 'training', $eid,
      'Saved '.count($annotations).' annotations for event '.$eid.' frame '.$fid);

    $stats = getTrainingStats();

    ajaxResponse([
      'saved' => true,
      'annotations_count' => count($annotations),
      'stats' => $stats,
    ]);
    break;

  case 'labels':
    // Return current class label list + auto-discovered labels
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
    $base = getTrainingDataDir();
    $stem = 'event_'.$eid.'_frame_'.$fid;

    $imgFile = $base.'/images/all/'.$stem.'.jpg';
    $lblFile = $base.'/labels/all/'.$stem.'.txt';

    $deleted = false;
    if (file_exists($imgFile)) { unlink($imgFile); $deleted = true; }
    if (file_exists($lblFile)) { unlink($lblFile); $deleted = true; }

    if ($deleted) {
      regenerateDataYaml();
      ZM\AuditAction('delete', 'training', $eid,
        'Removed annotation for event '.$eid.' frame '.$fid);
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

  default:
    ajaxError('Unknown action: '.$_REQUEST['action']);
    break;
}
?>
```

**Step 2: Verify PHP syntax**

```bash
php -l web/ajax/training.php
```
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add web/ajax/training.php
git commit -m "feat: add AJAX backend for annotation training data

Handles load (detection data from objects.json), save (image + YOLO
label file), delete, labels management, and training dataset
statistics. Writes Roboflow-compatible YOLO format to configurable
training directory."
```

---

## Task 5: Add CSS for Annotation Editor

**Files:**
- Create: `web/skins/classic/css/base/views/training.css`

Note: ZM auto-loads `css/base/views/{basename}.css` for the current view. Since our editor is part of the `event` view, we will name the file to match the event view loading OR explicitly include it. Since the annotation panel is conditionally loaded on the event view (not its own view), we should include it from event.php directly. The file should be named descriptively.

**Step 1: Create the CSS file**

```css
/* Annotation Editor Panel - Custom Model Training */

#annotationPanel {
  display: none;
  border-top: 2px solid #dee2e6;
  padding: 15px;
  margin-top: 10px;
  background: #f8f9fa;
}

#annotationPanel.open {
  display: block;
}

/* Frame selector bar */
.annotation-frame-selector {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.annotation-frame-selector .btn {
  padding: 4px 10px;
  font-size: 0.85rem;
}

.annotation-frame-selector .frame-input {
  width: 80px;
  display: inline-block;
}

/* Canvas + sidebar layout */
.annotation-workspace {
  display: flex;
  gap: 15px;
  align-items: flex-start;
}

/* Canvas container */
.annotation-canvas-container {
  position: relative;
  flex: 1;
  min-width: 0;
  border: 1px solid #ced4da;
  background: #000;
  overflow: hidden;
}

.annotation-canvas-container canvas {
  display: block;
  width: 100%;
  cursor: crosshair;
}

.annotation-canvas-container canvas.mode-select {
  cursor: default;
}

.annotation-canvas-container canvas.mode-move {
  cursor: move;
}

.annotation-canvas-container canvas.mode-resize-nw,
.annotation-canvas-container canvas.mode-resize-se {
  cursor: nwse-resize;
}

.annotation-canvas-container canvas.mode-resize-ne,
.annotation-canvas-container canvas.mode-resize-sw {
  cursor: nesw-resize;
}

.annotation-canvas-container canvas.mode-resize-n,
.annotation-canvas-container canvas.mode-resize-s {
  cursor: ns-resize;
}

.annotation-canvas-container canvas.mode-resize-e,
.annotation-canvas-container canvas.mode-resize-w {
  cursor: ew-resize;
}

/* Object sidebar */
.annotation-sidebar {
  width: 240px;
  min-width: 240px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  background: #fff;
  max-height: 500px;
  overflow-y: auto;
}

.annotation-sidebar-header {
  padding: 8px 12px;
  font-weight: 600;
  border-bottom: 1px solid #dee2e6;
  background: #e9ecef;
  font-size: 0.9rem;
}

.annotation-object-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.annotation-object-item {
  display: flex;
  align-items: center;
  padding: 6px 12px;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  font-size: 0.85rem;
}

.annotation-object-item:hover {
  background: #f0f7ff;
}

.annotation-object-item.selected {
  background: #d4e8ff;
}

.annotation-object-item .color-swatch {
  width: 12px;
  height: 12px;
  border-radius: 2px;
  margin-right: 8px;
  flex-shrink: 0;
}

.annotation-object-item .object-label {
  flex: 1;
}

.annotation-object-item .object-confidence {
  color: #6c757d;
  font-size: 0.8rem;
  margin-left: 4px;
}

.annotation-object-item .btn-remove {
  padding: 0 4px;
  font-size: 0.75rem;
  line-height: 1;
  color: #dc3545;
  background: none;
  border: none;
  cursor: pointer;
  opacity: 0.6;
  margin-left: 4px;
}

.annotation-object-item .btn-remove:hover {
  opacity: 1;
}

.annotation-add-btn {
  display: block;
  width: 100%;
  padding: 6px 12px;
  text-align: left;
  font-size: 0.85rem;
  border: none;
  background: none;
  color: #007bff;
  cursor: pointer;
}

.annotation-add-btn:hover {
  background: #f0f7ff;
}

/* Training stats section in sidebar */
.annotation-stats {
  padding: 8px 12px;
  border-top: 1px solid #dee2e6;
  background: #f8f9fa;
  font-size: 0.8rem;
}

.annotation-stats-header {
  font-weight: 600;
  margin-bottom: 4px;
  font-size: 0.85rem;
}

.annotation-stats dt {
  font-weight: normal;
  color: #6c757d;
}

.annotation-stats dd {
  margin-bottom: 2px;
  margin-left: 0;
}

.annotation-stats .class-count {
  display: flex;
  justify-content: space-between;
  padding: 1px 0;
}

.annotation-stats .class-count .count {
  font-weight: 600;
}

.annotation-stats .training-guidance {
  margin-top: 6px;
  padding: 6px 8px;
  background: #fff3cd;
  border-radius: 3px;
  color: #856404;
  font-size: 0.78rem;
  line-height: 1.3;
}

.annotation-stats .training-ready {
  background: #d4edda;
  color: #155724;
}

/* Label picker dropdown (appears on new box) */
.annotation-label-picker {
  position: absolute;
  z-index: 1000;
  background: #fff;
  border: 1px solid #ced4da;
  border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  min-width: 160px;
  max-height: 200px;
  overflow-y: auto;
}

.annotation-label-picker .label-option {
  display: block;
  width: 100%;
  padding: 6px 12px;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  font-size: 0.85rem;
}

.annotation-label-picker .label-option:hover {
  background: #007bff;
  color: #fff;
}

.annotation-label-picker .new-label-input {
  display: flex;
  border-top: 1px solid #dee2e6;
  padding: 6px;
}

.annotation-label-picker .new-label-input input {
  flex: 1;
  font-size: 0.85rem;
  padding: 2px 6px;
  border: 1px solid #ced4da;
  border-radius: 3px;
}

/* Bottom action bar */
.annotation-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #dee2e6;
}

.annotation-actions .label-select {
  width: 150px;
}

.annotation-status {
  margin-left: auto;
  font-size: 0.85rem;
  color: #28a745;
}
```

**Step 2: Commit**

```bash
git add web/skins/classic/css/base/views/training.css
git commit -m "feat: add CSS styles for annotation editor panel

Styles for canvas workspace, object sidebar, label picker dropdown,
training statistics panel, and action buttons."
```

---

## Task 6: Create JavaScript Annotation Editor (web/skins/classic/views/js/training.js)

This is the largest task. The Canvas-based annotation editor with full interaction support.

**Files:**
- Create: `web/skins/classic/views/js/training.js`

**Step 1: Create the annotation editor JS**

This file implements the `AnnotationEditor` class. Due to size, here is the structure with key methods. Each method should be implemented following the interaction spec from the design doc.

```javascript
/**
 * AnnotationEditor - Canvas-based bounding box annotation editor
 * for ZoneMinder custom model training.
 *
 * Usage:
 *   var editor = new AnnotationEditor({
 *     canvasId: 'annotationCanvas',
 *     sidebarId: 'annotationObjectList',
 *     eventId: eventData.Id,
 *     translations: trainingTranslations
 *   });
 *   editor.open();
 */

// Box colors palette for class labels
var ANNOTATION_COLORS = [
  '#e6194b', '#3cb44b', '#4363d8', '#f58231', '#911eb4',
  '#42d4f4', '#f032e6', '#bfef45', '#fabed4', '#469990',
  '#dcbeff', '#9A6324', '#800000', '#aaffc3', '#808000',
  '#000075', '#a9a9a9'
];

function AnnotationEditor(options) {
  this.canvasId = options.canvasId;
  this.canvas = null;
  this.ctx = null;
  this.sidebarEl = null;
  this.statsEl = null;
  this.eventId = options.eventId;
  this.translations = options.translations || {};

  // State
  this.currentFrameId = null;
  this.annotations = [];      // [{label, x1, y1, x2, y2, confidence}]
  this.selectedIndex = -1;
  this.classLabels = [];
  this.dirty = false;

  // Image state
  this.image = null;
  this.imageNaturalW = 0;
  this.imageNaturalH = 0;

  // Drawing state
  this.isDrawing = false;
  this.drawStart = null;       // {x, y} in image space
  this.drawCurrent = null;

  // Drag/resize state
  this.isDragging = false;
  this.isResizing = false;
  this.resizeHandle = null;    // 'nw','n','ne','e','se','s','sw','w'
  this.dragOffset = null;      // {x, y}

  // Undo stack
  this.undoStack = [];
  this.maxUndo = 50;

  // Label picker
  this.labelPickerEl = null;

  // Training stats
  this.trainingStats = null;
}

AnnotationEditor.prototype = {

  /**
   * Initialize the editor: set up canvas, event listeners.
   */
  init: function() {
    this.canvas = document.getElementById(this.canvasId);
    this.ctx = this.canvas.getContext('2d');
    this.sidebarEl = document.getElementById('annotationObjectList');
    this.statsEl = document.getElementById('annotationStats');

    this._bindCanvasEvents();
    this._bindKeyboardEvents();
  },

  /**
   * Open the editor panel for the current event.
   * Loads detection data via AJAX, displays default frame.
   */
  open: function() {
    var self = this;
    var panel = document.getElementById('annotationPanel');

    $j.ajax({
      url: '?request=training&action=load&eid=' + this.eventId,
      dataType: 'json',
      success: function(data) {
        if (data.result === 'Error') {
          alert(data.message || 'Failed to load detection data');
          return;
        }

        panel.classList.add('open');

        // Store metadata
        self.imageNaturalW = parseInt(data.width);
        self.imageNaturalH = parseInt(data.height);
        self.totalFrames = parseInt(data.totalFrames);

        // Load annotations from detection data
        if (data.detectionData) {
          self._loadDetectionData(data.detectionData);
        }

        // Set default frame
        self.currentFrameId = data.defaultFrameId || 'alarm';
        self._updateFrameSelector(data.availableFrames);

        // Load frame image
        self._loadFrameImage(self.currentFrameId);

        // Load class labels
        self._loadLabels();

        // Load training stats
        self._loadStats();
      },
      error: function() {
        alert('Failed to load training data');
      }
    });
  },

  /**
   * Close the editor panel. Prompt if dirty.
   */
  close: function() {
    if (this.dirty) {
      if (!confirm(this.translations.UnsavedAnnotations ||
          'You have unsaved annotations. Discard changes?')) {
        return;
      }
    }
    document.getElementById('annotationPanel').classList.remove('open');
    this.annotations = [];
    this.selectedIndex = -1;
    this.dirty = false;
    this.undoStack = [];
    this._hideLabelPicker();
  },

  // ---- Detection Data ----

  /**
   * Convert objects.json detection data into annotation objects.
   */
  _loadDetectionData: function(data) {
    this.annotations = [];
    if (!data.labels || !data.boxes) return;

    for (var i = 0; i < data.labels.length; i++) {
      this.annotations.push({
        label: data.labels[i],
        x1: data.boxes[i][0],
        y1: data.boxes[i][1],
        x2: data.boxes[i][2],
        y2: data.boxes[i][3],
        confidence: data.confidences ? data.confidences[i] : null
      });
    }
  },

  // ---- Frame Loading ----

  /**
   * Load a frame image onto the canvas.
   */
  _loadFrameImage: function(frameId) {
    var self = this;
    var img = new Image();
    var src;

    if (['alarm', 'snapshot', 'objdetect'].indexOf(frameId) !== -1) {
      src = '?view=image&eid=' + this.eventId + '&fid=' + frameId;
    } else {
      src = '?view=image&eid=' + this.eventId + '&fid=' + frameId + '&show=capture';
    }

    img.onload = function() {
      self.image = img;
      self.imageNaturalW = img.naturalWidth;
      self.imageNaturalH = img.naturalHeight;

      // Set canvas dimensions to match image aspect ratio
      self.canvas.width = img.naturalWidth;
      self.canvas.height = img.naturalHeight;

      self._render();
      self._updateSidebar();
    };
    img.onerror = function() {
      alert('Failed to load frame image');
    };
    img.src = src;
  },

  /**
   * Switch to a different frame. Prompt if dirty.
   */
  switchFrame: function(frameId) {
    if (this.dirty) {
      if (!confirm(this.translations.UnsavedAnnotations ||
          'You have unsaved annotations. Discard changes?')) {
        return;
      }
    }
    this.currentFrameId = frameId;
    this.annotations = [];
    this.selectedIndex = -1;
    this.dirty = false;
    this.undoStack = [];
    this._hideLabelPicker();
    this._loadFrameImage(frameId);
  },

  // ---- Rendering ----

  /**
   * Render the canvas: image + all bounding boxes + handles.
   */
  _render: function() {
    if (!this.image || !this.ctx) return;

    var ctx = this.ctx;
    ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

    // Draw image
    ctx.drawImage(this.image, 0, 0);

    // Draw all annotation boxes
    for (var i = 0; i < this.annotations.length; i++) {
      this._drawBox(this.annotations[i], i === this.selectedIndex, i);
    }

    // Draw in-progress drawing box
    if (this.isDrawing && this.drawStart && this.drawCurrent) {
      ctx.setLineDash([6, 3]);
      ctx.strokeStyle = '#fff';
      ctx.lineWidth = 2;
      var x = Math.min(this.drawStart.x, this.drawCurrent.x);
      var y = Math.min(this.drawStart.y, this.drawCurrent.y);
      var w = Math.abs(this.drawCurrent.x - this.drawStart.x);
      var h = Math.abs(this.drawCurrent.y - this.drawStart.y);
      ctx.strokeRect(x, y, w, h);
      ctx.setLineDash([]);
    }
  },

  /**
   * Draw a single bounding box with label and optional handles.
   */
  _drawBox: function(ann, isSelected, index) {
    var ctx = this.ctx;
    var color = this._getColorForLabel(ann.label);
    var x = ann.x1;
    var y = ann.y1;
    var w = ann.x2 - ann.x1;
    var h = ann.y2 - ann.y1;

    // Fill with semi-transparent color
    ctx.fillStyle = color + '1a'; // 10% opacity
    ctx.fillRect(x, y, w, h);

    // Border
    ctx.strokeStyle = color;
    ctx.lineWidth = isSelected ? 3 : 2;
    ctx.strokeRect(x, y, w, h);

    // Label text above box
    var labelText = ann.label;
    if (ann.confidence !== null && ann.confidence !== undefined) {
      labelText += ' ' + Math.round(ann.confidence * 100) + '%';
    }
    ctx.font = '14px sans-serif';
    var textWidth = ctx.measureText(labelText).width;
    ctx.fillStyle = color;
    ctx.fillRect(x, y - 20, textWidth + 8, 20);
    ctx.fillStyle = '#fff';
    ctx.fillText(labelText, x + 4, y - 5);

    // Resize handles if selected
    if (isSelected) {
      var handles = this._getHandlePositions(ann);
      ctx.fillStyle = '#fff';
      ctx.strokeStyle = color;
      ctx.lineWidth = 1;
      for (var key in handles) {
        var hp = handles[key];
        ctx.fillRect(hp.x - 4, hp.y - 4, 8, 8);
        ctx.strokeRect(hp.x - 4, hp.y - 4, 8, 8);
      }
    }
  },

  /**
   * Get resize handle positions for a box.
   */
  _getHandlePositions: function(ann) {
    var mx = (ann.x1 + ann.x2) / 2;
    var my = (ann.y1 + ann.y2) / 2;
    return {
      nw: {x: ann.x1, y: ann.y1},
      n:  {x: mx,     y: ann.y1},
      ne: {x: ann.x2, y: ann.y1},
      e:  {x: ann.x2, y: my},
      se: {x: ann.x2, y: ann.y2},
      s:  {x: mx,     y: ann.y2},
      sw: {x: ann.x1, y: ann.y2},
      w:  {x: ann.x1, y: my}
    };
  },

  /**
   * Get color for a class label (consistent assignment from palette).
   */
  _getColorForLabel: function(label) {
    var idx = this.classLabels.indexOf(label);
    if (idx === -1) idx = this.annotations.findIndex(function(a) { return a.label === label; });
    if (idx === -1) idx = 0;
    return ANNOTATION_COLORS[idx % ANNOTATION_COLORS.length];
  },

  // ---- Mouse Coordinate Conversion ----

  /**
   * Convert mouse event to image-space coordinates.
   */
  _mouseToImage: function(e) {
    var rect = this.canvas.getBoundingClientRect();
    var scaleX = this.canvas.width / rect.width;
    var scaleY = this.canvas.height / rect.height;
    return {
      x: (e.clientX - rect.left) * scaleX,
      y: (e.clientY - rect.top) * scaleY
    };
  },

  // ---- Canvas Event Handlers ----

  _bindCanvasEvents: function() {
    var self = this;

    this.canvas.addEventListener('mousedown', function(e) {
      if (e.button !== 0) return; // left click only
      self._onMouseDown(e);
    });

    this.canvas.addEventListener('mousemove', function(e) {
      self._onMouseMove(e);
    });

    this.canvas.addEventListener('mouseup', function(e) {
      if (e.button !== 0) return;
      self._onMouseUp(e);
    });

    this.canvas.addEventListener('contextmenu', function(e) {
      e.preventDefault();
      // Cancel drawing
      if (self.isDrawing) {
        self.isDrawing = false;
        self.drawStart = null;
        self.drawCurrent = null;
        self._render();
      }
    });
  },

  _onMouseDown: function(e) {
    var pos = this._mouseToImage(e);

    // Check if clicking on a resize handle of selected box
    if (this.selectedIndex >= 0) {
      var handle = this._hitTestHandles(pos, this.annotations[this.selectedIndex]);
      if (handle) {
        this.isResizing = true;
        this.resizeHandle = handle;
        return;
      }
    }

    // Check if clicking on an existing box
    var hitIndex = this._hitTestBoxes(pos);
    if (hitIndex >= 0) {
      this._pushUndo();
      this.selectedIndex = hitIndex;
      this.isDragging = true;
      var ann = this.annotations[hitIndex];
      this.dragOffset = {x: pos.x - ann.x1, y: pos.y - ann.y1};
      this._render();
      this._updateSidebar();
      return;
    }

    // Start drawing a new box
    this._hideLabelPicker();
    this.selectedIndex = -1;
    this.isDrawing = true;
    this.drawStart = pos;
    this.drawCurrent = pos;
    this._updateSidebar();
  },

  _onMouseMove: function(e) {
    var pos = this._mouseToImage(e);

    if (this.isDrawing) {
      this.drawCurrent = pos;
      this._render();
      return;
    }

    if (this.isDragging && this.selectedIndex >= 0) {
      var ann = this.annotations[this.selectedIndex];
      var w = ann.x2 - ann.x1;
      var h = ann.y2 - ann.y1;
      ann.x1 = Math.max(0, Math.min(pos.x - this.dragOffset.x, this.canvas.width - w));
      ann.y1 = Math.max(0, Math.min(pos.y - this.dragOffset.y, this.canvas.height - h));
      ann.x2 = ann.x1 + w;
      ann.y2 = ann.y1 + h;
      ann.confidence = null; // Clear confidence on user edit
      this.dirty = true;
      this._render();
      return;
    }

    if (this.isResizing && this.selectedIndex >= 0) {
      this._doResize(pos);
      this._render();
      return;
    }

    // Update cursor based on hover
    this._updateCursor(pos);
  },

  _onMouseUp: function(e) {
    var pos = this._mouseToImage(e);

    if (this.isDrawing) {
      this.isDrawing = false;
      var x1 = Math.min(this.drawStart.x, pos.x);
      var y1 = Math.min(this.drawStart.y, pos.y);
      var x2 = Math.max(this.drawStart.x, pos.x);
      var y2 = Math.max(this.drawStart.y, pos.y);

      // Minimum box size (10px)
      if ((x2 - x1) < 10 || (y2 - y1) < 10) {
        this.drawStart = null;
        this.drawCurrent = null;
        this._render();
        return;
      }

      // Show label picker at the box position
      this._showLabelPicker(x1, y1, x2, y2, e);
      this.drawStart = null;
      this.drawCurrent = null;
      return;
    }

    if (this.isDragging) {
      this.isDragging = false;
      this.dragOffset = null;
      return;
    }

    if (this.isResizing) {
      this.isResizing = false;
      this.resizeHandle = null;
      return;
    }
  },

  // ---- Hit Testing ----

  _hitTestBoxes: function(pos) {
    // Test in reverse order (top-most box first)
    for (var i = this.annotations.length - 1; i >= 0; i--) {
      var a = this.annotations[i];
      if (pos.x >= a.x1 && pos.x <= a.x2 && pos.y >= a.y1 && pos.y <= a.y2) {
        return i;
      }
    }
    return -1;
  },

  _hitTestHandles: function(pos, ann) {
    var handles = this._getHandlePositions(ann);
    var threshold = 8; // pixels
    for (var key in handles) {
      var hp = handles[key];
      if (Math.abs(pos.x - hp.x) <= threshold && Math.abs(pos.y - hp.y) <= threshold) {
        return key;
      }
    }
    return null;
  },

  // ---- Resize ----

  _doResize: function(pos) {
    var ann = this.annotations[this.selectedIndex];
    ann.confidence = null; // Clear on edit

    switch (this.resizeHandle) {
      case 'nw': ann.x1 = pos.x; ann.y1 = pos.y; break;
      case 'n':  ann.y1 = pos.y; break;
      case 'ne': ann.x2 = pos.x; ann.y1 = pos.y; break;
      case 'e':  ann.x2 = pos.x; break;
      case 'se': ann.x2 = pos.x; ann.y2 = pos.y; break;
      case 's':  ann.y2 = pos.y; break;
      case 'sw': ann.x1 = pos.x; ann.y2 = pos.y; break;
      case 'w':  ann.x1 = pos.x; break;
    }

    // Ensure x1 < x2, y1 < y2
    if (ann.x1 > ann.x2) { var t = ann.x1; ann.x1 = ann.x2; ann.x2 = t; }
    if (ann.y1 > ann.y2) { var t = ann.y1; ann.y1 = ann.y2; ann.y2 = t; }

    // Clamp to canvas
    ann.x1 = Math.max(0, ann.x1);
    ann.y1 = Math.max(0, ann.y1);
    ann.x2 = Math.min(this.canvas.width, ann.x2);
    ann.y2 = Math.min(this.canvas.height, ann.y2);

    this.dirty = true;
  },

  // ---- Cursor ----

  _updateCursor: function(pos) {
    if (this.selectedIndex >= 0) {
      var handle = this._hitTestHandles(pos, this.annotations[this.selectedIndex]);
      if (handle) {
        this.canvas.className = 'mode-resize-' + handle;
        return;
      }
    }
    var hit = this._hitTestBoxes(pos);
    if (hit >= 0 && hit === this.selectedIndex) {
      this.canvas.className = 'mode-move';
    } else if (hit >= 0) {
      this.canvas.className = 'mode-select';
    } else {
      this.canvas.className = '';
    }
  },

  // ---- Label Picker ----

  _showLabelPicker: function(x1, y1, x2, y2, mouseEvent) {
    var self = this;
    this._hideLabelPicker();

    var picker = document.createElement('div');
    picker.className = 'annotation-label-picker';
    this.labelPickerEl = picker;

    // Position near the box (screen coordinates)
    var rect = this.canvas.getBoundingClientRect();
    var scaleX = rect.width / this.canvas.width;
    picker.style.left = (rect.left + x2 * scaleX + window.scrollX + 5) + 'px';
    picker.style.top = (rect.top + y1 * (rect.height / this.canvas.height) + window.scrollY) + 'px';
    picker.style.position = 'absolute';

    // Existing labels
    var labels = this.classLabels.slice();
    // Sort by most recently used
    var usedLabels = this.annotations.map(function(a) { return a.label; }).reverse();
    labels.sort(function(a, b) {
      var ai = usedLabels.indexOf(a);
      var bi = usedLabels.indexOf(b);
      if (ai === -1 && bi === -1) return 0;
      if (ai === -1) return 1;
      if (bi === -1) return -1;
      return ai - bi;
    });

    labels.forEach(function(label) {
      var btn = document.createElement('button');
      btn.className = 'label-option';
      btn.textContent = label;
      btn.onclick = function() {
        self._addAnnotation(label, x1, y1, x2, y2);
        self._hideLabelPicker();
      };
      picker.appendChild(btn);
    });

    // New label input
    var inputDiv = document.createElement('div');
    inputDiv.className = 'new-label-input';
    var input = document.createElement('input');
    input.type = 'text';
    input.placeholder = self.translations.NewLabel || 'New label...';
    input.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && input.value.trim()) {
        var newLabel = input.value.trim().toLowerCase();
        self._addAnnotation(newLabel, x1, y1, x2, y2);
        self._hideLabelPicker();
      } else if (e.key === 'Escape') {
        self._hideLabelPicker();
        self._render();
      }
    });
    inputDiv.appendChild(input);
    picker.appendChild(inputDiv);

    document.body.appendChild(picker);

    // Focus the input if no existing labels
    if (labels.length === 0) {
      input.focus();
    }
  },

  _hideLabelPicker: function() {
    if (this.labelPickerEl && this.labelPickerEl.parentNode) {
      this.labelPickerEl.parentNode.removeChild(this.labelPickerEl);
    }
    this.labelPickerEl = null;
  },

  // ---- Annotation CRUD ----

  _addAnnotation: function(label, x1, y1, x2, y2) {
    this._pushUndo();

    this.annotations.push({
      label: label,
      x1: Math.round(x1),
      y1: Math.round(y1),
      x2: Math.round(x2),
      y2: Math.round(y2),
      confidence: null
    });

    // Add to class labels if new
    if (this.classLabels.indexOf(label) === -1) {
      this.classLabels.push(label);
    }

    this.selectedIndex = this.annotations.length - 1;
    this.dirty = true;
    this._render();
    this._updateSidebar();
  },

  deleteAnnotation: function(index) {
    if (index < 0 || index >= this.annotations.length) return;
    this._pushUndo();
    this.annotations.splice(index, 1);
    if (this.selectedIndex === index) this.selectedIndex = -1;
    else if (this.selectedIndex > index) this.selectedIndex--;
    this.dirty = true;
    this._render();
    this._updateSidebar();
  },

  relabelAnnotation: function(index, newLabel) {
    if (index < 0 || index >= this.annotations.length) return;
    this._pushUndo();
    this.annotations[index].label = newLabel;
    this.annotations[index].confidence = null;
    if (this.classLabels.indexOf(newLabel) === -1) {
      this.classLabels.push(newLabel);
    }
    this.dirty = true;
    this._render();
    this._updateSidebar();
  },

  selectAnnotation: function(index) {
    this.selectedIndex = index;
    this._render();
    this._updateSidebar();
  },

  // ---- Undo ----

  _pushUndo: function() {
    this.undoStack.push(JSON.parse(JSON.stringify(this.annotations)));
    if (this.undoStack.length > this.maxUndo) {
      this.undoStack.shift();
    }
  },

  undo: function() {
    if (this.undoStack.length === 0) return;
    this.annotations = this.undoStack.pop();
    this.selectedIndex = -1;
    this.dirty = true;
    this._render();
    this._updateSidebar();
  },

  // ---- Keyboard Events ----

  _bindKeyboardEvents: function() {
    var self = this;
    document.addEventListener('keydown', function(e) {
      // Only handle when annotation panel is open
      if (!document.getElementById('annotationPanel').classList.contains('open')) return;

      if (e.key === 'Delete' || e.key === 'Backspace') {
        if (self.selectedIndex >= 0) {
          e.preventDefault();
          self.deleteAnnotation(self.selectedIndex);
        }
      } else if (e.key === 'Escape') {
        if (self.isDrawing) {
          self.isDrawing = false;
          self.drawStart = null;
          self.drawCurrent = null;
          self._render();
        } else {
          self._hideLabelPicker();
        }
      } else if (e.key === 'z' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        self.undo();
      }
    });
  },

  // ---- Sidebar ----

  _updateSidebar: function() {
    if (!this.sidebarEl) return;
    var self = this;
    var html = '';

    this.annotations.forEach(function(ann, i) {
      var color = self._getColorForLabel(ann.label);
      var selected = (i === self.selectedIndex) ? ' selected' : '';
      var confStr = (ann.confidence !== null && ann.confidence !== undefined)
        ? ' <span class="object-confidence">' + Math.round(ann.confidence * 100) + '%</span>'
        : '';

      html += '<li class="annotation-object-item' + selected + '" data-index="' + i + '">';
      html += '<span class="color-swatch" style="background:' + color + '"></span>';
      html += '<span class="object-label">' + ann.label + '</span>';
      html += confStr;
      html += '<button class="btn-remove" data-index="' + i + '" title="' +
              (self.translations.DeleteBox || 'Delete') + '">&times;</button>';
      html += '</li>';
    });

    this.sidebarEl.innerHTML = html;

    // Bind click events
    $j(this.sidebarEl).find('.annotation-object-item').on('click', function() {
      var idx = parseInt($j(this).data('index'));
      self.selectAnnotation(idx);
    });

    $j(this.sidebarEl).find('.btn-remove').on('click', function(e) {
      e.stopPropagation();
      var idx = parseInt($j(this).data('index'));
      self.deleteAnnotation(idx);
    });

    // Update label dropdown for selected box
    this._updateLabelDropdown();
  },

  _updateLabelDropdown: function() {
    var select = document.getElementById('annotationLabelSelect');
    if (!select) return;

    var self = this;
    select.innerHTML = '';

    this.classLabels.forEach(function(label) {
      var opt = document.createElement('option');
      opt.value = label;
      opt.textContent = label;
      if (self.selectedIndex >= 0 && self.annotations[self.selectedIndex].label === label) {
        opt.selected = true;
      }
      select.appendChild(opt);
    });

    select.disabled = (this.selectedIndex < 0);
  },

  // ---- Label Management ----

  _loadLabels: function() {
    var self = this;
    $j.ajax({
      url: '?request=training&action=labels',
      dataType: 'json',
      success: function(data) {
        if (data.labels) {
          self.classLabels = data.labels;
          // Also add any labels from current annotations that aren't in the list
          self.annotations.forEach(function(ann) {
            if (self.classLabels.indexOf(ann.label) === -1) {
              self.classLabels.push(ann.label);
            }
          });
          self._updateSidebar();
        }
      }
    });
  },

  // ---- Training Stats ----

  _loadStats: function() {
    var self = this;
    $j.ajax({
      url: '?request=training&action=status',
      dataType: 'json',
      success: function(data) {
        if (data.stats) {
          self.trainingStats = data.stats;
          self._renderStats();
        }
      }
    });
  },

  _renderStats: function() {
    if (!this.statsEl || !this.trainingStats) return;
    var stats = this.trainingStats;
    var t = this.translations;
    var html = '';

    html += '<div class="annotation-stats-header">' +
            (t.TrainingDataStats || 'Training Data Statistics') + '</div>';

    html += '<dl>';
    html += '<dt>' + (t.TotalAnnotatedImages || 'Total annotated images') + '</dt>';
    html += '<dd>' + stats.total_images + '</dd>';
    html += '<dt>' + (t.TotalClasses || 'Total classes') + '</dt>';
    html += '<dd>' + stats.total_classes + '</dd>';
    html += '</dl>';

    if (stats.images_per_class && Object.keys(stats.images_per_class).length > 0) {
      html += '<div style="font-weight:600;margin-bottom:2px">' +
              (t.ImagesPerClass || 'Images per class') + ':</div>';
      for (var label in stats.images_per_class) {
        var count = stats.images_per_class[label];
        html += '<div class="class-count">';
        html += '<span>' + label + '</span>';
        html += '<span class="count">' + count + '</span>';
        html += '</div>';
      }
    }

    // Training readiness guidance
    var minReady = 50;
    var goodReady = 200;
    var allReady = true;
    var anyClass = false;

    if (stats.images_per_class) {
      for (var label in stats.images_per_class) {
        anyClass = true;
        if (stats.images_per_class[label] < minReady) {
          allReady = false;
        }
      }
    }

    if (anyClass) {
      var guidanceClass = allReady ? 'training-ready' : '';
      html += '<div class="training-guidance ' + guidanceClass + '">';
      if (allReady) {
        html += (t.TrainingGuidance ||
          'Training is generally possible with at least 50-100 images per class. ' +
          'For best results, aim for 200+ images per class with varied angles and lighting conditions.');
      } else {
        html += (t.TrainingGuidance ||
          'Training is generally possible with at least 50-100 images per class. ' +
          'For best results, aim for 200+ images per class with varied angles and lighting conditions.');
      }
      html += '</div>';
    }

    this.statsEl.innerHTML = html;
  },

  // ---- Frame Selector ----

  _updateFrameSelector: function(availableFrames) {
    var self = this;
    var selector = document.getElementById('annotationFrameSelector');
    if (!selector) return;

    // Update button states
    availableFrames.forEach(function(frame) {
      var btn = selector.querySelector('[data-frame="' + frame + '"]');
      if (btn) btn.style.display = '';
    });

    // Highlight current frame
    selector.querySelectorAll('.btn').forEach(function(btn) {
      btn.classList.remove('btn-primary');
      btn.classList.add('btn-normal');
      if (btn.dataset.frame === String(self.currentFrameId)) {
        btn.classList.remove('btn-normal');
        btn.classList.add('btn-primary');
      }
    });
  },

  // ---- Save ----

  save: function() {
    var self = this;
    var annotationsData = this.annotations.map(function(ann) {
      return {
        label: ann.label,
        x1: Math.round(ann.x1),
        y1: Math.round(ann.y1),
        x2: Math.round(ann.x2),
        y2: Math.round(ann.y2)
      };
    });

    $j.ajax({
      url: '?request=training&action=save',
      method: 'POST',
      data: {
        eid: self.eventId,
        fid: self.currentFrameId,
        annotations: JSON.stringify(annotationsData),
        width: self.imageNaturalW,
        height: self.imageNaturalH
      },
      dataType: 'json',
      success: function(data) {
        if (data.result === 'Error') {
          alert(data.message || 'Save failed');
          return;
        }
        self.dirty = false;

        // Update stats display
        if (data.stats) {
          self.trainingStats = data.stats;
          self._renderStats();
        }

        // Show success feedback
        var statusEl = document.getElementById('annotationStatus');
        if (statusEl) {
          statusEl.textContent = self.translations.AnnotationSaved || 'Annotation saved to training set';
          setTimeout(function() { statusEl.textContent = ''; }, 3000);
        }
      },
      error: function() {
        alert('Failed to save annotation');
      }
    });
  }
};
```

**Step 2: Verify no lint errors**

```bash
npx eslint web/skins/classic/views/js/training.js
```

Fix any lint errors per Google JS style guide (ZM's ESLint config).

**Step 3: Commit**

```bash
git add web/skins/classic/views/js/training.js
git commit -m "feat: add Canvas-based annotation editor JavaScript

Implements AnnotationEditor class with:
- Canvas rendering of bounding boxes with labels/colors
- Click-drag to draw new boxes with label picker dropdown
- Select, move, resize existing boxes with handles
- Delete boxes via sidebar or keyboard
- Undo support (Ctrl+Z, max 50 actions)
- AJAX save to YOLO format via training.php backend
- Training dataset statistics display with readiness guidance
- Frame navigation (alarm/snapshot/objdetect/any frame)"
```

---

## Task 7: Modify Event View (event.php + event.js.php)

Add the Annotate button and annotation editor panel HTML to the event view.

**Files:**
- Modify: `web/skins/classic/views/event.php:197-217` (toolbar) and after line 455 (panel)
- Modify: `web/skins/classic/views/js/event.js.php:112-122` (translations)

**Step 1: Add Annotate button to toolbar**

In `web/skins/classic/views/event.php`, after the Toggle Zones button (line 214), add:

```php
<?php
  if (defined('ZM_OPT_TRAINING') and ZM_OPT_TRAINING) { ?>
    <button id="annotateBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Annotate') ?>"><i class="fa fa-pencil-square-o"></i></button>
<?php
  }
```

**Step 2: Add CSS include for training styles**

In `web/skins/classic/views/event.php`, near the top (after the opening PHP block), add a conditional CSS include:

```php
<?php if (defined('ZM_OPT_TRAINING') and ZM_OPT_TRAINING) { ?>
  <link rel="stylesheet" href="<?php echo cache_bust(getSkinFile('css/base/views/training.css')) ?>" type="text/css"/>
<?php } ?>
```

Note: Check if `cache_bust()` and `getSkinFile()` are available at that scope. If not, the CSS can be included from `functions.php` conditionally or loaded in the annotation panel div inline.

**Step 3: Add annotation editor panel HTML**

In `web/skins/classic/views/event.php`, after the EventData div (after line 455, before the closing `</div>` and `} // end if Event exists`), add:

```php
<?php if (defined('ZM_OPT_TRAINING') and ZM_OPT_TRAINING && $Event->Id()) { ?>
        <div id="annotationPanel">
          <div id="annotationFrameSelector" class="annotation-frame-selector">
            <span><strong><?php echo translate('Frame') ?>:</strong></span>
            <button class="btn btn-normal" data-frame="prev" title="<?php echo translate('PreviousFrame') ?>"><i class="fa fa-chevron-left"></i></button>
            <button class="btn btn-normal" data-frame="alarm" style="display:none"><?php echo translate('Alarm') ?></button>
            <button class="btn btn-normal" data-frame="snapshot" style="display:none"><?php echo translate('Snapshot') ?></button>
            <button class="btn btn-normal" data-frame="objdetect" style="display:none">ObjDetect</button>
            <button class="btn btn-normal" data-frame="next" title="<?php echo translate('NextFrame') ?>"><i class="fa fa-chevron-right"></i></button>
            <input type="number" class="form-control form-control-sm frame-input" id="annotationFrameInput" min="1" max="<?php echo $Event->Frames() ?>" placeholder="#" title="<?php echo translate('GoToFrame') ?>"/>
            <button class="btn btn-normal" id="annotationGoToFrame"><?php echo translate('GoToFrame') ?></button>
          </div>

          <div class="annotation-workspace">
            <div class="annotation-canvas-container">
              <canvas id="annotationCanvas"></canvas>
            </div>

            <div class="annotation-sidebar">
              <div class="annotation-sidebar-header"><?php echo translate('Objects') ?></div>
              <ul id="annotationObjectList" class="annotation-object-list">
                <!-- Populated by JS -->
              </ul>
              <div id="annotationStats" class="annotation-stats">
                <!-- Populated by JS -->
              </div>
            </div>
          </div>

          <div class="annotation-actions">
            <select id="annotationLabelSelect" class="form-control form-control-sm label-select" disabled>
            </select>
            <button id="annotationDeleteBtn" class="btn btn-danger btn-sm"><?php echo translate('DeleteBox') ?></button>
            <button id="annotationSaveBtn" class="btn btn-success btn-sm"><?php echo translate('SaveToTrainingSet') ?></button>
            <button id="annotationCancelBtn" class="btn btn-normal btn-sm"><?php echo translate('Cancel') ?></button>
            <span id="annotationStatus" class="annotation-status"></span>
          </div>
        </div>
<?php } ?>
```

**Step 4: Add training translations and init to event.js.php**

In `web/skins/classic/views/js/event.js.php`, after the existing `translate` object (around line 122), add:

```javascript
<?php if (defined('ZM_OPT_TRAINING') and ZM_OPT_TRAINING) { ?>
var trainingTranslations = {
  "Annotate": "<?php echo translate('Annotate') ?>",
  "AnnotationSaved": "<?php echo translate('AnnotationSaved') ?>",
  "AnnotationsRemoved": "<?php echo translate('AnnotationsRemoved') ?>",
  "DeleteBox": "<?php echo translate('DeleteBox') ?>",
  "DrawBox": "<?php echo translate('DrawBox') ?>",
  "GoToFrame": "<?php echo translate('GoToFrame') ?>",
  "NewLabel": "<?php echo translate('NewLabel') ?>",
  "NoDetectionData": "<?php echo translate('NoDetectionData') ?>",
  "SaveToTrainingSet": "<?php echo translate('SaveToTrainingSet') ?>",
  "SelectLabel": "<?php echo translate('SelectLabel') ?>",
  "UnsavedAnnotations": "<?php echo translate('UnsavedAnnotations') ?>",
  "TrainingDataStats": "<?php echo translate('TrainingDataStats') ?>",
  "TotalAnnotatedImages": "<?php echo translate('TotalAnnotatedImages') ?>",
  "TotalClasses": "<?php echo translate('TotalClasses') ?>",
  "ImagesPerClass": "<?php echo translate('ImagesPerClass') ?>",
  "TrainingGuidance": "<?php echo translate('TrainingGuidance') ?>"
};
<?php } ?>
```

**Step 5: Add JS include and initialization to event.php**

At the bottom of `event.php` (before the closing `<?php } // end if Event exists ?>`), add:

```php
<?php if (defined('ZM_OPT_TRAINING') and ZM_OPT_TRAINING) { ?>
<script src="<?php echo cache_bust(getSkinFile('views/js/training.js')) ?>"></script>
<script nonce="<?php echo $cspNonce; ?>">
  var annotationEditor = null;

  $j(document).ready(function() {
    annotationEditor = new AnnotationEditor({
      canvasId: 'annotationCanvas',
      sidebarId: 'annotationObjectList',
      eventId: eventData.Id,
      translations: trainingTranslations
    });
    annotationEditor.init();

    // Annotate button toggle
    $j('#annotateBtn').on('click', function() {
      if (document.getElementById('annotationPanel').classList.contains('open')) {
        annotationEditor.close();
      } else {
        annotationEditor.open();
      }
    });

    // Save button
    $j('#annotationSaveBtn').on('click', function() {
      annotationEditor.save();
    });

    // Cancel button
    $j('#annotationCancelBtn').on('click', function() {
      annotationEditor.close();
    });

    // Delete button
    $j('#annotationDeleteBtn').on('click', function() {
      if (annotationEditor.selectedIndex >= 0) {
        annotationEditor.deleteAnnotation(annotationEditor.selectedIndex);
      }
    });

    // Label dropdown change
    $j('#annotationLabelSelect').on('change', function() {
      if (annotationEditor.selectedIndex >= 0) {
        annotationEditor.relabelAnnotation(
          annotationEditor.selectedIndex,
          $j(this).val()
        );
      }
    });

    // Frame navigation
    $j('#annotationFrameSelector [data-frame]').on('click', function() {
      var frame = $j(this).data('frame');
      if (frame === 'prev') {
        var current = parseInt(annotationEditor.currentFrameId);
        if (!isNaN(current) && current > 1) {
          annotationEditor.switchFrame(String(current - 1));
        }
      } else if (frame === 'next') {
        var current = parseInt(annotationEditor.currentFrameId);
        if (!isNaN(current)) {
          annotationEditor.switchFrame(String(current + 1));
        } else {
          annotationEditor.switchFrame('1');
        }
      } else {
        annotationEditor.switchFrame(frame);
      }
    });

    // Go to frame number
    $j('#annotationGoToFrame').on('click', function() {
      var fid = $j('#annotationFrameInput').val();
      if (fid && parseInt(fid) > 0) {
        annotationEditor.switchFrame(fid);
      }
    });

    $j('#annotationFrameInput').on('keydown', function(e) {
      if (e.key === 'Enter') {
        $j('#annotationGoToFrame').click();
      }
    });
  });
</script>
<?php } ?>
```

**Step 6: Verify PHP syntax and lint JS**

```bash
php -l web/skins/classic/views/event.php
npx eslint web/skins/classic/views/js/training.js
npx eslint --ext .js.php web/skins/classic/views/js/event.js.php
```

**Step 7: Commit**

```bash
git add web/skins/classic/views/event.php web/skins/classic/views/js/event.js.php
git commit -m "feat: integrate annotation editor into event view

Adds Annotate button to event toolbar (visible when ZM_OPT_TRAINING
enabled). Opens inline annotation panel with canvas editor, object
sidebar with training statistics, frame navigation, and save/cancel
actions. All strings use translate() for i18n."
```

---

## Task 8: Manual Testing Checklist

This task has no code to write. It is a testing checklist.

**Step 1: Enable training in the database**

```sql
UPDATE Config SET Value='1' WHERE Name='ZM_OPT_TRAINING';
UPDATE Config SET Value='/tmp/zm_training_test' WHERE Name='ZM_TRAINING_DATA_DIR';
```

Then restart ZM or reload the config.

**Step 2: Verify Options screen**

- [ ] Navigate to Options > Config tab
- [ ] Verify ZM_OPT_TRAINING toggle appears
- [ ] Enable it — verify ZM_TRAINING_DATA_DIR and ZM_TRAINING_LABELS appear
- [ ] Disable it — verify the other two options hide

**Step 3: Verify Annotate button**

- [ ] Navigate to an event that has `objdetect.jpg` and `objects.json`
- [ ] Verify the Annotate button appears in the toolbar (pencil-square icon)
- [ ] Click it — annotation panel opens below video
- [ ] Existing detection boxes load from objects.json
- [ ] Boxes displayed on canvas with labels and confidence percentages

**Step 4: Test annotation interactions**

- [ ] Click a box — it becomes selected (thicker border, handles appear)
- [ ] Drag a box — it moves
- [ ] Drag a handle — box resizes
- [ ] Click-drag on empty area — draws new box, label picker appears
- [ ] Select a label — box solidifies with color
- [ ] Type a new label — box gets new label
- [ ] Click X on sidebar item — box deleted
- [ ] Select box + Delete key — box deleted
- [ ] Ctrl+Z — undo works

**Step 5: Test frame navigation**

- [ ] Click alarm/snapshot/objdetect buttons — correct frames load
- [ ] Type a frame number + Go — loads that frame
- [ ] Prev/Next arrows work
- [ ] Switching with unsaved changes — confirmation dialog appears

**Step 6: Test save**

- [ ] Draw/edit some boxes, click Save
- [ ] Verify image copied to `{training_dir}/images/all/event_{eid}_frame_{fid}.jpg`
- [ ] Verify label file at `{training_dir}/labels/all/event_{eid}_frame_{fid}.txt`
- [ ] Verify YOLO format: `class_id cx cy w h` (all normalized 0-1)
- [ ] Verify `data.yaml` generated with correct class names
- [ ] Verify success message shows briefly

**Step 7: Test training stats display**

- [ ] After saving, sidebar shows training data statistics
- [ ] Total images count is correct
- [ ] Images per class counts are correct
- [ ] Training guidance text appears
- [ ] After 50+ images for all classes, guidance turns green

**Step 8: Test with no detection data**

- [ ] Navigate to an event without objects.json
- [ ] Annotate button still works
- [ ] Falls back to alarm frame
- [ ] Can draw boxes from scratch

**Step 9: Verify cancel and close**

- [ ] Click Cancel — panel closes
- [ ] Make changes, click Cancel — confirmation dialog
- [ ] Decline — stays open
- [ ] Accept — panel closes, changes discarded

**Step 10: Verify pyzm compatibility**

```bash
cd /tmp/zm_training_test
# Verify directory structure
ls -la images/all/ labels/all/ data.yaml

# Verify data.yaml format
cat data.yaml

# Verify label file format
cat labels/all/*.txt
```

The directory should be directly importable by pyzm's `local_import.py` or Roboflow.

---

## Summary

| Task | Description | Key Files |
|------|-------------|-----------|
| 1 | Config options in ConfigData.pm.in | `scripts/.../ConfigData.pm.in` |
| 2 | Database migration | `db/zm_update-1.39.2.sql` |
| 3 | i18n strings | `web/lang/en_gb.php`, `web/lang/en_us.php` |
| 4 | AJAX backend | `web/ajax/training.php` |
| 5 | CSS styles | `web/skins/classic/css/base/views/training.css` |
| 6 | JS annotation editor | `web/skins/classic/views/js/training.js` |
| 7 | Event view integration | `web/skins/classic/views/event.php`, `event.js.php` |
| 8 | Manual testing | No code — verification checklist |
