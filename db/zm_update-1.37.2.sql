UPDATE MontageLayouts SET `Positions` = '{ "default":{"float":"left","left":"0px","right":"0px","top":"0px","bottom":"0px","width":"auto"} }' WHERE `Name`='Freeform';
UPDATE MontageLayouts SET `Positions` = '{ "default":{"float":"left", "width":"49%","left":"0px","right":"0px","top":"0px","bottom":"0px"} }' WHERE `Name`='2 Wide';
UPDATE MontageLayouts SET `Positions` = '{ "default":{"float":"left", "width":"25%","left":"0px","right":"0px","top":"0px","bottom":"0px"} }' WHERE `Name`='4 Wide';
UPDATE MontageLayouts SET `Positions` = '{ "default":{"float":"left", "width":"20%","left":"0px","right":"0px","top":"0px","bottom":"0px"} }' WHERE `Name`='5 Wide';

UPDATE Monitors set Importance = 'Normal' where Importance IS NULL;
ALTER TABLE `Monitors` MODIFY `Importance`  enum('Normal','Less','Not') NOT NULL default 'Normal';
