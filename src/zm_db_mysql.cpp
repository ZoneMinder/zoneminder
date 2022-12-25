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
#include "zm_db.h"
#include "zm_signal.h"

#ifdef HAVE_LIBSOCI_MYSQL
#include "zm_db_mysql.h"

std::string load_monitor_mysql =
"SELECT `Id`, `Name`, `ServerId`, `StorageId`, `Type`, `Capturing`, `Analysing`, `AnalysisSource`, `AnalysisImage`,"
"`Recording`, `RecordingSource`, `Decoding`, "
"`JanusEnabled`, `JanusAudioEnabled`, `Janus_Profile_Override`, `Janus_Use_RTSP_Restream`,"
"`LinkedMonitors`, `EventStartCommand`, `EventEndCommand`, `AnalysisFPSLimit`, `AnalysisUpdateDelay`, `MaxFPS`, `AlarmMaxFPS`,"
"`Device`, `Channel`, `Format`, `V4LMultiBuffer`, `V4LCapturesPerFrame`, " // V4L Settings
"`Protocol`, `Method`, `Options`, `User`, `Pass`, `Host`, `Port`, `Path`, `SecondPath`, `Width`, `Height`, `Colours`, `Palette`, `Orientation`, `Deinterlacing`, "
"`DecoderHWAccelName`, `DecoderHWAccelDevice`, `RTSPDescribe`, "
"`SaveJPEGs`, `VideoWriter`, `EncoderParameters`, "
"`OutputCodec`, `Encoder`, `OutputContainer`, "
"`RecordAudio`, "
"`Brightness`, `Contrast`, `Hue`, `Colour`, "
"`EventPrefix`, `LabelFormat`, `LabelX`, `LabelY`, `LabelSize`,"
"`ImageBufferCount`, `MaxImageBufferCount`, `WarmupCount`, `PreEventCount`, `PostEventCount`, `StreamReplayBuffer`, `AlarmFrameCount`, "
"`SectionLength`, `MinSectionLength`, `FrameSkip`, `MotionFrameSkip`, "
"`FPSReportInterval`, `RefBlendPerc`, `AlarmRefBlendPerc`, `TrackMotion`, `Exif`,"
"`RTSPServer`, `RTSPStreamName`, `ONVIF_Alarm_Text`,"
"`ONVIF_URL`, `ONVIF_Username`, `ONVIF_Password`, `ONVIF_Options`, `ONVIF_Event_Listener`, `use_Amcrest_API`, "
"`SignalCheckPoints`, `SignalCheckColour`, `Importance`, ZoneCount FROM `Monitors`"
" WHERE ";

zmDbMySQLAdapter::zmDbMySQLAdapter() : zmDb()
{
    if (connected())
        return;

    std::string paramsStr("");
    paramsStr += "db=" + staticConfig.DB_NAME;
    paramsStr += " charset=utf8";
    paramsStr += " user=" + staticConfig.DB_USER;
    paramsStr += " pass='" + staticConfig.DB_PASS + "'";

    std::string::size_type colonIndex = staticConfig.DB_HOST.find(":");

    if (colonIndex == std::string::npos)
    {
        // HOST
        paramsStr += " host='" + staticConfig.DB_HOST + "'";
    }
    else
    {
        std::string dbHost = staticConfig.DB_HOST.substr(0, colonIndex);
        std::string dbPortOrSocket = staticConfig.DB_HOST.substr(colonIndex + 1);
        if (dbPortOrSocket[0] == '/')
        {
            // SOCKET
            paramsStr += " unix_socket='" + dbPortOrSocket + "'";
        }
        else
        {
            // HOST + PORT
            paramsStr += " host='" + dbHost + "'";
            paramsStr += " port=" + dbPortOrSocket;
        }
    }

    soci::connection_parameters params(soci::mysql, paramsStr);

    db.open( params );

    if (!connected())
    {
        Error("Can't connect to server: %s", paramsStr.c_str());
        return;
    }

    soci::mysql_session_backend *concreteDb = (soci::mysql_session_backend *)db.get_backend();

    if( concreteDb->conn_ == NULL ) {
        Error("Cannot connect to database");
        
    } else {
        bool reconnect = true;
        unsigned int CONNECT_TIMEOUT = 2;
        unsigned int READ_TIMEOUT = 2;
        unsigned int WRITE_TIMEOUT = 2;

        mysql_options(concreteDb->conn_, MYSQL_OPT_RECONNECT, &reconnect);
        mysql_options(concreteDb->conn_, MYSQL_OPT_CONNECT_TIMEOUT, &CONNECT_TIMEOUT);
        mysql_options(concreteDb->conn_, MYSQL_OPT_READ_TIMEOUT, &READ_TIMEOUT);
        mysql_options(concreteDb->conn_, MYSQL_OPT_WRITE_TIMEOUT, &WRITE_TIMEOUT);

#if defined(MYSQL_OPT_NONBLOCK)
        unsigned int OPT_NONBLOCK = 1;
        mysql_options(concreteDb->conn_, MYSQL_OPT_NONBLOCK, &OPT_NONBLOCK);
#endif
        if ( mysql_query(concreteDb->conn_, "SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED") ) {
            Error("Can't set isolation level: %s", mysql_error(concreteDb->conn_));
        }
    }

    for (int i = 0; i < LAST_QUERY; i++)
    {
        mapStatements[i] = new soci::statement(db);
        mapStatements[i]->alloc();
    }

    prepareSelectStatements();
    prepareSelectAllStatements();
    prepareSelectMonitorStatements();
    prepareUpdateStatements();
    prepareInsertStatements();

    for (int i = 0; i < LAST_QUERY; i++)
    {
        mapStatements[i]->define_and_bind();
    }
}

