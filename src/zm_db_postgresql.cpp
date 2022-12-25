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

#ifdef HAVE_LIBSOCI_POSTGRESQL
#include "zm_db_postgresql.h"

std::string load_monitor_postgresql =
"SELECT Id, Name, ServerId, StorageId, Type, Capturing, Analysing, AnalysisSource, AnalysisImage,"
"Recording, RecordingSource, Decoding, "
"JanusEnabled, JanusAudioEnabled, Janus_Profile_Override, Janus_Use_RTSP_Restream,"
"LinkedMonitors, EventStartCommand, EventEndCommand, AnalysisFPSLimit, AnalysisUpdateDelay, MaxFPS, AlarmMaxFPS,"
"Device, Channel, Format, V4LMultiBuffer, V4LCapturesPerFrame, " // V4L Settings
"Protocol, Method, Options, \"user\", Pass, Host, Port, Path, SecondPath, Width, Height, Colours, Palette, Orientation, Deinterlacing, "
"DecoderHWAccelName, DecoderHWAccelDevice, RTSPDescribe, "
"SaveJPEGs, VideoWriter, EncoderParameters, "
"OutputCodec, Encoder, OutputContainer, "
"RecordAudio, "
"Brightness, Contrast, Hue, Colour, "
"EventPrefix, LabelFormat, LabelX, LabelY, LabelSize,"
"ImageBufferCount, MaxImageBufferCount, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, "
"SectionLength, MinSectionLength, FrameSkip, MotionFrameSkip, "
"FPSReportInterval, RefBlendPerc, AlarmRefBlendPerc, TrackMotion, Exif,"
"RTSPServer, RTSPStreamName, ONVIF_Alarm_Text,"
"ONVIF_URL, ONVIF_Username, ONVIF_Password, ONVIF_Options, ONVIF_Event_Listener, use_Amcrest_API, "
"SignalCheckPoints, SignalCheckColour, Importance, ZoneCount FROM \"monitors\""
" WHERE ";

zmDbPostgreSQLAdapter::zmDbPostgreSQLAdapter() : zmDb()
{
    if (connected())
        return;

    std::string paramsStr("");
    paramsStr += "dbname=" + staticConfig.DB_NAME;
    paramsStr += " user=" + staticConfig.DB_USER;
    paramsStr += " password='" + staticConfig.DB_PASS + "'";
    paramsStr += " connect_timeout=2";

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
            paramsStr += " hostaddr='" + dbHost + "'";
            paramsStr += " port=" + dbPortOrSocket;
        }
    }

    soci::connection_parameters params(soci::postgresql, paramsStr);

    db.open( params );

    if (!connected())
    {
        Error("Can't connect to server: %s", paramsStr.c_str());
        return;
    }

    soci::postgresql_session_backend *concreteDb = (soci::postgresql_session_backend *)db.get_backend();

    if( concreteDb->conn_ == NULL ) 
    {
        Error("Cannot connect to database");
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

zmDbPostgreSQLAdapter::~zmDbPostgreSQLAdapter()
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

#if SOCI_VERSION < 400001 // before version 4.0.1 session::is_connected was not supported
bool zmDbPostgreSQLAdapter::connected() {
    soci::postgresql_session_backend *concreteDb = (soci::postgresql_session_backend *)db.get_backend();
    if ( concreteDb == NULL )
    {
        return false;
    }
    
    // note: taken nearly verbatim from soci source
    // For the connection to work, its status must be OK, but this is not
    // sufficient, so try to actually do something with it, even if it's
    // something as trivial as sending an empty command to the server.
    if ( PQstatus(concreteDb->conn_) != CONNECTION_OK ) 
    {
        return false;
    }

    PQclear( PQexec(concreteDb->conn_, "/* ping */") );

    // And then check it again.
    return PQstatus(concreteDb->conn_) == CONNECTION_OK;
}
#endif

uint64_t zmDbPostgreSQLAdapter::lastInsertID(const zmDbQueryID &queryId)
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
        return id;

    return 0;
}

std::string zmDbPostgreSQLAdapter::realColumnName(const std::string& column) {
    return StringToLower( column );
}

