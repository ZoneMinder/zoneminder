
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Storage'
      AND column_name = 'ServerId'
    ) > 0,
    "SELECT 'Column ServerId already exists in Storage'",
    "ALTER TABLE Storage ADD `ServerId` int(10) unsigned AFTER `Scheme`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM Filters WHERE Name = 'Update DiskSpace'
      AND Query = '{"terms":[{"attr":"DiskSpace","op":"IS","val":"NULL"}],"sort_field":"Id","sort_asc":"1","limit":"100"}'
    ) > 0,
    "SELECT 'Update Disk Space Filter already exists.'",
    "INSERT INTO Filters (Name,Query,UpdateDiskSpace,Background) values ('Update DiskSpace','{\"terms\":[{\"attr\":\"DiskSpace\",\"op\":\"IS\",\"val\":\"NULL\"}],\"sort_field\":\"Id\",\"sort_asc\":\"1\",\"limit\":\"100\"}',1,1)"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;
