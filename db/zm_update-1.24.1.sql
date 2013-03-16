--
-- This updates a 1.24.1 database to the next version
--

--
-- Replace local presets with V4L versioned ones
--
delete from MonitorPresets where Type = 'Local';
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L2), PAL, 320x240','Local','/dev/video<?>','<?>',255,NULL,'v4l2',NULL,NULL,NULL,NULL,320,240,1345466932,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L2), PAL, 320x240, max 5 FPS','Local','/dev/video<?>','<?>',255,NULL,'v4l2',NULL,NULL,NULL,NULL,320,240,1345466932,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L2), PAL, 640x480','Local','/dev/video<?>','<?>',255,NULL,'v4l2',NULL,NULL,NULL,NULL,640,480,1345466932,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L2), PAL, 640x480, max 5 FPS','Local','/dev/video<?>','<?>',255,NULL,'v4l2',NULL,NULL,NULL,NULL,640,480,1345466932,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L2), NTSC, 320x240','Local','/dev/video<?>','<?>',45056,NULL,'v4l2',NULL,NULL,NULL,NULL,320,240,1345466932,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L2), NTSC, 320x240, max 5 FPS','Local','/dev/video<?>','<?>',45056,NULL,'v4l2',NULL,NULL,NULL,NULL,320,240,1345466932,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L2), NTSC, 640x480','Local','/dev/video<?>','<?>',45056,NULL,'v4l2',NULL,NULL,NULL,NULL,640,480,1345466932,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L2), NTSC, 640x480, max 5 FPS','Local','/dev/video<?>','<?>',45056,NULL,'v4l2',NULL,NULL,NULL,NULL,640,480,1345466932,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L1), PAL, 320x240','Local','/dev/video<?>','<?>',0,NULL,'v4l1',NULL,NULL,NULL,NULL,320,240,13,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L1), PAL, 320x240, max 5 FPS','Local','/dev/video<?>','<?>',0,NULL,'v4l1',NULL,NULL,NULL,NULL,320,240,13,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L1), PAL, 640x480','Local','/dev/video<?>','<?>',0,NULL,'v4l1',NULL,NULL,NULL,NULL,640,480,13,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L1), PAL, 640x480, max 5 FPS','Local','/dev/video<?>','<?>',0,NULL,'v4l1',NULL,NULL,NULL,NULL,640,480,13,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L1), NTSC, 320x240','Local','/dev/video<?>','<?>',1,NULL,'v4l1',NULL,NULL,NULL,NULL,320,240,13,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L1), NTSC, 320x240, max 5 FPS','Local','/dev/video<?>','<?>',1,NULL,'v4l1',NULL,NULL,NULL,NULL,320,240,13,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L1), NTSC, 640x480','Local','/dev/video<?>','<?>',1,NULL,'v4l1',NULL,NULL,NULL,NULL,640,480,13,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video (V4L1), NTSC, 640x480, max 5 FPS','Local','/dev/video<?>','<?>',1,NULL,'v4l1',NULL,NULL,NULL,NULL,640,480,13,5.0,0,NULL,NULL,NULL,100,100);

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
