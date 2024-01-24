UPDATE Monitors SET ControlAddress='' WHERE ControlAddress='user:port@ip';

ALTER TABLE Users MODIFY `Monitors` enum('None','View','Edit','Create') NOT NULL default 'None';
UPDATE Users SET Monitors='Create' WHERE Monitors='Edit';
