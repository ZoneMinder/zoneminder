--
-- This updates a 1.32.2 database to 1.32.3
--

--
-- Add some additional monitor preset values
--

INSERT INTO MonitorPresets VALUES (NULL,'D-link DCS-930L, 640x480, mjpeg','Remote','http',0,0,'http','simple','<ip-address>',80,'/mjpeg.cgi',NULL,640,480,3,NULL,0,NULL,NULL,NULL,100,100);
