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


PluginManager::PluginManager()
{
}



/*!\fn PluginManager::loadPlugin(const string &sFilename))
 * \param sFilename is the name of plugin file to load
 */
void PluginManager::loadPlugin(const string &sFilename)
{
    try
    {
        if(m_LoadedPlugins.find(sFilename) == m_LoadedPlugins.end())
            m_LoadedPlugins.insert(PluginMap::value_type(sFilename, Plugin(sFilename))).first->second.registerPlugin(*this);
    }
    catch(runtime_error &ex)
    {
        Info("Runtime error: %s", ex.what());
    }
    catch(...)
    {
        Info("Unknown exception. Could not load %s.", sFilename.c_str());
    }
}



/*!\fn PluginManager::findPlugins(const string &sPath)
 * \param sPath is the path to folder to search plugins
 * return count of found plugins
 */
int PluginManager::findPlugins(const string &sPath)
{
    
    struct direct **files;

    int count = scandir(sPath.c_str(), &files, file_select, alphasort);

    for (int i = 1; i < count + 1; ++i)
    {
        string sFileName = files[i-1]->d_name;
        string sFullPath = join_paths(sPath, sFileName);
        
        Info("Loading plugin %s ... ", sFullPath.c_str());
        
        loadPlugin(sFullPath);
    }

    return count;
}


/*!\fn PluginManager::configurePlugins(string sConfigFileName)
 *  \param sConfigFileName is the path to the configuration file, where parameters for all plugins are given.
 */
void PluginManager::configurePlugins(string sConfigFileName)
{
    m_ImageAnalyser.configurePlugins(sConfigFileName);
}
