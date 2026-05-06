--
-- Update Monitors table to have a WhatDisplay Column
--

SELECT 'Checking for WhatDisplay in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'WhatDisplay'
  ) > 0,
"ALTER TABLE Monitors MODIFY `WhatDisplay` enum('OnlyVideo','OnlyAudioVisualization','VideoAudioVisualization') NOT NULL DEFAULT 'OnlyVideo'",
"ALTER TABLE Monitors ADD COLUMN `WhatDisplay` enum('OnlyVideo','OnlyAudioVisualization','VideoAudioVisualization') NOT NULL DEFAULT 'OnlyVideo' AFTER `Decoding`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
