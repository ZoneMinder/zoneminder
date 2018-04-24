ALTER TABLE Monitors MODIFY `Type` enum('Local','Remote','File','Ffmpeg','Libvlc','cURL','NVSocket') NOT NULL default 'Local';
