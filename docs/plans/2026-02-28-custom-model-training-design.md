# Custom Model Training Annotation UI — Design Document

**Date**: 2026-02-28
**Status**: Approved

## Goal

Add an integrated annotation UI to ZoneMinder that lets users correct object detection
results (wrong classifications, missing detections) and export corrected images +
annotations in Roboflow-compatible YOLO format for custom model training via pyzm.

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Architecture | Integrated ZM view with AJAX backend | Fits existing ZM patterns, no new deps |
| Frame selection | Detected frame default, fallback alarm/snapshot, any frame navigable | Maximum flexibility |
| Storage path | Configurable via ZM option (`ZM_TRAINING_DATA_DIR`) | Admin can point to SSD, shared mount, etc. |
| Class labels | Auto-populated from detections + user-editable | Adaptive, low friction |
| UI entry point | Button on event view | Simple, integrated |
| Box editor | HTML5 Canvas overlay | Lightweight, no external deps |

---

## 1. ZM Options

Three new Config entries in the `config` category:

| Name | Type | Default | Description |
|------|------|---------|-------------|
| `ZM_OPT_TRAINING` | boolean | 0 | Master toggle for all training features |
| `ZM_TRAINING_DATA_DIR` | text | `@ZM_CACHEDIR@/training` | Path for annotated images + YOLO labels |
| `ZM_TRAINING_LABELS` | text | (empty) | Comma-separated class labels, auto-populated + user-editable |

`ZM_TRAINING_DATA_DIR` and `ZM_TRAINING_LABELS` use the `Requires` field to depend on
`ZM_OPT_TRAINING`, so they only appear when training is enabled.

Default path uses `@ZM_CACHEDIR@` (CMake-substituted) to respect the install prefix.

## 2. Training Data Directory Layout

Roboflow-compatible YOLO format, matching pyzm's `YOLODataset` staging convention:

```
{ZM_TRAINING_DATA_DIR}/
  images/
    all/
      event_{eid}_frame_{fid}.jpg
      ...
  labels/
    all/
      event_{eid}_frame_{fid}.txt   # YOLO: class_id cx cy w h (normalized)
      ...
  data.yaml                          # Regenerated on each save
```

`data.yaml` example:

```yaml
path: .
train: images/all
val: images/all
names:
  0: person
  1: car
  2: dog
```

Class-to-ID mapping derived from `ZM_TRAINING_LABELS` order. Order must not change once
training has started. New classes are appended only.

File naming: `event_{eid}_frame_{fid}.jpg` ensures uniqueness and traceability.

## 3. Annotation Editor UI

### Entry Point

An "Annotate" button in the event view toolbar (`event.php`), visible only when
`ZM_OPT_TRAINING` is enabled. Opens an inline editor panel below the video area.

### Layout

```
+---------------------------------------------------+
|  Event View (existing toolbar, video, etc.)        |
|  [Annotate] <- new button                          |
+---------------------------------------------------+
|  Annotation Editor (slides open)                   |
|                                                    |
|  Frame: [< Prev] [alarm] [snapshot] [objdetect]   |
|         [Next >] [Go to #___]                      |
|                                                    |
|  +----------------------------+  +--------------+  |
|  |                            |  | Objects:     |  |
|  |  Canvas with frame image   |  |              |  |
|  |  + bounding box overlays   |  | [x] person   |  |
|  |                            |  |     95%      |  |
|  |  (click+drag to draw)      |  | [x] car      |  |
|  |  (click box to select)     |  |     87%      |  |
|  |                            |  |              |  |
|  +----------------------------+  | [+ Add]      |  |
|                                  +--------------+  |
|  Label: [person v]  [Delete Box]                   |
|                                                    |
|  [Save to Training Set]  [Cancel]                  |
+---------------------------------------------------+
```

### Default Frame

1. Detected frame (from `objects.json` `frame_id`) if detection exists
2. Fallback: alarm frame
3. Fallback: snapshot frame

User can navigate to any frame in the event.

### Interactions

| Action | Gesture | Feedback |
|--------|---------|----------|
| Select box | Click on box | Thicker border, resize handles, sidebar highlight |
| Delete box | Click x on sidebar, or select + Delete key | Box fades out |
| Draw new box | Click-drag on empty area | Dashed rect follows cursor, label picker on release |
| Resize box | Drag corner/edge handles | Live resize |
| Move box | Drag selected box body | Box follows cursor |
| Relabel box | Select box, change dropdown | Box color updates |
| Cancel draw | Escape or right-click | In-progress box discarded |
| Undo | Ctrl+Z | Reverts last action (max 50 stack) |
| Navigate frame | Frame selector | Confirm if unsaved changes, load new frame |

### Label Assignment

