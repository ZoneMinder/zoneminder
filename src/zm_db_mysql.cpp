//
// ZoneMinder MySQL Database Implementation, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
// 
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
// 
#include "zm_db_mysql.h"

// --- zmDb subclass --- //

zmDbMySQLAdapter::zmMySQLAdapterDb () : zmDb() {
    if( db.is_connected() )
        return;

    std::string paramsStr("");
    paramsStr += "db=" + staticConfig.DB_NAME;
    paramsStr += " charset=utf8";
    paramsStr += " user=" + staticConfig.DB_USER;
    paramsStr += " pass='" + staticConfig.DB_PASS + "'";

    std::string::size_type colonIndex = staticConfig.DB_HOST.find(":");

    if ( colonIndex == std::string::npos ) {
        // HOST
        paramsStr += " host='" + staticConfig.DB_HOST + "'";
    } else {
        std::string dbHost = staticConfig.DB_HOST.substr(0, colonIndex);
        std::string dbPortOrSocket = staticConfig.DB_HOST.substr(colonIndex+1);
        if ( dbPortOrSocket[0] == '/' ) {
            // SOCKET
            paramsStr += " unix_socket='" + dbPortOrSocket + "'";
        } else {
            // HOST + PORT
            paramsStr += " host='" + dbHost + "'";
            paramsStr += " port=" + dbPortOrSocket;
        }
    }

    soci::connection_parameters params( "mysql", params_str );

    try {
        db.open(params);
        if( !db.is_connected() ) {
            Error("Can't connect to server: %s", paramsStr.c_str());
            return;
        }

        mysql_session_backend concreteDb = (mysql_session_backend)db.get_backend();

        if ( mysql_options(concreteDb._conn, MYSQL_OPT_RECONNECT, NULL) )
           Error("Can't set database auto reconnect option: %s", mysql_error(concreteDb._conn));

        prepareStatements();
        for( int i=0; i<LAST_QUERY; i++ ) {
            mapStatements[i].define_and_bind();
        }
    }
    catch( const std::exception& err ) {
        Error("Can't connect to server: %s", paramsStr.c_str());
    }
}

zmDbMySQLAdapter::~zmMySQLAdapterDb () {
    if( !db.is_connected() )
        return;

    try {
        db.close();
    }
    catch( const std::exception& err ) {
        Error("Can't disconnect server: %s", staticConfig.DB_HOST.c_str());
    }
}

zmDbQuery* zmDbMySQLAdapter::getQuery(zmDbQueryID queryID) {
    return new zmDbQuery( db, queryID );
}