zmDbMySQLAdapter::~zmDbMySQLAdapter()
{
    if (!connected()) 
    {
        return;
    }

    try
    {
        db.close();
    }
    catch (const std::exception &err)
    {
        Error("Can't disconnect server: %s", staticConfig.DB_HOST.c_str());
    }
}

#if (SOCI_VERSION < 400001) // before version 4.0.1 session::is_connected was not supported
bool zmDbMySQLAdapter::connected() {
    soci::mysql_session_backend *concreteDb = (soci::mysql_session_backend *)db.get_backend();
    if ( concreteDb == NULL )
    {
        return false;
    }

    // note: taken nearly verbatim from soci source
    return mysql_ping(concreteDb->conn_) == 0;
}
#endif

uint64_t zmDbMySQLAdapter::lastInsertID(const zmDbQueryID &queryId)
{
    if (!connected())
    {
        return 0;
    }

#if (SOCI_VERSION < 400000) // before version 4.0.0 IDs for sequences were not treated as 64bits
    long id = 0;
#else
    long long id = 0;
#endif
    if (db.get_last_insert_id(autoIncrementTable[queryId], id))
    {
        return (long long)id;
    }

    return 0;
}

std::string zmDbMySQLAdapter::realColumnName(const std::string& column) {
    return column;
}