unsigned int zmDbPostgreSQLAdapter::getUnsignedIntColumn(soci::rowset_iterator<soci::row>* result_iter, const int position) {
    try {
        return (unsigned int)((*result_iter)->get<long long>(position));
    }
    catch (std::bad_cast const & e)
    {
        try {
            return (unsigned int)((*result_iter)->get<int>(position));
        }
        catch (std::bad_cast const & e)
        {
            throw new soci::soci_error(e.what());
        }
    }
}

unsigned int zmDbPostgreSQLAdapter::getUnsignedIntColumn(soci::rowset_iterator<soci::row>* result_iter, const std::string& name) {
    try {
        return (unsigned int)((*result_iter)->get<long long>(realColumnName(name)));
    }
    catch (std::bad_cast const & e)
    {
        try {
            return (unsigned int)((*result_iter)->get<int>(realColumnName(name)));
        }
        catch (std::bad_cast const & e)
        {
            throw new soci::soci_error(e.what());
        }
    }
}

void zmDbPostgreSQLAdapter::prepareSelectStatements()
{
    if (!connected())
        return;

    mapStatements[SELECT_SERVER_ID_WITH_NAME]->prepare("SELECT Id FROM \"servers\" WHERE Name=:name");

    mapStatements[SELECT_SERVER_NAME_WITH_ID]->prepare("SELECT Name FROM \"servers\" WHERE Id=:id");

    mapStatements[SELECT_SERVER_DATA_WITH_ID]->prepare("SELECT Id, Name, Protocol, Hostname, PathToIndex, PathToZMS, PathToApi FROM \"servers\" WHERE Id=:id");

    mapStatements[SELECT_GROUP_WITH_ID]->prepare("SELECT Id, ParentId, Name FROM \"groups\" WHERE Id=:id");

    mapStatements[SELECT_MAX_EVENTS_ID_WITH_MONITORID_AND_FRAMES_NOT_ZERO]->prepare(
        "SELECT MAX(Id) FROM \"events\" WHERE MonitorId=:id AND Frames > 0");

    // rewritten to remove nested query not suppoerted by soci mysql backend
    mapStatements[SELECT_GROUPS_PARENT_OF_MONITOR_ID]->prepare(
        "SELECT DISTINCT \"groups\".Id, \"groups\".ParentId, \"groups\".Name FROM \"groups\", \"groups_monitors\" WHERE \"groups_monitors\".MonitorId=:id AND \"groups\".Id=\"groups_monitors\".Id;");

    mapStatements[SELECT_MONITOR_ID_REMOTE_RTSP_AND_RTPUNI]->prepare(
        "SELECT Id FROM \"monitors\" WHERE Function != 'None' AND Type = 'Remote' AND Protocol = 'rtsp' AND Method = 'rtpUni' ORDER BY Id ASC");

    mapStatements[SELECT_STORAGE_WITH_ID]->prepare("SELECT Id, Name, Path, Type, Scheme FROM \"storage\" WHERE Id=:id");

    mapStatements[SELECT_USER_AND_DATA_WITH_USERNAME_ENABLED]->prepare(
        "SELECT Id, Username, Password, Enabled,Stream, Events, Control, Monitors, System, MonitorIds FROM \"users\" WHERE Username = :username AND Enabled = 1");

    mapStatements[SELECT_USER_AND_DATA_WITH_USERID_ENABLED]->prepare(
        "SELECT Id, Username, Password, Enabled,Stream, Events, Control, Monitors, System, MonitorIds FROM \"users\" WHERE Enabled = 1 AND Id=:id");

    mapStatements[SELECT_USER_AND_DATA_PLUS_TOKEN_WITH_USERNAME_ENABLED]->prepare(
        "SELECT Id, Username, Password, Enabled,Stream, Events, Control, Monitors, System, MonitorIds, TokenMinExpiry FROM \"users\" WHERE Username = :username AND Enabled = 1");

    mapStatements[SELECT_GROUP_PERMISSIONS_FOR_USERID]->prepare(
        "SELECT Id,UserId,GroupId,Permission FROM \"groups_permissions\" WHERE UserId=:id");

    mapStatements[SELECT_MONITOR_PERMISSIONS_FOR_USERID]->prepare(
        "SELECT Id,UserId,MonitorId,Permission FROM \"monitors_permissions\" WHERE UserId=:id");

    mapStatements[SELECT_MONITOR_FOR_GROUPID]->prepare(
        "SELECT MonitorId FROM Groups_Monitors WHERE GroupId=:id");
}