void zmDbMySQLAdapter::prepareStatements() {
    if( !db.is_connected() )
        return;

    mapStatements[SELECT_SERVER_ID_WITH_NAME] = db.prepare("SELECT `Id` FROM `Servers` WHERE `Name`=':name'");

    mapStatements[SELECT_SERVER_NAME_WITH_ID] = db.prepare("SELECT `Name` FROM `Servers` WHERE `Id`=':id'");

    mapStatements[SELECT_GROUP_WITH_ID] = db.prepare("SELECT `Id`, `ParentId`, `Name` FROM `Group` WHERE `Id`=:id");

    mapStatements[SELECT_MAX_EVENTS_ID_WITH_MONITORID_AND_FRAMES_NOT_ZERO] = 
        db.prepare("SELECT MAX(`Id`) FROM `Events` WHERE `MonitorId`=:id AND `Frames` > 0");

    mapStatements[SELECT_GROUPS_PARENT_OF_MONITOR_ID] = 
        db.prepare("SELECT `Id`, `ParentId`, `Name` FROM `Groups` WHERE `Groups.Id` IN \
(SELECT `GroupId` FROM `Groups_Monitors` WHERE `MonitorId`=:id)");

    mapStatements[SELECT_MONITOR_ID_REMOTE_RTSP_AND_RTPUNI] = 
        db.prepare("SELECT `Id` FROM `Monitors` WHERE `Function` != 'None' AND `Type` = 'Remote' AND `Protocol` = 'rtsp' AND `Method` = 'rtpUni' ORDER BY `Id` ASC");

    mapStatements[SELECT_STORAGE_WITH_ID] = db.prepare("SELECT `Id`, `Name`, `Path`, `Type`, `Scheme` FROM `Storage` WHERE `Id`=:id");

    mapStatements[SELECT_USER_AND_DATA_WITH_USERNAME_ENABLED] = 
        db.prepare("SELECT `Id`, `Username`, `Password`, `Enabled`,`Stream`+0, `Events`+0, `Control`+0, `Monitors`+0, `System`+0, `MonitorIds` FROM `Users` \
WHERE `Username` = :username AND `Enabled` = 1");

    mapStatements[SELECT_USER_AND_DATA_PLUS_TOKEN_WITH_USERNAME_ENABLED] = 
        db.prepare("SELECT `Id`, `Username`, `Password`, `Enabled`,`Stream`+0, `Events`+0, `Control`+0, `Monitors`+0, `System`+0, `MonitorIds`, \
`TokenMinExpiry` FROM `Users` WHERE `Username` = :username AND `Enabled` = 1");

    mapStatements[SELECT_ALL_ACTIVE_STATES_ID] = db.prepare("SELECT Id FROM States WHERE IsActive=1");

    mapStatements[SELECT_ALL_CONFIGS] = db.prepare("SELECT `Name`, `Value`, `Type` FROM `Config` ORDER BY `Id`");

    mapStatements[SELECT_ALL_STORAGE_ID_DIFFERENT_THAN] = db.prepare("SELECT `Id` FROM `Storage` WHERE `Id` != :id");

    mapStatements[SELECT_ALL_STORAGE_ID_WITH_SERVERID_NULL] = db.prepare("SELECT `Id` FROM `Storage` WHERE ServerId IS NULL");

    mapStatements[SELECT_ALL_EVENTS_ID_WITH_MONITORID_EQUAL] = 
        db.prepare("SELECT `Id` FROM `Events` WHERE `MonitorId` = :id AND unix_timestamp(`EndDateTime`) > :timestamp \
        ORDER BY `Id` ASC LIMIT 1");

    mapStatements[SELECT_ALL_FRAMES_WITH_DATA_OF_EVENT_WITH_ID] = 
        db.prepare("SELECT `MonitorId`, `StorageId`, `Frames`, unix_timestamp( `StartDateTime` ) AS StartTimestamp, \
unix_timestamp( `EndDateTime` ) AS EndTimestamp, (SELECT max(`Delta`)-min(`Delta`) FROM `Frames` \
WHERE `EventId`=`Events`.`Id`) AS FramesDuration, `DefaultVideo`, `Scheme`, `SaveJPEGs`, `Orientation`+0 FROM `Events` \
WHERE `Id` = :id");

    mapStatements[SELECT_ALL_FRAMES_OF_EVENT_WITH_ID] = db.prepare("SELECT `FrameId`, unix_timestamp(`TimeStamp`), `Delta` FROM `Frames` \
        WHERE `EventId` = :id  ORDER BY `FrameId` ASC");

    mapStatements[SELECT_ALL_EVENTS_ID_WITH_MONITORID_AND_ID_LESSER_THAN] = 
        db.prepare("SELECT `Id` FROM `Events` WHERE `MonitorId` = :monitorId AND `Id` < :eventId  ORDER BY `Id` DESC LIMIT 1");

    mapStatements[SELECT_ALL_EVENTS_ID_WITH_MONITORID_AND_ID_LARGER_THAN] = 
        db.prepare("SELECT `Id` FROM `Events` WHERE `MonitorId` = :monitorId AND `Id` > :eventId  ORDER BY `Id` ASC LIMIT 1");

    mapStatements[SELECT_ALL_MONITORS_DATA_OFFICIAL] = 
        db.prepare("SELECT `Id`, `Name`, `ServerId`, `StorageId`, `Type`, `Capturing`+0, `Analysing`+0, `AnalysisSource`+0, `AnalysisImage`+0, \
`Recording`+0, `RecordingSource`+0, `Decoding`+0, \
`JanusEnabled`, `JanusAudioEnabled`, `Janus_Profile_Override`, `Janus_Use_RTSP_Restream`, \
`LinkedMonitors`, `EventStartCommand`, `EventEndCommand`, `AnalysisFPSLimit`, `AnalysisUpdateDelay`, `MaxFPS`, `AlarmMaxFPS`, \
`Device`, `Channel`, `Format`, `V4LMultiBuffer`, `V4LCapturesPerFrame`, \
`Protocol`, `Method`, `Options`, `User`, `Pass`, `Host`, `Port`, `Path`, `SecondPath`, `Width`, `Height`, `Colours`, `Palette`, `Orientation`+0, `Deinterlacing`, \
`DecoderHWAccelName`, `DecoderHWAccelDevice`, `RTSPDescribe`, \
`SaveJPEGs`, `VideoWriter`, `EncoderParameters`, \
`OutputCodec`, `Encoder`, `OutputContainer`, \
`RecordAudio`, \
`Brightness`, `Contrast`, `Hue`, `Colour`, \
`EventPrefix`, `LabelFormat`, `LabelX`, `LabelY`, `LabelSize`, \
`ImageBufferCount`, `MaxImageBufferCount`, `WarmupCount`, `PreEventCount`, `PostEventCount`, `StreamReplayBuffer`, `AlarmFrameCount`, \
`SectionLength`, `MinSectionLength`, `FrameSkip`, `MotionFrameSkip`, \
`FPSReportInterval`, `RefBlendPerc`, `AlarmRefBlendPerc`, `TrackMotion`, `Exif`, \
`RTSPServer`, `RTSPStreamName`, `ONVIF_Alarm_Text`, \
`ONVIF_URL`, `ONVIF_Username`, `ONVIF_Password`, `ONVIF_Options`, `ONVIF_Event_Listener`, `use_Amcrest_API`, \
`SignalCheckPoints`, `SignalCheckColour`, `Importance`-1, ZoneCount FROM `Monitors`");

    mapStatements[SELECT_ALL_USERS_AND_DATA_ENABLED] = 
        db.prepare("SELECT `Id`, `Username`, `Password`, `Enabled`, `Stream`+0, `Events`+0, `Control`+0, `Monitors`+0, `System`+0, `MonitorIds` FROM `Users` \
        WHERE `Enabled` = 1");

    mapStatements[SELECT_ALL_ZONES_WITH_MONITORID_EQUAL_TO] = 
        db.prepare("SELECT Id,Name,Type+0,Units,Coords,AlarmRGB,CheckMethod+0, \
MinPixelThreshold,MaxPixelThreshold,MinAlarmPixels,MaxAlarmPixels, \
FilterX,FilterY,MinFilterPixels,MaxFilterPixels, \
MinBlobPixels,MaxBlobPixels,MinBlobs,MaxBlobs, \
OverloadFrames,ExtendAlarmFrames \
FROM Zones WHERE MonitorId = :id ORDER BY Type");

    mapStatements[SELECT_ALL_MONITORS_DATA] = db.prepare("SELECT `Id`, `Capturing`+0, `Analysing`+0, `Recording`+0 FROM `Monitors`");
    
    mapStatements[UPDATE_NEW_EVENT_WITH_ID] = 
        db.prepare("UPDATE Events SET Name=:name, EndDateTime = from_unixtime(:enddatetime), Length = :length, Frames = :frames, \
AlarmFrames = :alarm_frames, TotScore = :total_score, AvgScore = avg_score, MaxScore = max_score, DefaultVideo=default_video \
WHERE Id = :id AND Name='New Event'");

    mapStatements[UPDATE_EVENT_WITH_ID_SET_NOTES] = db.prepare("UPDATE `Events` SET `Notes` = ':notes' WHERE `Id` = :id");

    mapStatements[UPDATE_EVENT_WITH_ID_SET_SCORE] = db.prepare("UPDATE Events \
SET Length = :length, Frames = :frames, AlarmFrames = :alarm_frames, TotScore = total_score, AvgScore = avg_score, MaxScore = max_score WHERE Id = :id");

    mapStatements[UPDATE_EVENT_WITH_ID_SET_STORAGEID] = db.prepare("UPDATE Events SET StorageId = :storageId WHERE Id=:id");

    mapStatements[UPDATE_EVENT_WITH_ID_SET_SAVEJPEGS] = db.prepare("UPDATE Events SET SaveJpegs=:save_jpegs WHERE Id=:id");

    mapStatements[UPDATE_MONITORSTATUS_WITH_MONITORID_SET_CAPTUREFPS] = 
        db.prepare("UPDATE LOW_PRIORITY Monitor_Status SET CaptureFPS = :capture_fps, CaptureBandwidth=capture_bandwitdh, AnalysisFPS = analysis_fps WHERE MonitorId=:id");
    mapStatements[INSERT_EVENTS] = db.prepare("INSERT INTO `Events` \
( `MonitorId`, `StorageId`, `Name`, `StartDateTime`, `Width`, `Height`, `Cause`, `Notes`, `StateId`, `Orientation`, `Videoed`, `DefaultVideo`, `SaveJPEGs`, `Scheme` ) \
VALUES \
(:monitor_id, :storage_id, 'New Event', from_unixtime(:start_datetime), :width, :height, :cause, :notes, :state_id, :orientation, :videoed, :default_video, :save_jpegs, :scheme)");

    mapStatements[INSERT_FRAMES] = db.prepare("INSERT INTO `Frames` (`EventId`, `FrameId`, `Type`, `TimeStamp`, `Delta`, `Score`) VALUES \
(:event_id, :frame_id, :type, from_unixtime( :timestamp ), :delta, :score)");

    mapStatements[INSERT_STATS_SINGLE] = db.prepare("INSERT INTO Stats SET MonitorId=:monitorid, ZoneId=:zone_id, EventId=:event_id , FrameId=:frame_id, \
PixelDiff=:pixel_diff, AlarmPixels=:alarm_pixels, FilterPixels=:filter_pixels, BlobPixels=:blob_pixels, \
Blobs=:blobs, MinBlobSize=:min_blobsize, MaxBlobSize=:max_blobsize, \
MinX=:minx, MinY=:miny, MaxX=:maxx, MaxY=:maxy, Score=:score");

    mapStatements[INSERT_STATS_MULTIPLE] = db.prepare("INSERT INTO `Stats` (`EventId`, `FrameId`, `MonitorId`, `ZoneId`, \
`PixelDiff`, `AlarmPixels`, `FilterPixels`, `BlobPixels`, \
`Blobs`,`MinBlobSize`, `MaxBlobSize`, \
`MinX`, `MinY`, `MaxX`, `MaxY`,`Score`) VALUES \
(:event_id, :frame_id, :monitor_id, :zone_id, :pixel_diff, :alarm_pixels, :filter_pixels, :blob_pixels, :blobs, \
:min_blobsize, :max_blobsize,  :minx, :miny, :maxx, :maxy, :score)");

    mapStatements[INSERT_LOGS] = db.prepare("INSERT INTO `Logs` \
( `TimeKey`, `Component`, `ServerId`, `Pid`, `Level`, `Code`, `Message`, `File`, `Line` ) \
 VALUES \
( :time_key, :component, :server_id, :pid, :level, :code, :message, :file, :line )");

    mapStatements[INSERT_MONITOR_STATUS_RUNNING] = 
        db.prepare("INSERT INTO Monitor_Status (MonitorId,Status,CaptureFPS,AnalysisFPS) \
VALUES (:id, 'Running',0,0) ON DUPLICATE KEY UPDATE Status='Running',CaptureFPS=0,AnalysisFPS=0");

    mapStatements[INSERT_MONITOR_STATUS_CONNECTED] = 
        db.prepare("INSERT INTO Monitor_Status (MonitorId,Status) VALUES (:id, 'Connected') ON DUPLICATE KEY UPDATE Status='Connected'");

    mapStatements[INSERT_MONITOR_STATUS_NOTRUNNING] = 
        db.prepare("INSERT INTO Monitor_Status (MonitorId,Status) VALUES (:id, 'NotRunning') ON DUPLICATE KEY UPDATE Status='NotRunning'");

}
