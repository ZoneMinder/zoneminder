#include "zm_image_analyser.h"

ImageAnalyser::ImageAnalyser( int nMonitorId )
{
    if ( nMonitorId > 0 )
    {
        m_nMonitorId = nMonitorId;
        m_bIsAnalyserEnabled = getMonitorZones();
    }
    else
        m_bIsAnalyserEnabled = false;
    m_bIsNativeDetEnabled = false;
}

/*!\fn ImageAnalyser::ImageAnalyser(const ImageAnalyser& source)
 * \param source is the object to copy
 */
ImageAnalyser::ImageAnalyser(const ImageAnalyser& source)
{
    m_Detectors = source.m_Detectors;
}



/*!\fn ImageAnalyser::operator=(const ImageAnalyser& source)
 * \param source is the object to copy
 */
ImageAnalyser& ImageAnalyser::operator=(const ImageAnalyser& source)
{
    m_Detectors = source.m_Detectors;
    return *this;
}



ImageAnalyser::~ImageAnalyser()
{
    for(DetectorsList::reverse_iterator It = m_Detectors.rbegin();
        It != m_Detectors.rend();
        ++It)
      delete *It;
}



/*!\fn ImageAnalyser::DoDetection(const Image &comp_image, Zone** zones, Event::StringSetMap noteSetMap, string& det_cause)
 * \param comp_image is the image to analyse
 * \param zones is the zones array to analyse
 * \param noteSetMap is the map of events descriptions
 * \param det_cause is a string describing detection cause
 * \param score is the plugin score
 */
bool ImageAnalyser::DoDetection(const Image &comp_image, Zone** zones, Event::StringSetMap& noteSetMap, string& det_cause, unsigned int& score)
{
    Event::StringSet zoneSet;
    score = 0;
    bool alarm = false;

    for ( DetectorsList::iterator It = m_Detectors.begin();
        It != m_Detectors.end();
        ++It )
    {
        unsigned int detect_score = 0;
        if ( (*It)->Detect( &comp_image, zones, zoneSet, detect_score ) )
        {
            alarm = true;
            score += detect_score;
            std::string new_cause = (*It)->getDetectionCause();
            noteSetMap[new_cause] = zoneSet;
            if ( det_cause.find( new_cause ) == std::string::npos )
            {
                if ( det_cause.length() )
                    det_cause += ", ";
                det_cause += new_cause;
            }
        }
    }
    return alarm;
}



/*!\fn ImageAnalyser::configurePlugins(string sConfigFileName)
 *\param sConfigFileName is the path to the configuration file, where parameters for all plugins are given.
 * \param bDoNativeDet is true if native detection will be performed
*/
void ImageAnalyser::configurePlugins(string sConfigFileName, bool bDoNativeDet)
{
    string sLoadedPlugins;
    if ( !m_bIsAnalyserEnabled ) return;
    m_bIsNativeDetEnabled = bDoNativeDet;
    for ( DetectorsList::iterator It = m_Detectors.begin(); It != m_Detectors.end(); ++It )
    {
        string sPluginName = (*It)->getPluginName();
        try
        {
            if ( isValidConfigFile( sPluginName, sConfigFileName ) )
            {
                Info("Configure plugin '%s' with config file '%s'.", sPluginName.c_str(), sConfigFileName.c_str());
                map<unsigned int,map<string,string> > mapPluginConf;
                vector<unsigned int> vnPluginZones;
                bool plugEnabled = getEnabledZonesForPlugin( sPluginName, vnPluginZones );
                if ( getPluginConfig( sPluginName, vnPluginZones, mapPluginConf )
                        && (*It)->loadConfig( sConfigFileName, mapPluginConf ) )
                {
                    mapRegPluginGenConf[sPluginName].Configured = true;
                    if ( plugEnabled )
                    {
                        (*It)->EnablePlugin( vnPluginZones );
                        if ( sLoadedPlugins.length() )
                            sLoadedPlugins += ", ";
                        sLoadedPlugins += "'" + sPluginName + "'";
                    }
                }
            }
        }
        catch(...)
        {
            Error("Plugin '%s' couldn't be loaded", sPluginName.c_str());
        }
    }
    getZonesConfig( sLoadedPlugins );
}



/*!\fn ImageAnalyser::isValidConfigFile(string sPluginName, string sConfigFileName)
 * \param sPluginName is the name of the plugin (filename without extension)
 * \param sConfigFileName is the path to the configuration file which should include configuration directives for the plugin
 * \return true if the config file contains the right section name
 */
bool ImageAnalyser::isValidConfigFile(string sPluginName, string sConfigFileName)
{
    ifstream ifs(sConfigFileName.c_str());
    string line;
    bool rtnVal = false;
    while (getline(ifs, line))
    {
        if (line == "[" + sPluginName + "]")
        {
            rtnVal = true;
            break;
        }
    }
    ifs.close();
    return rtnVal;
}


