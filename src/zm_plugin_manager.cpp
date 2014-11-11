#include "zm_plugin_manager.h"



/*! \fn file_select(const struct direct *entry)
 *  A functor for selection of files with specified extension.
 *  \param entry is file structure
 *  \return 1 if file match selection criteria and
 *          0 otherwise.
 *  NOTE: file extension is specified by PluginManager::m_sPluginExt
 *  static variable.
 */
int file_select(const struct direct *entry)
{
    char *ptr;

    if ((strcmp(entry->d_name, ".")== 0) || (strcmp(entry->d_name, "..") == 0))
        return 0;

    // Check for filename extensions.
    ptr = rindex((char*)entry->d_name, '.');
    if ((ptr != NULL) && (strcmp(ptr, (PluginManager::m_sPluginExt).c_str()) == 0))
        return 1;
    else
        return 0;
}




/*! \fn join_paths(const string& p1, const string& p2)
 *  \param p1 is the first part of desired path
 *  \param p2 is the second part of desired path
 *  \return joined path string.
 */
string join_paths(const string& p1, const string& p2)
{
    char sep = '/';
    string tmp = p1;

#ifdef _WIN32
    sep = '\\';
#endif

    if (p1[p1.length()] != sep)
    { // Need to add a path separator
        tmp += sep;
        return(tmp + p2);
    }
    else
        return(p1 + p2);
}



string PluginManager::m_sPluginExt = DEFAULT_PLUGIN_EXT;


PluginManager::PluginManager() {}


PluginManager::PluginManager(
    int nMonitorId
) :
    m_ImageAnalyser( nMonitorId )
{}



/*!\fn PluginManager::loadPlugin(const string &sFilename))
 * \param sFilename is the name of plugin file to load
 */
bool PluginManager::loadPlugin(const string &sFilename)
{
    try
    {
        if(m_LoadedPlugins.find(sFilename) == m_LoadedPlugins.end())
            m_LoadedPlugins.insert(PluginMap::value_type(sFilename, Plugin(sFilename))).first->second.registerPlugin(*this);
    }
    catch(runtime_error &ex)
    {
        Error("Runtime error: %s", ex.what());
        return false;
    }
    catch(...)
    {
        Error("Unknown exception. Could not load %s.", sFilename.c_str());
        return false;
    }
    return true;
}


/*!\fn PluginManager::findPlugins(const string &sPath, bool loadPlugins)
 * \param sPath is the path to folder to search plugins
 * \param loadPlugins is a flag to allow loading of plugins
 * \param nNumPlugLoaded is the number of loaded plugins
 * \return the number of found plugins
 */
int PluginManager::findPlugins(const string sPath, bool loadPlugins, unsigned int& nNumPlugLoaded)
{
    struct direct **files;
    int count = scandir(sPath.c_str(), &files, file_select, alphasort);
    if(count <= 0) count = 0;

    for (int i = 0; i < count; ++i)
    {
        string sFileName = string(files[i]->d_name);
        string sFullPath = join_paths(sPath, sFileName);
        size_t idx = sFileName.rfind('.');
        if (idx != string::npos)
            sFileName = sFileName.substr(0, idx);
        bool IsPluginRegistered = false;
        if(config.load_plugins || loadPlugins)
        {
            Info("Loading plugin %s ... ", sFullPath.c_str());
            IsPluginRegistered = loadPlugin(sFullPath);
        }
        mapPluginReg.insert( pair<string,bool>(sFileName, IsPluginRegistered) );
        if (IsPluginRegistered) nNumPlugLoaded++;
    }
    return count;
}


/*!\fn PluginManager::configurePlugins(string sConfigFileName)
 * \param sConfigFileName is the path to the configuration file, where parameters for all plugins are given.
 * \param bDoNativeDet is true if native detection will be performed
*/
void PluginManager::configurePlugins(string sConfigFileName, bool bDoNativeDet)
{
    m_ImageAnalyser.configurePlugins(sConfigFileName, bDoNativeDet);
}


/*!\fn PluginManager::getPluginsGenConf(map<string,pGenConf>& mapPluginGenConf)
 * \param mapPluginGenConf is the map of general settings for the plugins
 * \param mapPluginZoneConf is the map of zone settings for the plugins
 * \return the number of found plugins
 */
unsigned long PluginManager::getPluginsGenConf(map<string,pGenConf>& mapPluginGenConf)
{
    for (map<string,bool>::iterator it = mapPluginReg.begin() ; it != mapPluginReg.end(); ++it)
    {
        pGenConf plugGenConf;
        m_ImageAnalyser.getRegPluginGenConf( it->first, plugGenConf );
        plugGenConf.Registered = it->second;
        mapPluginGenConf.insert( pair<string,pGenConf>(it->first, plugGenConf) );
    }
    return mapPluginGenConf.size();
}


/*!\fn PluginManager::getPluginZoneConf(string sPluginName, PluginZoneConf& mapPluginZoneConf)
 * \param mapPluginZoneConf is the map of zone settings for the plugin
 */
void PluginManager::getPluginZoneConf(string sPluginName, PluginZoneConf& mapPluginZoneConf)
{
    m_ImageAnalyser.getRegPluginZoneConf( sPluginName, mapPluginZoneConf );
}
