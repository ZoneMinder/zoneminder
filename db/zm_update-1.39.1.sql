--
-- This updates a 1.39.0 database to 1.39.1
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
DEALLOCATE PREPARE stmt;

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
    Help='Filesystem path where corrected annotation images and YOLO label files are stored. The directory will be created automatically if it does not exist. Uses Roboflow-compatible YOLO directory layout (images/all/, labels/all/, data.yaml). The default is set from ConfigData after running zmupdate --freshen.',
    Category='config',
    Readonly='0',
    Private='0',
    System='0',
    Requires='ZM_OPT_TRAINING=1'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM Config WHERE Name='ZM_TRAINING_DETECT_SCRIPT') > 0,
  "SELECT 'ZM_TRAINING_DETECT_SCRIPT already exists'",
  "INSERT INTO Config SET
    Name='ZM_TRAINING_DETECT_SCRIPT',
    Value='',
    Type='string',
    DefaultValue='',
    Hint='/path/to/zm_detect.py',
    Prompt='Object detection script location',
    Help='Full path to the object detection script (e.g. /var/lib/zmeventnotification/bin/zm_detect.py). When set, a Detect button appears in the annotation editor that runs detection on the current frame.',
    Category='config',
    Readonly='0',
    Private='0',
    System='0',
    Requires='ZM_OPT_TRAINING=1'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