void zmDbMySQLAdapter::prepareSelectStatements()
{
    if (!connected()) 
    {
        return;
    }

    mapStatements[SELECT_SERVER_ID_WITH_NAME]->prepare("SELECT `Id` FROM `Servers` WHERE `Name`=:name");

    mapStatements[SELECT_SERVER_NAME_WITH_ID]->prepare("SELECT `Name` FROM `Servers` WHERE `Id`=:id");

    mapStatements[SELECT_SERVER_DATA_WITH_ID]->prepare("SELECT `Id`, `Name`, `Protocol`, `Hostname`, `PathToIndex`, `PathToZMS`, `PathToApi` FROM `Servers` WHERE `Id`=:id");

    mapStatements[SELECT_GROUP_WITH_ID]->prepare("SELECT `Id`, `ParentId`, `Name` FROM `Groups` WHERE `Id`=:id");

    mapStatements[SELECT_MAX_EVENTS_ID_WITH_MONITORID_AND_FRAMES_NOT_ZERO]->prepare(
        "SELECT MAX(`Id`) FROM `Events` WHERE `MonitorId`=:id AND `Frames` > 0");

    // rewritten to remove nested query not suppoerted by soci mysql backend
    mapStatements[SELECT_GROUPS_PARENT_OF_MONITOR_ID]->prepare(
        "SELECT DISTINCT `Groups`.`Id`, `Groups`.`ParentId`, `Groups`.`Name` FROM `Groups`, `Groups_Monitors` WHERE `Groups_Monitors`.`MonitorId`=:id AND `Groups`.`Id`=`Groups_Monitors`.`Id`;");

    mapStatements[SELECT_MONITOR_ID_REMOTE_RTSP_AND_RTPUNI]->prepare(
        "SELECT `Id` FROM `Monitors` WHERE `Function` != 'None' AND `Type` = 'Remote' AND `Protocol` = 'rtsp' AND `Method` = 'rtpUni' ORDER BY `Id` ASC");

    mapStatements[SELECT_STORAGE_WITH_ID]->prepare("SELECT `Id`, `Name`, `Path`, `Type`, `Scheme` FROM `Storage` WHERE `Id`=:id");

    mapStatements[SELECT_USER_AND_DATA_WITH_USERNAME_ENABLED]->prepare(
        "SELECT `Id`, `Username`, `Password`, `Enabled`,`Stream`, `Events`, `Control`, `Monitors`, `System`, `MonitorIds` FROM `Users` WHERE `Username` = :username AND `Enabled` = 1");

    mapStatements[SELECT_USER_AND_DATA_WITH_USERID_ENABLED]->prepare(
        "SELECT `Id`, `Username`, `Password`, `Enabled`,`Stream`, `Events`, `Control`, `Monitors`, `System`, `MonitorIds` FROM `Users` WHERE `Enabled` = 1 AND `Id`=:id");

    mapStatements[SELECT_USER_AND_DATA_PLUS_TOKEN_WITH_USERNAME_ENABLED]->prepare(
        "SELECT `Id`, `Username`, `Password`, `Enabled`,`Stream`, `Events`, `Control`, `Monitors`, `System`, `MonitorIds`, `TokenMinExpiry` FROM `Users` WHERE `Username` = :username AND `Enabled` = 1");

    mapStatements[SELECT_GROUP_PERMISSIONS_FOR_USERID]->prepare(
        "SELECT `Id`,`UserId`,`GroupId`,`Permission` FROM Groups_Permissions WHERE `UserId`=:id");

    mapStatements[SELECT_MONITOR_PERMISSIONS_FOR_USERID]->prepare(
        "SELECT `Id`,`UserId`,`MonitorId`,`Permission` FROM Monitors_Permissions WHERE `UserId`=:id");

    mapStatements[SELECT_MONITOR_FOR_GROUPID]->prepare(
        "SELECT `MonitorId` FROM Groups_Monitors WHERE `GroupId`=:id");
}

void zmDbMySQLAdapter::prepareSelectMonitorStatements()
{
    if (!connected()) 
    {
        return;
    }

    std::string op_and = " AND ";
    std::string cond_id = "`Id` = :id";
    std::string cond_type = " `Capturing` != 'None' AND `Type` = :type";
    std::string cond_device = "`Device` = :device";
    std::string cond_server_id = "`ServerId` = :server_id";
    std::string cond_protocol = "`Protocol` = :protocol AND `Host` = :host AND `Port` = :port AND `Path` = :path";
    std::string cond_path = "`Path` = :path";
    std::string cond_rtsp = "`Function` != 'None' AND `RTSPserver` != false";

    std::string base_query = load_monitor_mysql + cond_type + op_and;

    mapStatements[SELECT_MONITOR_WITH_ID]->prepare(load_monitor_mysql + cond_id);

    mapStatements[SELECT_MONITOR_TYPE]->prepare(load_monitor_mysql + cond_type);

    mapStatements[SELECT_MONITOR_TYPE_AND_DEVICE]->prepare(base_query + cond_device);

    mapStatements[SELECT_MONITOR_TYPE_AND_SERVER]->prepare(base_query + cond_server_id);

    mapStatements[SELECT_MONITOR_TYPE_AND_DEVICE_AND_SERVER]->prepare(base_query + cond_device + op_and + cond_server_id);

    mapStatements[SELECT_MONITOR_TYPE_AND_PROTOCOL]->prepare(base_query + cond_protocol);

    mapStatements[SELECT_MONITOR_TYPE_AND_SERVER_AND_PROTOCOL]->prepare(base_query + cond_server_id + op_and + cond_protocol);

    mapStatements[SELECT_MONITOR_TYPE_AND_PATH]->prepare(base_query + cond_path);

    mapStatements[SELECT_MONITOR_TYPE_AND_PATH_AND_SERVER]->prepare(base_query + cond_path + op_and + cond_server_id);

    mapStatements[SELECT_MONITOR_TYPE_RTSP]->prepare(load_monitor_mysql + cond_rtsp);

    mapStatements[SELECT_MONITOR_TYPE_RTSP_AND_SERVER]->prepare(load_monitor_mysql + cond_rtsp + op_and + cond_server_id);

    mapStatements[SELECT_MONITOR_TYPE_RTSP_AND_ID]->prepare(load_monitor_mysql + cond_rtsp + op_and + cond_id);

    mapStatements[SELECT_MONITOR_TYPE_RTSP_AND_SERVER_AND_ID]->prepare(load_monitor_mysql + cond_rtsp + op_and + cond_server_id + op_and + cond_id);

}

