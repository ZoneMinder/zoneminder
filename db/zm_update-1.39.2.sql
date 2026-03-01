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

--
-- Derive default for ZM_TRAINING_DATA_DIR from ZM_DIR_EVENTS
-- e.g. /var/cache/zoneminder/events -> /var/cache/zoneminder/training
-- Guard against ZM_DIR_EVENTS not being in Config table (fresh builds)
--
SET @evdir = (SELECT Value FROM Config WHERE Name='ZM_DIR_EVENTS');
SET @traindir = IF(@evdir IS NOT NULL, REPLACE(@evdir, '/events', '/training'), NULL);
UPDATE Config SET Value=COALESCE(@traindir, Value), DefaultValue=COALESCE(@traindir, DefaultValue)
  WHERE Name='ZM_TRAINING_DATA_DIR' AND Value='';

