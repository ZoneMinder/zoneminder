UPDATE Monitors set Importance = 'Normal' where Importance IS NULL;
ALTER TABLE `Monitors` MODIFY `Importance`  enum('Normal','Less','Not') NOT NULL default 'Normal';
