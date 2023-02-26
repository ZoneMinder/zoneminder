/*ALTER TABLE `Monitors` MODIFY `Decoding` enum('None','Ondemand','KeyFrames','Always') NOT NULL default 'Always';*/
 ALTER TABLE `Monitors` MODIFY `Decoding` enum('None','Ondemand','KeyFrames','KeyFrames+Ondemand', 'Always') NOT NULL default 'Always'; 
