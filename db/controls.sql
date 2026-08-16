--
-- Control protocol definitions shipped with ZoneMinder.
--
-- Pulled in by zm_create.sql with
--   source @PKGDATADIR@/db/controls.sql
-- the same way manufacturers.sql, models.sql and triggers.sql are.  The
-- install(FILES ${dbfileslist}) glob in db/CMakeLists.txt puts it next to
-- zm_create.sql, which is where that path resolves to.
--
-- Adding an entry here only affects new installs.  Existing databases are
-- upgraded by a db/zm_update-<version>.sql, which should use the
-- INSERT ... SELECT ... WHERE NOT EXISTS form so it can be re-run safely.
--
INSERT INTO `Controls` VALUES (NULL,'Pelco-D','Local','PelcoD',1,1,0,0,1,1,0,0,1,NULL,NULL,NULL,NULL,1,0,3,1,1,0,0,1,NULL,NULL,NULL,NULL,0,NULL,NULL,1,1,0,1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,1,0,1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,1,20,1,1,1,1,0,0,0,1,1,NULL,NULL,NULL,NULL,1,0,63,1,254,1,NULL,NULL,NULL,NULL,1,0,63,1,254,0,0);
INSERT INTO `Controls` VALUES (NULL,'Pelco-P','Local','PelcoP',1,1,0,0,1,1,0,0,1,NULL,NULL,NULL,NULL,1,0,3,1,1,0,0,1,NULL,NULL,NULL,NULL,0,NULL,NULL,1,1,0,1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,1,0,1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,1,20,1,1,1,1,0,0,0,1,1,NULL,NULL,NULL,NULL,1,0,63,1,254,1,NULL,NULL,NULL,NULL,1,0,63,1,254,0,0);
INSERT INTO `Controls` VALUES (NULL,'Sony VISCA','Local','Visca',1,1,0,0,1,0,0,0,1,0,16384,10,4000,1,1,6,1,1,1,0,1,0,1536,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,1,3,1,1,1,1,0,1,1,0,1,-15578,15578,100,10000,1,1,50,1,254,1,-7789,7789,100,5000,1,1,50,1,254,0,0);
INSERT INTO `Controls` VALUES (NULL,'Axis API v2','Remote','AxisV2',0,0,0,0,1,0,0,1,0,0,9999,10,2500,0,NULL,NULL,1,1,0,1,0,0,9999,10,2500,0,NULL,NULL,1,1,0,1,0,0,9999,10,2500,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,1,12,1,1,1,1,1,0,1,0,1,-360,360,1,90,0,NULL,NULL,0,NULL,1,-360,360,1,90,0,NULL,NULL,0,NULL,0,0);
INSERT INTO `Controls` VALUES (NULL,'Panasonic IP','Remote','PanasonicIP',0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,1,8,1,1,1,0,1,0,0,1,1,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,1,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,0,0);
INSERT INTO `Controls` VALUES (NULL,'Neu-Fusion NCS370','Remote','Ncs370',0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,1,24,1,0,1,1,0,0,0,1,1,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,1,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,0,0);
INSERT INTO `Controls` VALUES (NULL,'AirLink SkyIPCam 7xx','Remote','SkyIPCam7xx',0,0,1,0,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,1,8,1,1,1,0,1,0,1,0,1,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,1,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,0,0);
INSERT INTO `Controls` VALUES (NULL,'Pelco-D','Ffmpeg','PelcoD',1,1,0,0,1,1,0,0,1,NULL,NULL,NULL,NULL,1,0,3,1,1,0,0,1,NULL,NULL,NULL,NULL,0,NULL,NULL,1,1,0,1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,1,0,1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,1,20,1,1,1,1,0,0,0,1,1,NULL,NULL,NULL,NULL,1,0,63,1,254,1,NULL,NULL,NULL,NULL,1,0,63,1,254,0,0);
INSERT INTO `Controls` VALUES (NULL,'Pelco-P','Ffmpeg','PelcoP',1,1,0,0,1,1,0,0,1,NULL,NULL,NULL,NULL,1,0,3,1,1,0,0,1,NULL,NULL,NULL,NULL,0,NULL,NULL,1,1,0,1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,1,0,1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,1,20,1,1,1,1,0,0,0,1,1,NULL,NULL,NULL,NULL,1,0,63,1,254,1,NULL,NULL,NULL,NULL,1,0,63,1,254,0,0);
INSERT INTO `Controls` VALUES (NULL,'Foscam FI8620','Ffmpeg','FI8620_Y2k',0,0,0,0,1,0,0,0,1,1,10,1,10,1,1,63,1,1,0,0,1,1,63,1,63,1,1,63,1,1,0,0,1,0,0,0,0,1,0,255,0,0,0,0,0,0,0,0,0,0,0,0,1,1,0,0,1,0,0,0,0,1,0,255,0,0,1,8,0,1,1,1,0,0,0,1,1,1,360,1,360,1,1,63,0,0,1,1,90,1,90,1,1,63,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Foscam FI8608W','Ffmpeg','FI8608W_Y2k',1,0,1,0,1,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,1,0,0,1,0,0,0,0,1,0,255,0,0,0,0,0,0,0,0,0,0,0,0,1,1,0,0,1,0,0,0,0,1,0,255,0,0,1,8,0,1,1,1,0,0,0,1,1,0,0,0,0,1,1,128,0,0,1,0,0,0,0,1,1,128,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Foscam FI8908W','Remote','FI8908W',0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,1,1,0,0,0,1,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Foscam FI9821W','Ffmpeg','FI9821W_Y2k',1,0,1,0,1,0,0,0,1,0,0,0,0,0,0,0,1,1,0,0,1,0,0,0,0,1,0,100,1,1,0,0,1,0,100,0,100,1,0,100,0,0,0,0,0,0,0,0,0,0,0,0,1,1,0,0,1,0,100,0,100,1,0,100,0,0,1,16,0,1,1,1,0,0,0,1,1,0,360,0,360,1,0,4,0,0,1,0,90,0,90,1,0,4,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Loftek Sentinel','Remote','LoftekSentinel',0,0,1,0,1,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,255,16,16,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,6,1,1,0,0,0,0,0,1,10,0,1,1,1,0,0,0,1,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Toshiba IK-WB11A','Remote','Toshiba_IK_WB11A',0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,10,0,1,1,0,1,0,1,0,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'WanscamPT','Remote','Wanscam',1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,16,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,16,0,0,0,0,0,0,0,1,16,1,1,1,1,0,0,0,1,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'3S Domo N5071', 'Remote', '3S', 0, 0, 1, 0,1, 0, 1, 1, 0, 0, 9999, 0, 9999, 0, 0, 0, 1, 1, 1, 1, 0, 0, 9999, 20, 9999, 0, 0, 0, 1, 1, 1, 1, 0, 0, 9999, 1, 9999, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,0,0, 1, 64, 1, 0, 1, 1, 0, 0, 0, 0, 1, -180, 180, 40, 100, 1, 40, 100, 0, 0, 1, -180, 180, 40, 100, 1, 40, 100, 0, 0, 0, 0);
INSERT INTO `Controls` VALUES (NULL,'ONVIF Camera','Ffmpeg','ONVIF',0,0,1,1,1,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,255,16,16,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,6,1,1,0,0,0,0,0,1,10,1,1,1,1,1,0,1,1,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Foscam 9831W','Ffmpeg','FI9831W',0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,16,1,1,1,1,0,0,0,1,1,0,360,0,360,1,0,4,0,0,1,0,90,0,90,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Foscam FI8918W','Ffmpeg','FI8918W',0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,8,0,1,1,1,0,0,0,1,1,0,360,0,360,1,0,4,0,0,1,0,90,0,90,1,0,4,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'SunEyes SP-P1802SWPTZ','Libvlc','SPP1802SWPTZ',0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,8,0,1,1,0,0,0,0,1,1,0,0,0,0,1,0,64,0,0,1,0,0,0,0,1,0,64,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Wanscam HW0025','Libvlc','WanscamHW0025', 1, 1, 1, 0,1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,0,0, 1, 16, 1, 1, 1, 1, 0, 1, 1, 0, 1, 0, 350, 0, 0, 1, 0, 10, 0, 0, 1, 0, 0, 0, 0, 1, 0, 10, 0, 0, 0, 0);
INSERT INTO `Controls` VALUES (NULL,'IPCC 7210W','Remote','IPCC7210W', 1, 1, 1, 0,1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,0,0, 1, 16, 1, 1, 1, 1, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `Controls` VALUES (NULL,'Vivotek ePTZ','Remote','Vivotek_ePTZ',0,0,1,0,1,0,0,0,1,0,0,0,0,1,0,5,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,0,0,0,1,1,0,0,0,0,1,0,5,0,0,1,0,0,0,0,1,0,5,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Netcat ONVIF','Ffmpeg','ONVIF',0,0,1,0,1,0,0,0,1,0,0,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,100,5,5,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,1,1,0,0,0,100,5,5,0,0,0,0,0,1,255,1,1,1,1,0,0,0,1,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Keekoon','Remote','Keekoon', 0, 0, 0, 0,0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,0,0, 1, 6, 0, 1, 1, 1, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `Controls` VALUES (NULL,'HikVision','Local','HikVision',0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,20,1,1,1,1,0,0,0,1,1,0,0,0,0,1,1,100,0,0,1,0,0,0,0,1,1,100,1,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Maginon Supra IPC','cURL','MaginonIPC',0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,4,0,1,1,1,0,0,1,1,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Floureon 1080P','Ffmpeg','Floureon',0,0,0,0,1,0,0,0,1,1,18,1,1,0,0,0,1,1,0,0,1,0,0,0,0,0,0,0,1,1,0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,20,0,1,1,1,0,0,0,1,1,0,0,0,0,1,1,8,0,0,1,0,0,0,0,1,1,8,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Reolink RLC-423','Ffmpeg','ONVIF',0,0,1,0,1,0,0,0,1,0,0,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,64,1,1,1,1,0,0,0,1,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Reolink RLC-411','Ffmpeg','ONVIF',0,0,1,0,1,0,0,0,1,0,0,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Reolink RLC-420','Ffmpeg','ONVIF',0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'D-LINK DCS-3415','Remote','DCS3415',0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'D-Link DCS-5020L','Remote','DCS5020L',1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,24,1,0,1,1,1,0,1,0,1,0,0,1,30,0,0,0,0,0,1,0,0,1,30,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'IOS Camera','Ffmpeg','IPCAMIOS',0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,1,0,0,0,0,1,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Dericam P2','Ffmpeg','DericamP2',0,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,10,0,1,1,1,0,0,0,1,1,0,0,0,0,1,1,45,0,0,1,0,0,0,0,1,1,45,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Trendnet','Remote','Trendnet',1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,1,1,0,1,0,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'PSIA','Remote','PSIA',0,0,0,0,1,0,0,1,0,0,0,0,0,0,0,100,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,20,0,1,1,1,0,0,1,0,1,0,0,0,0,1,-100,100,0,0,1,0,0,0,0,1,-100,100,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'Dahua','Ffmpeg','Dahua',0,0,1,1,1,0,0,1,0,0,0,0,0,0,0,0,1,0,0,1,0,0,0,0,0,0,0,0,1,0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,20,1,1,1,1,0,0,1,0,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `Controls` VALUES (NULL,'FOSCAMR2C','Libvlc','FOSCAMR2C',1,1,1,0,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,1,12,0,1,1,1,0,0,0,1,1,NULL,NULL,NULL,NULL,1,0,4,0,NULL,1,NULL,NULL,NULL,NULL,1,0,4,0,NULL,0,0);
INSERT INTO `Controls` VALUES (NULL,'Amcrest HTTP API','Ffmpeg','Amcrest_HTTP',0,0,1,0,1,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,5,0,0,1,0,0,0,0,1,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,5);
INSERT INTO `Controls` (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,`CanZoom`,`CanZoomCon`,`HasPresets`,`NumPresets`,`HasHomePreset`,`CanSetPresets`,`CanMove`,`CanMoveDiag`,`CanMoveCon`,`CanPan`,`CanTilt`,`CanLight`,`CanIndicatorLight`) VALUES ('Dahua/Amcrest RPC','Ffmpeg','Dahua_RPC',1,1,1,1,1,25,1,1,1,1,1,1,1,1,1);
INSERT INTO `Controls` (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,`CanZoom`,`CanZoomCon`,`HasPresets`,`NumPresets`,`HasHomePreset`,`CanSetPresets`,`CanMove`,`CanMoveDiag`,`CanMoveCon`,`CanPan`,`CanTilt`,`CanIndicatorLight`) VALUES ('Amcrest ASH21-B RPC','Ffmpeg','Dahua_RPC',1,1,0,0,0,0,0,0,1,0,1,1,1,1);
INSERT INTO `Controls` (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,`CanZoom`,`CanZoomCon`,`HasPresets`,`NumPresets`,`HasHomePreset`,`CanSetPresets`,`CanMove`,`CanMoveDiag`,`CanMoveCon`,`CanPan`,`CanTilt`,`CanLight`,`CanIndicatorLight`) VALUES ('Amcrest ADC2W RPC','Ffmpeg','Dahua_RPC',1,1,0,0,0,0,0,0,0,0,0,0,0,1,1);
INSERT INTO `Controls` (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,`CanIndicatorLight`) VALUES ('Amcrest ASH42-B RPC','Ffmpeg','Dahua_RPC',1,1,1);
INSERT INTO `Controls` (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,`CanLight`) VALUES ('LTS CMIP1342WE-28MDA','Ffmpeg','HikVision',0,1,1);
INSERT INTO `Controls` (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,`CanLight`) VALUES ('LTS CMIP3CD42WI-28AISP','Ffmpeg','HikVision',0,1,1);
INSERT INTO `Controls` VALUES (NULL,'ONVIF','Ffmpeg','ONVIF',0,0,1,1,1,0,0,0,1,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,0,1,0,0,0,100,1,1,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,0,1,0,0,0,100,1,1,0,NULL,NULL,0,0,1,20,1,1,1,1,1,0,1,1,1,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,1,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,0,0);
INSERT INTO `Controls` VALUES (NULL,'HiSilicon Hi3510 CGI','Ffmpeg','HiSilicon_Hi3510_CGI',0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,10,0,1,1,1,0,0,0,1,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);

--
-- Amcrest IP5M-1190EW, verified against firmware 2.810.00AC004.0.R.
--
-- The generic 'Amcrest HTTP API' entry above advertises zoom and continuous
-- zoom and no presets, which is backwards for this model.  Pan, tilt, the
-- diagonals and continuous move all physically move it, arg2 really is the
-- speed, and GotoPreset works.  Zoom, focus and iris do not: ZoomTele,
-- ZoomWide, FocusNear and FocusFar all answer OK and do nothing, while
-- autoFocus, getFocusStatus, IrisLarge and AutoPanOn 400.  PositionABS
-- answers OK and is inert (24 pan angles and 5 tilt values all produced an
-- identical frame), so CanMoveAbs and CanMoveMap stay 0, and moveMap is
-- built on PositionABS anyway.  It has a WhiteLight in Lighting_V2 but
-- coaxialControlIO 400s and config writes to Lighting/Lighting_V2 are
-- accepted and then ignored, so CanLight stays 0.
--
-- Beware when checking any of this yourself: ptz.cgi?action=getStatus on
-- this firmware always reports Postion=0/16.65 MoveStatus=Idle no matter
-- what the camera is doing.  Status cannot tell you whether a move worked;
-- compare snapshots instead.
--
-- NumPresets 255 is the tinyint maximum and the documented Dahua limit.
-- SetPreset was accepted and read back at every index tried up to 256, so
-- the firmware does not appear to bound-check it.
--
INSERT INTO `Controls` (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,`HasPresets`,`NumPresets`,`HasHomePreset`,`CanSetPresets`,`CanMove`,`CanMoveDiag`,`CanMoveCon`,`CanPan`,`HasPanSpeed`,`MinPanSpeed`,`MaxPanSpeed`,`CanTilt`,`HasTiltSpeed`,`MinTiltSpeed`,`MaxTiltSpeed`) VALUES ('Amcrest IP5M-1190EW HTTP','Ffmpeg','Amcrest_HTTP',1,1,1,255,1,1,1,1,1,1,1,1,8,1,1,1,8);