void zmDbMySQLAdapter::prepareSelectAllStatements()
{
    if (!connected()) 
    {
        return;
    }

    mapStatements[SELECT_ALL_ACTIVE_STATES_ID]->prepare("SELECT Id FROM States WHERE IsActive=1");

    mapStatements[SELECT_ALL_CONFIGS]->prepare("SELECT `Name`, `Value`, `Type` FROM `Config` ORDER BY `Id`");

    mapStatements[SELECT_ALL_STORAGE_ID]->prepare("SELECT `Id` FROM `Storage` WHERE `Id` != :id");

    mapStatements[SELECT_ALL_STORAGE_ID_AND_SERVER_ID]->prepare("SELECT `Id` FROM `Storage` WHERE `Id` != :id AND ServerId = :server_id");

    mapStatements[SELECT_ALL_STORAGE_ID_WITH_SERVERID_NULL]->prepare("SELECT `Id` FROM `Storage` WHERE ServerId IS NULL");

    mapStatements[SELECT_ALL_STORAGE_ID_WITH_SERVERID_NULL_OR_DIFFERENT]->prepare("SELECT `Id` FROM `Storage` WHERE ServerId IS NULL OR ServerId != :server_id");

    mapStatements[SELECT_ALL_EVENTS_ID_WITH_MONITORID_EQUAL]->prepare(
        "SELECT `Id` FROM `Events` WHERE `MonitorId` = :id AND unix_timestamp(`EndDateTime`) > :timestamp         ORDER BY `Id` ASC LIMIT 1");

    mapStatements[SELECT_EVENT_WITH_ID]->prepare(
        "SELECT `Events`.`Id`, `MonitorId`, `StorageId`, `Frames`, unix_timestamp( `StartDateTime` ) AS StartTimestamp, unix_timestamp( `EndDateTime` ) AS EndTimestamp, `DefaultVideo`, `Scheme`, `SaveJPEGs`, `Orientation`, max(f.`Delta`)-min(f.`Delta`) AS FramesDuration FROM `Events` JOIN `Frames` f ON `Events`.`Id` = f.`Id` WHERE `Events`.`Id` = :id");

    mapStatements[SELECT_ALL_FRAMES_OF_EVENT_WITH_ID]->prepare("SELECT `FrameId`, unix_timestamp(`TimeStamp`), `Delta` FROM `Frames`     WHERE `EventId` = :id  ORDER BY `FrameId` ASC");

    mapStatements[SELECT_ALL_EVENTS_ID_WITH_MONITORID_AND_ID_LESSER_THAN]->prepare(
        "SELECT `Id` FROM `Events` WHERE `MonitorId` = :monitor_id AND `Id` < :event_id  ORDER BY `Id` DESC LIMIT 1");

    mapStatements[SELECT_ALL_EVENTS_ID_WITH_MONITORID_AND_ID_LARGER_THAN]->prepare(
        "SELECT `Id` FROM `Events` WHERE `MonitorId` = :monitor_id AND `Id` > :event_id  ORDER BY `Id` ASC LIMIT 1");

    mapStatements[SELECT_ALL_USERS_AND_DATA_ENABLED]->prepare(
        "SELECT `Id`, `Username`, `Password`, `Enabled`, `Stream`, `Events`, `Control`, `Monitors`, `System`, `MonitorIds` FROM `Users`         WHERE `Enabled` = 1");

    mapStatements[SELECT_ALL_ZONES_WITH_MONITORID_EQUAL_TO]->prepare(
        "SELECT Id,Name,Type,Units,Coords,AlarmRGB,CheckMethod, MinPixelThreshold,MaxPixelThreshold,MinAlarmPixels,MaxAlarmPixels, FilterX,FilterY,MinFilterPixels,MaxFilterPixels, MinBlobPixels,MaxBlobPixels,MinBlobs,MaxBlobs, OverloadFrames,ExtendAlarmFrames FROM Zones WHERE MonitorId = :id ORDER BY Type");

    mapStatements[SELECT_ALL_MONITORS_DATA]->prepare("SELECT `Id`, `Capturing`, `Analysing`, `Recording` FROM `Monitors` ORDER BY Id ASC");

    mapStatements[SELECT_ALL_MONITORS_DATA_VERBOSE]->prepare("SELECT `Id`, `Capturing`, `Analysing`, `Recording` FROM `Monitors` WHERE `Capturing` != 'None' ORDER BY Id ASC");
}

