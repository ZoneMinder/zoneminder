ALTER TABLE Monitors MODIFY `Type` enum('Local','Remote','File','Ffmpeg','Libvlc','cURL','NVSocket','VNC') NOT NULL default 'Local';