On new box draw (mouse-up):
1. Dropdown anchored to box's top-right corner
2. Shows existing classes (most recently used first)
3. Text input at bottom for new class name
4. Enter/click confirms, Escape cancels the box

### Visual Design

- 2px box borders, class-specific colors from a fixed palette
- Selected: 3px border + corner/edge resize handles
- Semi-transparent fill on hover (10% opacity)
- Labels above boxes: "person 95%" (confidence shown for existing, omitted for user-drawn)
- Cursor changes: crosshair (drawing), move (over box), resize arrows (over handles)

### Coordinate System

Internal tracking in image-space pixels (native resolution). Canvas CSS-scales to fit
the panel. Mouse-to-image conversion:

```
imageX = (mouseX - canvasOffset) * (naturalWidth / displayWidth)
imageY = (mouseY - canvasOffset) * (naturalHeight / displayHeight)
```

YOLO normalized conversion on save:

```
cx = ((x1 + x2) / 2) / image_width
cy = ((y1 + y2) / 2) / image_height
w  = (x2 - x1) / image_width
h  = (y2 - y1) / image_height
```

## 4. Backend (AJAX Handler)

### New File: `web/ajax/training.php`

| Action | Method | Description |
|--------|--------|-------------|
| `load` | GET | Returns objects.json data + frame image URL for event/frame |
| `save` | POST | Saves image + YOLO label file to training dir, regenerates data.yaml |
| `labels` | GET | Returns current class label list |
| `addlabel` | POST | Appends new class to ZM_TRAINING_LABELS |
| `delete` | POST | Removes a saved annotation from training dir |
| `status` | GET | Returns annotation count, classes, dataset stats |

### Security

- All actions gated on `canEdit('Events')`
- `ZM_OPT_TRAINING` checked at entry (403 if disabled)
- CSRF token validation on POST actions (`getCSRFMagic()`)
- File path validation — no directory traversal
- Images copied from event path, never from user-supplied paths

### Data Flow

```
Canvas annotations (pixel coords)
  -> AJAX POST training.php?action=save {eid, fid, annotations[], width, height}
  -> PHP: validate permissions + CSRF
  -> Copy frame image to images/all/event_{eid}_frame_{fid}.jpg
  -> Convert pixel coords to YOLO normalized
  -> Write labels/all/event_{eid}_frame_{fid}.txt
  -> Regenerate data.yaml
  -> Audit log entry
  -> Return success + count
```

## 5. Database Migration

### Migration: `db/zm_update-1.37.78.sql`

Three INSERT statements for `ZM_OPT_TRAINING`, `ZM_TRAINING_DATA_DIR`,
`ZM_TRAINING_LABELS` (see Section 1 for details).

### Fresh Install: `db/zm_create.sql.in`

Same three INSERT statements added to the Config data block.

## 6. i18n

All user-visible strings use ZM's `$SLANG` translation system:

- PHP templates: `<?php echo translate('Annotate') ?>`
- JS strings: passed via `var translations = {...}` in `event.js.php`
- New `$SLANG` entries in `web/lang/en_gb.php` and `web/lang/en_us.php`
- `$OLANG` help text entries for the three new Config options

## 7. Files Created / Modified

| File | Change |
|------|--------|
| `db/zm_update-1.37.78.sql` | New — migration with 3 Config inserts |
| `db/zm_create.sql.in` | Modified — add same 3 Config inserts |
| `web/lang/en_gb.php` | Modified — add $SLANG entries |
| `web/lang/en_us.php` | Modified — add $SLANG + $OLANG entries |
| `web/skins/classic/views/event.php` | Modified — Annotate button + editor panel HTML |
| `web/skins/classic/views/js/event.js.php` | Modified — pass training config/translations to JS |
| `web/skins/classic/views/js/training.js` | New — Canvas annotation editor |
| `web/skins/classic/css/training.css` | New — editor panel styles |
| `web/ajax/training.php` | New — AJAX backend |

## 8. Training Data Statistics

The annotation editor sidebar shows live training dataset statistics:

- **Total annotated images** — count of image files in `images/all/`
- **Total classes** — number of distinct classes with at least one annotation
- **Images per class** — breakdown showing count per class label
- **Training readiness guidance** — contextual help text:
  - Below 50 images/class: yellow banner explaining minimum requirements
  - 50+ images/class: green banner indicating training is possible
  - Text: "Training is generally possible with at least 50-100 images per class.
    For best results, aim for 200+ images per class with varied angles and lighting."

Stats refresh after each save operation. The `status` AJAX action can also be called
independently to check dataset readiness.

### Not Touched

- No C++ changes
- No changes to zm_detect or pyzm (produces data pyzm already consumes)
- No changes to event recording or detection pipeline
- No new PHP dependencies