/*!\fn ImageAnalyser::getMonitorZones()
 * \return true if at least a zone is configured for the monitor
 */
bool ImageAnalyser::getMonitorZones()
{
    static char sql[ZM_SQL_MED_BUFSIZ];

    // We use the same ordering as in Monitor::Load
    snprintf(sql, sizeof(sql), "SELECT `Id`, `Name`, `Type` FROM `Zones` WHERE `MonitorId` = %d ORDER BY `Type`, `Id`;", m_nMonitorId);

    if (mysql_query(&dbconn, sql))
    {
        Error("Can't run query: %s", mysql_error(&dbconn));
        exit(mysql_errno(&dbconn));
    }

    MYSQL_RES *result = mysql_store_result(&dbconn);
    if (!result)
    {
        Error("Can't use query result: %s", mysql_error(&dbconn));
        exit(mysql_errno(&dbconn));
    }

    if (mysql_num_rows(result) > 0)
    {
        for (unsigned int i = 0; MYSQL_ROW dbrow = mysql_fetch_row(result); i++)
        {
            if (mysql_errno(&dbconn))
            {
                Error("Can't fetch row: %s", mysql_error(&dbconn));
                exit(mysql_errno(&dbconn));
            }
            zSetting zone;
            zone.id = (unsigned int)strtoul(dbrow[0], NULL, 0);
            zone.name = string(dbrow[1]);
            zone.type = string(dbrow[2]);
            m_vMonitorZones.push_back(zone);
        }
    }
    mysql_free_result(result);

    return ( m_vMonitorZones.size() );
}



/*!\fn ImageAnalyser::getPluginConfig(string sPluginName, map<unsigned int,map<string,string> >& mapPluginConf)
 * \param sPluginName is the name of the plugin (filename without extension)
 * \param vnPluginZones is a vector containing the index of zones enabled for the plugin (not the zone Id in the database)
 * \param mapPluginConf is the map filled with configuration parameters for the plugin
 * \return true if all found parameters are applied to the map
 */
bool ImageAnalyser::getPluginConfig(string sPluginName, vector<unsigned int> vnPluginZones, map<unsigned int,map<string,string> >& mapPluginConf)
{
    static char sql[ZM_SQL_MED_BUFSIZ];

    // Get plugin configuration parameters from `PluginsConfig` table
    snprintf(sql, sizeof(sql), "SELECT `ZoneId`, `Name`, `Value` FROM `PluginsConfig` WHERE `MonitorId`=%d AND `pluginName`='%s' ORDER BY `ZoneId` ASC;", m_nMonitorId, sPluginName.c_str());

    if (mysql_query(&dbconn, sql))
    {
        Error("Can't run query: %s", mysql_error(&dbconn));
        exit(mysql_errno(&dbconn));
    }

    MYSQL_RES *result = mysql_store_result(&dbconn);
    if (!result)
    {
        Error("Can't use query result: %s", mysql_error(&dbconn));
        exit(mysql_errno(&dbconn));
    }

    size_t nParamCnt = 0;
    size_t nParamNum = mysql_num_rows(result);

    if (nParamNum > 0)
    {
        vector<MYSQL_ROW> vRows;
        for (unsigned int i = 0; MYSQL_ROW dbrow = mysql_fetch_row(result); i++)
        {
            if (mysql_errno(&dbconn))
            {
                Error("Can't fetch row: %s", mysql_error(&dbconn));
                mysql_free_result(result);
                exit(mysql_errno(&dbconn));
            }
            vRows.push_back(dbrow);
        }
        // Iterate over the zones
        for (size_t i = 0; i < m_vMonitorZones.size(); i++)
        {
            // Iterate over the configuration parameters
            for (vector<MYSQL_ROW>::iterator it = vRows.begin(); it != vRows.end(); it++)
            {
                // Add the parameter to the map if the zone id is found
                if ( (unsigned int)strtoul((*it)[0], NULL, 0) == m_vMonitorZones[i].id )
                {
                    nParamCnt++;
                    string name((*it)[1]);
                    string value((*it)[2]);
                    if((name == "Enabled") && (value == "Yes")) {
                        mapRegPluginZoneConf[sPluginName][m_vMonitorZones[i].id].Enabled = true;
                    } else if((name == "RequireNatDet") && (value == "Yes")) {
                        mapRegPluginZoneConf[sPluginName][m_vMonitorZones[i].id].RequireNatDet = true;
                    } else if((name == "IncludeNatDet") && (value == "Yes")) {
                        mapRegPluginZoneConf[sPluginName][m_vMonitorZones[i].id].IncludeNatDet = true;
                    } else if((name == "ReInitNatDet") && (value == "Yes")) {
                        mapRegPluginZoneConf[sPluginName][m_vMonitorZones[i].id].ReInitNatDet = true;
                    }
                    // Keep only enabled zones in mapPluginConf
                    if (binary_search(vnPluginZones.begin(), vnPluginZones.end(), i)) {
                        mapPluginConf[i][name] = value;
                    }
                }
            }
            if ( mapRegPluginZoneConf[sPluginName][m_vMonitorZones[i].id].Enabled
                    && mapRegPluginZoneConf[sPluginName][m_vMonitorZones[i].id].RequireNatDet
                    && !m_bIsNativeDetEnabled )
                Warning("Plugin '%s' will never enter in alarm because native detection is required but not enabled", sPluginName.c_str());
        }
    }
    mysql_free_result(result);

    return ( nParamNum == nParamCnt );
}