void zmDbMySQLAdapter::prepareUpdateStatements()
{
    if (!connected()) 
    {
        return;
    }

    mapStatements[UPDATE_NEW_EVENT_WITH_ID]->prepare(
        "UPDATE Events SET Name=:name, EndDateTime = from_unixtime(:enddatetime), Length = :length, Frames = :frames, AlarmFrames = :alarm_frames, TotScore = :total_score, AvgScore = :avg_score, MaxScore = :max_score, DefaultVideo=:default_video WHERE Id = :id AND Name='New Event'");

    mapStatements[UPDATE_NEW_EVENT_WITH_ID_NO_NAME]->prepare(
        "UPDATE Events SET EndDateTime = from_unixtime(:enddatetime), Length = :length, Frames = :frames, AlarmFrames = :alarm_frames,         TotScore = :total_score, AvgScore = :avg_score, MaxScore = :max_score, DefaultVideo=:default_video WHERE Id = :id");

    mapStatements[UPDATE_EVENT_WITH_ID_SET_NOTES]->prepare("UPDATE `Events` SET `Notes` = :notes WHERE `Id` = :id");

    mapStatements[UPDATE_EVENT_WITH_ID_SET_SCORE]->prepare("UPDATE Events SET Length = :length, Frames = :frames, AlarmFrames = :alarm_frames, TotScore = :total_score, AvgScore = :avg_score, MaxScore = :max_score WHERE Id = :id");

    mapStatements[UPDATE_EVENT_WITH_ID_SET_STORAGEID]->prepare("UPDATE Events SET StorageId = :storage_id WHERE Id=:id");

    mapStatements[UPDATE_EVENT_WITH_ID_SET_SAVEJPEGS]->prepare("UPDATE Events SET SaveJpegs=:save_jpegs WHERE Id=:id");

    mapStatements[UPDATE_MONITORSTATUS_WITH_MONITORID_SET_CAPTUREFPS]->prepare("UPDATE LOW_PRIORITY Monitor_Status SET CaptureFPS = :capture_fps, CaptureBandwidth=:capture_bandwitdh, AnalysisFPS = :analysis_fps WHERE MonitorId=:id");
}

