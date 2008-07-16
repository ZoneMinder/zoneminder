--
-- This updates a 1.23.3 database to 1.24.0
--

--
-- Add protocol column for monitors
--
alter table Monitors add column `Protocol` varchar(16) not null default '' after `Format`;
alter table MonitorPresets add column `Protocol` varchar(16) default NULL after `Format`;
update Monitors set Protocol = "http" where Type = "Remote";
update MonitorPresets set Protocol = "http" where Type = "Remote";

--
-- Add method column for monitors;
--
alter table Monitors add column `Method` varchar(16) not null default '' after `Protocol`;
alter table MonitorPresets add column `Method` varchar(16) default NULL after `Protocol`;
update Monitors set Method = "simple" where Type = "Remote" and ( select Value from Config where Name = "ZM_NETCAM_REGEXPS" ) = 0;
update Monitors set Method = "regexp" where Type = "Remote" and ( select Value from Config where Name = "ZM_NETCAM_REGEXPS" ) = 1;
update MonitorPresets set Method = "simple" where Type = "Remote" and ( select Value from Config where Name = "ZM_NETCAM_REGEXPS" ) = 0;
update MonitorPresets set Method = "regexp" where Type = "Remote" and ( select Value from Config where Name = "ZM_NETCAM_REGEXPS" ) = 1;

--
-- Add subpath for remote RTSP monitors (only for now at least)
--
alter table Monitors add column `SubPath` varchar(64) not null default '' after `Path`;
alter table MonitorPresets add column `SubPath` varchar(64) default NULL after `Path`;

--
-- Add in new MPEG presets
--
insert into MonitorPresets values ('','Axis IP, mpeg4, unicast','Remote',NULL,NULL,NULL,'rtsp','rtpUni','<ip-address>',554,'/mpeg4/media.amp','/trackID=',NULL,NULL,4,NULL,0,NULL,NULL,NULL,100,100);
insert into MonitorPresets values ('','Axis IP, mpeg4, multicast','Remote',NULL,NULL,NULL,'rtsp','rtpMulti','<ip-address>',554,'/mpeg4/media.amp','/trackID=',NULL,NULL,4,NULL,0,NULL,NULL,NULL,100,100);
insert into MonitorPresets values ('','Axis IP, mpeg4, RTP/RTSP','Remote',NULL,NULL,NULL,'rtsp','rtpRtsp','<ip-address>',554,'/mpeg4/media.amp','/trackID=',NULL,NULL,4,NULL,0,NULL,NULL,NULL,100,100);
insert into MonitorPresets values ('','Axis IP, mpeg4, RTP/RTSP/HTTP','Remote',NULL,NULL,NULL,'rtsp','rtpRtspHttp','<ip-address>',554,'/mpeg4/media.amp','/trackID=',NULL,NULL,4,NULL,0,NULL,NULL,NULL,100,100);
insert into MonitorPresets values ('','ACTi IP, mpeg4, unicast','Remote',NULL,NULL,NULL,'rtsp','rtpUni','<ip-address>',7070,'','/track',NULL,NULL,4,NULL,0,NULL,NULL,NULL,100,100);

--
-- Get rid of never used columnn Learn State
--
alter table Events drop column LearnState;

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