/*!\fn ImageAnalyser::getEnabledZonesForPlugin(string sPluginName, vector<unsigned int>& vnPluginZones)
 * \param sPluginName is the name of the plugin (filename without extension)
 * \param vnPluginZones is the vector list filled with zones enabled for this plugin
 * \return true if at least one active or exclusive zone exist
 */
bool ImageAnalyser::getEnabledZonesForPlugin(string sPluginName, vector<unsigned int>& vnPluginZones)
{
    static char sql[ZM_SQL_MED_BUFSIZ];
    bool bPluginEnabled = false;
    string sZones;

    // Get the sorted list of zones ids which have the plugin enabled
    snprintf(sql, sizeof(sql), "SELECT `ZoneId` FROM `PluginsConfig` WHERE `MonitorId`=%d AND `pluginName`='%s' AND `Name`='Enabled' AND `Value`='yes' ORDER BY `ZoneId` ASC;", m_nMonitorId, sPluginName.c_str());

    if (mysql_query( &dbconn, sql))
    {
        Error("Can't run query: %s", mysql_error(&dbconn));
        exit(mysql_errno(&dbconn));
    }

    MYSQL_RES *result = mysql_store_result(&dbconn);
    if (!result)
    {
        Error("Can't use query result: %s", mysql_error(&dbconn));
        exit(mysql_errno(&dbconn));
    }

    if (mysql_num_rows(result) > 0)
    {
        vector<unsigned int> vnEnabledZoneIds;
        for (unsigned int i = 0; MYSQL_ROW dbrow = mysql_fetch_row(result); i++)
        {
            if (mysql_errno(&dbconn))
            {
                Error("Can't fetch row: %s", mysql_error(&dbconn));
                mysql_free_result(result);
                exit(mysql_errno(&dbconn));
            }
            vnEnabledZoneIds.push_back(atoi(dbrow[0]));
        }

        // Iterate over the zones
        for (size_t i = 0; i < m_vMonitorZones.size(); i++)
        {
            if (binary_search(vnEnabledZoneIds.begin(), vnEnabledZoneIds.end(), m_vMonitorZones[i].id))
            {
                // Add the index to the vector if the zone id is found
                vnPluginZones.push_back(i);
                string sZoneType = m_vMonitorZones[i].type;
                if ((sZoneType == "Active") || (sZoneType == "Exclusive"))
                    bPluginEnabled = true;
                if ( sZones.length() )
                    sZones += ", ";
                sZones += m_vMonitorZones[i].name + " (" + sZoneType + ")";
            }
        }
    }
    mysql_free_result(result);

    if (bPluginEnabled)
    {
        Info("Plugin '%s' is enabled for zone(s): %s", sPluginName.c_str(), sZones.c_str());
    }
    else
    {
        Info("Plugin '%s' is disabled (not enabled for any active or exclusive zones)", sPluginName.c_str());
    }
    return bPluginEnabled;
}


/*!\fn ImageAnalyser::getZonesConfig()
 * \param sLoadedPlugins is the formatted list of loaded plugins
 */