void zmDbPostgreSQLAdapter::prepareSelectMonitorStatements()
{
    if (!connected())
        return;

    std::string op_and = " AND ";
    std::string cond_id = "Id = :id";
    std::string cond_type = " Capturing != 'None' AND Type = :type";
    std::string cond_device = "Device = :device";
    std::string cond_server_id = "ServerId = :server_id";
    std::string cond_protocol = "Protocol = :protocol AND Host = :host AND Port = :port AND Path = :path";
    std::string cond_path = "Path = :path";
    std::string cond_rtsp = "Function != 'None' AND RTSPserver != 0";

    std::string base_query = load_monitor_postgresql + cond_type + op_and;

    mapStatements[SELECT_MONITOR_WITH_ID]->prepare(load_monitor_postgresql + cond_id);

    mapStatements[SELECT_MONITOR_TYPE]->prepare(load_monitor_postgresql + cond_type);

    mapStatements[SELECT_MONITOR_TYPE_AND_DEVICE]->prepare(base_query + cond_device);

    mapStatements[SELECT_MONITOR_TYPE_AND_SERVER]->prepare(base_query + cond_server_id);

    mapStatements[SELECT_MONITOR_TYPE_AND_DEVICE_AND_SERVER]->prepare(base_query + cond_device + op_and + cond_server_id);

    mapStatements[SELECT_MONITOR_TYPE_AND_PROTOCOL]->prepare(base_query + cond_protocol);

    mapStatements[SELECT_MONITOR_TYPE_AND_SERVER_AND_PROTOCOL]->prepare(base_query + cond_server_id + op_and + cond_protocol);

    mapStatements[SELECT_MONITOR_TYPE_AND_PATH]->prepare(base_query + cond_path);

    mapStatements[SELECT_MONITOR_TYPE_AND_PATH_AND_SERVER]->prepare(base_query + cond_path + op_and + cond_server_id);

    mapStatements[SELECT_MONITOR_TYPE_RTSP]->prepare(load_monitor_postgresql + cond_rtsp);

    mapStatements[SELECT_MONITOR_TYPE_RTSP_AND_SERVER]->prepare(load_monitor_postgresql + cond_rtsp + op_and + cond_server_id);

    mapStatements[SELECT_MONITOR_TYPE_RTSP_AND_ID]->prepare(load_monitor_postgresql + cond_rtsp + op_and + cond_id);

    mapStatements[SELECT_MONITOR_TYPE_RTSP_AND_SERVER_AND_ID]->prepare(load_monitor_postgresql + cond_rtsp + op_and + cond_server_id + op_and + cond_id);

}