void zmDbMySQLAdapter::prepareInsertStatements()
{
    if (!connected()) 
    {
        return;
    }

    mapStatements[INSERT_EVENTS]->prepare("INSERT INTO `Events` ( `MonitorId`, `StorageId`, `Name`, `StartDateTime`, `Width`, `Height`, `Cause`, `Notes`, `StateId`, `Orientation`, `Videoed`, `DefaultVideo`, `SaveJPEGs`, `Scheme` ) VALUES (:monitor_id, :storage_id, 'New Event', from_unixtime(:start_datetime), :width, :height, :cause, :notes, :state_id, :orientation, :videoed, :default_video, :save_jpegs, :scheme)");

    mapStatements[INSERT_FRAMES]->prepare("INSERT INTO `Frames` (`EventId`, `FrameId`, `Type`, `TimeStamp`, `Delta`, `Score`) VALUES (:event_id, :frame_id, :type, from_unixtime( :timestamp ), :delta, :score)");

    mapStatements[INSERT_STATS_SINGLE]->prepare("INSERT INTO Stats SET MonitorId=:monitorid, ZoneId=:zone_id, EventId=:event_id , FrameId=:frame_id, PixelDiff=:pixel_diff, AlarmPixels=:alarm_pixels, FilterPixels=:filter_pixels, BlobPixels=:blob_pixels, Blobs=:blobs, MinBlobSize=:min_blobsize, MaxBlobSize=:max_blobsize, MinX=:minx, MinY=:miny, MaxX=:maxx, MaxY=:maxy, Score=:score");

    mapStatements[INSERT_STATS_MULTIPLE]->prepare("INSERT INTO `Stats` (`EventId`, `FrameId`, `MonitorId`, `ZoneId`, `PixelDiff`, `AlarmPixels`, `FilterPixels`, `BlobPixels`, `Blobs`,`MinBlobSize`, `MaxBlobSize`, `MinX`, `MinY`, `MaxX`, `MaxY`,`Score`) VALUES (:event_id, :frame_id, :monitor_id, :zone_id, :pixel_diff, :alarm_pixels, :filter_pixels, :blob_pixels, :blobs, :min_blobsize, :max_blobsize,  :minx, :miny, :maxx, :maxy, :score)");

    mapStatements[INSERT_LOGS]->prepare("INSERT INTO `Logs` ( `TimeKey`, `Component`, `ServerId`, `Pid`, `Level`, `Code`, `Message`, `File`, `Line` )  VALUES ( :time_key, :component, :server_id, :pid, :level, :code, :message, :file, :line )");

    mapStatements[INSERT_MONITOR_STATUS_RUNNING]->prepare(
        "INSERT INTO Monitor_Status (MonitorId,Status,CaptureFPS,AnalysisFPS) VALUES (:id, 'Running',0,0) ON DUPLICATE KEY UPDATE Status='Running',CaptureFPS=0,AnalysisFPS=0");

    mapStatements[INSERT_MONITOR_STATUS_CONNECTED]->prepare(
        "INSERT INTO Monitor_Status (MonitorId,Status) VALUES (:id, 'Connected') ON DUPLICATE KEY UPDATE Status='Connected'");

    mapStatements[INSERT_MONITOR_STATUS_NOTRUNNING]->prepare(
        "INSERT INTO Monitor_Status (MonitorId,Status) VALUES (:id, 'NotRunning') ON DUPLICATE KEY UPDATE Status='NotRunning'");
}

void zmDbMySQLAdapter::prepareAutoIncrementTables()
{
    // auto increment table
    /*
    autoIncrementTable[UPDATE_NEW_EVENT_WITH_ID] = "Events";
    autoIncrementTable[UPDATE_NEW_EVENT_WITH_ID_NO_NAME] = "Events";
    autoIncrementTable[UPDATE_EVENT_WITH_ID_SET_NOTES] = "Events";
    autoIncrementTable[UPDATE_EVENT_WITH_ID_SET_SCORE] = "Events";
    autoIncrementTable[UPDATE_EVENT_WITH_ID_SET_STORAGEID] = "Events";
    autoIncrementTable[UPDATE_EVENT_WITH_ID_SET_SAVEJPEGS] = "Events";
    autoIncrementTable[UPDATE_MONITORSTATUS_WITH_MONITORID_SET_CAPTUREFPS] = "Monitor_Status";
    */
    autoIncrementTable[INSERT_EVENTS] = "Events";
    autoIncrementTable[INSERT_FRAMES] = "Frames";
    autoIncrementTable[INSERT_STATS_SINGLE] = "Stats";
    autoIncrementTable[INSERT_STATS_MULTIPLE] = "Stats";
    autoIncrementTable[INSERT_LOGS] = "Logs";
    autoIncrementTable[INSERT_MONITOR_STATUS_RUNNING] = "Monitor_Status";
    autoIncrementTable[INSERT_MONITOR_STATUS_CONNECTED] = "Monitor_Status";
    autoIncrementTable[INSERT_MONITOR_STATUS_NOTRUNNING] = "Monitor_Status";
}

#endif