bool ImageAnalyser::getZonesConfig(string sLoadedPlugins)
{
    static char sql[ZM_SQL_MED_BUFSIZ];

    if ( !sLoadedPlugins.length() ) return false;

    // Get the sorted list of zones and which have a setting enabled
    snprintf(sql, sizeof(sql), "SELECT DISTINCT `ZoneId`, `Name` FROM `PluginsConfig` WHERE `MonitorId` = %d AND `pluginName` IN (%s) AND `Name` IN ('RequireNatDet', 'IncludeNatDet', 'ReInitNatDet') AND `Value` = 'yes' ORDER BY `ZoneId` ASC;", m_nMonitorId, sLoadedPlugins.c_str());
    if (mysql_query(&dbconn, sql))
    {
        Error("Can't run query: %s", mysql_error(&dbconn));
        exit(mysql_errno(&dbconn));
    }
    MYSQL_RES *result = mysql_store_result(&dbconn);
    if (!result)
    {
        Error("Can't use query result: %s", mysql_error(&dbconn));
        exit(mysql_errno(&dbconn));
    }
    if (mysql_num_rows(result) > 0)
    {
        vector<zIdName> vSettings;
        for (unsigned int i = 0; MYSQL_ROW dbrow = mysql_fetch_row(result); i++)
        {
            if (mysql_errno(&dbconn))
            {
                Error("Can't fetch row: %s", mysql_error(&dbconn));
                mysql_free_result(result);
                exit(mysql_errno(&dbconn));
            }
            zIdName setting;
            setting.zoneId = (unsigned int)strtoul(dbrow[0], NULL, 0);
            setting.name = dbrow[1];
            vSettings.push_back(setting);
        }

        // Iterate over the zones and add the index to the vector if the zone id is found
        for (size_t i = 0; i != m_vMonitorZones.size(); i++)
        {
            zConf zoneConf;
            for (vector<zIdName>::iterator it = vSettings.begin(); it != vSettings.end(); it++)
            {
                if (it->zoneId == m_vMonitorZones[i].id)
                {
                    if (it->name == "RequireNatDet")
                        zoneConf.RequireNatDet = true;
                    else if (it->name == "IncludeNatDet")
                        zoneConf.IncludeNatDet = true;
                    else if (it->name == "ReInitNatDet")
                        zoneConf.ReInitNatDet = true;
                }
            }
            m_vZonesConfig.push_back(zoneConf);
        }
    }
    mysql_free_result(result);

    return true;
}


/*!\fn ImageAnalyser::getZoneConfig(int nZone, zConf& zoneConf)
 * \param nZone is the zone index (not the id in sql database)
 * \param zoneConf is a structure filled with the plugin settings of nZone
 */
bool ImageAnalyser::getZoneConfig(unsigned int nZone, zConf& zoneConf)
{
    if (nZone < m_vZonesConfig.size())
        zoneConf = m_vZonesConfig[nZone];
    else
        return false;
    return true;
}


/*!\fn ImageAnalyser::getRegPluginGenConf(string sPluginName, pGenConf& regPluginGenConf)
 * \param sPluginName is the name of the plugin (filename without extension)
 * \param regPluginGenConf is a structure filled with the general settings of the plugin
 * \return false if no setting is found
 */
bool ImageAnalyser::getRegPluginGenConf(string sPluginName, pGenConf& regPluginGenConf)
{
    map<string,pGenConf>::iterator it = mapRegPluginGenConf.find( sPluginName );
    if ( it == mapRegPluginGenConf.end() )
        return false;
    regPluginGenConf = it->second;
    return true;
}


/*!\fn ImageAnalyser::getRegPluginZoneConf(string sPluginName, PluginZoneConf& regPluginZoneConf)
 * \param sPluginName is the name of the plugin (filename without extension)
 * \param regPluginZoneConf is a map filled with the zone settings of the plugin
 */
void ImageAnalyser::getRegPluginZoneConf(string sPluginName, PluginZoneConf& regPluginZoneConf)
{
    map<string,PluginZoneConf>::iterator it = mapRegPluginZoneConf.find( sPluginName );

    if ( it != mapRegPluginZoneConf.end() )
        regPluginZoneConf = it->second;

    pZoneConf empty;

    for (size_t i = 0; i != m_vMonitorZones.size(); i++)
    {
        PluginZoneConf::iterator it2 = regPluginZoneConf.find( m_vMonitorZones[i].id );
         if ( it2 == regPluginZoneConf.end() )
             regPluginZoneConf[m_vMonitorZones[i].id] = empty;
    }
}

void ImageAnalyser::cleanupPlugins()
{

    string sPluginsToKeep;
    string sRequest;
    static char sql[ZM_SQL_MED_BUFSIZ];

    for ( DetectorsList::iterator It = m_Detectors.begin(); It != m_Detectors.end(); ++It )
    {
        if ( sPluginsToKeep.length() )
            sPluginsToKeep += ", ";
        sPluginsToKeep += "'" + (*It)->getPluginName() + "'";
    }

    if ( sPluginsToKeep.length() )
        sRequest = " AND `pluginName` NOT IN (" + sPluginsToKeep + ")";

    snprintf(sql, sizeof(sql), "DELETE FROM `PluginsConfig` WHERE `MonitorId` = %d%s;", m_nMonitorId, sRequest.c_str());

    if ( mysql_query( &dbconn, sql ) )
    {
        Error( "Can't delete plugint: %s", mysql_error( &dbconn ) );
        exit( mysql_errno( &dbconn ) );
    }
}