void zmDbPostgreSQLAdapter::prepareSelectAllStatements()
{
    if (!connected())
        return;

    mapStatements[SELECT_ALL_ACTIVE_STATES_ID]->prepare("SELECT Id FROM \"states\" WHERE IsActive=1");

    mapStatements[SELECT_ALL_CONFIGS]->prepare("SELECT Name, Value, Type FROM \"config\" ORDER BY Id");

    mapStatements[SELECT_ALL_STORAGE_ID]->prepare("SELECT Id FROM \"storage\" WHERE Id != :id");

    mapStatements[SELECT_ALL_STORAGE_ID_AND_SERVER_ID]->prepare("SELECT Id FROM \"storage\" WHERE Id != :id AND ServerId = :server_id");

    mapStatements[SELECT_ALL_STORAGE_ID_WITH_SERVERID_NULL]->prepare("SELECT Id FROM \"storage\" WHERE ServerId IS NULL");

    mapStatements[SELECT_ALL_STORAGE_ID_WITH_SERVERID_NULL_OR_DIFFERENT]->prepare("SELECT Id FROM \"storage\" WHERE ServerId IS NULL OR ServerId != :server_id");

    mapStatements[SELECT_ALL_EVENTS_ID_WITH_MONITORID_EQUAL]->prepare(
        "SELECT Id FROM \"events\" WHERE MonitorId = :id AND extract(epoch from EndDateTime at time zone 'utc') > :timestamp         ORDER BY Id ASC LIMIT 1");

    mapStatements[SELECT_EVENT_WITH_ID]->prepare(
        "SELECT Events.Id, MonitorId, StorageId, Frames, extract(epoch from  StartDateTime at time zone 'utc') AS StartTimestamp, extract(epoch from EndDateTime at time zone 'utc') AS EndTimestamp, DefaultVideo, Scheme, SaveJPEGs, Orientation, max(f.Delta)-min(f.Delta) AS FramesDuration FROM \"events\" JOIN \"frames\" f ON Events.Id = f.Id WHERE Events.Id = :id GROUP BY Events.Id");

    mapStatements[SELECT_ALL_FRAMES_OF_EVENT_WITH_ID]->prepare("SELECT FrameId, extract(epoch from TimeStamp at time zone 'utc'), Delta FROM \"frames\" WHERE EventId = :id  ORDER BY FrameId ASC");

    mapStatements[SELECT_ALL_EVENTS_ID_WITH_MONITORID_AND_ID_LESSER_THAN]->prepare(
        "SELECT Id FROM \"events\" WHERE MonitorId = :monitor_id AND Id < :event_id  ORDER BY Id DESC LIMIT 1");

    mapStatements[SELECT_ALL_EVENTS_ID_WITH_MONITORID_AND_ID_LARGER_THAN]->prepare(
        "SELECT Id FROM \"events\" WHERE MonitorId = :monitor_id AND Id > :event_id  ORDER BY Id ASC LIMIT 1");

    mapStatements[SELECT_ALL_USERS_AND_DATA_ENABLED]->prepare(
        "SELECT Id, Username, Password, Enabled, Stream, Events, Control, Monitors, System, MonitorIds FROM \"users\" WHERE Enabled = 1");

    mapStatements[SELECT_ALL_ZONES_WITH_MONITORID_EQUAL_TO]->prepare(
        "SELECT Id,Name,Type,Units,Coords,AlarmRGB,CheckMethod, MinPixelThreshold,MaxPixelThreshold,MinAlarmPixels,MaxAlarmPixels, FilterX,FilterY,MinFilterPixels,MaxFilterPixels, MinBlobPixels,MaxBlobPixels,MinBlobs,MaxBlobs, OverloadFrames,ExtendAlarmFrames FROM \"zones\" WHERE MonitorId = :id ORDER BY Type");

    mapStatements[SELECT_ALL_MONITORS_DATA]->prepare("SELECT Id, Capturing, Analysing, Recording FROM \"monitors\" ORDER BY Id ASC");

    mapStatements[SELECT_ALL_MONITORS_DATA_VERBOSE]->prepare("SELECT Id, Capturing, Analysing, Recording FROM \"monitors\" WHERE Capturing != 'None' ORDER BY Id ASC");
}

void zmDbPostgreSQLAdapter::prepareUpdateStatements()
{
    if (!connected())
        return;

    mapStatements[UPDATE_NEW_EVENT_WITH_ID]->prepare(
        "UPDATE \"events\" SET Name=:name, EndDateTime = :enddatetime, Length = :length, Frames = :frames, AlarmFrames = :alarm_frames, TotScore = :total_score, AvgScore = :avg_score, MaxScore = :max_score, DefaultVideo=:default_video WHERE Id = :id AND Name='New Event'");

    mapStatements[UPDATE_NEW_EVENT_WITH_ID_NO_NAME]->prepare(
        "UPDATE \"events\" SET EndDateTime = :enddatetime, Length = :length, Frames = :frames, AlarmFrames = :alarm_frames, TotScore = :total_score, AvgScore = :avg_score, MaxScore = :max_score, DefaultVideo=:default_video WHERE Id = :id");

    mapStatements[UPDATE_EVENT_WITH_ID_SET_NOTES]->prepare("UPDATE \"events\" SET Notes = :notes WHERE Id = :id");

    mapStatements[UPDATE_EVENT_WITH_ID_SET_SCORE]->prepare("UPDATE \"events\" SET Length = :length, Frames = :frames, AlarmFrames = :alarm_frames, TotScore = :total_score, AvgScore = :avg_score, MaxScore = :max_score WHERE Id = :id");

    mapStatements[UPDATE_EVENT_WITH_ID_SET_STORAGEID]->prepare("UPDATE \"events\" SET StorageId = :storage_id WHERE Id=:id");

    mapStatements[UPDATE_EVENT_WITH_ID_SET_SAVEJPEGS]->prepare("UPDATE \"events\" SET SaveJpegs=:save_jpegs WHERE Id=:id");

    // note: LOW PRIORITY does not exist in postgres
    mapStatements[UPDATE_MONITORSTATUS_WITH_MONITORID_SET_CAPTUREFPS]->prepare("UPDATE \"monitor_status\" SET CaptureFPS = :capture_fps, CaptureBandwidth=:capture_bandwitdh, AnalysisFPS = :analysis_fps WHERE MonitorId=:id");
}

