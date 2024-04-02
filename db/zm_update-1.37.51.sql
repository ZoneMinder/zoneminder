--
-- Update Monitors table to have SOAP_wsa_compl
--

SELECT 'Checking for SOAP_wsa_compl in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'SOAP_wsa_compl'
  ) > 0,
"SELECT 'Column SOAP_wsa_compl already exists on Monitors'",
 "ALTER TABLE Monitors add SOAP_wsa_compl BOOLEAN NOT NULL default TRUE after RTSPStreamName"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