void zmDbPostgreSQLAdapter::prepareInsertStatements()
{
    if (!connected())
        return;

    mapStatements[INSERT_EVENTS]->prepare("INSERT INTO \"events\" ( MonitorId, StorageId, Name, StartDateTime, Width, Height, Cause, Notes, StateId, Orientation, Videoed, DefaultVideo, SaveJPEGs, Scheme ) VALUES (:monitor_id, :storage_id, 'New Event', :start_datetime, :width, :height, :cause, :notes, :state_id, :orientation, :videoed, :default_video, :save_jpegs, :scheme)");

    mapStatements[INSERT_FRAMES]->prepare("INSERT INTO \"frames\" (EventId, FrameId, Type, TimeStamp, Delta, Score) VALUES (:event_id, :frame_id, :type, :timestamp, :delta, :score)");

    mapStatements[INSERT_STATS_SINGLE]->prepare("INSERT INTO \"stats\" (EventId, FrameId, MonitorId, ZoneId, PixelDiff, AlarmPixels, FilterPixels, Blobs, BlobPixels, MinBlobSize, MaxBlobSize, MinX, MinY, MaxX, MaxY,Score) VALUES (:event_id, :frame_id, :monitorid, :zone_id, :pixel_diff, :alarm_pixels, :filter_pixels, :blobs, :blob_pixels, :min_blobsize, :max_blobsize, :minx, :miny, :maxx, :maxy, :score)");

    mapStatements[INSERT_STATS_MULTIPLE]->prepare("INSERT INTO \"stats\" (EventId, FrameId, MonitorId, ZoneId, PixelDiff, AlarmPixels, FilterPixels, Blobs, BlobPixels, MinBlobSize, MaxBlobSize, MinX, MinY, MaxX, MaxY,Score) VALUES (:event_id, :frame_id, :monitor_id, :zone_id, :pixel_diff, :alarm_pixels, :filter_pixels, :blobs, :blob_pixels, :min_blobsize, :max_blobsize,  :minx, :miny, :maxx, :maxy, :score)");

    mapStatements[INSERT_LOGS]->prepare("INSERT INTO \"logs\" ( TimeKey, Component, ServerId, Pid, Level, Code, Message, File, Line )  VALUES ( :time_key, :component, :server_id, :pid, :level, :code, :message, :file, :line )");

    mapStatements[INSERT_MONITOR_STATUS_RUNNING]->prepare(
        "INSERT INTO \"monitor_status\" (MonitorId,Status,CaptureFPS,AnalysisFPS) VALUES (:id, 'Running',0,0) ON CONFLICT (MonitorId) DO UPDATE SET Status='Running',CaptureFPS=0,AnalysisFPS=0");

    mapStatements[INSERT_MONITOR_STATUS_CONNECTED]->prepare(
        "INSERT INTO \"monitor_status\" (MonitorId,Status) VALUES (:id, 'Connected') ON CONFLICT (MonitorId) DO UPDATE SET Status='Connected'");

    mapStatements[INSERT_MONITOR_STATUS_NOTRUNNING]->prepare(
        "INSERT INTO \"monitor_status\" (MonitorId,Status) VALUES (:id, 'NotRunning') ON CONFLICT (MonitorId) DO UPDATE SET Status='NotRunning'");
}

void zmDbPostgreSQLAdapter::prepareAutoIncrementTables()
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
